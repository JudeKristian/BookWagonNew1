<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "db_connect.php";
require_once "../includes/notification_helper.php";

$adminId = $_SESSION['admin_id'] ?? 1;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$message = "";
$messageType = "success";

// Helper for audit logging
function log_admin_event($conn, $adminId, $activity, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, activity, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $adminId, $activity, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = intval($_POST['order_id'] ?? 0);

    if ($action === 'approve_payment') {
        $conn->begin_transaction();
        try {
            // Update order status
            $updateStmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'processing' WHERE order_id = ?");
            $updateStmt->bind_param("i", $orderId);
            $updateStmt->execute();

            // Fetch sellers involved in this order
            $sellerStmt = $conn->prepare("SELECT DISTINCT seller_id FROM order_items WHERE order_id = ?");
            $sellerStmt->bind_param("i", $orderId);
            $sellerStmt->execute();
            $sellerResult = $sellerStmt->get_result();

            while ($row = $sellerResult->fetch_assoc()) {
                $sellerId = $row['seller_id'];
                // Notify seller
                $notifContent = "You have received a new order (Order #$orderId)! Payment has been verified. Please check your pending orders for details.";
                sendNotification($conn, $sellerId, $adminId, 'new_order', $notifContent);
            }

            log_admin_event($conn, $adminId, "Verified Payment", "Approved payment receipt for Order #$orderId");
            $conn->commit();
            $message = "Payment for Order #$orderId successfully verified!";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error verifying payment: " . $e->getMessage();
            $messageType = "danger";
        }
    } elseif ($action === 'reject_payment') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $conn->begin_transaction();
        try {
            // Cancel order
            $updateStmt = $conn->prepare("UPDATE orders SET payment_status = 'failed', order_status = 'cancelled' WHERE order_id = ?");
            $updateStmt->bind_param("i", $orderId);
            $updateStmt->execute();

            // Fetch buyer ID
            $buyerStmt = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
            $buyerStmt->bind_param("i", $orderId);
            $buyerStmt->execute();
            $buyerId = $buyerStmt->get_result()->fetch_assoc()['user_id'] ?? 0;

            if ($buyerId) {
                // Notify buyer
                $notifContent = "Your payment for Order #$orderId was rejected. Reason: $reason. Your order has been cancelled.";
                sendNotification($conn, $buyerId, $adminId, 'payment_rejected', $notifContent);
            }
            
            // Revert Stock
            $itemsStmt = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->bind_param("i", $orderId);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();
            
            $stockStmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE book_id = ?");
            while ($item = $itemsResult->fetch_assoc()) {
                $stockStmt->bind_param("ii", $item['quantity'], $item['book_id']);
                $stockStmt->execute();
            }

            log_admin_event($conn, $adminId, "Rejected Payment", "Rejected payment for Order #$orderId. Reason: $reason");
            $conn->commit();
            $message = "Payment for Order #$orderId rejected and order cancelled.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error rejecting payment: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch Pending Payments
$pendingPayments = [];
$query = "SELECT o.*, CONCAT(u.firstName, ' ', u.lastName) as buyer_name, u.email as buyer_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.payment_status = 'pending_verification' 
          ORDER BY o.order_date ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orderId = $row['order_id'];
        $itemsQuery = "SELECT oi.*, b.title, b.cover_image, CONCAT(u.firstName, ' ', u.lastName) as seller_name 
                       FROM order_items oi 
                       JOIN books b ON oi.book_id = b.book_id 
                       JOIN users u ON oi.seller_id = u.id 
                       WHERE oi.order_id = $orderId";
        $itemsResult = $conn->query($itemsQuery);
        $items = [];
        if ($itemsResult) {
            while ($item = $itemsResult->fetch_assoc()) {
                $items[] = $item;
            }
        }
        $row['items'] = $items;
        $pendingPayments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - Bookwagon Admin</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bw-primary: #f8a100;
            --bw-dark: #1e293b;
            --bw-bg: #f8fafc;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bw-bg); }
        .dashboard-container { padding: 20px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .receipt-thumbnail { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid #dee2e6; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include "admin_sidebar.php"; ?>

    <div class="flex-grow-1 dashboard-container" style="margin-left: 260px; min-height: 100vh;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Payment Verification</h4>
            <div>
                <span class="badge bg-white text-dark border p-2 shadow-sm rounded-pill">
                    <i class="fa-solid fa-user-tie me-1"></i> <?php echo htmlspecialchars($adminUsername); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4">
            <h5 class="fw-bold mb-3">Pending Verification (<?php echo count($pendingPayments); ?>)</h5>
            
            <?php if (empty($pendingPayments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-check-circle mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                    <h5>All Caught Up!</h5>
                    <p>There are no pending payments to verify.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Buyer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Receipt</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                                <tr>
                                    <td><strong>#<?php echo $payment['order_id']; ?></strong></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($payment['buyer_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($payment['buyer_email']); ?></div>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($payment['order_date'])); ?></td>
                                    <td class="fw-bold text-success">₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo strtoupper($payment['payment_method']); ?></span></td>
                                    <td>
                                        <?php if (!empty($payment['payment_receipt']) && file_exists('../' . $payment['payment_receipt'])): ?>
                                            <img src="../<?php echo htmlspecialchars($payment['payment_receipt']); ?>" class="receipt-thumbnail" 
                                                 onclick="showReceiptModal('../<?php echo htmlspecialchars($payment['payment_receipt']); ?>')" alt="Receipt">
                                        <?php else: ?>
                                            <span class="text-muted small">No File</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-info text-white fw-bold mb-1 w-100" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $payment['order_id']; ?>">
                                            <i class="fa-solid fa-list me-1"></i> Details
                                        </button>
                                        <div class="d-flex gap-1 mt-1 justify-content-end">
                                            <form method="POST" class="d-inline flex-grow-1">
                                                <input type="hidden" name="order_id" value="<?php echo $payment['order_id']; ?>">
                                                <input type="hidden" name="action" value="approve_payment">
                                                <button type="submit" class="btn btn-sm btn-success fw-bold w-100" onclick="return confirm('Approve this payment and notify seller?');">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold flex-grow-1" onclick="openRejectModal(<?php echo $payment['order_id']; ?>)">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Receipt Viewer Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">View Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-dark p-0">
                <img id="receiptModalImg" src="" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Reject Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="reject_order_id">
                    <input type="hidden" name="action" value="reject_payment">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="rejection_reason" rows="3" required placeholder="e.g. Receipt is blurry, Amount does not match, etc."></textarea>
                    </div>
                    <div class="text-muted small">This will cancel the order and notify the buyer to try again.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Order Details Modals -->
<?php foreach ($pendingPayments as $payment): ?>
<div class="modal fade" id="detailsModal<?php echo $payment['order_id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Order Details #<?php echo $payment['order_id']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Buyer Information</p>
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($payment['buyer_name']); ?></p>
                        <p class="small"><?php echo htmlspecialchars($payment['buyer_email']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1 text-muted small">Total Paid</p>
                        <h4 class="text-success fw-bold">₱<?php echo number_format($payment['total_amount'], 2); ?></h4>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3 border-bottom pb-2">Items Included</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Book</th>
                                <th>Seller</th>
                                <th>Type</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment['items'] as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../<?php echo !empty($item['cover_image']) && strpos($item['cover_image'], 'uploads/') !== 0 ? 'uploads/covers/'.$item['cover_image'] : ($item['cover_image'] ?: 'uploads/covers/default_book.jpg'); ?>" style="width: 40px; height: 50px; object-fit: cover; border-radius: 4px;" class="me-2" onerror="this.src='../uploads/covers/default_book.jpg'">
                                        <span class="fw-semibold"><?php echo htmlspecialchars($item['title']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['seller_name']); ?></td>
                                <td>
                                    <?php if ($item['purchase_type'] === 'rent'): ?>
                                        <span class="badge bg-info">Rental (<?php echo $item['rental_weeks']; ?> wks)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Purchase</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">₱<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showReceiptModal(src) {
        document.getElementById('receiptModalImg').src = src;
        new bootstrap.Modal(document.getElementById('receiptModal')).show();
    }
    function openRejectModal(orderId) {
        document.getElementById('reject_order_id').value = orderId;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }
</script>
</body>
</html>
