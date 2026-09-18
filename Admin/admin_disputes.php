<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "db_connect.php";

$adminId = $_SESSION['admin_id'] ?? 1;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'process_refund') {
        $itemId = intval($_POST['item_id'] ?? 0);
        $orderId = intval($_POST['order_id'] ?? 0);
        $refundNotes = trim($_POST['refund_notes'] ?? '');
        
        if ($itemId > 0) {
            $stmt = $conn->prepare("UPDATE order_items SET refund_status = 'refunded', refund_notes = ? WHERE item_id = ?");
            $stmt->bind_param("si", $refundNotes, $itemId);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Refund successfully processed for Item #$itemId.";
                
                // Log audit event
                $details = "Admin processed manual refund for cancelled Item #$itemId (Order #$orderId). Notes: $refundNotes";
                $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, activity, details) VALUES (?, 'MANUAL_REFUND_PROCESSED', ?)");
                if ($logStmt) {
                    $logStmt->bind_param("is", $adminId, $details);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } else {
                $_SESSION['error_msg'] = "Failed to process refund.";
            }
            $stmt->close();
        }
        header("Location: admin_disputes.php?tab=refunds");
        exit();
    }
}

// Fetch Cancelled Orders Pending Refund
$pendingRefunds = [];
$refundSql = "
    SELECT 
        oi.item_id, oi.order_id, oi.quantity, oi.unit_price, oi.purchase_type, oi.refund_status,
        b.title as book_title,
        u.firstname, u.lastname, u.email, u.payout_provider, u.payout_number, u.payout_name, u.payout_qr_code,
        o.order_date
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN books b ON oi.book_id = b.book_id
    JOIN users u ON o.user_id = u.id
    WHERE oi.status = 'cancelled' AND (oi.refund_status = 'none' OR oi.refund_status = 'pending')
    ORDER BY o.order_date DESC
";
$res = $conn->query($refundSql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pendingRefunds[] = $row;
    }
}

// Fetch Pending Damage Disputes
$pendingDisputes = [];
$disputeSql = "
    SELECT 
        oi.item_id, oi.order_id, oi.damage_notes, oi.damage_photo, oi.initial_condition_photo,
        b.title as book_title, b.rent_price,
        u.firstname as renter_first, u.lastname as renter_last, u.email as renter_email,
        s.shop_name, s.first_name as seller_first, s.last_name as seller_last, s.business_email as seller_email
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN books b ON oi.book_id = b.book_id
    JOIN users u ON o.user_id = u.id
    JOIN sellers s ON oi.seller_id = s.id
    WHERE oi.dispute_status = 'pending_admin'
    ORDER BY o.order_date DESC
";
$resDispute = $conn->query($disputeSql);
if ($resDispute) {
    while ($row = $resDispute->fetch_assoc()) {
        $pendingDisputes[] = $row;
    }
}

// Check for session messages
$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$activeTab = $_GET['tab'] ?? 'refunds';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disputes & Refunds | Bookwagon Admin</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #10B981;
            --primary-dark: #059669;
            --secondary: #3B82F6;
            --bg: #F3F4F6;
            --card-bg: #FFFFFF;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: 260px; /* Sidebar width */
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--card-bg);
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: var(--text-main);
        }

        .nav-tabs .nav-link {
            color: var(--text-muted);
            font-weight: 600;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 12px 20px;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            background: none;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-dark);
        }

        .card-custom {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .table-responsive {
            padding: 0;
        }

        .table-custom {
            margin: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom th {
            background: #F9FAFB;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            border-top: none;
        }

        .table-custom td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            font-size: 14px;
        }

        .table-custom tr:last-child td {
            border-bottom: none;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge-pending { background: #FEF3C7; color: #D97706; }
        .badge-refunded { background: #D1FAE5; color: #059669; }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }
        
        .btn-process {
            background: #EFF6FF;
            color: var(--secondary);
            border-color: #BFDBFE;
        }
        .btn-process:hover { background: var(--secondary); color: white; }

        .payment-info {
            background: #F9FAFB;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            font-size: 13px;
            margin-top: 5px;
        }

        .qr-preview {
            max-width: 120px;
            max-height: 120px;
            border-radius: 8px;
            border: 1px solid var(--border);
            object-fit: cover;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-handshake-angle me-2" style="color: var(--primary);"></i> Disputes & Refunds</h1>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($errorMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card-custom">
            <ul class="nav nav-tabs px-3 pt-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab === 'refunds' ? 'active' : ''; ?>" id="refunds-tab" data-bs-toggle="tab" data-bs-target="#refunds" type="button" role="tab">
                        <i class="fas fa-undo me-2"></i> Pending Refunds
                        <?php if (count($pendingRefunds) > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-2"><?php echo count($pendingRefunds); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab === 'disputes' ? 'active' : ''; ?>" id="disputes-tab" data-bs-toggle="tab" data-bs-target="#disputes" type="button" role="tab">
                        <i class="fas fa-balance-scale me-2"></i> Damage Disputes
                        <?php if (count($pendingDisputes) > 0): ?>
                            <span class="badge bg-warning rounded-pill ms-2 text-dark"><?php echo count($pendingDisputes); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- Refunds Tab -->
                <div class="tab-pane fade <?php echo $activeTab === 'refunds' ? 'show active' : ''; ?>" id="refunds" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Order Info</th>
                                    <th>Buyer Details</th>
                                    <th>Refund Amount</th>
                                    <th>Payout Method</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingRefunds)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-check-circle fs-2 mb-3 text-success"></i><br>
                                            No pending refunds found. All caught up!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingRefunds as $r): 
                                        $totalRefund = $r['quantity'] * $r['unit_price'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">Order #<?php echo $r['order_id']; ?> (Item #<?php echo $r['item_id']; ?>)</div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($r['book_title']); ?></div>
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($r['order_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($r['email']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success fs-5">₱<?php echo number_format($totalRefund, 2); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($r['payout_provider']): ?>
                                                <div class="fw-semibold" style="color: var(--secondary);"><?php echo htmlspecialchars($r['payout_provider']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($r['payout_name']); ?></div>
                                                <div class="text-dark fw-bold mb-2"><?php echo htmlspecialchars($r['payout_number']); ?></div>
                                                <?php if ($r['payout_qr_code']): ?>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="viewQr('../<?php echo htmlspecialchars($r['payout_qr_code']); ?>')">
                                                        <i class="fas fa-qrcode me-1"></i> View QR
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Provider Linked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-process" onclick="openRefundModal(<?php echo $r['item_id']; ?>, <?php echo $r['order_id']; ?>, <?php echo $totalRefund; ?>, '<?php echo htmlspecialchars(addslashes($r['firstname'] . ' ' . $r['lastname'])); ?>')">
                                                Process Refund
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Disputes Tab -->
                <div class="tab-pane fade <?php echo $activeTab === 'disputes' ? 'show active' : ''; ?>" id="disputes" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Rental Info</th>
                                    <th>Renter Details</th>
                                    <th>Seller Details</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingDisputes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-check-circle fs-2 mb-3 text-success"></i><br>
                                            No pending damage disputes.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingDisputes as $d): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">Order #<?php echo $d['order_id']; ?> (Item #<?php echo $d['item_id']; ?>)</div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($d['book_title']); ?></div>
                                            <div class="text-danger small mt-1">
                                                Deposit at risk: ₱<?php echo number_format($d['rent_price'], 2); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($d['renter_first'] . ' ' . $d['renter_last']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($d['renter_email']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($d['shop_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($d['seller_email']); ?></div>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-process" onclick="openDisputeModal(<?php echo htmlspecialchars(json_encode($d)); ?>)">
                                                Review Evidence
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Process Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Process Manual Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_disputes.php" method="POST">
                    <input type="hidden" name="action" value="process_refund">
                    <input type="hidden" name="item_id" id="modalItemId">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    <div class="modal-body pt-2">
                        <div class="alert alert-info" style="border-radius: 8px;">
                            You are processing a refund for <strong><span id="modalBuyerName"></span></strong>.
                            Amount: <strong class="fs-5 text-success">₱<span id="modalAmount"></span></strong>
                        </div>
                        <p class="small text-muted mb-3">
                            By confirming, you verify that you have manually transferred the funds to the buyer's requested payout account. This action cannot be undone.
                        </p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Transaction ID / Notes</label>
                            <input type="text" name="refund_notes" class="form-control" placeholder="e.g. GCash Ref No. 123456789" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn fw-bold text-white px-4" style="background-color: var(--primary);">Confirm & Mark Refunded</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- QR View Modal -->
    <div class="modal fade" id="qrViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center p-3">
                <h6 class="fw-bold mb-3">Payout QR Code</h6>
                <img id="qrViewImg" src="" alt="QR Code" class="img-fluid rounded border mb-3">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>

    <!-- Dispute Review Modal -->
    <div class="modal fade" id="disputeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Review Damage Evidence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-2">Initial Condition</h6>
                            <div class="bg-light rounded p-2 text-center" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                                <img id="modalInitialPhoto" src="" alt="Initial Condition" style="max-height: 100%; max-width: 100%; object-fit: contain; cursor: pointer;" onclick="viewQr(this.src)" onerror="this.src='../img/default-book.png'">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-danger mb-2">Reported Damage</h6>
                            <div class="bg-light rounded p-2 text-center border border-danger" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                                <img id="modalDamagePhoto" src="" alt="Damage Photo" style="max-height: 100%; max-width: 100%; object-fit: contain; cursor: pointer;" onclick="viewQr(this.src)" onerror="this.src='../img/default-book.png'">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="fw-semibold text-dark">Seller's Notes</h6>
                        <div class="p-3 bg-light rounded" id="modalDamageNotes" style="font-size: 14px;"></div>
                    </div>
                    
                    <div class="mt-4 alert alert-warning d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fs-3 me-3"></i>
                        <div>
                            <strong>Decision Required</strong><br>
                            <span style="font-size: 13px;">Charging the renter will forfeit their deposit to the seller. Dismissing will refund the deposit to the renter.</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 pt-0 justify-content-between">
                    <form action="process_dispute_resolution.php" method="POST" class="d-inline">
                        <input type="hidden" name="item_id" class="dispute-item-id">
                        <input type="hidden" name="action" value="dismiss">
                        <button type="submit" class="btn btn-outline-secondary fw-semibold">Dismiss & Forgive (Refund Renter)</button>
                    </form>
                    
                    <form action="process_dispute_resolution.php" method="POST" class="d-inline">
                        <input type="hidden" name="item_id" class="dispute-item-id">
                        <input type="hidden" name="action" value="charge">
                        <button type="submit" class="btn btn-danger fw-bold px-4">Charge Deposit (Pay Seller)</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openRefundModal(itemId, orderId, amount, buyerName) {
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalAmount').innerText = parseFloat(amount).toFixed(2);
            document.getElementById('modalBuyerName').innerText = buyerName;
            
            const modal = new bootstrap.Modal(document.getElementById('refundModal'));
            modal.show();
        }
        
        function openDisputeModal(data) {
            document.querySelectorAll('.dispute-item-id').forEach(el => el.value = data.item_id);
            
            // Set photos (handling empty/null cases)
            const initialPhoto = data.initial_condition_photo ? '../' + data.initial_condition_photo : '../img/default-book.png';
            const damagePhoto = data.damage_photo ? '../' + data.damage_photo : '../img/default-book.png';
            
            document.getElementById('modalInitialPhoto').src = initialPhoto;
            document.getElementById('modalDamagePhoto').src = damagePhoto;
            
            document.getElementById('modalDamageNotes').innerText = data.damage_notes || 'No notes provided by seller.';
            
            const modal = new bootstrap.Modal(document.getElementById('disputeModal'));
            modal.show();
        }

        function viewQr(src) {
            document.getElementById('qrViewImg').src = src;
            const modal = new bootstrap.Modal(document.getElementById('qrViewModal'));
            modal.show();
        }
    </script>
</body>
</html>
