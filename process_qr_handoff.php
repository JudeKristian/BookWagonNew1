<?php
include("session.php");
include("connect.php");
require_once "includes/audit_logger.php";

$userId = $_SESSION['id'] ?? 0;
$userType = $_SESSION['usertype'] ?? '';

if ($userType !== 'seller' || !$userId) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $itemId = (int)($_POST['item_id'] ?? 0);
    $buyerId = (int)($_POST['buyer_id'] ?? 0);
    $token = $_POST['token'] ?? ''; // Might be empty if manual override

    if ($orderId && $itemId && $buyerId) {
        
        // Handle Photo Upload (Optional)
        $photoPath = null;
        if (isset($_FILES['condition_photo']) && $_FILES['condition_photo']['error'] === UPLOAD_ERR_OK) {
            include("upload_helper.php");
            $photoPath = uploadImage($_FILES['condition_photo'], 'uploads/returns/');
            if (!$photoPath) {
                $_SESSION['error_message'] = "Failed to upload handover condition photo.";
                header("Location: order.php");
                exit();
            }
        }
        
        $finalizeAction = $_POST['finalize_action'] ?? 'delivered';

        // Ensure seller owns the item
        $checkStmt = $conn->prepare("SELECT book_id, quantity, unit_price, purchase_type, rental_weeks, renter_handover_token FROM order_items WHERE item_id = ? AND seller_id = ?");
        $checkStmt->bind_param("ii", $itemId, $userId);
        $checkStmt->execute();
        $itemRow = $checkStmt->get_result()->fetch_assoc();

        if ($itemRow) {
            // If not manual override, verify that the buyer actually confirmed it on their phone
            if ($finalizeAction !== 'manual' && $itemRow['renter_handover_token'] !== 'confirmed') {
                $_SESSION['error_message'] = "Cannot finalize: The Buyer has not scanned the QR and confirmed the book condition yet. (If their phone is broken, use the Manual Override button below).";
                header("Location: order.php");
                exit();
            }

            $bookId = $itemRow['book_id'];
            $pType = $itemRow['purchase_type'];
            $rentalWeeks = $itemRow['rental_weeks'];
            
            // Mark item as delivered and save photo (if provided)
            if ($photoPath) {
                $updItem = $conn->prepare("UPDATE order_items SET status = 'delivered', initial_condition_photo = ? WHERE item_id = ?");
                $updItem->bind_param("si", $photoPath, $itemId);
            } else {
                $updItem = $conn->prepare("UPDATE order_items SET status = 'delivered' WHERE item_id = ?");
                $updItem->bind_param("i", $itemId);
            }
            $updItem->execute();
            
            // Log the seller scan
            $logStmt = $conn->prepare("INSERT INTO payment_logs (order_id, user_id, action, status, details) VALUES (?, ?, 'qr_seller_scanned', 'success', ?)");
            $details = "Seller finalized handover for item #$itemId." . ($photoPath ? " Condition photo uploaded." : " Manual override used.");
            $logStmt->bind_param("iis", $orderId, $userId, $details);
            $logStmt->execute();
            
            log_activity($userId, 'QR Confirmed', 'Seller finalized handover for Item #' . $itemId . ' (Order #' . $orderId . ')');
            log_activity($buyerId, 'Order Received', 'Buyer successfully received Item #' . $itemId . ' from Seller');

            // If it's a rental, create the active rental record now
            if ($pType === 'rent') {
                $rentalCost = $itemRow['unit_price'] * $itemRow['quantity'];
                
                // Get sellers table ID
                $sellersTableId = 0;
                $sellerLookStmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ?");
                $sellerLookStmt->bind_param("i", $userId);
                $sellerLookStmt->execute();
                if ($sRow = $sellerLookStmt->get_result()->fetch_assoc()) {
                    $sellersTableId = (int)$sRow['id'];
                }

                $rentalSql = "INSERT INTO book_rentals (user_id, book_id, seller_id, order_id, rental_date, due_date, rental_weeks, status, total_price) 
                              VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? WEEK), ?, 'active', ?)";
                $rentalStmt = $conn->prepare($rentalSql);
                $rentalStmt->bind_param("iiiiisd", $buyerId, $bookId, $sellersTableId, $orderId, $rentalWeeks, $rentalWeeks, $rentalCost);
                $rentalStmt->execute();
            }

            // Check if all items in order are delivered
            $allItemsStmt = $conn->prepare("SELECT COUNT(*) as uncompleted FROM order_items WHERE order_id = ? AND status != 'delivered'");
            $allItemsStmt->bind_param("i", $orderId);
            $allItemsStmt->execute();
            if ($allItemsStmt->get_result()->fetch_assoc()['uncompleted'] == 0) {
                $updOrder = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ?");
                $updOrder->bind_param("i", $orderId);
                $updOrder->execute();
            }

            $_SESSION['success_message'] = "Handover confirmed! The transaction is now finalized.";
        } else {
            $_SESSION['error_message'] = "Item not found or unauthorized.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid data provided.";
    }
}

header("Location: order.php");
exit();
