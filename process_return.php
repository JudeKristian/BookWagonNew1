<?php
include("session.php");
include("connect.php");
require_once "includes/audit_logger.php";

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    $_SESSION['error_message'] = "Please log in to continue.";
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['id'];

// Process return request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initiate_return') {
    // Get form data
    $rentalId = isset($_POST['rental_id']) ? intval($_POST['rental_id']) : 0;
    $returnMethod = isset($_POST['return_method']) ? $_POST['return_method'] : '';
    $returnDetails = '';
    
    // Validate rental ID
    if ($rentalId <= 0) {
        $_SESSION['error_message'] = "Invalid rental information.";
        header("Location: rented_books.php?tab=rentals");
        exit();
    }
    
    // Process return details based on method
    if ($returnMethod === 'dropoff') {
        if (!isset($_POST['dropoff_location'])) {
            $_SESSION['error_message'] = "Please select a drop-off location.";
            header("Location: rented_books.php?tab=rentals");
            exit();
        }
        
        $dropoffLocation = trim($_POST['dropoff_location']);
        $dropoffNotes = trim($_POST['dropoff_notes'] ?? '');
        
        $combinedLocation = $dropoffLocation;
        if (!empty($dropoffNotes)) {
            $combinedLocation .= " [Note: $dropoffNotes]";
        }
        
        $returnDetails = json_encode([
            'dropoff_location' => $combinedLocation,
            'dropoff_notes' => $dropoffNotes
        ]);
        
    } else if ($returnMethod === 'pickup') {
        // Validate pickup information
        if (!isset($_POST['pickup_date']) || !isset($_POST['pickup_time']) || !isset($_POST['pickup_address'])) {
            $_SESSION['error_message'] = "Please provide all required pickup information.";
            header("Location: rented_books.php?tab=rentals");
            exit();
        }
        
        $pickupDate = $_POST['pickup_date'];
        $pickupTime = $_POST['pickup_time'];
        $pickupAddress = $_POST['pickup_address'];
        $pickupNotes = isset($_POST['pickup_notes']) ? $_POST['pickup_notes'] : '';
        
        // Format pickup information for JSON storage
        $returnDetails = json_encode([
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'pickup_address' => $pickupAddress,
            'pickup_notes' => $pickupNotes
        ]);
    } else {
        $_SESSION['error_message'] = "Please select a valid return method.";
        header("Location: rented_books.php?tab=rentals");
        exit();
    }
    
    // Verify the rental belongs to the user and get all needed data
    $rentalStmt = $conn->prepare("
        SELECT 
            br.rental_id, 
            br.book_id,
            br.seller_id,
            br.status,
            br.due_date,
            b.title,
            b.rent_price
        FROM book_rentals br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.rental_id = ? AND br.user_id = ? AND br.status = 'active'
    ");
    $rentalStmt->bind_param("ii", $rentalId, $userId);
    $rentalStmt->execute();
    $rentalResult = $rentalStmt->get_result();
    
    if ($rentalResult->num_rows === 0) {
        $_SESSION['error_message'] = "Invalid rental or rental not in active status.";
        header("Location: rented_books.php?tab=rentals");
        exit();
    }
    
    $rentalData = $rentalResult->fetch_assoc();
    
    // Calculate if book is overdue and late fees
    $dueDate = strtotime($rentalData['due_date']);
    $currentDate = time();
    $isOverdue = $currentDate > $dueDate;
    $daysOverdue = 0;
    $lateFee = 0.00;
    $additionalFee = 0.00;
    
    if ($isOverdue) {
        $daysOverdue = ceil(($currentDate - $dueDate) / (60 * 60 * 24));
        $dailyLateFee = $rentalData['rent_price'] * 0.2; // 20% of daily rental price per day
        $lateFee = $daysOverdue * $dailyLateFee;
    }
    
    // Add pickup fee if needed
    if ($returnMethod === 'pickup') {
        $additionalFee = 50.00; // ₱50 pickup fee
    }
    
    // Create a return request record
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Insert return request into book_returns
        $returnStmt = $conn->prepare("
            INSERT INTO book_returns (
                rental_id, 
                user_id,
                book_id,
                seller_id,
                return_method, 
                return_details,
                status,
                request_date,
                is_overdue,
                days_overdue,
                late_fee,
                additional_fee
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?, ?)
        ");
        $bookId = $rentalData['book_id'];
        $sellerId = $rentalData['seller_id'];
        $returnStmt->bind_param(
            "iiisssiidd", 
            $rentalId, 
            $userId, 
            $bookId,
            $sellerId,
            $returnMethod, 
            $returnDetails,
            $isOverdue,
            $daysOverdue,
            $lateFee,
            $additionalFee
        );
        $returnStmt->execute();
        
        // Generate secure return token
        $returnToken = bin2hex(random_bytes(16));
        
        // Update the rental status to 'return_pending' and save the token
        $updateRentalStmt = $conn->prepare("
            UPDATE book_rentals
            SET status = 'return_pending',
                return_requested_date = NOW(),
                return_token = ?
            WHERE rental_id = ? AND user_id = ?
        ");
        $updateRentalStmt->bind_param("sii", $returnToken, $rentalId, $userId);
        $updateRentalStmt->execute();
        
        log_activity($userId, 'Return Initiated', 'Buyer initiated return for Rental #' . $rentalId . ' via ' . $returnMethod);
        
        // Commit transaction
        $conn->commit();
        
        // Get the return ID for the newly inserted record
        $returnId = $conn->insert_id;
        
        // Success message with fee information if applicable
        $successMessage = "Return request for '{$rentalData['title']}' has been submitted successfully. ";
        
        if ($returnMethod === 'dropoff') {
            $successMessage .= "Please drop off the book at the selected location.";
        } else {
            $successMessage .= "We will pick up the book on the scheduled date. A pickup fee of ₱50 will be applied.";
        }
        
        if ($isOverdue) {
            $successMessage .= " Late fee of ₱" . number_format($lateFee, 2) . " for {$daysOverdue} days overdue has been applied.";
        }
        
        $successMessage .= " You can track the status of your return in the Returns section of your Order History.";
        
        $_SESSION['success_message'] = $successMessage;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to process return request: " . $e->getMessage();
        
        header("Location: rented_books.php?tab=rentals");
        exit();
    }
    
    // Redirect back to rentals tab
    header("Location: rented_books.php?tab=rentals");
    exit();
} else {
    // Invalid request
    header("Location: rented_books.php");
    exit();
}
?>