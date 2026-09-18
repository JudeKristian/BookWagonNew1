<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "connect.php";
require_once "includes/notification_helper.php";

$userId = $_SESSION['id'];
$renterToken = isset($_GET['token']) ? $_GET['token'] : '';
$message = "";
$messageType = "danger";

if (empty($renterToken)) {
    die("Invalid Handover Token.");
}

// Fetch item based on renter token and ensure logged-in user is the SELLER
$itemQuery = "SELECT oi.*, b.title, o.first_name as buyer_name, o.user_id as buyer_id 
              FROM order_items oi 
              JOIN books b ON oi.book_id = b.book_id 
              JOIN orders o ON oi.order_id = o.order_id 
              WHERE oi.renter_handover_token = ? AND oi.seller_id = ?";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("si", $renterToken, $userId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Invalid request. Only the seller of this item can finalize the transaction.");
}

if ($item['status'] !== 'pending_meetup') {
    $message = "This item has already been handed over.";
}

// Handle Final Confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_handover']) && $item['status'] === 'pending_meetup') {
    $conn->begin_transaction();
    try {
        $newItemStatus = ($item['purchase_type'] === 'rent') ? 'active' : 'delivered';
        
        $updStmt = $conn->prepare("UPDATE order_items SET status = ? WHERE item_id = ?");
        $updStmt->bind_param("si", $newItemStatus, $item['item_id']);
        $updStmt->execute();
        
        if ($item['purchase_type'] === 'rent') {
            // Create active rental record
            $rentalWeeks = (int)$item['rental_weeks'];
            $totalPrice = (float)$item['unit_price'] * $rentalWeeks; // Rent price only, deposit is in order total
            
            $rentalSql = "INSERT INTO book_rentals (user_id, book_id, seller_id, order_id, rental_date, due_date, rental_weeks, status, total_price) 
                          VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? WEEK), ?, 'active', ?)";
            $rentStmt = $conn->prepare($rentalSql);
            $rentStmt->bind_param("iiiiiid", $item['buyer_id'], $item['book_id'], $item['seller_id'], $item['order_id'], $rentalWeeks, $rentalWeeks, $totalPrice);
            $rentStmt->execute();
        }

        // Notify Buyer
        $buyerMsg = "Your transaction for '" . $item['title'] . "' is complete! " . ($item['purchase_type'] === 'rent' ? "Your rental period has started." : "Enjoy your book!");
        sendNotification($conn, $item['buyer_id'], $userId, 'order_update', $buyerMsg);
        
        $conn->commit();
        
        $message = "Transaction Complete! The escrow is locked and your payout is pending release.";
        $messageType = "success";
        $item['status'] = $newItemStatus; // update for UI
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error finalizing handover: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalize Handover - Bookwagon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<?php include "include/user_header.php"; ?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4 text-center">
                
                <?php if ($item['status'] === 'pending_meetup'): ?>
                    <h3 class="fw-bold mb-3">Double Handshake Complete!</h3>
                    
                    <div class="alert alert-info text-start">
                        <i class="fa-solid fa-circle-check me-2"></i> <strong><?php echo htmlspecialchars($item['buyer_name']); ?></strong> has confirmed the book condition and clicked "Receive".
                    </div>
                    
                    <p class="mb-4">Click the button below to finalize this transaction. This will activate the rental clock and lock the escrow payout into your account.</p>
                    
                    <form method="POST">
                        <input type="hidden" name="finalize_handover" value="1">
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-3 fs-5 shadow-sm">
                            <i class="fa-solid fa-handshake me-2"></i> Order Received / Handover Complete
                        </button>
                    </form>
                <?php else: ?>
                    <i class="fa-solid fa-circle-check text-success mb-3" style="font-size: 4rem;"></i>
                    <h3 class="fw-bold text-success mb-3">Transaction Complete!</h3>
                    <p class="text-muted">The handover for <strong><?php echo htmlspecialchars($item['title']); ?></strong> was successful.</p>
                    
                    <a href="seller_dashboard.php" class="btn btn-light w-100 border mt-3">Back to Seller Dashboard</a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
