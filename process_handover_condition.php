<?php
include("session.php");
include("connect.php");
require_once "includes/audit_logger.php";

header('Content-Type: application/json');

$userId = $_SESSION['id'] ?? 0;

if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized or invalid method.']);
    exit();
}

$orderId = (int)($_POST['order_id'] ?? 0);
$itemId = (int)($_POST['item_id'] ?? 0);
$comments = trim($_POST['comments'] ?? '');

if (!$orderId || !$itemId || empty($comments)) {
    echo json_encode(['success' => false, 'error' => 'Missing required data (order_id, item_id, or comments).']);
    exit();
}

// Ensure the item belongs to the buyer
$checkStmt = $conn->prepare("
    SELECT oi.item_id 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE oi.item_id = ? AND o.user_id = ?
");
$checkStmt->bind_param("ii", $itemId, $userId);
$checkStmt->execute();
$res = $checkStmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized to update this item.']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Save condition comments and mark the renter's side of handover as confirmed
    $upd = $conn->prepare("
        UPDATE order_items 
        SET renter_condition_comments = ?, renter_handover_token = 'confirmed'
        WHERE item_id = ?
    ");
    $upd->bind_param("si", $comments, $itemId);
    $upd->execute();
    
    // Log the action
    log_activity($userId, 'Condition Logged', 'Buyer logged condition check and confirmed receipt for Item #' . $itemId . '.');
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Condition checked and handover confirmed.'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Database error occurred.']);
}
?>
