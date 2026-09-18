<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "db_connect.php";

$adminId = $_SESSION['admin_id'] ?? 1;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$message = "";

// Helper for audit logging
function log_admin_event($conn, $adminId, $activity, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, activity, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $adminId, $activity, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Middleman Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Settle Damage Dispute (Phase 8)
    if ($action === 'settle_damage') {
        $rentalId = intval($_POST['rental_id'] ?? 0);
        $returnId = intval($_POST['return_id'] ?? 0);
        $depositHeld = floatval($_POST['deposit_held'] ?? 0);
        $damageFee = floatval($_POST['damage_fee'] ?? 0);
        $sellerNotes = trim($_POST['admin_notes'] ?? 'Admin approved damage assessment');

        if ($rentalId > 0) {
            // Cap damage fee to deposit
            if ($damageFee > $depositHeld) $damageFee = $depositHeld;
            $renterRefund = max(0, $depositHeld - $damageFee);

            // Fetch renter ID and seller ID
            $renterId = 0;
            $sellerId = 0;
            $sellerUserId = 0;
            $stmtRenter = $conn->prepare("SELECT user_id, seller_id FROM book_rentals WHERE rental_id = ?");
            $stmtRenter->bind_param("i", $rentalId);
            $stmtRenter->execute();
            $resRenter = $stmtRenter->get_result();
            if ($row = $resRenter->fetch_assoc()) {
                $renterId = intval($row['user_id']);
                $sellerId = intval($row['seller_id']);
            }
            $stmtRenter->close();
            
            if ($sellerId > 0) {
                $stmtSeller = $conn->prepare("SELECT user_id FROM sellers WHERE id = ?");
                $stmtSeller->bind_param("i", $sellerId);
                $stmtSeller->execute();
                $resSeller = $stmtSeller->get_result();
                if ($rowSeller = $resSeller->fetch_assoc()) {
                    $sellerUserId = intval($rowSeller['user_id']);
                }
                $stmtSeller->close();
            }

            // Update return record
            if ($returnId > 0) {
                $stmt = $conn->prepare("UPDATE book_returns SET damage_fee = ?, status = 'completed', completed_date = NOW() WHERE return_id = ?");
                $stmt->bind_param("di", $damageFee, $returnId);
                $stmt->execute();
                $stmt->close();
            }

            // Update rental record
            $stmt = $conn->prepare("UPDATE book_rentals SET status = 'returned', return_date = NOW() WHERE rental_id = ?");
            $stmt->bind_param("i", $rentalId);
            $stmt->execute();
            $stmt->close();

            // Restock book
            $stmtRestock = $conn->prepare("UPDATE books b JOIN book_rentals br ON b.book_id = br.book_id SET b.stock = COALESCE(b.stock, 0) + 1 WHERE br.rental_id = ?");
            $stmtRestock->bind_param("i", $rentalId);
            $stmtRestock->execute();
            $stmtRestock->close();

            // Credit Renter's Unified Wallet with their refund portion
            if ($renterId > 0 && $renterRefund > 0) {
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->bind_param("di", $renterRefund, $renterId);
                $stmt->execute();
                $stmt->close();
            }

            // Credit Seller's Unified Wallet with their damage compensation
            if ($sellerUserId > 0 && $damageFee > 0) {
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->bind_param("di", $damageFee, $sellerUserId);
                $stmt->execute();
                $stmt->close();
            }

            // Audit Trail
            $details = "Admin approved damage settlement for Rental #$rentalId. Deposit: ₱" . number_format($depositHeld, 2) . " -> Seller Awarded: ₱" . number_format($damageFee, 2) . " | Student Refund: ₱" . number_format($renterRefund, 2) . " (Credited to Wallet). Notes: $sellerNotes";
            log_admin_event($conn, $adminId, 'ESCROW_DAMAGE_SETTLED', $details);

            $message = "success|Damage settlement finalized. ₱" . number_format($damageFee, 2) . " transferred to seller as compensation, and ₱" . number_format($renterRefund, 2) . " credited to the student's BookWagon Wallet.";
        }
    }

    // 2. Declare Lost & Forfeit Escrow (Phase 10)
    elseif ($action === 'forfeit_lost') {
        $rentalId = intval($_POST['rental_id'] ?? 0);
        $renterId = intval($_POST['renter_id'] ?? 0);
        $depositHeld = floatval($_POST['deposit_held'] ?? 0);
        $reason = trim($_POST['forfeit_reason'] ?? 'Delinquent non-return: grace period expired');

        if ($rentalId > 0 && $renterId > 0) {
            // Fetch seller ID to credit their wallet
            $sellerId = 0;
            $sellerUserId = 0;
            $stmtRental = $conn->prepare("SELECT seller_id FROM book_rentals WHERE rental_id = ?");
            $stmtRental->bind_param("i", $rentalId);
            $stmtRental->execute();
            $resRental = $stmtRental->get_result();
            if ($row = $resRental->fetch_assoc()) {
                $sellerId = intval($row['seller_id']);
            }
            $stmtRental->close();

            if ($sellerId > 0) {
                $stmtSeller = $conn->prepare("SELECT user_id FROM sellers WHERE id = ?");
                $stmtSeller->bind_param("i", $sellerId);
                $stmtSeller->execute();
                $resSeller = $stmtSeller->get_result();
                if ($rowSeller = $resSeller->fetch_assoc()) {
                    $sellerUserId = intval($rowSeller['user_id']);
                }
                $stmtSeller->close();
            }

            // Mark rental as lost
            $stmt = $conn->prepare("UPDATE book_rentals SET status = 'lost' WHERE rental_id = ?");
            $stmt->bind_param("i", $rentalId);
            $stmt->execute();
            $stmt->close();

            // Automatically suspend the delinquent renter
            $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
            $stmt->bind_param("i", $renterId);
            $stmt->execute();
            $stmt->close();

            // Credit Seller's Unified Wallet with the full forfeited deposit
            if ($sellerUserId > 0 && $depositHeld > 0) {
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->bind_param("di", $depositHeld, $sellerUserId);
                $stmt->execute();
                $stmt->close();
            }

            // Audit Trail
            $details = "Admin declared Rental #$rentalId LOST. 100% Escrow deposit (₱" . number_format($depositHeld, 2) . ") forfeited to seller. Delinquent renter ID #$renterId was SUSPENDED. Reason: $reason";
            log_admin_event($conn, $adminId, 'ESCROW_DEPOSIT_FORFEITED', $details);

            $message = "success|Rental #$rentalId declared Lost. ₱" . number_format($depositHeld, 2) . " forfeited to seller as full replacement compensation. Renter account suspended.";
        }
    }

    // 3. Release Full Deposit (Clean Return)
    elseif ($action === 'release_full') {
        $rentalId = intval($_POST['rental_id'] ?? 0);
        $returnId = intval($_POST['return_id'] ?? 0);
        $depositHeld = floatval($_POST['deposit_held'] ?? 0);
        $refundNotes = trim($_POST['refund_notes'] ?? 'Manual refund processed');

        if ($rentalId > 0) {
            // Prevent double refunding if already settled
            $stmtCheck = $conn->prepare("SELECT status FROM book_rentals WHERE rental_id = ?");
            $stmtCheck->bind_param("i", $rentalId);
            $stmtCheck->execute();
            $checkRes = $stmtCheck->get_result();
            $rentalData = $checkRes->fetch_assoc();
            $stmtCheck->close();
            
            if ($rentalData && $rentalData['status'] === 'returned') {
                $message = "error|This rental has already been refunded and settled.";
            } else {
                // Fetch renter ID to credit their wallet
                $renterId = 0;
                $stmtRenter = $conn->prepare("SELECT user_id FROM book_rentals WHERE rental_id = ?");
                $stmtRenter->bind_param("i", $rentalId);
                $stmtRenter->execute();
                $resRenter = $stmtRenter->get_result();
                if ($row = $resRenter->fetch_assoc()) {
                    $renterId = intval($row['user_id']);
                }
                $stmtRenter->close();

                if ($returnId > 0) {
                    $stmtRet = $conn->prepare("UPDATE book_returns SET status = 'completed', completed_date = NOW(), damage_fee = 0.00 WHERE return_id = ?");
                    $stmtRet->bind_param("i", $returnId);
                    $stmtRet->execute();
                    $stmtRet->close();
                }
                $stmtRent = $conn->prepare("UPDATE book_rentals SET status = 'returned', return_date = NOW() WHERE rental_id = ?");
                $stmtRent->bind_param("i", $rentalId);
                $stmtRent->execute();
                $stmtRent->close();

                $stmtStock = $conn->prepare("UPDATE books b JOIN book_rentals br ON b.book_id = br.book_id SET b.stock = COALESCE(b.stock, 0) + 1 WHERE br.rental_id = ?");
                $stmtStock->bind_param("i", $rentalId);
                $stmtStock->execute();
                $stmtStock->close();

                // Credit Renter's Unified Wallet with full deposit
                if ($renterId > 0 && $depositHeld > 0) {
                    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $depositHeld, $renterId);
                    $stmt->execute();
                    $stmt->close();
                }

                $details = "Admin confirmed clean return for Rental #$rentalId. 100% Escrow deposit (₱" . number_format($depositHeld, 2) . ") credited to renter's wallet. Refund Notes: $refundNotes";
                log_admin_event($conn, $adminId, 'ESCROW_CLEAN_RELEASE', $details);

                $message = "success|Full deposit (₱" . number_format($depositHeld, 2) . ") successfully credited to the renter's BookWagon Wallet. Rental marked completed.";
            }
        }
    }
}

// Metrics Calculation
$totalEscrowHeld = 0.00;
$activeRentalsCount = 0;
$overdueRentalsCount = 0;
$disputeReviewsCount = 0;

$res = $conn->query("
    SELECT 
        SUM(COALESCE(NULLIF(b.security_deposit, 0), NULLIF(b.book_value, 0), 0)) as total_escrow,
        COUNT(CASE WHEN r.status = 'active' AND r.due_date >= NOW() THEN 1 END) as active_count,
        COUNT(CASE WHEN r.status = 'overdue' OR (r.status = 'active' AND r.due_date < NOW()) THEN 1 END) as overdue_count
    FROM book_rentals r
    JOIN books b ON r.book_id = b.book_id
    WHERE r.status IN ('active', 'overdue')
");
if ($res && $row = $res->fetch_assoc()) {
    $totalEscrowHeld = floatval($row['total_escrow'] ?? 0);
    $activeRentalsCount = intval($row['active_count'] ?? 0);
    $overdueRentalsCount = intval($row['overdue_count'] ?? 0);
}

$resDispute = $conn->query("
    SELECT COUNT(*) as dispute_count 
    FROM book_returns 
    WHERE status = 'pending' OR book_condition = 'damaged'
");
if ($resDispute && $row = $resDispute->fetch_assoc()) {
    $disputeReviewsCount = intval($row['dispute_count'] ?? 0);
}

// Fetch all rentals with full relational data
$rentals = [];
$sql = "
    SELECT 
        r.rental_id, r.order_id, r.book_id, r.user_id as renter_id, r.seller_id,
        r.rental_weeks, r.rental_date, r.due_date, r.return_date, r.total_price, r.late_fee, r.status as rental_status,
        b.title as book_title, b.author as book_author, COALESCE(b.price, 250.00) as book_price, COALESCE(b.rent_price, 0) as rental_price,
        b.security_deposit, b.book_value,
        u.firstname as renter_fn, u.lastname as renter_ln, u.email as renter_email, u.status as renter_status,
        u.payout_provider, u.payout_number, u.payout_name, u.payout_qr_code,
        COALESCE(sel.shop_name, 'Direct Seller') as shop_name,
        COALESCE(sel_u.firstname, 'Seller') as seller_fn, COALESCE(sel_u.lastname, '') as seller_ln, COALESCE(sel_u.email, '-') as seller_email,
        ret.return_id, ret.status as return_status, ret.book_condition, ret.damage_fee, ret.return_method, ret.return_details
    FROM book_rentals r
    JOIN books b ON r.book_id = b.book_id
    JOIN users u ON r.user_id = u.id
    LEFT JOIN sellers sel ON r.seller_id = sel.id
    LEFT JOIN users sel_u ON COALESCE(sel.user_id, b.user_id) = sel_u.id
    LEFT JOIN book_returns ret ON r.rental_id = ret.rental_id
    ORDER BY r.rental_date DESC
";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $rentals[] = $row;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escrow & Rental Oversight - BookWagon Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .escrow-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .escrow-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .escrow-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .escrow-val { font-size: 20px; font-weight: 700; color: var(--text-dark); line-height: 1.2; }
        .escrow-lbl { font-size: 12px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-active { background: #eff6ff; color: #1d4ed8; }
        .badge-overdue { background: #fef2f2; color: #b91c1c; }
        .badge-damaged { background: #fff7ed; color: #c2410c; }
        .badge-completed { background: #ecfdf5; color: #047857; }
        .badge-lost { background: #f3f4f6; color: #4b5563; text-decoration: line-through; }

        /* Toolbar & Filters */
        .toolbar-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }
        .search-box {
            display: flex;
            align-items: center;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 14px;
            gap: 10px;
            min-width: 280px;
        }
        .search-box input {
            border: none;
            background: transparent;
            font-size: 13px;
            outline: none;
            width: 100%;
            font-family: inherit;
        }
        .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-pill {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            background: var(--bg);
            color: var(--text-muted);
            cursor: pointer;
            border: 1px solid var(--border);
            transition: all 0.15s;
        }
        .filter-pill.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* Action Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-action.settle { background: #eff6ff; color: #2563eb; }
        .btn-action.settle:hover { background: #dbeafe; }
        .btn-action.forfeit { background: #fef2f2; color: #ef4444; }
        .btn-action.forfeit:hover { background: #fee2e2; }
        .btn-action.clean { background: #ecfdf5; color: #059669; }
        .btn-action.clean:hover { background: #d1fae5; }

        /* Modals */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 200;
            align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; padding: 28px;
            max-width: 520px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-box h3 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
        .modal-box p { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 18px; }
        .calc-breakdown {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .calc-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 4px 0;
        }
        .calc-row.total {
            border-top: 1px solid var(--border);
            padding-top: 8px;
            margin-top: 8px;
            font-weight: 700;
            font-size: 14px;
        }
        .modal-input {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border);
            border-radius: 8px; font-size: 13px; font-family: inherit;
            margin-bottom: 12px; outline: none;
        }
        .modal-input:focus { border-color: var(--primary); }
        .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }

        @media (max-width: 992px) {
            .escrow-stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .escrow-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include "admin_sidebar.php"; ?>

    <!-- Modal: Damage Dispute Settlement (Phase 8) -->
    <div class="modal-overlay" id="settleModal">
        <div class="modal-box">
            <h3 style="color: var(--text-dark);"><i class="fa-solid fa-scale-balanced" style="color: var(--primary); margin-right: 8px;"></i>Arbitrate Damage Settlement</h3>
            <p>Review the physical damage assessment and authorize deposit disbursement between the renter and store owner.</p>

            
            <form method="POST">
                <input type="hidden" name="action" value="settle_damage">
                <input type="hidden" name="rental_id" id="modalRentalId">
                <input type="hidden" name="return_id" id="modalReturnId">
                <input type="hidden" name="deposit_held" id="modalDepositHeld">

                <div class="calc-breakdown">
                    <div class="calc-row">
                        <span style="color: var(--text-muted);">Book Title:</span>
                        <strong id="modalBookTitle">-</strong>
                    </div>
                    <div class="calc-row">
                        <span style="color: var(--text-muted);">Platform Escrow Held:</span>
                        <strong id="modalDepositDisplay">₱0.00</strong>
                    </div>
                    <div class="calc-row">
                        <span style="color: var(--text-muted);">Renter:</span>
                        <span id="modalRenter">-</span>
                    </div>
                    <div class="calc-row">
                        <span style="color: var(--text-muted);">Seller / Shop:</span>
                        <span id="modalSeller">-</span>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Authorized Damage Penalty (Paid to Seller):</label>
                    <input type="number" step="0.01" min="0" name="damage_fee" id="modalDamageInput" class="modal-input" oninput="updatePayoutCalc()" required>
                </div>

                <div class="calc-breakdown" style="background: #fffbeb; border-color: #fde68a;">
                    <div class="calc-row">
                        <span>Compensation to Seller:</span>
                        <strong style="color: #b45309;" id="modalSellerPayout">₱0.00</strong>
                    </div>
                    <div class="calc-row total">
                        <span>Net Refund to Renter:</span>
                        <strong style="color: #047857;" id="modalRenterRefund">₱0.00</strong>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Arbitration Notes / Reason:</label>
                    <input type="text" name="admin_notes" class="modal-input" value="Damage verified via photographic inspection; penalty approved." required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-action" style="background: var(--bg); color: var(--text-muted);" onclick="closeSettleModal()">Cancel</button>
                    <button type="submit" class="btn-action settle" style="padding: 10px 18px; font-size: 13px;"><i class="fa-solid fa-check" style="margin-right: 4px;"></i>Authorize Payout</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Declare Lost & Forfeit Escrow (Phase 10) -->
    <div class="modal-overlay" id="forfeitModal">
        <div class="modal-box">
            <h3 style="color: #b91c1c;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i>Declare Lost & Forfeit Escrow</h3>
            <p>The grace period has expired and the renter has not returned the book. As the platform intermediary, you will compensate the seller and enforce disciplinary action.</p>


            <form method="POST">
                <input type="hidden" name="action" value="forfeit_lost">
                <input type="hidden" name="rental_id" id="forfeitRentalId">
                <input type="hidden" name="renter_id" id="forfeitRenterId">
                <input type="hidden" name="deposit_held" id="forfeitDepositHeld">

                <div class="calc-breakdown" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="calc-row">
                        <span style="color: #7f1d1d;">Book:</span>
                        <strong id="forfeitBookTitle" style="color: #7f1d1d;">-</strong>
                    </div>
                    <div class="calc-row">
                        <span style="color: #7f1d1d;">Delinquent Renter:</span>
                        <strong id="forfeitRenterName" style="color: #7f1d1d;">-</strong>
                    </div>
                    <div class="calc-row total" style="color: #991b1b;">
                        <span>100% Escrow Forfeited to Seller:</span>
                        <span id="forfeitDepositDisplay">₱0.00</span>
                    </div>
                </div>

                <div style="background: #fff; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 12px; color: #9a3412;">
                    <i class="fa-solid fa-shield-halved" style="margin-right: 4px;"></i>
                    <strong>Automated Security Action:</strong> Submitting this will automatically mark the renter's account as <strong>Suspended</strong>, blocking them from logging in or initiating new rentals.
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Formal Justification:</label>
                    <input type="text" name="forfeit_reason" class="modal-input" value="Unreturned book past 7-day grace period; replacement compensation issued to seller." required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-action" style="background: var(--bg); color: var(--text-muted);" onclick="closeForfeitModal()">Cancel</button>
                    <button type="submit" class="btn-action forfeit" style="padding: 10px 18px; font-size: 13px;"><i class="fa-solid fa-gavel" style="margin-right: 4px;"></i>Forfeit Escrow & Suspend</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Release Full Deposit -->
    <div class="modal-overlay" id="releaseModal">
        <div class="modal-box">
            <h3 style="color: #047857;"><i class="fa-solid fa-hand-holding-dollar" style="margin-right: 8px;"></i>Release Full Deposit</h3>
            <p>Automatically credit the escrow deposit back to the renter's BookWagon Wallet and record the transaction to settle the rental.</p>


            <form method="POST">
                <input type="hidden" name="action" value="release_full">
                <input type="hidden" name="rental_id" id="releaseRentalId">
                <input type="hidden" name="return_id" id="releaseReturnId">
                <input type="hidden" name="deposit_held" id="releaseDepositHeld">

                <div class="calc-breakdown" style="background: #ecfdf5; border-color: #a7f3d0; margin-bottom: 20px;">
                    <div class="calc-row">
                        <span style="color: #065f46;">Renter to Credit:</span>
                        <strong id="releaseRenterName" style="color: #065f46;">-</strong>
                    </div>
                    <div class="calc-row total" style="color: #064e3b;">
                        <span>Credit Amount:</span>
                        <span id="releaseDepositDisplay">₱0.00</span>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin-bottom: 20px; font-size: 13px; color: #334155;">
                    <i class="fa-solid fa-circle-info" style="color: #f8a100; margin-right: 6px;"></i> The full deposit amount will be instantly credited to the renter's BookWagon Wallet balance. They can withdraw it or use it for future rentals.
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Action Notes (Optional):</label>
                    <input type="text" name="refund_notes" class="modal-input" placeholder="e.g. Clean return confirmed" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-action" style="background: var(--bg); color: var(--text-muted);" onclick="closeReleaseModal()">Cancel</button>
                    <button type="submit" class="btn-action clean" style="padding: 10px 18px; font-size: 13px;"><i class="fa-solid fa-check" style="margin-right: 4px;"></i>Credit to Wallet Balance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <div class="topbar-title">
                    <h1>Escrow & Rental Oversight</h1>
                    <p>Platform middleman custody, damage arbitration, and non-return enforcement</p>
                </div>
            </div>
            <div class="topbar-date">
                <i class="fa-regular fa-calendar"></i>
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <div class="page-content">

            <?php if (!empty($message)): 
                $parts = explode('|', $message);
                $msgType = $parts[0];
                $msgText = $parts[1];
            ?>
                <div style="background: <?php echo ($msgType === 'success') ? '#ecfdf5' : '#fef2f2'; ?>; border: 1px solid <?php echo ($msgType === 'success') ? '#a7f3d0' : '#fecaca'; ?>; color: <?php echo ($msgType === 'success') ? '#065f46' : '#991b1b'; ?>; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-<?php echo ($msgType === 'success') ? 'circle-check' : 'circle-exclamation'; ?>" style="font-size: 16px;"></i>
                    <?php echo htmlspecialchars($msgText); ?>
                </div>
            <?php endif; ?>

            <!-- Metric Summary Cards -->
            <div class="escrow-stats">
                <div class="escrow-card">
                    <div class="escrow-icon" style="background: #fef7e8; color: #d97706;">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                    <div>
                        <div class="escrow-val">₱<?php echo number_format($totalEscrowHeld, 2); ?></div>
                        <div class="escrow-lbl">Total Escrow in Custody</div>
                    </div>
                </div>
                <div class="escrow-card">
                    <div class="escrow-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa-solid fa-book-open-reader"></i>
                    </div>
                    <div>
                        <div class="escrow-val"><?php echo $activeRentalsCount; ?></div>
                        <div class="escrow-lbl">Active Campus Leases</div>
                    </div>
                </div>
                <div class="escrow-card">
                    <div class="escrow-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <div class="escrow-val"><?php echo $disputeReviewsCount; ?></div>
                        <div class="escrow-lbl">Returns Needing Review</div>
                    </div>
                </div>
                <div class="escrow-card">
                    <div class="escrow-icon" style="background: #fef2f2; color: #ef4444;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <div class="escrow-val"><?php echo $overdueRentalsCount; ?></div>
                        <div class="escrow-lbl">Overdue / At-Risk</div>
                    </div>
                </div>
            </div>

            <!-- Content Table Card -->
            <div class="content-card">
                <div class="toolbar-wrap">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass" style="color: var(--text-light);"></i>
                        <input type="text" id="rentalSearchInput" placeholder="Search book, renter, or seller..." oninput="filterRentals()">
                    </div>
                    <div class="filter-pills">
                        <span class="filter-pill active" data-filter="all" onclick="setRentalFilter('all', this)">All (<?php echo count($rentals); ?>)</span>
                        <span class="filter-pill" data-filter="active" onclick="setRentalFilter('active', this)">Active Leases</span>
                        <span class="filter-pill" data-filter="dispute" onclick="setRentalFilter('dispute', this)">Damage Disputes (<?php echo $disputeReviewsCount; ?>)</span>
                        <span class="filter-pill" data-filter="overdue" onclick="setRentalFilter('overdue', this)">Overdue & Lost</span>
                        <span class="filter-pill" data-filter="completed" onclick="setRentalFilter('completed', this)">Settled</span>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="data-table" id="rentalsTable">
                        <thead>
                            <tr>
                                <th>Rental Details</th>
                                <th>Renter</th>
                                <th>Store / Seller</th>
                                <th>Escrow Held</th>
                                <th>Timeline & Condition</th>
                                <th>Status</th>
                                <th>Middleman Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rentals)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 32px; color: var(--text-muted);">
                                        <i class="fa-solid fa-folder-open" style="font-size: 24px; margin-bottom: 8px; display:block;"></i>
                                        No rental records found in the database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rentals as $r): 
                                    $deposit = floatval($r['security_deposit'] > 0 ? $r['security_deposit'] : ($r['book_value'] > 0 ? $r['book_value'] : 0));
                                    $isOverdue = ($r['rental_status'] === 'overdue' || ($r['rental_status'] === 'active' && strtotime($r['due_date']) < time()));
                                    $isDamaged = ($r['book_condition'] === 'damaged' || floatval($r['damage_fee']) > 0);
                                    
                                    // Row category for filtering
                                    $category = 'active';
                                    if ($r['rental_status'] === 'returned' || $r['rental_status'] === 'completed') $category = 'completed';
                                    elseif ($isDamaged) $category = 'dispute';
                                    elseif ($isOverdue || $r['rental_status'] === 'lost') $category = 'overdue';
                                ?>
                                <tr class="rental-row" 
                                    data-category="<?php echo $category; ?>"
                                    data-search="<?php echo strtolower($r['book_title'] . ' ' . $r['renter_fn'] . ' ' . $r['renter_ln'] . ' ' . $r['shop_name']); ?>">
                                    
                                    <!-- Book Details -->
                                    <td>
                                        <div style="font-weight: 600; font-size: 13px; color: var(--text-dark);"><?php echo htmlspecialchars($r['book_title']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                            Rental #<?php echo $r['rental_id']; ?> &middot; <?php echo htmlspecialchars($r['book_author']); ?>
                                        </div>
                                    </td>

                                    <!-- Renter Info -->
                                    <td>
                                        <div style="font-weight: 600; font-size: 13px;">
                                            <?php echo htmlspecialchars($r['renter_fn'] . ' ' . $r['renter_ln']); ?>
                                            <?php if ($r['renter_status'] === 'suspended'): ?>
                                                <span style="font-size: 10px; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 4px;">Suspended</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($r['renter_email']); ?></div>
                                    </td>

                                    <!-- Seller Info -->
                                    <td>
                                        <div style="font-weight: 600; font-size: 13px; color: var(--primary-dark);"><i class="fa-solid fa-store" style="font-size: 11px; margin-right: 4px;"></i><?php echo htmlspecialchars($r['shop_name']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($r['seller_fn'] . ' ' . $r['seller_ln']); ?></div>
                                    </td>

                                    <!-- Escrow Held -->
                                    <td>
                                        <div style="font-weight: 700; font-size: 13px; color: #047857;">
                                            ₱<?php echo number_format($deposit, 2); ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted);">
                                            Rental Fee: ₱<?php echo number_format($r['total_price'], 2); ?>
                                        </div>
                                    </td>

                                    <!-- Timeline & Condition -->
                                    <td>
                                        <div style="font-size: 12px; color: var(--text-dark);">
                                            Due: <?php echo date('M d, Y', strtotime($r['due_date'])); ?>
                                        </div>
                                        <?php if (!empty($r['book_condition'])): ?>
                                            <div style="font-size: 11px; margin-top: 2px; color: <?php echo ($r['book_condition'] === 'damaged') ? '#b91c1c' : '#047857'; ?>; font-weight: 600;">
                                                Condition: <?php echo ucfirst($r['book_condition']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status Badge -->
                                    <td>
                                        <?php if ($r['rental_status'] === 'lost'): ?>
                                            <span class="status-badge badge-lost"><i class="fa-solid fa-circle-xmark"></i> Lost/Forfeited</span>
                                        <?php elseif ($isDamaged && $r['rental_status'] !== 'returned'): ?>
                                            <span class="status-badge badge-damaged"><i class="fa-solid fa-triangle-exclamation"></i> Damage Review</span>
                                        <?php elseif ($isOverdue): ?>
                                            <span class="status-badge badge-overdue"><i class="fa-solid fa-clock"></i> Overdue</span>
                                        <?php elseif ($r['rental_status'] === 'returned' || $r['rental_status'] === 'completed'): ?>
                                            <span class="status-badge badge-completed"><i class="fa-solid fa-check-double"></i> Settled</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-active"><i class="fa-solid fa-rotate"></i> Active Lease</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Middleman Actions -->
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <?php if ($isDamaged && $r['rental_status'] !== 'returned'): ?>
                                                <button type="button" class="btn-action settle" onclick='openSettleModal(<?php echo htmlspecialchars(json_encode([
                                                    "rental_id" => $r["rental_id"],
                                                    "return_id" => $r["return_id"],
                                                    "book_title" => $r["book_title"],
                                                    "deposit" => $deposit,
                                                    "damage_fee" => floatval($r["damage_fee"] ?: 100),
                                                    "renter_name" => $r["renter_fn"] . " " . $r["renter_ln"],
                                                    "seller_name" => $r["shop_name"]
                                                ]), ENT_QUOTES, 'UTF-8'); ?>)'>
                                                    <i class="fa-solid fa-scale-balanced"></i> Arbitrate
                                                </button>
                                            <?php elseif ($isOverdue && $r['rental_status'] !== 'lost'): ?>
                                                <button type="button" class="btn-action forfeit" onclick='openForfeitModal(<?php echo htmlspecialchars(json_encode([
                                                    "rental_id" => $r["rental_id"],
                                                    "renter_id" => $r["renter_id"],
                                                    "book_title" => $r["book_title"],
                                                    "deposit" => $deposit,
                                                    "renter_name" => $r["renter_fn"] . " " . $r["renter_ln"]
                                                ]), ENT_QUOTES, 'UTF-8'); ?>)'>
                                                    <i class="fa-solid fa-gavel"></i> Declare Lost
                                                </button>
                                            <?php elseif ($r['rental_status'] === 'active' || $r['rental_status'] === 'return_pending'): ?>
                                                <button type="button" class="btn-action clean" onclick='openReleaseModal(<?php echo htmlspecialchars(json_encode([
                                                    "rental_id" => $r["rental_id"],
                                                    "return_id" => $r["return_id"] ?? 0,
                                                    "deposit" => $deposit,
                                                    "renter_name" => $r["renter_fn"] . " " . $r["renter_ln"],
                                                    "payout_provider" => $r["payout_provider"],
                                                    "payout_name" => $r["payout_name"],
                                                    "payout_number" => $r["payout_number"],
                                                    "payout_qr_code" => $r["payout_qr_code"]
                                                ]), ENT_QUOTES, 'UTF-8'); ?>)' title="Complete Clean Return & Refund">
                                                    <i class="fa-solid fa-hand-holding-dollar"></i> Refund
                                                </button>

                                            <?php else: ?>
                                                <span style="font-size: 11px; color: var(--text-light); font-style: italic;">No pending action</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        let currentFilter = 'all';

        function filterRentals() {
            const query = document.getElementById('rentalSearchInput').value.toLowerCase();
            document.querySelectorAll('.rental-row').forEach(row => {
                const searchTxt = row.dataset.search;
                const category = row.dataset.category;

                let matchSearch = searchTxt.includes(query);
                let matchFilter = (currentFilter === 'all') || (category === currentFilter);

                row.style.display = (matchSearch && matchFilter) ? '' : 'none';
            });
        }

        function setRentalFilter(filter, el) {
            currentFilter = filter;
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            filterRentals();
        }

        // Settle Modal Functions
        function openSettleModal(data) {
            document.getElementById('modalRentalId').value = data.rental_id;
            document.getElementById('modalReturnId').value = data.return_id || 0;
            document.getElementById('modalDepositHeld').value = data.deposit;
            document.getElementById('modalBookTitle').innerText = data.book_title;
            document.getElementById('modalDepositDisplay').innerText = '₱' + parseFloat(data.deposit).toFixed(2);
            document.getElementById('modalRenter').innerText = data.renter_name;
            document.getElementById('modalSeller').innerText = data.seller_name;
            document.getElementById('modalDamageInput').value = data.damage_fee;
            
            updatePayoutCalc();
            document.getElementById('settleModal').classList.add('show');
        }

        function closeSettleModal() {
            document.getElementById('settleModal').classList.remove('show');
        }

        function updatePayoutCalc() {
            const deposit = parseFloat(document.getElementById('modalDepositHeld').value) || 0;
            const damageInput = parseFloat(document.getElementById('modalDamageInput').value) || 0;
            const cappedDamage = Math.min(deposit, damageInput);
            const renterRefund = Math.max(0, deposit - cappedDamage);

            document.getElementById('modalSellerPayout').innerText = '₱' + cappedDamage.toFixed(2);
            document.getElementById('modalRenterRefund').innerText = '₱' + renterRefund.toFixed(2);
        }

        // Forfeit Modal Functions
        function openForfeitModal(data) {
            document.getElementById('forfeitRentalId').value = data.rental_id;
            document.getElementById('forfeitRenterId').value = data.renter_id;
            document.getElementById('forfeitDepositHeld').value = data.deposit;
            document.getElementById('forfeitBookTitle').innerText = data.book_title;
            document.getElementById('forfeitRenterName').innerText = data.renter_name;
            document.getElementById('forfeitDepositDisplay').innerText = '₱' + parseFloat(data.deposit).toFixed(2);

            document.getElementById('forfeitModal').classList.add('show');
        }

        function closeForfeitModal() {
            document.getElementById('forfeitModal').classList.remove('show');
        }

        // Release Modal Functions
        function openReleaseModal(data) {
            document.getElementById('releaseRentalId').value = data.rental_id;
            document.getElementById('releaseReturnId').value = data.return_id;
            document.getElementById('releaseDepositHeld').value = data.deposit;
            document.getElementById('releaseRenterName').innerText = data.renter_name;
            document.getElementById('releaseDepositDisplay').innerText = '₱' + parseFloat(data.deposit).toFixed(2);

            document.getElementById('releaseModal').classList.add('show');
        }

        function closeReleaseModal() {
            document.getElementById('releaseModal').classList.remove('show');
        }

        // Close on backdrop click
        document.getElementById('settleModal').addEventListener('click', function(e) {
            if (e.target === this) closeSettleModal();
        });
        document.getElementById('forfeitModal').addEventListener('click', function(e) {
            if (e.target === this) closeForfeitModal();
        });
        document.getElementById('releaseModal').addEventListener('click', function(e) {
            if (e.target === this) closeReleaseModal();
        });
    </script>
</body>
</html>
