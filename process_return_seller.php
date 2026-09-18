<?php
include("session.php");
include("connect.php");
require_once "includes/audit_logger.php";

// Ensure the user is logged in and is a seller
$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

if ($userType !== 'seller' || !isset($_SESSION['id'])) {
    $_SESSION['error_message'] = "You don't have permission to access this feature.";
    header("Location: login.php");
    exit();
}

// Get the seller's ID
$sellerStmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ?");
$sellerStmt->bind_param("i", $userId);
$sellerStmt->execute();
$sellerResult = $sellerStmt->get_result();
$sellerData = $sellerResult->fetch_assoc();

if (!$sellerData) {
    $_SESSION['error_message'] = "Seller profile not found.";
    header("Location: dashboard.php");
    exit();
}

$sellerId = $sellerData['id'];

// Process QR Code Scan
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'scan_return') {
    $rentalId = intval($_GET['rental_id']);
    $token = $_GET['token'] ?? '';
    
    // Verify the token
    $stmt = $conn->prepare("SELECT return_id, status FROM book_rentals WHERE rental_id = ? AND return_token = ? AND user_id != ?"); // Ensure it's not the seller themself
    $stmt->bind_param("isi", $rentalId, $token, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $rental = $res->fetch_assoc();
        
        $conn->begin_transaction();
        try {
            // Mark rental as returned but pending inspection
            $upd = $conn->prepare("UPDATE book_rentals SET status = 'returned' WHERE rental_id = ?");
            $upd->bind_param("i", $rentalId);
            $upd->execute();
            
            // Mark the return request as received
            $updRet = $conn->prepare("UPDATE book_returns SET status = 'received', received_date = NOW() WHERE rental_id = ? AND seller_id = ?");
            $updRet->bind_param("ii", $rentalId, $sellerId);
            $updRet->execute();
            
            $conn->commit();
            $_SESSION['success_message'] = "QR Code verified! The return has been marked as Received. Please manually inspect the book to complete the return.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Database error during QR verification.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid or expired QR code.";
    }
    
    header("Location: renter.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_return') {
    
    // Validate required fields
    if (!isset($_POST['return_id']) || !isset($_POST['return_status'])) {
        $_SESSION['error_message'] = "Missing required information.";
        header("Location: renter.php");
        exit();
    }
    
    $returnId = intval($_POST['return_id']);
    $returnStatus = $_POST['return_status'];
    $notes = $_POST['notes'] ?? '';
    
    // Verify the return belongs to this seller
    $returnStmt = $conn->prepare("
        SELECT 
            br.return_id, 
            br.rental_id,
            br.book_id,
            br.user_id,
            br.status as current_status,
            r.status as rental_status,
            b.title as book_title
        FROM book_returns br
        JOIN book_rentals r ON br.rental_id = r.rental_id
        JOIN books b ON br.book_id = b.book_id
        WHERE br.return_id = ? AND br.seller_id = ?
    ");
    $returnStmt->bind_param("ii", $returnId, $sellerId);
    $returnStmt->execute();
    $returnResult = $returnStmt->get_result();
    
    if ($returnResult->num_rows === 0) {
        $_SESSION['error_message'] = "Invalid return request or you don't have permission to process it.";
        header("Location: renter.php");
        exit();
    }
    
    $returnData = $returnResult->fetch_assoc();
    $currentStatus = $returnData['current_status'];
    $bookId = $returnData['book_id'];
    $rentalId = $returnData['rental_id'];
    $renterId = $returnData['user_id'];
    
    // Validate status transition
    $validTransition = false;
    switch ($currentStatus) {
        case 'pending':
            $validTransition = ($returnStatus === 'received');
            break;
        case 'received':
            $validTransition = ($returnStatus === 'inspected');
            break;
        case 'inspected':
            $validTransition = ($returnStatus === 'completed');
            break;
        default:
            $validTransition = false;
    }
    
    if (!$validTransition) {
        $_SESSION['error_message'] = "Invalid status transition from {$currentStatus} to {$returnStatus}.";
        header("Location: renter.php");
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update basic return status
        $updateReturnSql = "
            UPDATE book_returns 
            SET status = ?";
            
        $updateParams = array($returnStatus);
        $updateTypes = "s";
        
        // Handle additional data based on status
        if ($returnStatus === 'received') {
            $updateReturnSql .= ", received_date = NOW()";
        }
        else if ($returnStatus === 'inspected') {
            // Process inspection details
            if (!isset($_POST['book_condition'])) {
                throw new Exception("Book condition is required for inspection.");
            }
            
            $bookCondition = $_POST['book_condition'];
            $damageDescription = $_POST['damage_description'] ?? '';
            $damageFee = 0;
            
            if ($bookCondition === 'damaged' || $bookCondition === 'fair') {
                $damageFee = !empty($_POST['damage_fee']) ? floatval($_POST['damage_fee']) : 0;
            }
            
            $updateReturnSql .= ", book_condition = ?, damage_description = ?, damage_fee = ?";
            $updateTypes .= "ssd";
            $updateParams[] = $bookCondition;
            $updateParams[] = $damageDescription;
            $updateParams[] = $damageFee;
        }
        else if ($returnStatus === 'completed') {
            $updateReturnSql .= ", completed_date = NOW()";
            
            // Update book rental status
            $updateRentalStmt = $conn->prepare("
                UPDATE book_rentals 
                SET status = 'returned', return_date = NOW()
                WHERE rental_id = ?
            ");
            $updateRentalStmt->bind_param("i", $rentalId);
            $updateRentalStmt->execute();
            
            // Update book availability
            $updateBookStmt = $conn->prepare("
                UPDATE books 
                SET stock = stock + 1 
                WHERE book_id = ?
            ");
            $updateBookStmt->bind_param("i", $bookId);
            $updateBookStmt->execute();
        }
        
        // Add notes if provided
        if (!empty($notes)) {
            $updateReturnSql .= ", notes = ?";
            $updateTypes .= "s";
            $updateParams[] = $notes;
        }
        
        // Add staff ID (which is the current user)
        $updateReturnSql .= ", staff_id = ?";
        $updateTypes .= "i";
        $updateParams[] = $userId;
        
        // Complete the SQL
        $updateReturnSql .= " WHERE return_id = ?";
        $updateTypes .= "i";
        $updateParams[] = $returnId;
        
        // Execute the update
        $updateReturnStmt = $conn->prepare($updateReturnSql);
        $updateReturnStmt->bind_param($updateTypes, ...$updateParams);
        $updateReturnStmt->execute();
        
        // Log the action
        $logStmt = $conn->prepare("
            INSERT INTO payment_logs (
                order_id, 
                user_id, 
                action, 
                status, 
                amount, 
                details
            ) VALUES (
                (SELECT order_id FROM book_rentals WHERE rental_id = ?),
                ?,
                ?,
                'success',
                ?,
                ?
            )
        ");
        
        $logAction = 'return_' . $returnStatus;
        $logAmount = ($returnStatus === 'inspected' && isset($damageFee)) ? $damageFee : 0;
        $logDetails = "Book return {$returnStatus}: " . $returnData['book_title'];
        
        $logStmt->bind_param(
            "iisds", 
            $rentalId, 
            $renterId, 
            $logAction, 
            $logAmount, 
            $logDetails
        );
        $logStmt->execute();
        
        // Log in the audit_logs
        log_activity($userId, 'Return ' . ucfirst($returnStatus), "Seller updated return status to {$returnStatus} for Book '{$returnData['book_title']}' (Rental #{$rentalId})");
        
        if ($returnStatus === 'completed') {
            log_activity($renterId, 'Escrow Settled', "Deposit returned/settled for Book '{$returnData['book_title']}' (Rental #{$rentalId})");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set appropriate success message based on the status
        switch ($returnStatus) {
            case 'received':
                $_SESSION['success_message'] = "Book received successfully. Please inspect the book for damage.";
                break;
            case 'inspected':
                $_SESSION['success_message'] = "Book inspection completed.";
                if (isset($damageFee) && $damageFee > 0) {
                    $_SESSION['success_message'] .= " A damage fee of ₱" . number_format($damageFee, 2) . " has been applied.";
                }
                break;
            case 'completed':
                $_SESSION['success_message'] = "Return process completed successfully. The book has been added back to inventory.";
                break;
        }
        
    } catch (Exception $e) {
        // Roll back transaction
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to process return: " . $e->getMessage();
        error_log("Return processing error: " . $e->getMessage());
    }
    
    // Redirect back to the rentals page
    header("Location: renter.php");
    exit();
}

// If we get here, something went wrong with the request
$_SESSION['error_message'] = "Invalid request.";
header("Location: renter.php");
exit();
?>