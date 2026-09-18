<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Debug mode
if (isset($_GET['debug']) && $_GET['debug'] == 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    error_log("Debug mode enabled in rented_books.php");

    if (isset($_GET['test_db'])) {
        try {
            $testQuery = "SELECT * FROM sellers LIMIT 1";
            $testResult = $conn->query($testQuery);
            $testData = $testResult->fetch_assoc();
            error_log("DB Connection test: " . print_r($testData, true));
        } catch (Exception $e) {
            error_log("DB Connection test error: " . $e->getMessage());
        }
    }
}

// Handle rental actions
if (isset($_GET['action']) && isset($_GET['rental_id'])) {
    $rentalId = intval($_GET['rental_id']);
    $action = $_GET['action'];

    if ($action == 'return') {
        // Fetch rental details including order_id for comprehensive return process
        $rentalStmt = $conn->prepare("
            SELECT 
                br.rental_id, 
                br.book_id, 
                br.user_id, 
                br.seller_id, 
                br.total_price, 
                br.rental_weeks, 
                br.rental_date, 
                br.due_date,
                br.order_id,  /* Make sure to fetch the order_id */
                b.title as book_title,
                b.author as book_author,
                b.rent_price
            FROM book_rentals br
            JOIN books b ON br.book_id = b.book_id
            WHERE br.rental_id = ? AND br.user_id = ?
        ");
        $rentalStmt->bind_param("ii", $rentalId, $userId);
        $rentalStmt->execute();
        $rentalResult = $rentalStmt->get_result();
        $rentalDetails = $rentalResult->fetch_assoc();

        if ($rentalDetails) {
            // Check if the book is overdue
            $dueDate = strtotime($rentalDetails['due_date']);
            $returnDate = time();
            $isOverdue = $returnDate > $dueDate;

            // Calculate late fee if overdue
            $lateFee = 0;
            $daysOverdue = 0;
            if ($isOverdue) {
                $daysOverdue = ceil(($returnDate - $dueDate) / (60 * 60 * 24));
                $dailyLateFee = $rentalDetails['rent_price'] * 0.2; // 20% of daily rental price per day
                $lateFee = $daysOverdue * $dailyLateFee;
            }

            // Start a transaction to ensure data consistency
            $conn->begin_transaction();

            try {
                // Update rental status with return details
                $updateRentalStmt = $conn->prepare("
                    UPDATE book_rentals 
                    SET 
                        status = 'returned', 
                        return_date = NOW(),
                        late_fee = ?,
                        days_overdue = ?
                    WHERE rental_id = ? AND user_id = ?
                ");
                $updateRentalStmt->bind_param("diii", $lateFee, $daysOverdue, $rentalId, $userId);
                $updateRentalStmt->execute();

                // Update book availability (increase stock)
                $updateBookStmt = $conn->prepare("
                    UPDATE books 
                    SET stock = stock + 1 
                    WHERE book_id = ?
                ");
                $updateBookStmt->bind_param("i", $rentalDetails['book_id']);
                $updateBookStmt->execute();

                // Use the actual order_id from the rental record or a default if needed
                // If order_id is NULL in database but column cannot be NULL, use a placeholder ID like 0 or 999999
                $orderIdForLog = $rentalDetails['order_id'] ?? 999999; // Use a placeholder ID if NULL

                // Log the return with a valid order_id
                $logStmt = $conn->prepare("
                    INSERT INTO payment_logs (
                        order_id, 
                        user_id, 
                        action, 
                        status, 
                        amount, 
                        details
                    ) VALUES (
                        ?, 
                        ?, 
                        'book_return', 
                        'success', 
                        ?, 
                        ?
                    )
                ");
                $logDetails = "Book returned: " . $rentalDetails['book_title'] . 
                              ($isOverdue ? " (Overdue: $daysOverdue days)" : "");
                $logStmt->bind_param("iids", $orderIdForLog, $userId, $lateFee, $logDetails);
                $logStmt->execute();

                // Commit the transaction
                $conn->commit();

                // Prepare success message
                $successMessage = "Book '{$rentalDetails['book_title']}' has been returned successfully.";
                if ($isOverdue) {
                    $successMessage .= " A late fee of ₱" . number_format($lateFee, 2) . " has been applied.";
                }

                $_SESSION['success_message'] = $successMessage;
            } catch (Exception $e) {
                // Rollback the transaction in case of error
                $conn->rollback();
                $_SESSION['error_message'] = "Failed to process book return: " . $e->getMessage();
                error_log("Book return error: " . $e->getMessage());
            }
        } else {
            $_SESSION['error_message'] = "Invalid rental record.";
        }

        // Redirect back to the rentals page
        header("Location: rented_books.php?tab=rentals");
        exit();
    } elseif ($action == 'extend') {
        // Default extension to 1 week
        $extendWeeks = 1;

        // Fetch current rental details
        $rentalStmt = $conn->prepare("
            SELECT 
                due_date, 
                rental_weeks, 
                total_price, 
                book_id,
                status
            FROM book_rentals 
            WHERE rental_id = ? AND user_id = ?
        ");
        $rentalStmt->bind_param("ii", $rentalId, $userId);
        $rentalStmt->execute();
        $rentalResult = $rentalStmt->get_result();

        if ($rental = $rentalResult->fetch_assoc()) {
            // Check if rental is already overdue
            $currentDate = new DateTime();
            $dueDate = new DateTime($rental['due_date']);
            
            if ($currentDate > $dueDate) {
                $_SESSION['error_message'] = "Cannot extend an overdue rental. Please return the book.";
                header("Location: rented_books.php?tab=rentals");
                exit();
            }

            // Check remaining extensions
            $extensionQuery = $conn->prepare("
                SELECT COUNT(*) as extension_count 
                FROM book_rentals 
                WHERE rental_id = ? AND user_id = ? AND extensions_used > 0
            ");
            $extensionQuery->bind_param("ii", $rentalId, $userId);
            $extensionQuery->execute();
            $extensionResult = $extensionQuery->get_result()->fetch_assoc();
            
            // Limit to 2 total extensions
            if ($extensionResult['extension_count'] >= 2) {
                $_SESSION['error_message'] = "You have reached the maximum number of rental extensions.";
                header("Location: rented_books.php?tab=rentals");
                exit();
            }

            // Fetch book details for rental price
            $bookStmt = $conn->prepare("SELECT rent_price FROM books WHERE book_id = ?");
            $bookStmt->bind_param("i", $rental['book_id']);
            $bookStmt->execute();
            $book = $bookStmt->get_result()->fetch_assoc();

            // Calculate new dates and prices
            $currentDueDate = new DateTime($rental['due_date']);
            $currentDueDate->modify("+{$extendWeeks} week");
            $newDueDate = $currentDueDate->format('Y-m-d H:i:s');

            $newRentalWeeks = $rental['rental_weeks'] + $extendWeeks;
            $newTotalPrice = $rental['total_price'] + ($book['rent_price'] * $extendWeeks);

            // Start transaction for extension
            $conn->begin_transaction();

            try {
                // Update rental details
                $updateStmt = $conn->prepare("
                    UPDATE book_rentals 
                    SET 
                        due_date = ?, 
                        rental_weeks = ?, 
                        total_price = ?,
                        extensions_used = COALESCE(extensions_used, 0) + 1
                    WHERE rental_id = ? AND user_id = ?
                ");
                $updateStmt->bind_param("sidii", $newDueDate, $newRentalWeeks, $newTotalPrice, $rentalId, $userId);
                $updateStmt->execute();

                // Log the extension
                $logStmt = $conn->prepare("
                    INSERT INTO payment_logs (
                        order_id, 
                        user_id, 
                        action, 
                        status, 
                        amount, 
                        details
                    ) VALUES (
                        NULL, 
                        ?, 
                        'rental_extension', 
                        'success', 
                        ?, 
                        ?
                    )
                ");
                $logDetails = "Rental extended by $extendWeeks week(s)";
                $logStmt->bind_param("ids", $userId, $book['rent_price'] * $extendWeeks, $logDetails);
                $logStmt->execute();

                // Commit transaction
                $conn->commit();

                $_SESSION['success_message'] = "Rental successfully extended by $extendWeeks week(s). New due date is " . $newDueDate;
            } catch (Exception $e) {
                // Rollback transaction
                $conn->rollback();
                $_SESSION['error_message'] = "Failed to extend rental. Please try again.";
                error_log("Rental extension error: " . $e->getMessage());
            }
        } else {
            $_SESSION['error_message'] = "Invalid rental record.";
        }

        // Redirect back to rentals page
        header("Location: rented_books.php?tab=rentals");
        exit();
    }
}

// Process Order Received & Book Condition Inspection
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_order_received') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderItemId = (int)($_POST['item_id'] ?? 0);
    $bookCondition = trim($_POST['book_condition'] ?? 'good');
    $conditionNotes = trim($_POST['condition_notes'] ?? '');
    
    // Validate that the buyer completed the physical condition checklist
    $chkCover = isset($_POST['chk_cover']) ? 1 : 0;
    $chkPages = isset($_POST['chk_pages']) ? 1 : 0;
    $chkSpine = isset($_POST['chk_spine']) ? 1 : 0;

    if (!$chkCover || !$chkPages || !$chkSpine) {
        $_SESSION['error_message'] = "Please inspect and check all 3 physical condition verification items before confirming receipt.";
        header("Location: rented_books.php?tab=to_receive");
        exit();
    }

    // Verify order item belongs to this buyer
    $vStmt = $conn->prepare("
        SELECT oi.item_id, oi.order_id, oi.book_id, oi.seller_id, oi.purchase_type, oi.rental_weeks, oi.unit_price,
               b.title as book_title, b.author as book_author, b.price as book_price, b.book_value, b.cover_image,
               o.payment_method, o.payment_status
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN books b ON oi.book_id = b.book_id
        WHERE oi.item_id = ? AND o.order_id = ? AND o.user_id = ?
    ");
    $vStmt->bind_param("iii", $orderItemId, $orderId, $userId);
    $vStmt->execute();
    $item = $vStmt->get_result()->fetch_assoc();

    if (!$item) {
        $_SESSION['error_message'] = "Invalid order item or permission denied.";
        header("Location: rented_books.php?tab=to_receive");
        exit();
    }

    // 1. Update order item status to delivered
    $updItem = $conn->prepare("UPDATE order_items SET status = 'delivered' WHERE item_id = ?");
    $updItem->bind_param("i", $orderItemId);
    $updItem->execute();

    // 2. Check if all items in order are now delivered
    $chkAll = $conn->prepare("SELECT COUNT(*) as uncompleted FROM order_items WHERE order_id = ? AND status != 'delivered'");
    $chkAll->bind_param("i", $orderId);
    $chkAll->execute();
    if ($chkAll->get_result()->fetch_assoc()['uncompleted'] == 0) {
        $updOrder = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ?");
        $updOrder->bind_param("i", $orderId);
        $updOrder->execute();
    }

    $rentalWeeks = max(1, (int)$item['rental_weeks']);
    $depositHeld = (float)($item['book_value'] ?? ($item['book_price'] * 0.5));
    if ($depositHeld <= 0) $depositHeld = round((float)$item['unit_price'] * 2.5, 2);

    $notesText = "Verified upon receipt: " . ucfirst($bookCondition) . ". Passed checklist (Cover intact, Pages complete, Spine secure). " . ($conditionNotes ? "Notes: $conditionNotes" : "");

    // 3. If rental, activate the rental record and set rental/due dates
    if ($item['purchase_type'] === 'rent') {
        // Look up seller DB ID
        $sellerDbId = (int)$item['seller_id'];
        $sLook = $conn->prepare("SELECT id FROM sellers WHERE user_id = ? OR id = ? LIMIT 1");
        $sLook->bind_param("ii", $item['seller_id'], $item['seller_id']);
        $sLook->execute();
        if ($sRow = $sLook->get_result()->fetch_assoc()) {
            $sellerDbId = (int)$sRow['id'];
        }

        // Check if rental record already exists
        $chkRent = $conn->prepare("SELECT rental_id FROM book_rentals WHERE order_id = ? AND book_id = ? LIMIT 1");
        $chkRent->bind_param("ii", $orderId, $item['book_id']);
        $chkRent->execute();
        $rRow = $chkRent->get_result()->fetch_assoc();

        if ($rRow) {
            $rId = (int)$rRow['rental_id'];
            $updRental = $conn->prepare("
                UPDATE book_rentals 
                SET status = 'active', 
                    rental_date = NOW(), 
                    due_date = DATE_ADD(NOW(), INTERVAL ? WEEK),
                    book_condition = ?,
                    return_notes = ?
                WHERE rental_id = ?
            ");
            $updRental->bind_param("issi", $rentalWeeks, $bookCondition, $notesText, $rId);
            $updRental->execute();
        } else {
            $insRental = $conn->prepare("
                INSERT INTO book_rentals (
                    user_id, book_id, seller_id, rental_date, due_date, rental_weeks, status, total_price, order_id, book_condition, return_notes
                ) VALUES (
                    ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? WEEK), ?, 'active', ?, ?, ?, ?
                )
            ");
            $insRental->bind_param(
                "iiiiidiss", 
                $userId, $item['book_id'], $sellerDbId, 
                $rentalWeeks, $rentalWeeks, 
                $item['unit_price'], $orderId, 
                $bookCondition, $notesText
            );
            $insRental->execute();
            $rId = $conn->insert_id;
        }
    }

    // 4. Digital Signature & Audit Logging
    $receiptTime = date('Y-m-d H:i:s');
    $rawSig = "ORDER:{$orderId}|ITEM:{$orderItemId}|BUYER:{$userId}|DATE:{$receiptTime}|COND:{$bookCondition}|BW-RECEIPT-VERIFIED";
    $verificationHash = hash('sha256', $rawSig);

    $logStmt = $conn->prepare("INSERT INTO payment_logs (order_id, user_id, action, status, amount, details) VALUES (?, ?, 'order_received', 'success', ?, ?)");
    $logDetails = "Order #{$orderId} received & physical condition verified: " . ucfirst($bookCondition) . " (Sig: " . substr($verificationHash, 0, 16) . ")";
    $logAmount = ($item['purchase_type'] === 'rent') ? $depositHeld : $item['unit_price'];
    $logStmt->bind_param("iids", $orderId, $userId, $logAmount, $logDetails);
    $logStmt->execute();

    $_SESSION['success_message'] = "Order received! Book condition documented successfully.";
    header("Location: rented_books.php?tab=" . ($item['purchase_type'] === 'rent' ? 'rentals' : 'completed'));
    exit();
}

// Rest of the code remains unchanged
// Fetch orders query
$ordersQuery = "
    SELECT o.order_id, o.order_date, o.payment_method, o.payment_status, o.order_status,
           oi.item_id, oi.book_id, oi.purchase_type, oi.rental_weeks, oi.status as item_status, oi.unit_price,
           b.title, b.author, b.cover_image, b.description, b.price, b.rent_price,
           s.firstname as seller_firstname, s.lastname as seller_lastname, s.username as seller_username
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN books b ON oi.book_id = b.book_id
    JOIN users s ON oi.seller_id = s.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
";

$ordersStmt = $conn->prepare($ordersQuery);
$ordersStmt->bind_param("i", $userId);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);

// Organize orders by status for the tabbed interface
$toPay = [];
$toShip = [];
$toReceive = [];
$completed = [];
$cancelled = [];
$activeRentals = [];

// First, fetch user's active rentals
$rentalsQuery = "
    SELECT br.*, b.title, b.author, b.cover_image, b.description, b.ISBN, 
           u.firstname, u.lastname, u.username, o.order_id
    FROM book_rentals br
    JOIN books b ON br.book_id = b.book_id
    JOIN sellers s ON br.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN orders o ON br.order_id = o.order_id
    WHERE br.user_id = ? AND br.status IN ('active', 'return_pending')
    ORDER BY br.rental_date DESC
";

$rentalsStmt = $conn->prepare($rentalsQuery);
$rentalsStmt->bind_param("i", $userId);
$rentalsStmt->execute();
$rentalsResult = $rentalsStmt->get_result();
$activeRentals = $rentalsResult->fetch_all(MYSQLI_ASSOC);

// Debug information
error_log("Active Rentals Query Result: " . count($activeRentals));
if (count($activeRentals) > 0) {
    error_log("First Rental: " . print_r($activeRentals[0], true));
}

// Categorize orders by status
foreach ($orders as $order) {
    // Initialize variables for better type detection
    $isRental = false;
    
    // Determine purchase type if empty
    $purchaseType = $order['purchase_type'];
    if (empty($purchaseType)) {
        // If rental_weeks > 0 and unit price approximately matches rent_price * rental_weeks, it's a rental
        if ($order['rental_weeks'] > 0 && 
            abs($order['unit_price'] - ($order['rent_price'] * $order['rental_weeks'])) < 5) {
            $purchaseType = 'rent';
        }
        // If unit price is significantly less than book price, it's likely a rental
        else if ($order['unit_price'] < ($order['price'] * 0.9)) {
            $purchaseType = 'rent';
        }
        // If unit price is close to book price, it's a purchase
        else if (abs($order['unit_price'] - $order['price']) < ($order['price'] * 0.1)) {
            $purchaseType = 'buy';
        }
        // Default: if has rental weeks, assume rental
        else if ($order['rental_weeks'] > 0) {
            $purchaseType = 'rent';
        }
        // Otherwise, assume it's a buy
        else {
            $purchaseType = 'buy';
        }
        
        // Update the order array with the determined purchase type
        $order['purchase_type'] = $purchaseType;
    }
    
    // Check if this is a rental based on purchase_type
    if ($order['purchase_type'] == 'rent') {
        $isRental = true;
    }
    
    // Now determine which category this order belongs to
    if ($order['payment_method'] == 'bank_transfer' && $order['payment_status'] == 'awaiting_payment') {
        $toPay[] = $order;
    } 
    elseif (($order['payment_status'] == 'paid' || $order['payment_method'] == 'cod' || $order['payment_method'] == 'pickup') 
            && ($order['item_status'] == 'pending' || $order['item_status'] == 'processing' || $order['item_status'] == 'pending_meetup')) {
        $toShip[] = $order;
    }
    elseif ($order['item_status'] == 'shipped' || $order['item_status'] == 'shipped_pending_confirmation') {
        $toReceive[] = $order;
    }
    elseif ($order['item_status'] == 'delivered') {
        if ($isRental) {
            // This is a rental item that's been delivered
            // Check if it already has an active rental record
            $checkRentalStmt = $conn->prepare(
                "SELECT rental_id, status FROM book_rentals 
                 WHERE order_id = ? AND book_id = ? AND user_id = ?"
            );
            $checkRentalStmt->bind_param("iii", $order['order_id'], $order['book_id'], $userId);
            $checkRentalStmt->execute();
            $rentalResult = $checkRentalStmt->get_result();
            
            if ($rentalResult->num_rows > 0) {
                $rentalRecord = $rentalResult->fetch_assoc();
                // Skip if the rental has a pending return request
                if ($rentalRecord['status'] === 'return_pending' || $rentalRecord['status'] === 'returned') {
                    // Don't add to any active tab - it should only appear in history
                    continue;
                }
            }
            
            // If no active rental exists, add to To Receive for confirmation
            if ($rentalResult->num_rows == 0) {
                $toReceive[] = $order;
            }
            // If it has an active entry in book_rentals, it will be shown in the rentals tab
        } else {
            // This is a regular purchase, add to completed
            $completed[] = $order;
        }
    }
    elseif ($order['item_status'] == 'cancelled') {
        $cancelled[] = $order;
    }
}

// Rest of the file remains unchanged...

// Count items in each category
$toPayCount = count($toPay);
$toShipCount = count($toShip);
$toReceiveCount = count($toReceive);
$completedCount = count($completed);
$cancelledCount = count($cancelled);
$activeRentalsCount = count($activeRentals);

// Determine which tab to show based on URL parameter or default to 'all'
$activeTab = $_GET['tab'] ?? 'all';
$highlightRentalId = isset($_GET['highlight_rental']) ? intval($_GET['highlight_rental']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders & Rentals - BookWagon</title>
    <!-- Bootstrap CSS -->
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #f8a100;
            --secondary-color: #f8f9fa;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            background-color: #f8fafc;
        }
        .navbar {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .navbar-brand img {
            height: 60px;
        }

        /* Tab navigation styles */
        .order-tabs {
            display: flex;
            overflow-x: auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .order-tab {
            padding: 15px 20px;
            white-space: nowrap;
            color: var(--text-dark);
            text-decoration: none;
            position: relative;
            font-weight: 500;
            transition: all 0.2s;
            text-align: center;
            flex: 1;
        }
        
        .order-tab.active {
            color: var(--primary-color);
        }
        
        .order-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .order-tab:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .tab-count {
            display: inline-block;
            background-color: #f1f1f1;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 0.8rem;
            line-height: 24px;
            margin-left: 5px;
        }
        
        /* Order card styles */
        .order-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .order-card:hover {
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .order-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-date {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .order-number {
            font-weight: 600;
        }
        
        .order-status {
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-to-pay {
            background-color: #fff8e1;
            color: #ff9800;
        }
        
        .status-to-ship {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        
        .status-to-receive {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .status-completed {
            background-color: #e0f2f1;
            color: #009688;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-item {
            display: flex;
            align-items: flex-start;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .item-image {
            width: 80px;
            height: 110px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-author {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .item-type {
            display: inline-block;
            font-size: 0.8rem;
            padding: 3px 8px;
            background-color: #f1f1f1;
            border-radius: 4px;
            margin-right: 5px;
        }
        
        .order-footer {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-total {
            font-weight: 600;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .order-action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-confirm-receipt {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-confirm-receipt:hover {
            background-color: #218838;
            color: white;
        }
        
        .btn-view-details {
            background-color: var(--secondary-color);
            color: var(--text-dark);
        }
        
        .btn-view-details:hover {
            background-color: #e2e6ea;
        }
        
        .seller-info {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .highlighted-rental {
            background-color: #fffaeb;
            border: 2px solid #f8a100;
            box-shadow: 0 0 15px rgba(248, 161, 0, 0.2);
            animation: highlight-pulse 2s ease-in-out 3;
        }
        
        @keyframes highlight-pulse {
            0% { box-shadow: 0 0 15px rgba(248, 161, 0, 0.2); }
            50% { box-shadow: 0 0 20px rgba(248, 161, 0, 0.5); }
            100% { box-shadow: 0 0 15px rgba(248, 161, 0, 0.2); }
        }
        
        /* Sidebar and rental styles from the original file */
        .sidebar {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px 0;
            min-height: calc(100vh - 150px);
            position: sticky;
            top: 20px;
        }
        
        .sidebar-link {
            display: block;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(0, 123, 255, 0.05);
            color: #4a6cf7;
            border-left: 3px solid #4a6cf7;
        }
        
        .sidebar-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        /* Alert styles */
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        /* Return Book Modal Styles - Simple & Clean */
        .return-modal-dialog {
            max-width: 500px;
        }
        .return-method-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s ease;
            user-select: none;
        }
        .return-method-btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }
        .return-method-btn.active {
            border-color: #0f172a;
            background: #0f172a;
            color: #ffffff;
        }
        .loc-option-label {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            background: #ffffff;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }
        .loc-option-label:hover {
            border-color: #94a3b8 !important;
            background-color: #fafbfc !important;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include("include/user_header.php"); ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar Column -->
            <div class="col-lg-3 col-md-4 mb-4">
                <?php include("include/user_sidebar.php"); ?>
            </div>
            
            <!-- Main Content Column -->
            <div class="col-lg-9 col-md-8">
                <h2 class="mb-4" style="font-weight: 700; color: #0f172a;">My Orders & Rentals</h2>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Order Tabs -->
                <div class="order-tabs">
                    <a href="?tab=all" class="order-tab <?php echo $activeTab == 'all' ? 'active' : ''; ?>">
                        All
                    </a>
                    <a href="?tab=to_pay" class="order-tab <?php echo $activeTab == 'to_pay' ? 'active' : ''; ?>">
                        To Pay
                        <?php if ($toPayCount > 0): ?>
                        <span class="tab-count"><?php echo $toPayCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=to_ship" class="order-tab <?php echo $activeTab == 'to_ship' ? 'active' : ''; ?>">
                        Pending Meet-up
                        <?php if ($toShipCount > 0): ?>
                        <span class="tab-count"><?php echo $toShipCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=to_receive" class="order-tab <?php echo $activeTab == 'to_receive' ? 'active' : ''; ?>">
                        To Receive
                        <?php if ($toReceiveCount > 0): ?>
                        <span class="tab-count"><?php echo $toReceiveCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=completed" class="order-tab <?php echo $activeTab == 'completed' ? 'active' : ''; ?>">
                        Completed
                    </a>
                    <a href="?tab=cancelled" class="order-tab <?php echo $activeTab == 'cancelled' ? 'active' : ''; ?>">
                        Cancelled
                    </a>
                    <a href="?tab=rentals" class="order-tab <?php echo $activeTab == 'rentals' ? 'active' : ''; ?>">
                        Rentals
                        <?php if ($activeRentalsCount > 0): ?>
                        <span class="tab-count"><?php echo $activeRentalsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Orders Content -->
                <?php 
                // Determine which orders to display based on the active tab
                $displayOrders = [];
                
                switch ($activeTab) {
                    case 'to_pay':
                        $displayOrders = $toPay;
                        $emptyMessage = "You don't have any orders waiting for payment.";
                        break;
                    case 'to_ship':
                        $displayOrders = $toShip;
                        $emptyMessage = "You don't have any orders waiting to be shipped.";
                        break;
                    case 'to_receive':
                        $displayOrders = $toReceive;
                        $emptyMessage = "You don't have any orders waiting to be received.";
                        break;
                    case 'completed':
                        $displayOrders = $completed;
                        $emptyMessage = "You don't have any completed orders.";
                        break;
                    case 'cancelled':
                        $displayOrders = $cancelled;
                        $emptyMessage = "You don't have any cancelled orders.";
                        break;
                    case 'rentals':
                        // We'll handle rentals separately
                        $emptyMessage = "You don't have any active rentals.";
                        break;
                    default: // 'all'
                        $displayOrders = array_merge($toPay, $toShip, $toReceive, $completed, $cancelled);
                        $emptyMessage = "You don't have any orders yet.";
                        break;
                }
                
                // Display orders for the active tab
                if ($activeTab != 'rentals' && !empty($displayOrders)): 
                ?>
                    <?php foreach ($displayOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-date"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></div>
                                <div class="order-number">Order #<?php echo $order['order_id']; ?></div>
                            </div>
                            <?php
                            // Determine the status text and class based on the category
                            $statusText = "";
                            $statusClass = "";
                            
                            if ($order['payment_method'] == 'bank_transfer' && $order['payment_status'] == 'awaiting_payment') {
                                $statusText = "To Pay";
                                $statusClass = "status-to-pay";
                            } 
                            elseif ($order['item_status'] == 'pending' || $order['item_status'] == 'processing') {
                                $statusText = "To Ship";
                                $statusClass = "status-to-ship";
                            }
                            elseif ($order['item_status'] == 'pending_meetup') {
                                $statusText = "Pending Meet-up";
                                $statusClass = "status-to-ship";
                            }
                            elseif ($order['item_status'] == 'shipped') {
                                $statusText = "To Receive";
                                $statusClass = "status-to-receive";
                            }
                            elseif ($order['item_status'] == 'delivered') {
                                $statusText = "Completed";
                                $statusClass = "status-completed";
                            }
                            elseif ($order['item_status'] == 'cancelled') {
                                $statusText = "Cancelled";
                                $statusClass = "status-cancelled";
                            }
                            ?>
                            <div class="order-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-item">
                                <img src="<?php echo $order['cover_image']; ?>" alt="<?php echo $order['title']; ?>" class="item-image">
                                
                                <div class="item-details">
                                    <div class="item-title"><?php echo $order['title']; ?></div>
                                    <div class="item-author">by <?php echo $order['author']; ?></div>
                                    
                                    <div class="mt-2">
                                        <span class="item-type">
                                            <?php echo ucfirst($order['purchase_type']); ?>
                                            <?php if ($order['purchase_type'] == 'rent'): ?>
                                            (<?php echo $order['rental_weeks']; ?> week<?php echo $order['rental_weeks'] > 1 ? 's' : ''; ?>)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="item-price mt-2">
                                        ₱<?php echo number_format($order['unit_price'], 2); ?>
                                    </div>
                                    
                                    <div class="seller-info">
                                        Seller: <?php echo !empty($order['seller_username']) ? $order['seller_username'] : $order['seller_firstname'] . ' ' . $order['seller_lastname']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                Total: ₱<?php echo number_format($order['unit_price'], 2); ?>
                            </div>
                            
                            <div class="order-actions">
                                <?php if ($order['item_status'] == 'pending_meetup'): ?>
                                <button type="button" 
                                        class="order-action-btn btn-meetup-handshake"
                                        style="background-color: var(--primary-color); color: white;"
                                        data-order-id="<?php echo $order['order_id']; ?>"
                                        data-item-id="<?php echo $order['item_id']; ?>"
                                        data-buyer-id="<?php echo $userId; ?>"
                                        data-bs-toggle="modal" data-bs-target="#handshakeModal"
                                        onclick="initHandshake(this)">
                                    <i class="fas fa-handshake me-1"></i> Receive Book
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($order['item_status'] == 'shipped' || $order['item_status'] == 'shipped_pending_confirmation'): ?>
                                <button type="button" 
                                        class="order-action-btn btn-confirm-receipt btn-receive-with-inspection"
                                        data-order-id="<?php echo $order['order_id']; ?>"
                                        data-item-id="<?php echo $order['item_id']; ?>"
                                        data-book-id="<?php echo $order['book_id']; ?>"
                                        data-title="<?php echo htmlspecialchars($order['title']); ?>"
                                        data-author="<?php echo htmlspecialchars($order['author']); ?>"
                                        data-image="<?php echo htmlspecialchars(!empty($order['cover_image']) ? $order['cover_image'] : 'uploads/covers/default_book.jpg'); ?>"
                                        data-type="<?php echo htmlspecialchars($order['purchase_type']); ?>"
                                        data-weeks="<?php echo (int)($order['rental_weeks'] ?? 0); ?>"
                                        data-price="<?php echo number_format($order['unit_price'], 2); ?>"
                                        data-seller="<?php echo htmlspecialchars(!empty($order['seller_username']) ? $order['seller_username'] : ($order['seller_firstname'] . ' ' . $order['seller_lastname'])); ?>">
                                    Order Received
                                </button>
                                <?php endif; ?>
                                
                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="order-action-btn btn-view-details">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                
                <?php elseif ($activeTab == 'rentals' && !empty($activeRentals)): ?>
                    <!-- Display active rentals -->
                    <?php foreach ($activeRentals as $rental): 
                        $highlightClass = ($highlightRentalId > 0 && $rental['rental_id'] == $highlightRentalId) ? 'highlighted-rental' : '';
                    ?>
                    <div class="order-card <?php echo $highlightClass; ?>">
                        <div class="order-header">
                            <div>
                            <div class="order-date">Rental started: <?php echo date('F j, Y', strtotime($rental['rental_date'])); ?></div>
                                <div class="order-number">Rental #<?php echo $rental['rental_id']; ?></div>
                            </div>
                            <div class="order-status">
                                <?php 
                                $statusClass = '';
                                $statusText = ucfirst($rental['status']);
                                
                                switch ($rental['status']) {
                                    case 'active':
                                        $isOverdue = (strtotime($rental['due_date']) < time());
                                        $statusClass = $isOverdue ? 'status-to-pay' : 'status-to-receive';
                                        $statusText = $isOverdue ? 'Overdue' : 'Active';
                                        break;
                                    case 'return_pending':
                                        $statusClass = 'status-to-ship';
                                        $statusText = 'Return Pending';
                                        break;
                                    case 'overdue':
                                        $statusClass = 'status-to-pay';
                                        $statusText = 'Overdue';
                                        break;
                                    case 'returned':
                                        $statusClass = 'status-completed';
                                        $statusText = 'Returned';
                                        break;
                                    default:
                                        $statusClass = 'status-to-ship';
                                        $statusText = ucfirst($rental['status']);
                                }
                                ?>
                                <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-item">
                                <img src="<?php echo $rental['cover_image']; ?>" alt="<?php echo $rental['title']; ?>" class="item-image" onerror="this.src='img/default-book-cover.jpg'">
                                
                                <div class="item-details">
                                    <div class="item-title"><?php echo $rental['title']; ?></div>
                                    <div class="item-author">by <?php echo $rental['author']; ?></div>
                                    
                                    <div class="mt-2">
                                        <span class="item-type">
                                            Rental: <?php echo $rental['rental_weeks']; ?> week<?php echo $rental['rental_weeks'] > 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="item-price mt-2">
                                        ₱<?php echo number_format($rental['total_price'], 2); ?>
                                    </div>
                                    
                                    <div class="seller-info">
                                        Due Date: <?php echo date('F j, Y', strtotime($rental['due_date'])); ?>
                                        <span class="ms-2 text-muted">
                                            (<?php echo $rental['rental_weeks']; ?> week<?php echo $rental['rental_weeks'] > 1 ? 's' : ''; ?> rental)
                                        </span>
                                        <?php if (strtotime($rental['due_date']) < time() && $rental['status'] !== 'returned'): ?>
                                        <span class="text-danger fw-bold ms-2">OVERDUE</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="seller-info mt-1">
                                        Seller: <?php echo !empty($rental['username']) ? $rental['username'] : $rental['firstname'] . ' ' . $rental['lastname']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                Rental Fee: ₱<?php echo number_format($rental['total_price'], 2); ?>
                            </div>
                            
                            <div class="order-actions">
                            <button type="button" 
                                    class="order-action-btn btn-view-details btn-view-agreement-qr"
                                    data-order-id="<?php echo $rental['order_id'] ?? 0; ?>"
                                    data-rental-id="<?php echo $rental['rental_id']; ?>"
                                    data-title="<?php echo htmlspecialchars($rental['title']); ?>"
                                    data-author="<?php echo htmlspecialchars($rental['author']); ?>"
                                    data-condition="<?php echo htmlspecialchars(ucfirst($rental['book_condition'] ?? 'Good')); ?>"
                                    data-start="<?php echo date('M j, Y', strtotime($rental['rental_date'])); ?>"
                                    data-due="<?php echo date('M j, Y', strtotime($rental['due_date'])); ?>"
                                    data-fee="<?php echo number_format($rental['total_price'], 2); ?>"
                                    data-token="<?php echo $rental['return_token']; ?>"
                                    data-seller="<?php echo htmlspecialchars(!empty($rental['username']) ? $rental['username'] : ($rental['firstname'] . ' ' . $rental['lastname'])); ?>">
                                Return QR
                            </button>
                            <?php if ($rental['status'] === 'return_pending'): ?>
                                <span class="badge bg-light text-secondary border px-3 py-2 d-inline-flex align-items-center" style="font-size: 0.8rem;">
                                    <i class="fas fa-clock me-1 text-warning"></i> Return Pending Handover
                                </span>
                            <?php else: ?>
                                <a href="#" class="order-action-btn btn-confirm-receipt btn-return-book"
                                data-bs-toggle="modal" 
                                data-bs-target="#returnBookModal"
                                data-rental-id="<?php echo $rental['rental_id']; ?>"
                                data-book-id="<?php echo $rental['book_id']; ?>"
                                data-book-title="<?php echo htmlspecialchars($rental['title']); ?>"
                                data-book-author="<?php echo htmlspecialchars($rental['author']); ?>"
                                data-book-image="<?php echo $rental['cover_image']; ?>"
                                data-due-date="<?php echo date('F j, Y', strtotime($rental['due_date'])); ?>"
                                data-is-overdue="<?php echo (strtotime($rental['due_date']) < time()) ? 'true' : 'false'; ?>"
                                data-seller-id="<?php echo $rental['seller_id']; ?>">
                                    Return Book
                                </a>
                                <a href="rented_books.php?action=extend&rental_id=<?php echo $rental['rental_id']; ?>" class="order-action-btn btn-view-details">
                                    Extend Rental
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    </div>
                    <?php endforeach; ?>
                
                <?php else: ?>
                    <!-- Empty state when no orders in the selected category -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3 class="mb-3"><?php echo $emptyMessage; ?></h3>
                        <p class="text-muted mb-4">Browse books to start shopping or check other order categories.</p>
                        <a href="rentbooks.php" class="btn btn-primary">Browse Books</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
              
    
    <!-- Simple & Clean Return Book Modal (Icon-free, Meet-up ready) -->
    <div class="modal fade" id="returnBookModal" tabindex="-1" aria-labelledby="returnBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered return-modal-dialog">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid #e9ecef; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                <div class="modal-header py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="returnBookModalLabel" style="font-size: 1.05rem; color: #0f172a;">
                            Return Book
                        </h5>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">Select handover method and meet-up place</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                </div>
                
                <form action="process_return.php" method="POST" id="formReturnBook">
                    <input type="hidden" name="rental_id" id="return_rental_id" value="">
                    <input type="hidden" name="action" value="initiate_return">
                    
                    <div class="modal-body px-4 py-3">
                        <!-- Book Preview & Due Date -->
                        <div class="d-flex align-items-center gap-3 p-2-5 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <img src="" id="return_book_image" alt="Book Cover" style="width: 44px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; flex-shrink: 0;" onerror="this.src='img/default-book-cover.jpg'">
                            <div style="flex: 1; min-width: 0;">
                                <div class="fw-bold text-truncate" id="return_book_title" style="font-size: 0.9rem; color: #0f172a;"></div>
                                <div class="text-muted mb-1" id="return_book_author" style="font-size: 0.78rem;"></div>
                                <div style="font-size: 0.78rem;">
                                    Due Date: <span id="return_due_date" class="fw-semibold text-dark"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Overdue Alert (Dynamically Shown/Hidden) -->
                        <div id="overdue_notice" class="alert alert-danger py-2 px-3 mb-3 d-none" style="font-size: 0.8rem; border-radius: 6px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b;">
                            This rental is past its due date. Please return promptly to prevent additional fees.
                        </div>

                        <input type="hidden" name="return_method" id="return_dropoff" value="dropoff">

                        <!-- Drop-off & Meet-up Section -->
                        <div id="dropoff_locations" class="mb-3">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">
                                Meet-up / Drop-off Place
                            </label>
                            
                            <div id="dropoff_locations_container">
                                <div class="text-center text-muted py-2" style="font-size: 0.8rem;">Loading location options...</div>
                            </div>

                            <!-- Meet-up Note Input as requested: Note: [Campus Meet-up] -->
                            <div class="mt-2">
                                <label for="dropoff_notes" class="form-label fw-semibold mb-1" style="font-size: 0.78rem; color: #475569;">
                                    Meet-up Details / Note (Optional)
                                </label>
                                <input type="text" class="form-control form-control-sm" id="dropoff_notes" name="dropoff_notes" placeholder="e.g. Note: [Campus Meet-up: Main Library Ground Floor at 2 PM]" style="font-size: 0.82rem;">
                                <div class="d-flex flex-wrap align-items-center gap-1 mt-1 pt-1">
                                    <span class="text-muted" style="font-size: 0.72rem;">Quick presets:</span>
                                    <button type="button" class="btn btn-light border py-0 px-1" style="font-size: 0.72rem; line-height: 1.5;" onclick="setMeetupNote('Campus Library Lobby')">Library Lobby</button>
                                    <button type="button" class="btn btn-light border py-0 px-1" style="font-size: 0.72rem; line-height: 1.5;" onclick="setMeetupNote('Main Campus Gate')">Main Gate</button>
                                    <button type="button" class="btn btn-light border py-0 px-1" style="font-size: 0.72rem; line-height: 1.5;" onclick="setMeetupNote('Student Center')">Student Center</button>
                                    <button type="button" class="btn btn-light border py-0 px-1" style="font-size: 0.72rem; line-height: 1.5;" onclick="setMeetupNote('College Cafeteria')">Cafeteria</button>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.73rem;">
                                    Add your agreed meet-up location or specific time on campus.
                                </div>
                            </div>

                            <!-- Simple Clean Tip -->
                            <div class="p-2 mt-2" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.77rem; color: #475569;">
                                <strong>Tip:</strong> Present your <strong>Return QR</strong> during meet-up for immediate verification by the seller.
                            </div>
                        </div>


                        <!-- Book Condition Declaration Checkbox -->
                        <div class="p-2-5" style="background: #ffffff; border: 1px solid #e9ecef; border-radius: 6px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition_checkbox" name="condition_confirmation" required>
                                <label class="form-check-label text-dark" for="condition_checkbox" style="font-size: 0.82rem;">
                                    I confirm the book is complete and ready for return inspection.
                                </label>
                            </div>
                            <div class="text-muted mt-1 ps-4" style="font-size: 0.74rem;">
                                Subject to seller inspection upon receipt. <a href="#" data-bs-toggle="modal" data-bs-target="#damagePolicy" class="text-primary text-decoration-none">View Damage Policy</a>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm text-white fw-bold px-3">
                            Submit Return Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Damage Policy Modal - Clean & Simple -->
    <div class="modal fade" id="damagePolicy" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid #e9ecef; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                <div class="modal-header py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title fw-bold mb-0" style="font-size: 1rem; color: #0f172a;">Book Damage Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                </div>
                <div class="modal-body px-4 py-3" style="font-size: 0.82rem;">
                    <div class="mb-2 p-2-5 border rounded" style="background: #f8fafc;">
                        <div class="fw-bold text-success mb-1">Normal Wear & Tear (No Charge)</div>
                        <div class="text-muted">Minor spine creases, slight page yellowing, or minor corner scuffs.</div>
                    </div>
                    <div class="mb-2 p-2-5 border rounded" style="background: #f8fafc;">
                        <div class="fw-bold text-primary mb-1">Minor Damage (25% Book Value)</div>
                        <div class="text-muted">Small water spots not affecting readability, light pen marks on fewer than 5 pages.</div>
                    </div>
                    <div class="mb-2 p-2-5 border rounded" style="background: #f8fafc;">
                        <div class="fw-bold text-warning mb-1">Significant Damage (50% Book Value)</div>
                        <div class="text-muted">Torn pages, broken spine, or heavy highlighting across multiple chapters.</div>
                    </div>
                    <div class="p-2-5 border rounded" style="background: #fef2f2; border-color: #fecaca !important;">
                        <div class="fw-bold text-danger mb-1">Severe Damage / Lost (Full Book Value)</div>
                        <div class="text-muted">Missing pages, mold, detached cover, or failure to return within 30 days of due date.</div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to highlighted rental if present
            const highlightedRental = document.querySelector('.highlighted-rental');
            if (highlightedRental) {
                setTimeout(() => {
                    highlightedRental.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
            
            // Add event handler for purchase type radio buttons to ensure they're properly saved
            const purchaseTypeRadios = document.querySelectorAll('input[name="purchase_type"]');
            if (purchaseTypeRadios.length > 0) {
                purchaseTypeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        // When purchase type is changed, submit the form immediately
                        if (this.closest('form')) {
                            this.closest('form').submit();
                        }
                    });
                });
            }
            
            // Meet-up quick presets helper
            window.setMeetupNote = function(preset) {
                const noteInput = document.getElementById('dropoff_notes');
                if (noteInput) {
                    noteInput.value = `Note: [Campus Meet-up: ${preset}]`;
                    noteInput.focus();
                }
            };


            // Helper to render default drop-off / meet-up locations
            function renderDefaultDropoffLocations(container, sellerData) {
                let html = '';
                let hasSelectedDefault = false;

                // Option 1: Seller's Listing Meet-up Location (if specified)
                if (sellerData && sellerData.book_meetup && sellerData.book_meetup.trim() !== '') {
                    const spot = sellerData.book_meetup.trim();
                    html += `
                        <div class="mb-2">
                            <label class="loc-option-label d-block cursor-pointer" for="loc_seller_pref">
                                <div class="d-flex align-items-start gap-2">
                                    <input class="form-check-input mt-1" type="radio" name="dropoff_location" id="loc_seller_pref" value="${spot.replace(/"/g, '&quot;')}" checked>
                                    <div style="font-size: 0.82rem; line-height: 1.4;">
                                        <div class="fw-bold text-dark">${spot} <span class="badge bg-warning text-dark border ms-1" style="font-size: 0.68rem;">Original Listing Spot</span></div>
                                        <div class="text-muted" style="font-size: 0.77rem;">
                                            Seller's preferred meet-up location for this book.
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                    hasSelectedDefault = true;
                }

                // Option 2: Original Handover Location (from order checkout)
                if (sellerData && sellerData.order_pickup && sellerData.order_pickup.trim() !== '') {
                    const origHandover = sellerData.order_pickup.trim();
                    const isChecked = !hasSelectedDefault ? 'checked' : '';
                    if (isChecked) hasSelectedDefault = true;

                    html += `
                        <div class="mb-2">
                            <label class="loc-option-label d-block cursor-pointer" for="loc_orig_handover">
                                <div class="d-flex align-items-start gap-2">
                                    <input class="form-check-input mt-1" type="radio" name="dropoff_location" id="loc_orig_handover" value="${origHandover.replace(/"/g, '&quot;')}" ${isChecked}>
                                    <div style="font-size: 0.82rem; line-height: 1.4;">
                                        <div class="fw-bold text-dark">${origHandover} <span class="badge bg-info text-dark border ms-1" style="font-size: 0.68rem;">Previous Handover Location</span></div>
                                        <div class="text-muted" style="font-size: 0.77rem;">
                                            Location used during initial checkout/handover.
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                }

                // Option 3: Campus Meet-up (Landmark)
                const isCampusChecked = !hasSelectedDefault ? 'checked' : '';
                if (isCampusChecked) hasSelectedDefault = true;

                html += `
                    <div class="mb-2">
                        <label class="loc-option-label d-block cursor-pointer" for="loc_campus">
                            <div class="d-flex align-items-start gap-2">
                                <input class="form-check-input mt-1" type="radio" name="dropoff_location" id="loc_campus" value="Campus Meet-up" ${isCampusChecked}>
                                <div style="font-size: 0.82rem; line-height: 1.4;">
                                    <div class="fw-bold text-dark">Campus Meet-up <span class="badge bg-light text-primary border ms-1" style="font-size: 0.68rem;">Campus Landmark</span></div>
                                    <div class="text-muted" style="font-size: 0.77rem;">
                                        Direct student-to-student handover on campus (Library lobby, student lounge, cafeteria, or main gate).
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                `;

                // Option 4: Seller's Registered Location (if available)
                if (sellerData && sellerData.success && sellerData.address && sellerData.address.address) {
                    const sellerFullAddress = `${sellerData.address.name}, ${sellerData.address.address}, ${sellerData.address.city} ${sellerData.address.postal_code}`;
                    html += `
                        <div class="mb-2">
                            <label class="loc-option-label d-block cursor-pointer" for="seller_location">
                                <div class="d-flex align-items-start gap-2">
                                    <input class="form-check-input mt-1" type="radio" name="dropoff_location" id="seller_location" value="${sellerFullAddress.replace(/"/g, '&quot;')}">
                                    <div style="font-size: 0.82rem; line-height: 1.4;">
                                        <div class="fw-bold text-dark">${sellerData.address.name} <span class="badge bg-light text-dark border ms-1" style="font-size: 0.68rem;">Seller Location</span></div>
                                        <div class="text-muted" style="font-size: 0.77rem;">
                                            ${sellerData.address.address}, ${sellerData.address.city} ${sellerData.address.postal_code}
                                            ${sellerData.address.contact_person ? ` • Contact: ${sellerData.address.contact_person}` : ''}
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                }

                // Option 5: BookWagon Official Hub
                html += `
                    <div class="mb-1">
                        <label class="loc-option-label d-block cursor-pointer" for="location1">
                            <div class="d-flex align-items-start gap-2">
                                <input class="form-check-input mt-1" type="radio" name="dropoff_location" id="location1" value="BookWagon Hub, 123 Book Street, Manila">
                                <div style="font-size: 0.82rem; line-height: 1.4;">
                                    <div class="fw-bold text-dark">BookWagon Main Hub <span class="badge bg-light text-dark border ms-1" style="font-size: 0.68rem;">Official Hub</span></div>
                                    <div class="text-muted" style="font-size: 0.77rem;">
                                        123 Book Street, Manila • Mon-Fri: 9AM-6PM, Sat: 10AM-2PM
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                `;
                container.innerHTML = html;
            }

            // Get all Return Book buttons
            const returnButtons = document.querySelectorAll('.btn-return-book');
            
            // Add event listeners to Return Book buttons
            returnButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Get data from button attributes
                    const rentalId = this.getAttribute('data-rental-id');
                    const bookId = this.getAttribute('data-book-id') || 0;
                    const bookTitle = this.getAttribute('data-book-title');
                    const bookAuthor = this.getAttribute('data-book-author');
                    const bookImage = this.getAttribute('data-book-image');
                    const dueDate = this.getAttribute('data-due-date');
                    const isOverdue = this.getAttribute('data-is-overdue') === 'true';
                    const sellerId = this.getAttribute('data-seller-id');
                    
                    // Set values in the modal
                    document.getElementById('return_rental_id').value = rentalId;
                    document.getElementById('return_book_title').innerText = bookTitle;
                    document.getElementById('return_book_author').innerText = bookAuthor ? `by ${bookAuthor}` : '';
                    document.getElementById('return_book_image').src = bookImage || 'img/default-book-cover.jpg';
                    document.getElementById('return_due_date').innerText = dueDate;
                    
                    // Reset condition checkbox
                    const condBox = document.getElementById('condition_checkbox');
                    if (condBox) condBox.checked = false;


                    
                    // Show overdue notice if applicable
                    const overdueNotice = document.getElementById('overdue_notice');
                    if (overdueNotice) {
                        if (isOverdue) {
                            overdueNotice.classList.remove('d-none');
                        } else {
                            overdueNotice.classList.add('d-none');
                        }
                    }
                    
                    // Fetch seller's address, book meetup location, and order handover location
                    const container = document.getElementById('dropoff_locations_container');
                    container.innerHTML = '<div class="text-center text-muted py-2" style="font-size: 0.8rem;">Loading location details...</div>';

                    fetch(`get_seller_address.php?seller_id=${sellerId}&book_id=${bookId}&rental_id=${rentalId}`)
                        .then(response => response.json())
                        .then(data => {
                            renderDefaultDropoffLocations(container, data);
                        })
                        .catch(error => {
                            console.error('Error fetching seller address:', error);
                            renderDefaultDropoffLocations(container, null);
                        });
                });
            });
            

            // Helper for safe element assignment
            function safeSet(id, prop, value) {
                const el = document.getElementById(id);
                if (el) {
                    el[prop] = value;
                }
            }

            // ========================================================
            // Book Condition Documentation & Order Received Logic
            // ========================================================
            const receiveButtons = document.querySelectorAll('.btn-receive-with-inspection');

            receiveButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const orderId = this.getAttribute('data-order-id') || '';
                    const itemId = this.getAttribute('data-item-id') || '';
                    const title = this.getAttribute('data-title') || '';
                    const author = this.getAttribute('data-author') || '';
                    const image = this.getAttribute('data-image') || '';
                    const type = this.getAttribute('data-type') || '';
                    const weeks = this.getAttribute('data-weeks') || '1';
                    const seller = this.getAttribute('data-seller') || '';

                    safeSet('modal_order_id', 'value', orderId);
                    safeSet('modal_item_id', 'value', itemId);
                    safeSet('modal_book_title', 'innerText', title);
                    safeSet('modal_book_author', 'innerText', author);
                    safeSet('modal_book_seller', 'innerText', seller);
                    safeSet('modal_book_image', 'src', image);

                    const typeBadge = document.getElementById('modal_type_badge');
                    if (typeBadge) {
                        if (type === 'rent') {
                            typeBadge.innerText = `Rental (${weeks} ${weeks > 1 ? 'weeks' : 'week'})`;
                            typeBadge.className = 'badge bg-warning text-dark border';
                        } else {
                            typeBadge.innerText = 'Purchase';
                            typeBadge.className = 'badge bg-light text-secondary border';
                        }
                    }

                    // Reset checklist checkboxes
                    document.querySelectorAll('.check-inspect').forEach(cb => cb.checked = false);

                    const modalEl = document.getElementById('conditionInspectionModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalInstance.show();
                    }
                });
            });

            // ========================================================
            // Rental Return QR Code Modal Logic
            // ========================================================
            const viewQrButtons = document.querySelectorAll('.btn-view-agreement-qr');

            viewQrButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const orderId = this.getAttribute('data-order-id') || '';
                    const rentalId = this.getAttribute('data-rental-id') || '';
                    const title = this.getAttribute('data-title') || '';
                    const author = this.getAttribute('data-author') || '';
                    const condition = this.getAttribute('data-condition') || 'Good';
                    const startDate = this.getAttribute('data-start') || 'N/A';
                    const dueDate = this.getAttribute('data-due') || 'N/A';
                    const seller = this.getAttribute('data-seller') || 'Owner';
                    const token = this.getAttribute('data-token') || '';

                    const payload = {
                        cert: "BOOKWAGON_RENTAL_RETURN_RECEIPT",
                        rental_id: rentalId,
                        order_id: orderId,
                        token: token,
                        book: title,
                        seller: seller,
                        condition: condition,
                        return_due: dueDate
                    };

                    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(JSON.stringify(payload))}`;

                    safeSet('agreement_qr_img', 'src', qrUrl);
                    safeSet('agreement_book_title', 'innerText', title);
                    safeSet('agreement_meta_info', 'innerText', `by ${author} • Rental #${rentalId}`);
                    safeSet('agreement_condition', 'innerText', condition);
                    safeSet('agreement_timestamp', 'innerText', startDate);
                    safeSet('agreement_due_date', 'innerText', dueDate);
                    safeSet('agreement_deposit', 'innerText', 'Refundable upon return inspection');
                    safeSet('agreement_hash', 'innerText', `BW-RETURN-${rentalId}-${orderId}`);

                    const modalEl = document.getElementById('handoverAgreementModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalInstance.show();
                    }
                });
            });
        });
    </script>

    <!-- Book Condition Inspection (Order Received) Modal - Clean & Simple -->
    <div class="modal fade" id="conditionInspectionModal" tabindex="-1" aria-labelledby="conditionInspectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid #e9ecef;">
                <div class="modal-header py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="conditionInspectionModalLabel" style="font-size: 1.05rem; color: #0f172a;">
                            Book Condition Checklist
                        </h5>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">Please inspect the book before confirming delivery receipt.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="rented_books.php" method="POST" id="formOrderReceive">
                    <input type="hidden" name="action" value="confirm_order_received">
                    <input type="hidden" name="order_id" id="modal_order_id" value="">
                    <input type="hidden" name="item_id" id="modal_item_id" value="">
                    
                    <div class="modal-body px-4 py-3">
                        <!-- Book Preview -->
                        <div class="d-flex align-items-center gap-3 p-2 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <img src="" id="modal_book_image" alt="Cover" style="width: 44px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                            <div style="flex: 1; min-width: 0;">
                                <div class="fw-bold text-truncate mb-1" id="modal_book_title" style="font-size: 0.9rem; color: #0f172a;"></div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    by <span id="modal_book_author"></span> • Seller: <span id="modal_book_seller"></span> • <span id="modal_type_badge" class="badge bg-light text-dark border">Rental</span>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-2" style="font-size: 0.82rem; color: #0f172a; text-transform: uppercase;">
                                Inspection Checklist
                            </label>
                            <div class="p-3" style="background: #ffffff; border: 1px solid #e9ecef; border-radius: 6px;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input check-inspect" type="checkbox" name="chk_cover" id="chk_cover" required>
                                    <label class="form-check-label" for="chk_cover" style="font-size: 0.84rem;">
                                        Cover is in good condition (no major stains or tears)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input check-inspect" type="checkbox" name="chk_pages" id="chk_pages" required>
                                    <label class="form-check-label" for="chk_pages" style="font-size: 0.84rem;">
                                        Pages are complete and readable
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input check-inspect" type="checkbox" name="chk_spine" id="chk_spine" required>
                                    <label class="form-check-label" for="chk_spine" style="font-size: 0.84rem;">
                                        Spine and binding are secure
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Rating & Notes -->
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.8rem; color: #475569;">
                                    Condition
                                </label>
                                <select name="book_condition" class="form-select form-select-sm" required style="font-size: 0.82rem;">
                                    <option value="good" selected>Good</option>
                                    <option value="excellent">Excellent</option>
                                    <option value="fair">Fair</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.8rem; color: #475569;">
                                    Notes (Optional)
                                </label>
                                <input type="text" name="condition_notes" class="form-control form-control-sm" placeholder="Any remarks" style="font-size: 0.82rem;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm text-white fw-bold px-3" id="btnSubmitInspection">
                            Confirm Received
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rental Return QR Modal -->
    <div class="modal fade" id="handoverAgreementModal" tabindex="-1" aria-labelledby="handoverAgreementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center" style="border-radius: 10px; border: 1px solid #e9ecef;">
                <div class="modal-header justify-content-between py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <div class="text-start">
                        <h5 class="modal-title fw-bold mb-0" id="handoverAgreementModalLabel" style="font-size: 1.05rem; color: #0f172a;">
                            Rental Return QR
                        </h5>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">Present this QR to verify return and refund deposit.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-3">
                    <div class="p-2 mb-3 d-inline-block" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <img src="" id="agreement_qr_img" alt="Return QR Code" style="width: 170px; height: 170px; display: block; margin: 0 auto;">
                    </div>
                    
                    <h6 class="fw-bold mb-1" id="agreement_book_title" style="color: #0f172a; font-size: 0.95rem;"></h6>
                    <p class="text-muted mb-3" style="font-size: 0.8rem;" id="agreement_meta_info"></p>
                    
                    <div class="p-3 text-start mb-2" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 6px; font-size: 0.82rem;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Condition:</span>
                            <strong class="text-dark" id="agreement_condition">Good</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Return Due Date:</span>
                            <strong class="text-dark" id="agreement_due_date"></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Deposit Status:</span>
                            <span class="text-success fw-semibold" id="agreement_deposit">Refundable upon return</span>
                        </div>
                        <div style="display: none;">
                            <span id="agreement_timestamp"></span>
                            <span id="agreement_hash"></span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer justify-content-center px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-warning btn-sm text-white fw-bold px-4" data-bs-dismiss="modal">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Meet-up Handshake Modal (Single QR) -->
    <div class="modal fade" id="handshakeModal" tabindex="-1" aria-labelledby="handshakeModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="handshakeModalLabel">Receive Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopScanner()"></button>
                </div>
                <div class="modal-body text-center py-3">
                    
                    <!-- Scanner Section -->
                    <div id="scanner-section">
                        <p class="text-muted small mb-3">Scan the Seller's QR code to confirm handover.</p>
                        <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                        
                        <!-- Manual Override -->
                        <div class="mt-4 pt-3 border-top">
                            <p class="text-muted small mb-2"><i class="fas fa-info-circle"></i> Trouble scanning?</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 fw-bold" onclick="showConditionForm()">Manually Book Received</button>
                        </div>
                    </div>

                    <!-- Condition Check Form (Hidden until Scanned or Manual Override) -->
                    <div id="qr-result" class="mt-3" style="display: none;">
                        <div class="alert alert-success fw-semibold mb-2" id="scan-success-alert" style="display:none;"><i class="fas fa-check-circle me-1"></i> Seller QR Scanned!</div>
                        <div class="text-start p-3 bg-light rounded border mb-3">
                            <h6 class="fw-bold mb-2">Check Book Condition</h6>
                            <p class="small text-muted mb-3">Please physically inspect the book before confirming.</p>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="renter_condition_check" required>
                                <label class="form-check-label fw-semibold" for="renter_condition_check">
                                    I confirm the book is in good condition. <span class="text-danger">*</span>
                                </label>
                            </div>

                            <textarea id="renter_condition_comments" class="form-control form-control-sm mb-3" rows="2" placeholder="Optional comments..."></textarea>
                            
                            <input type="hidden" id="hs_order_id">
                            <input type="hidden" id="hs_item_id">
                            <button type="button" class="btn w-100 fw-bold" style="background-color: var(--success-color); color: white; padding: 12px;" onclick="submitConditionCheck()">Submit Confirmation</button>
                        </div>
                    </div>

                    <!-- Final Success Message -->
                    <div id="renter-qr-container" class="mt-3 text-center" style="display: none;">
                        <div class="alert alert-success mb-2">
                            <i class="fas fa-check-circle fs-2 mb-2 d-block"></i>
                            <strong>Confirmed!</strong>
                        </div>
                        <p class="small text-muted">You have successfully confirmed receipt of the book.<br>The Seller will now finalize the transaction on their end.</p>
                        <button type="button" class="btn btn-dark w-100 mt-2 fw-bold" onclick="window.location.reload()">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrcodeScanner = null;
        let activeOrderId = null;
        let activeItemId = null;
        
        function showConditionForm(isScan = false) {
            stopScanner();
            document.getElementById('scanner-section').style.display = 'none';
            document.getElementById('qr-result').style.display = 'block';
            if (isScan) {
                document.getElementById('scan-success-alert').style.display = 'block';
            }
        }

        function initHandshake(btn) {
            activeOrderId = btn.getAttribute('data-order-id');
            activeItemId = btn.getAttribute('data-item-id');
            
            // Set hidden inputs for receive form
            document.getElementById('hs_order_id').value = activeOrderId;
            document.getElementById('hs_item_id').value = activeItemId;
            
            // Reset UI
            document.getElementById('scanner-section').style.display = 'block';
            document.getElementById('qr-result').style.display = 'none';
            document.getElementById('scan-success-alert').style.display = 'none';
            document.getElementById('renter-qr-container').style.display = 'none';
            document.getElementById('renter_condition_check').checked = false;
            document.getElementById('renter_condition_comments').value = '';
            
            startScanner();
        }

        function startScanner() {
            if (html5QrcodeScanner) return; // Already running
            html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
            html5QrcodeScanner.render(onScanSuccess, onScanError);
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => {
                    console.error("Failed to clear scanner.", error);
                });
                html5QrcodeScanner = null;
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            try {
                const data = JSON.parse(decodedText);
                if (data.action === 'seller_handover' && data.order_id == activeOrderId && data.item_id == activeItemId) {
                    showConditionForm(true);
                } else {
                    alert("Invalid QR Code for this specific book.");
                }
            } catch (e) {
                alert("Unrecognized QR format.");
            }
        }
        
        function onScanError(errorMessage) {}

        function submitConditionCheck() {
            if (!document.getElementById('renter_condition_check').checked) {
                alert("You must confirm the book is in good condition.");
                return;
            }

            const comments = document.getElementById('renter_condition_comments').value.trim();
            
            const btn = document.querySelector('button[onclick="submitConditionCheck()"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            const formData = new FormData();
            formData.append('order_id', document.getElementById('hs_order_id').value);
            formData.append('item_id', document.getElementById('hs_item_id').value);
            formData.append('comments', comments);
            
            fetch('process_handover_condition.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('qr-result').style.display = 'none';
                    document.getElementById('renter-qr-container').style.display = 'block';
                } else {
                    alert(data.message || 'An error occurred.');
                    btn.disabled = false;
                    btn.innerHTML = 'Submit Confirmation';
                }
            })
            .catch(err => {
                alert('Connection error.');
                btn.disabled = false;
                btn.innerHTML = 'Submit Confirmation';
            });
        }

        document.getElementById('handshakeModal').addEventListener('hidden.bs.modal', function () {
            stopScanner();
        });
    </script>
</body>
</html>