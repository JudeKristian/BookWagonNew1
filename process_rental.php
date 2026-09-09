<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Ensure only sellers can access this page
if ($userType !== 'seller') {
    header("Location: login.php");
    exit();
}

// First, get the seller's record ID
$sellerStmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ? LIMIT 1");
$sellerStmt->bind_param("i", $userId);
$sellerStmt->execute();
$sellerData = $sellerStmt->get_result()->fetch_assoc();
$sellerDbId = $sellerData ? (int)$sellerData['id'] : $userId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'contact_renter' && isset($_POST['rental_id'])) {
        $rentalId = (int)$_POST['rental_id'];
        
        // Fetch rental details
        $stmt = $conn->prepare("
            SELECT 
                br.rental_id, 
                br.due_date, 
                b.title as book_title,
                u.email as renter_email,
                u.firstname as renter_firstname
            FROM book_rentals br
            JOIN books b ON br.book_id = b.book_id
            JOIN users u ON br.user_id = u.id
            WHERE br.rental_id = ? AND (br.seller_id = ? OR br.seller_id = ?)
        ");
        $stmt->bind_param("iii", $rentalId, $sellerDbId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rentalDetails = $result->fetch_assoc();
        
        if ($rentalDetails) {
            // Prepare email content
            $subject = "Overdue Book Rental Reminder";
            $message = "Dear " . htmlspecialchars($rentalDetails['renter_firstname']) . ",\n\n";
            $message .= "This is a reminder that the book '" . htmlspecialchars($rentalDetails['book_title']) . "' was due on " . 
                        date('M j, Y', strtotime($rentalDetails['due_date'])) . ".\n";
            $message .= "Please return the book as soon as possible or contact the seller to discuss an extension.\n\n";
            $message .= "Best regards,\nBookWagon Rental Team";
            
            // Log the contact attempt
            $logStmt = $conn->prepare("
                INSERT INTO payment_logs (order_id, user_id, action, status, details) 
                VALUES (NULL, ?, 'renter_contact', 'success', ?)
            ");
            $logDetails = "Sent overdue reminder for rental #" . $rentalId;
            $logStmt->bind_param("is", $userId, $logDetails);
            $logStmt->execute();
            
            // Optional: You might want to integrate an actual email sending mechanism here
            // For now, we'll just set a session message
            $_SESSION['success_message'] = "Overdue reminder prepared for " . htmlspecialchars($rentalDetails['renter_firstname']);
        } else {
            $_SESSION['error_message'] = "Invalid rental record.";
        }
    } 
    elseif ($action === 'mark_returned' && isset($_POST['rental_id'])) {
        $rentalId = $_POST['rental_id'];
        $bookCondition = $_POST['book_condition'] ?? 'good';
        $returnNotes = $_POST['return_notes'] ?? '';
        
        // Update rental status
        $updateStmt = $conn->prepare("
            UPDATE book_rentals 
            SET 
                status = 'returned', 
                return_date = NOW(), 
                book_condition = ?, 
                return_notes = ?
            WHERE rental_id = ? AND (seller_id = ? OR seller_id = ?)
        ");
        $updateStmt->bind_param("ssiii", $bookCondition, $returnNotes, $rentalId, $sellerDbId, $userId);
        
        if ($updateStmt->execute()) {
            // Fetch book details to update stock and order status
            $bookStmt = $conn->prepare("
                SELECT book_id, rental_weeks, order_id 
                FROM book_rentals 
                WHERE rental_id = ?
            ");
            $bookStmt->bind_param("i", $rentalId);
            $bookStmt->execute();
            $bookResult = $bookStmt->get_result();
            $bookData = $bookResult->fetch_assoc();
            
            if ($bookData) {
                // Update book availability
                $updateBookStmt = $conn->prepare("
                    UPDATE books 
                    SET stock = stock + 1 
                    WHERE book_id = ?
                ");
                $updateBookStmt->bind_param("i", $bookData['book_id']);
                $updateBookStmt->execute();

                // Also update order_items status to returned if order_id is linked
                if (!empty($bookData['order_id'])) {
                    $updItem = $conn->prepare("UPDATE order_items SET status = 'returned' WHERE order_id = ? AND book_id = ?");
                    $updItem->bind_param("ii", $bookData['order_id'], $bookData['book_id']);
                    $updItem->execute();
                }
            }
            
            // Log the return
            $logStmt = $conn->prepare("
                INSERT INTO payment_logs (order_id, user_id, action, status, details) 
                VALUES (NULL, ?, 'book_returned', 'success', ?)
            ");
            $logDetails = "Book returned for rental #" . $rentalId . ". Condition: " . $bookCondition;
            $logStmt->bind_param("is", $userId, $logDetails);
            $logStmt->execute();
            
            // Handle potential late return fees
            $lateReturnStmt = $conn->prepare("
                SELECT 
                    due_date, 
                    total_price / rental_weeks AS weekly_rate 
                FROM book_rentals 
                WHERE rental_id = ?
            ");
            $lateReturnStmt->bind_param("i", $rentalId);
            $lateReturnStmt->execute();
            $lateReturnResult = $lateReturnStmt->get_result();
            $lateReturnData = $lateReturnResult->fetch_assoc();
            
            // Calculate late fees if applicable
            $dueDate = strtotime($lateReturnData['due_date']);
            $returnDate = time();
            
            if ($returnDate > $dueDate) {
                $lateDays = ceil(($returnDate - $dueDate) / (60 * 60 * 24));
                $weeklyRate = $lateReturnData['weekly_rate'];
                $lateFee = $weeklyRate * $lateDays * 0.1; // 10% of weekly rate per late day
                
                // Insert late fee record
                $lateFeeStmt = $conn->prepare("
                    INSERT INTO payment_logs (order_id, user_id, action, status, amount, details) 
                    VALUES (NULL, ?, 'late_fee', 'pending', ?, ?)
                ");
                $lateFeeDetails = "Late return fee for rental #" . $rentalId . ". " . $lateDays . " days late";
                $lateFeeStmt->bind_param("ids", $userId, $lateFee, $lateFeeDetails);
                $lateFeeStmt->execute();
            }
            
            $_SESSION['success_message'] = "Book returned successfully. " . 
                ($bookCondition !== 'excellent' ? "Please note the book's condition was reported as " . $bookCondition . "." : "");
        } else {
            $_SESSION['error_message'] = "Failed to process book return.";
        }
    }
    else {
        $_SESSION['error_message'] = "Invalid action.";
    }
    
    // Redirect back to rentals page
    header("Location: renter.php");
    exit();
} else {
    // Unauthorized access
    header("Location: login.php");
    exit();
}