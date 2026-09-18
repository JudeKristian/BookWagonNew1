<?php
include("session.php");
include("connect.php");
require_once "includes/audit_logger.php";

$userId = $_SESSION['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $itemId = (int)($_POST['item_id'] ?? 0);

    if ($orderId && $itemId) {
        // Log that the buyer confirmed receipt via QR
        $logStmt = $conn->prepare("INSERT INTO payment_logs (order_id, user_id, action, status, details) VALUES (?, ?, 'qr_buyer_scanned', 'success', ?)");
        $details = "Buyer scanned Seller QR and confirmed receipt for item #$itemId.";
        $logStmt->bind_param("iis", $orderId, $userId, $details);
        $logStmt->execute();
        
        log_activity($userId, 'QR Confirmed', 'Buyer confirmed receipt for Item #' . $itemId . ' (Order #' . $orderId . ')');

        $_SESSION['success_message'] = "You have successfully confirmed receipt of the book! Once the seller scans your QR code, the transaction will be finalized.";
    } else {
        $_SESSION['error_message'] = "Invalid data provided.";
    }
}

header("Location: rented_books.php");
exit();
