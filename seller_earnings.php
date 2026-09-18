<?php
$currentPage = basename($_SERVER['PHP_SELF']);
require_once "connect.php";
require_once "session.php";

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Get Seller ID
$sellerId = 0;
$stmt = $conn->prepare("SELECT id, shop_name FROM sellers WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $sellerId = $row['id'];
    $shopName = $row['shop_name'];
} else {
    // Not an approved seller
    header("Location: start_selling.php");
    exit();
}
$stmt->close();

// Calculate Total Earnings
$totalEarnings = 0;

$stmt = $conn->prepare("
    SELECT SUM(total_price) as rental_earnings 
    FROM book_rentals 
    WHERE seller_id = ? AND status IN ('returned', 'completed')
");
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $totalEarnings += floatval($row['rental_earnings']);
}
$stmt->close();

$stmt = $conn->prepare("
    SELECT SUM(unit_price * quantity) as sales_earnings 
    FROM order_items 
    WHERE seller_id = ? AND purchase_type = 'buy' AND status IN ('delivered', 'completed')
");
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $totalEarnings += floatval($row['sales_earnings']);
}
$stmt->close();

// Calculate Total Requested/Paid Payouts
$totalPayouts = 0;
$stmt = $conn->prepare("SELECT SUM(amount) as total_payouts FROM seller_payouts WHERE seller_id = ? AND status IN ('pending', 'paid')");
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $totalPayouts = floatval($row['total_payouts']);
}
$stmt->close();

$availableBalance = max(0, $totalEarnings - $totalPayouts);

// Handle Payout Request with Balance Enforcement
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_payout') {
    $requestAmount = floatval($_POST['amount'] ?? 0);
    $payoutMethod = trim($_POST['payout_method'] ?? '');
    $payoutAccount = trim($_POST['payout_account'] ?? '');

    // Validate amount and balance
    if ($requestAmount <= 0) {
        $message = "error|Please enter a valid payout amount greater than zero.";
    } elseif ($requestAmount > $availableBalance) {
        $message = "error|Requested amount (₱" . number_format($requestAmount, 2) . ") exceeds your available balance of ₱" . number_format($availableBalance, 2) . ".";
    } elseif (empty($payoutMethod) || empty($payoutAccount)) {
        $message = "error|Please select a payout method and provide your payout account details.";
    } else {
        $stmt = $conn->prepare("INSERT INTO seller_payouts (seller_id, amount, status, payout_method, payout_account) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->bind_param("idss", $sellerId, $requestAmount, $payoutMethod, $payoutAccount);
        if ($stmt->execute()) {
            $message = "success|Payout request for ₱" . number_format($requestAmount, 2) . " submitted successfully.";
            // Deduct requested amount from available balance in memory for immediate view consistency
            $totalPayouts += $requestAmount;
            $availableBalance = max(0, $totalEarnings - $totalPayouts);
        } else {
            $message = "error|Failed to submit payout request.";
        }
        $stmt->close();
    }
}

// Fetch Payout History
$payouts = [];
$stmt = $conn->prepare("SELECT * FROM seller_payouts WHERE seller_id = ? ORDER BY request_date DESC");
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $payouts[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings & Payouts - BookWagon</title>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; color: #1a1d29; margin: 0; }
        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 4px 0; }
        .page-subtitle { font-size: 14px; color: #6b7280; margin: 0; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }

        .stat-icon.primary { background: #fef3c7; color: #d97706; }
        .stat-icon.success { background: #dcfce7; color: #16a34a; }

        .stat-details h4 { margin: 0; font-size: 13px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-details .value { font-size: 32px; font-weight: 800; color: #111827; margin: 4px 0 0 0; }

        .card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            display: flex; justify-content: space-between; align-items: center;
        }

        .card-title { font-size: 16px; font-weight: 600; margin: 0; color: #111827; }

        .payout-form {
            padding: 24px;
        }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 10px 14px;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 14px; color: #111827; box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: #f59e0b; }

        .btn-submit {
            background: #f59e0b; color: #fff; border: none;
            padding: 10px 20px; font-size: 14px; font-weight: 600;
            border-radius: 8px; cursor: pointer; transition: background 0.2s;
            width: 100%;
        }
        .btn-submit:hover { background: #d97706; }
        .btn-submit:disabled { background: #d1d5db; cursor: not-allowed; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 24px; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
        td { padding: 16px 24px; font-size: 14px; color: #111827; border-bottom: 1px solid #e5e7eb; }
        
        .badge {
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
            display: inline-block;
        }
        .badge.pending { background: #fef3c7; color: #b45309; }
        .badge.paid { background: #dcfce7; color: #166534; }
        .badge.rejected { background: #fee2e2; color: #991b1b; }

        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include "include/seller_sidebar.php"; ?>

    <main class="main-content">
        <div class="page-content">
            
            <div class="page-header">
                <h1 class="page-title">Earnings & Payouts</h1>
                <p class="page-subtitle">Track your rental and sales earnings and request withdrawals.</p>
            </div>

            <?php if ($message): ?>
                <?php list($type, $msg) = explode('|', $message); ?>
                <div class="alert <?php echo $type; ?>">
                    <i class="fa-solid <?php echo $type === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>" style="margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-wallet"></i></div>
                    <div class="stat-details">
                        <h4>Available Balance</h4>
                        <div class="value">₱<?php echo number_format($availableBalance, 2); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fa-solid fa-money-bill-transfer"></i></div>
                    <div class="stat-details">
                        <h4>Total Lifetime Earnings</h4>
                        <div class="value">₱<?php echo number_format($totalEarnings, 2); ?></div>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-hand-holding-dollar" style="color: #f59e0b; margin-right: 8px;"></i> Request Payout</h2>
                    </div>
                    <div class="payout-form">
                        <?php if ($availableBalance > 0): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="request_payout">
                            
                            <div class="form-group">
                                <label class="form-label">Amount to Withdraw (₱)</label>
                                <input type="number" name="amount" class="form-control" min="1" max="<?php echo $availableBalance; ?>" step="0.01" value="<?php echo $availableBalance; ?>" required>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Maximum available: ₱<?php echo number_format($availableBalance, 2); ?></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Payout Method</label>
                                <select name="payout_method" class="form-control" required>
                                    <option value="GCash">GCash</option>
                                    <option value="Maya">Maya</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Account Details (Name & Number)</label>
                                <input type="text" name="payout_account" class="form-control" placeholder="e.g. John Doe - 09123456789" required>
                            </div>

                            <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane" style="margin-right: 6px;"></i>Submit Request</button>
                        </form>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px;">
                                <i class="fa-solid fa-box-open" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                <h3 style="margin: 0 0 8px 0; color: #374151;">No Funds Available</h3>
                                <p style="margin: 0; color: #6b7280; font-size: 14px;">Complete more rentals or sales to earn funds.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color: #6b7280; margin-right: 8px;"></i> Payout History</h2>
                    </div>
                    <?php if (empty($payouts)): ?>
                        <div style="padding: 30px; text-align: center; color: #6b7280; font-size: 14px;">
                            No payout requests found.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payouts as $p): ?>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 13px;">
                                            <?php echo date('M d, Y', strtotime($p['request_date'])); ?>
                                        </td>
                                        <td style="font-weight: 600;">₱<?php echo number_format($p['amount'], 2); ?></td>
                                        <td style="font-size: 13px; color: #6b7280;"><?php echo htmlspecialchars($p['payout_method']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $p['status']; ?>">
                                                <?php echo ucfirst($p['status']); ?>
                                            </span>
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
    </main>
</body>
</html>
