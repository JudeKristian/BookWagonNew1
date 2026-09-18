<?php
$currentPage = basename($_SERVER['PHP_SELF']);
require_once "../connect.php";
session_start();

// Ensure Admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || !isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
$adminId = $_SESSION['admin_id'];

// Handle Payout Processing
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_payout') {
        $payoutId = intval($_POST['withdrawal_id'] ?? 0);
        $transactionId = trim($_POST['transaction_id'] ?? '');

        if ($payoutId > 0 && !empty($transactionId)) {
            $stmt = $conn->prepare("UPDATE wallet_withdrawals SET status = 'paid', transaction_id = ?, process_date = NOW() WHERE withdrawal_id = ? AND status = 'pending'");
            $stmt->bind_param("si", $transactionId, $payoutId);
            if ($stmt->execute()) {
                $message = "success|Payout processed and marked as paid successfully.";
                
                // Fetch user info for email notification
                require_once "../includes/send_mail.php";
                $fetchStmt = $conn->prepare("
                    SELECT u.email, u.firstname, w.amount 
                    FROM wallet_withdrawals w 
                    JOIN users u ON w.user_id = u.id 
                    WHERE w.withdrawal_id = ?
                ");
                $fetchStmt->bind_param("i", $payoutId);
                $fetchStmt->execute();
                $fetchRes = $fetchStmt->get_result();
                if ($userRow = $fetchRes->fetch_assoc()) {
                    sendPayoutNotificationEmail($userRow['email'], $userRow['firstname'], $userRow['amount'], $transactionId, true);
                    
                    // Insert into notifications table
                    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type, content) VALUES ((SELECT user_id FROM wallet_withdrawals WHERE withdrawal_id = ?), ?, 'payment_confirmed', ?)");
                    $notifContent = "Your withdrawal of ₱" . number_format($userRow['amount'], 2) . " has been sent to your E-Wallet.";
                    $notifStmt->bind_param("iis", $payoutId, $adminId, $notifContent);
                    $notifStmt->execute();
                }
            } else {
                $message = "error|Failed to process payout.";
            }
            $stmt->close();
        } else {
            $message = "error|Invalid payout or missing transaction ID.";
        }
    } elseif ($_POST['action'] === 'reject_payout') {
        $payoutId = intval($_POST['withdrawal_id'] ?? 0);
        if ($payoutId > 0) {
            $stmt = $conn->prepare("UPDATE wallet_withdrawals SET status = 'rejected', process_date = NOW() WHERE withdrawal_id = ? AND status = 'pending'");
            $stmt->bind_param("i", $payoutId);
            if ($stmt->execute()) {
                $message = "success|Payout request rejected.";
            } else {
                $message = "error|Failed to reject payout.";
            }
            $stmt->close();
        }
    }
}

// Fetch Pending Payouts
$pendingPayouts = [];
$res = $conn->query("
    SELECT p.*, u.firstname, u.lastname, u.email 
    FROM wallet_withdrawals p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'pending'
    ORDER BY p.request_date ASC
");
while ($row = $res->fetch_assoc()) {
    $pendingPayouts[] = $row;
}

// Fetch Processed Payouts
$processedPayouts = [];
$res = $conn->query("
    SELECT p.*, u.firstname, u.lastname, u.email 
    FROM wallet_withdrawals p
    JOIN users u ON p.user_id = u.id
    WHERE p.status != 'pending'
    ORDER BY p.process_date DESC
    LIMIT 100
");
while ($row = $res->fetch_assoc()) {
    $processedPayouts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renter Withdrawals - Admin Dashboard</title>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; margin: 0; color: #111827; }
        .main-content { margin-left: 250px; padding: 24px 32px; min-height: 100vh; }
        .page-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 4px 0; }
        .page-subtitle { font-size: 14px; color: #6b7280; margin: 0 0 24px 0; }

        .card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; margin-bottom: 24px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; font-size: 16px; font-weight: 600; }
        .card-body { padding: 0; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 24px; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
        td { padding: 16px 24px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge.pending { background: #fef3c7; color: #b45309; }
        .badge.paid { background: #dcfce7; color: #166534; }
        .badge.rejected { background: #fee2e2; color: #991b1b; }

        .btn-action { border: none; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; }
        .btn-action.process { background: #047857; color: #fff; }
        .btn-action.process:hover { background: #065f46; }
        .btn-action.reject { background: #fff; color: #b91c1c; border: 1px solid #fca5a5; }
        .btn-action.reject:hover { background: #fee2e2; }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(17, 24, 39, 0.7); display: flex;
            align-items: center; justify-content: center; z-index: 1000;
            opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .modal-overlay.show { opacity: 1; pointer-events: auto; }
        .modal-box {
            background: #fff; border-radius: 12px; width: 100%; max-width: 450px;
            padding: 24px; transform: translateY(20px); transition: transform 0.3s;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .modal-overlay.show .modal-box { transform: translateY(0); }

        .modal-title { margin: 0 0 16px 0; font-size: 18px; color: #111827; }
        
        .payout-details-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 16px;
        }
        
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">
        <h1 class="page-title">Renter Withdrawal Requests</h1>
        <p class="page-subtitle">Process renter deposit wallet withdrawals.</p>

        <?php if ($message): ?>
            <?php list($type, $msg) = explode('|', $message); ?>
            <div class="alert <?php echo $type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Pending Payouts -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clock" style="margin-right: 8px; color: #d97706;"></i>Pending Requests</div>
            <div class="card-body">
                <?php if (empty($pendingPayouts)): ?>
                    <div style="padding: 30px; text-align: center; color: #6b7280; font-size: 14px;">No pending payout requests.</div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User Name</th>
                                    <th>Amount</th>
                                    <th>Payout Details</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayouts as $p): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($p['request_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['firstname'] . ' ' . $p['lastname']); ?></strong><br>
                                        <span style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($p['email']); ?></span>
                                    </td>
                                    <td style="font-weight: 700; color: #047857;">₱<?php echo number_format($p['amount'], 2); ?></td>
                                    <td>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($p['payout_provider']); ?></span><br>
                                        <span style="font-size: 13px; color: #374151;"><?php echo htmlspecialchars($p['payout_account']); ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <button type="button" class="btn-action process" onclick='openProcessModal(<?php echo json_encode($p); ?>)'>
                                                <i class="fa-solid fa-check" style="margin-right: 4px;"></i> Process
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this payout request?');">
                                                <input type="hidden" name="action" value="reject_payout">
                                                <input type="hidden" name="withdrawal_id" value="<?php echo $p['withdrawal_id']; ?>">
                                                <button type="submit" class="btn-action reject"><i class="fa-solid fa-xmark"></i></button>
                                            </form>
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

        <!-- Processed Payouts -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-check-circle" style="margin-right: 8px; color: #16a34a;"></i>Recent History</div>
            <div class="card-body">
                <?php if (empty($processedPayouts)): ?>
                    <div style="padding: 30px; text-align: center; color: #6b7280; font-size: 14px;">No processed payouts yet.</div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Processed Date</th>
                                    <th>User Name</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Transaction ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processedPayouts as $p): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($p['process_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($p['firstname'] . " " . $p['lastname']); ?></td>
                                    <td style="font-weight: 600;">₱<?php echo number_format($p['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $p['status']; ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px; color: #6b7280;">
                                        <?php echo htmlspecialchars($p['transaction_id'] ?: '-'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal: Process Payout -->
    <div class="modal-overlay" id="processModal">
        <div class="modal-box">
            <h3 class="modal-title"><i class="fa-solid fa-money-bill-transfer" style="color: #047857; margin-right: 8px;"></i>Process Payout</h3>
            <p style="font-size: 13px; color: #6b7280; margin-top: 0;">Please transfer the exact amount to the seller's account below, then record the Transaction ID.</p>

            <div class="payout-details-box">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 13px; color: #6b7280;">User:</span>
                    <strong id="modalUserName">User Name</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 13px; color: #6b7280;">Amount to Send:</span>
                    <strong style="color: #047857; font-size: 16px;" id="modalAmount">₱0.00</strong>
                </div>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 12px 0;">
                <div style="margin-bottom: 4px;">
                    <span style="font-size: 12px; color: #6b7280; display: block; text-transform: uppercase;">Method:</span>
                    <strong id="modalMethod" style="font-size: 14px;">GCash</strong>
                </div>
                <div>
                    <span style="font-size: 12px; color: #6b7280; display: block; text-transform: uppercase;">Account Details:</span>
                    <strong id="modalAccount" style="font-size: 14px;">Account Name / Number</strong>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="process_payout">
                <input type="hidden" name="withdrawal_id" id="modalPayoutId">

                <div class="form-group">
                    <label class="form-label">Transfer Transaction ID / Reference No.</label>
                    <input type="text" name="transaction_id" class="form-control" placeholder="e.g. GCash Ref: 100293000" required>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn-action" style="flex: 1; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;" onclick="closeProcessModal()">Cancel</button>
                    <button type="submit" class="btn-action process" style="flex: 1;"><i class="fa-solid fa-check" style="margin-right: 6px;"></i>Confirm Paid</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openProcessModal(data) {
            document.getElementById('modalPayoutId').value = data.withdrawal_id;
            document.getElementById('modalUserName').innerText = data.firstname + ' ' + data.lastname;
            document.getElementById('modalAmount').innerText = '₱' + parseFloat(data.amount).toFixed(2);
            document.getElementById('modalMethod').innerText = data.payout_provider;
            document.getElementById('modalAccount').innerText = data.payout_account;
            document.getElementById('processModal').classList.add('show');
        }

        function closeProcessModal() {
            document.getElementById('processModal').classList.remove('show');
        }
    </script>
</body>
</html>
