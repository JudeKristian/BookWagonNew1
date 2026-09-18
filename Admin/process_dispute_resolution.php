<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "db_connect.php";

$adminId = $_SESSION['admin_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['item_id'])) {
    $action = $_POST['action'];
    $itemId = intval($_POST['item_id']);

    if ($itemId > 0 && ($action === 'charge' || $action === 'dismiss')) {
        
        $conn->begin_transaction();
        
        try {
            // Update the dispute status
            $stmt = $conn->prepare("UPDATE order_items SET dispute_status = 'resolved', status = 'returned' WHERE item_id = ?");
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            
            // Log the audit event
            $details = "";
            $activity = "";
            if ($action === 'charge') {
                $activity = "DISPUTE_CHARGE_RENTER";
                $details = "Admin resolved dispute for Item #$itemId by CHARGING the renter deposit and awarding it to the seller.";
            } else {
                $activity = "DISPUTE_DISMISS_FORGIVE";
                $details = "Admin resolved dispute for Item #$itemId by DISMISSING the damage claim (normal wear/tear) and refunding the renter.";
            }
            
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, activity, details) VALUES (?, ?, ?)");
            $logStmt->bind_param("iss", $adminId, $activity, $details);
            $logStmt->execute();
            
            $conn->commit();
            
            if ($action === 'charge') {
                $_SESSION['success_msg'] = "Dispute Resolved: The renter's deposit has been charged to compensate the seller.";
            } else {
                $_SESSION['success_msg'] = "Dispute Resolved: Damage claim dismissed. The renter will receive their full deposit refund.";
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_msg'] = "Failed to process the dispute resolution.";
        }
    } else {
        $_SESSION['error_msg'] = "Invalid dispute request.";
    }
}

header("Location: admin_disputes.php?tab=disputes");
exit();
?>
