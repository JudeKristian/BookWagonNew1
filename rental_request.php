<?php
$currentPage = 'rental_request.php';
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Ensure only sellers can access this page
if ($userType !== 'seller' || !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Get the seller's record and shop name
$sellerStmt = $conn->prepare("SELECT id, shop_name, business_name FROM sellers WHERE user_id = ? LIMIT 1");
$sellerStmt->bind_param("i", $userId);
$sellerStmt->execute();
$sellerResult = $sellerStmt->get_result();
$sellerData = $sellerResult->fetch_assoc();

if (!$sellerData) {
    $_SESSION['error_message'] = "Seller profile not found.";
    header("Location: dashboard.php");
    exit();
}

$sellerId = (int)$sellerData['id'];
$shopName = !empty($sellerData['shop_name']) ? $sellerData['shop_name'] : ($sellerData['business_name'] ?? 'Seller');

// Process return request actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $returnId = intval($_POST['return_id'] ?? 0);

    // 1. Receive return (mark as received upon physical handover)
    if ($action === 'receive_return') {
        $receiveStmt = $conn->prepare("
            UPDATE book_returns 
            SET 
                status = 'received', 
                received_date = NOW()
            WHERE return_id = ? AND seller_id = ?
        ");
        $receiveStmt->bind_param("ii", $returnId, $sellerId);
        
        if ($receiveStmt->execute()) {
            $_SESSION['success_message'] = "Return #$returnId has been received. You can now inspect book condition.";
        } else {
            $_SESSION['error_message'] = "Failed to update return status.";
        }
        header("Location: rental_request.php");
        exit();
    }

    // 2. Inspect return & finalize completion
    if ($action === 'inspect_return') {
        $bookCondition = $_POST['book_condition'] ?? 'good';
        $damageDescription = trim($_POST['damage_description'] ?? '');
        $damageFee = floatval($_POST['damage_fee'] ?? 0);
        $additionalFee = floatval($_POST['additional_fee'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $lateFee = floatval($_POST['late_fee'] ?? 0);

        $conn->begin_transaction();

        try {
            // Update return details
            $updateReturnStmt = $conn->prepare("
                UPDATE book_returns 
                SET 
                    status = 'completed', 
                    book_condition = ?, 
                    damage_description = ?, 
                    damage_fee = ?,
                    additional_fee = ?,
                    late_fee = ?,
                    notes = ?,
                    completed_date = NOW()
                WHERE return_id = ? AND seller_id = ?
            ");
            $updateReturnStmt->bind_param(
                "ssdddsii", 
                $bookCondition, 
                $damageDescription, 
                $damageFee,
                $additionalFee,
                $lateFee,
                $notes,
                $returnId, 
                $sellerId
            );
            $updateReturnStmt->execute();

            // Update rental status to returned
            $updateRentalStmt = $conn->prepare("
                UPDATE book_rentals r
                JOIN book_returns br ON r.rental_id = br.rental_id
                SET 
                    r.status = 'returned', 
                    r.return_date = NOW(),
                    r.book_condition = ?,
                    r.late_fee = ?
                WHERE br.return_id = ?
            ");
            $updateRentalStmt->bind_param("sdi", $bookCondition, $lateFee, $returnId);
            $updateRentalStmt->execute();

            // Restock the book in inventory (+1 stock)
            $updateBookStmt = $conn->prepare("
                UPDATE books b
                JOIN book_returns br ON b.book_id = br.book_id
                SET b.stock = b.stock + 1
                WHERE br.return_id = ?
            ");
            $updateBookStmt->bind_param("i", $returnId);
            $updateBookStmt->execute();

            $conn->commit();
            $_SESSION['success_message'] = "Return #$returnId completed successfully! Condition recorded and inventory restocked.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to complete return: " . $e->getMessage();
        }

        header("Location: rental_request.php");
        exit();
    }
}

// Fetch all return requests for this seller
$returnsQuery = "
    SELECT 
        br.return_id,
        br.rental_id,
        br.return_method,
        br.return_details,
        br.status,
        br.request_date,
        br.received_date,
        br.completed_date,
        br.is_overdue,
        br.days_overdue,
        br.late_fee,
        br.additional_fee,
        br.damage_fee,
        br.book_condition,
        br.damage_description,
        br.notes,
        b.book_id,
        b.title as book_title,
        b.author as book_author,
        b.cover_image,
        b.ISBN,
        b.stock,
        b.rent_price,
        b.price as book_price,
        u.firstname as renter_firstname,
        u.lastname as renter_lastname,
        u.email as renter_email,
        u.phone as renter_phone,
        r.rental_weeks,
        r.total_price as rental_price,
        r.order_id
    FROM book_returns br
    JOIN book_rentals r ON br.rental_id = r.rental_id
    JOIN books b ON br.book_id = b.book_id
    JOIN users u ON br.user_id = u.id
    WHERE br.seller_id = ?
    ORDER BY br.request_date DESC
";

$stmt = $conn->prepare($returnsQuery);
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$result = $stmt->get_result();
$returnRequests = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$totalRequests = count($returnRequests);
$pendingCount = 0;
$receivedCount = 0;
$completedCount = 0;
$overdueCount = 0;

foreach ($returnRequests as $req) {
    $st = strtolower($req['status']);
    if ($st === 'pending') $pendingCount++;
    elseif ($st === 'received') $receivedCount++;
    elseif ($st === 'completed') $completedCount++;

    if (!empty($req['is_overdue'])) $overdueCount++;
}

// Helper to parse return_details JSON or plain string
function parseReturnInfo($detailsStr) {
    $data = [
        'dropoff_location' => '',
        'dropoff_notes' => '',
        'pickup_date' => '',
        'pickup_time' => '',
        'pickup_address' => '',
        'pickup_notes' => ''
    ];

    if (empty($detailsStr)) return $data;

    $json = json_decode($detailsStr, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        $data['dropoff_location'] = $json['dropoff_location'] ?? '';
        $data['dropoff_notes'] = $json['dropoff_notes'] ?? '';
        $data['pickup_date'] = $json['pickup_date'] ?? '';
        $data['pickup_time'] = $json['pickup_time'] ?? '';
        $data['pickup_address'] = $json['pickup_address'] ?? '';
        $data['pickup_notes'] = $json['pickup_notes'] ?? '';
    } else {
        $data['dropoff_location'] = $detailsStr;
    }

    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Requests - BookWagon</title>
    
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (for sidebar and navigation only) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bw-primary: #f8a100;
            --bw-primary-hover: #e09000;
            --bw-dark: #0f172a;
            --bw-muted: #64748b;
            --bw-border: #e9ecef;
            --bw-bg: #f8fafc;
            --bw-card: #ffffff;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bw-bg);
            color: var(--bw-dark);
            -webkit-font-smoothing: antialiased;
        }

        .seller-main {
            padding: 24px 30px;
            max-width: 1100px;
        }

        /* Header */
        .seller-header {
            margin-bottom: 20px;
        }

        .seller-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--bw-dark);
            margin: 0;
        }

        .seller-header p {
            font-size: 0.85rem;
            color: var(--bw-muted);
            margin: 4px 0 0 0;
        }

        /* Summary Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .seller-main { padding: 18px 16px; }
        }

        .stat-box {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            padding: 14px 16px;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--bw-muted);
            margin-bottom: 4px;
        }

        .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--bw-dark);
            line-height: 1;
        }

        /* Filter Card & Tabs */
        .filter-card {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }

        .nav-tabs-clean {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            border-bottom: none;
            padding-bottom: 6px;
        }

        .nav-tab-link {
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 500;
            border-radius: 6px;
            color: var(--bw-muted);
            background: transparent;
            border: none;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .nav-tab-link:hover {
            color: var(--bw-dark);
            background: #f1f5f9;
        }

        .nav-tab-link.active {
            background: var(--bw-dark);
            color: #ffffff;
            font-weight: 600;
        }

        .tab-count {
            font-size: 0.72rem;
            padding: 1px 6px;
            border-radius: 99px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 600;
            margin-left: 4px;
        }

        .nav-tab-link.active .tab-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .filter-controls {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }

        .form-control-sm, .form-select-sm {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.82rem;
            padding: 6px 10px;
        }

        .form-control-sm:focus, .form-select-sm:focus {
            border-color: var(--bw-primary);
            box-shadow: 0 0 0 2px rgba(248, 161, 0, 0.15);
        }

        /* Return Cards */
        .return-card {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            margin-bottom: 14px;
            overflow: hidden;
            transition: border-color 0.15s ease;
        }

        .return-card:hover {
            border-color: #cbd5e1;
        }

        .return-header {
            padding: 11px 18px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .return-number {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--bw-dark);
        }

        .return-tag {
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
            color: var(--bw-muted);
            padding: 2px 7px;
            border-radius: 4px;
        }

        .return-date {
            font-size: 0.78rem;
            color: var(--bw-muted);
            margin-left: 6px;
        }

        /* Clean Badges (Text First, Consistent) */
        .badge-clean {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: capitalize;
            display: inline-block;
        }

        .badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-received { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-completed { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-neutral { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* Renter Info Strip */
        .renter-strip {
            background: #fafbfc;
            border-bottom: 1px solid #f1f5f9;
            padding: 8px 18px;
            font-size: 0.79rem;
            color: var(--bw-muted);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }

        .renter-strip strong {
            color: var(--bw-dark);
            font-weight: 600;
        }

        /* Meet-up & Drop-off Callout */
        .meetup-callout {
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            padding: 7px 18px;
            font-size: 0.78rem;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .meetup-tag {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #bae6fd;
        }

        .meetup-note-text {
            color: #0f172a;
            font-weight: 500;
        }

        /* Return Item Body */
        .return-item-body {
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .item-cover {
            width: 44px;
            height: 58px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--bw-border);
            background: #f8fafc;
            flex-shrink: 0;
        }

        .item-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--bw-dark);
            margin-bottom: 2px;
        }

        .item-meta {
            font-size: 0.78rem;
            color: var(--bw-muted);
        }

        .item-fee {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--bw-dark);
        }

        /* Button Utilities */
        .btn-clean-primary {
            background: var(--bw-primary);
            color: #ffffff;
            border: none;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 13px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-clean-primary:hover {
            background: var(--bw-primary-hover);
            color: #ffffff;
        }

        .btn-clean-success {
            background: #059669;
            color: #ffffff;
            border: none;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 13px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-clean-success:hover {
            background: #047857;
            color: #ffffff;
        }

        .btn-clean-outline {
            background: #ffffff;
            color: var(--bw-dark);
            border: 1px solid #cbd5e1;
            font-size: 0.8rem;
            font-weight: 500;
            padding: 6px 13px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-clean-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: var(--bw-dark);
        }

        /* Condition Segment Buttons in Inspect Modal */
        .condition-btn-group {
            display: flex;
            gap: 8px;
        }

        .condition-btn {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s ease;
        }

        .condition-btn:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .condition-btn.active {
            border-color: #0f172a;
            background: #0f172a;
            color: #ffffff;
        }

        /* Return Details Modal Enhancements */
        .det-stepper-wrap {
            position: relative;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            margin-bottom: 14px;
        }

        .det-stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .det-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
        }

        .det-step-bubble {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.74rem;
            font-weight: 700;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }

        .det-step.completed .det-step-bubble {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        .det-step.active .det-step-bubble {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        .det-step-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--bw-dark);
            line-height: 1.2;
        }

        .det-step-time {
            font-size: 0.68rem;
            color: var(--bw-muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .det-stepper-bar {
            position: absolute;
            top: 25px;
            left: 18%;
            right: 18%;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }

        .det-stepper-fill {
            height: 100%;
            background: #059669;
            width: 0%;
            transition: width 0.3s ease;
        }

        .det-card-section {
            background: #ffffff;
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }

        .det-sec-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--bw-muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .det-meetup-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 9px 12px;
        }

        .det-fee-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            background: #fafbfc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
        }

        /* Empty State */
        .empty-state {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            padding: 45px 20px;
            text-align: center;
        }

        .empty-state h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--bw-dark);
            margin-bottom: 4px;
        }

        .empty-state p {
            font-size: 0.84rem;
            color: var(--bw-muted);
            margin-bottom: 14px;
        }
    </style>
</head>
<body>
    <!-- Seller Sidebar -->
    <?php include("include/seller_sidebar.php"); ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="seller-main">

            <!-- Alerts -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success py-2 px-3 mb-3 d-flex justify-content-between align-items-center" style="font-size: 0.84rem; border-radius: 6px;">
                    <div><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger py-2 px-3 mb-3 d-flex justify-content-between align-items-center" style="font-size: 0.84rem; border-radius: 6px;">
                    <div><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Header -->
            <div class="seller-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h2>Return Requests</h2>
                    <p>Review customer returns, verify meet-up handovers, inspect book condition, and restock rentals for <?php echo htmlspecialchars($shopName); ?>.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="renter.php" class="btn-clean-outline">View Rentals</a>
                    <a href="order.php" class="btn-clean-outline">View Orders</a>
                    <button onclick="window.print();" class="btn-clean-outline">Print</button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Total Requests</div>
                    <div class="stat-number"><?php echo $totalRequests; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Pending Handover</div>
                    <div class="stat-number <?php echo $pendingCount > 0 ? 'text-warning' : ''; ?>"><?php echo $pendingCount; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Received / Inspecting</div>
                    <div class="stat-number text-primary"><?php echo $receivedCount; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Completed</div>
                    <div class="stat-number text-success"><?php echo $completedCount; ?></div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="filter-card">
                <div class="nav-tabs-clean">
                    <button type="button" class="nav-tab-link active" data-tab="all">
                        All <span class="tab-count"><?php echo $totalRequests; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="pending">
                        Pending <span class="tab-count"><?php echo $pendingCount; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="received">
                        Received <span class="tab-count"><?php echo $receivedCount; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="overdue">
                        Overdue <span class="tab-count"><?php echo $overdueCount; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="completed">
                        Completed <span class="tab-count"><?php echo $completedCount; ?></span>
                    </button>
                </div>

                <div class="filter-controls">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6 col-sm-12">
                            <input type="text" id="searchReturns" class="form-control form-control-sm" placeholder="Search by Return ID, Rental ID, Order ID, Book, or Renter...">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <select id="methodFilter" class="form-select form-select-sm">
                                <option value="">All Handover Methods</option>
                                <option value="dropoff">Drop-off / Meet-up</option>
                                <option value="pickup">Courier Pickup</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6 text-md-end">
                            <span id="returnCountDisplay" class="text-muted" style="font-size: 0.8rem;">
                                Showing <?php echo $totalRequests; ?> request<?php echo $totalRequests != 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Returns List -->
            <div id="returnsListContainer">
                <?php if (empty($returnRequests)): ?>
                    <div class="empty-state">
                        <h4>No Return Requests</h4>
                        <p>There are no return requests recorded for your store yet.</p>
                        <a href="renter.php" class="btn-clean-primary">View Active Rentals</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($returnRequests as $req): 
                        $status = strtolower($req['status']);
                        $isOverdue = !empty($req['is_overdue']);
                        $parsed = parseReturnInfo($req['return_details']);
                        
                        $searchHaystack = strtolower(
                            $req['return_id'] . ' ' .
                            $req['rental_id'] . ' ' .
                            ($req['order_id'] ?? '') . ' ' .
                            $req['book_title'] . ' ' .
                            $req['book_author'] . ' ' .
                            $req['renter_firstname'] . ' ' .
                            $req['renter_lastname'] . ' ' .
                            $req['renter_email'] . ' ' .
                            $req['return_method'] . ' ' .
                            $parsed['dropoff_location'] . ' ' .
                            $parsed['dropoff_notes']
                        );

                        // Format details modal data JSON payload
                        $modalData = json_encode([
                            'return_id' => $req['return_id'],
                            'rental_id' => $req['rental_id'],
                            'order_id' => $req['order_id'] ?? '—',
                            'status' => $status,
                            'status_text' => ucwords(str_replace('_', ' ', $req['status'])),
                            'request_date' => date('M j, Y, g:i a', strtotime($req['request_date'])),
                            'received_date' => !empty($req['received_date']) ? date('M j, Y, g:i a', strtotime($req['received_date'])) : null,
                            'completed_date' => !empty($req['completed_date']) ? date('M j, Y, g:i a', strtotime($req['completed_date'])) : null,
                            'book_title' => $req['book_title'],
                            'book_author' => $req['book_author'],
                            'cover_image' => !empty($req['cover_image']) ? $req['cover_image'] : 'img/default-book-cover.jpg',
                            'ISBN' => $req['ISBN'] ?? '—',
                            'stock' => $req['stock'],
                            'rental_weeks' => $req['rental_weeks'],
                            'rental_price' => number_format((float)$req['rental_price'], 2),
                            'renter_name' => $req['renter_firstname'] . ' ' . $req['renter_lastname'],
                            'renter_email' => $req['renter_email'],
                            'renter_phone' => $req['renter_phone'] ?? '—',
                            'return_method' => ucfirst($req['return_method']),
                            'is_overdue' => $isOverdue ? 1 : 0,
                            'days_overdue' => (int)$req['days_overdue'],
                            'late_fee' => number_format((float)$req['late_fee'], 2),
                            'damage_fee' => number_format((float)$req['damage_fee'], 2),
                            'additional_fee' => number_format((float)$req['additional_fee'], 2),
                            'book_condition' => !empty($req['book_condition']) ? ucfirst($req['book_condition']) : '—',
                            'damage_description' => $req['damage_description'] ?? '',
                            'notes' => $req['notes'] ?? '',
                            'dropoff_location' => $parsed['dropoff_location'],
                            'dropoff_notes' => $parsed['dropoff_notes'],
                            'pickup_date' => $parsed['pickup_date'],
                            'pickup_time' => $parsed['pickup_time'],
                            'pickup_address' => $parsed['pickup_address']
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                    ?>
                        <div class="return-card"
                             data-return-id="<?php echo $req['return_id']; ?>"
                             data-status="<?php echo $status; ?>"
                             data-is-overdue="<?php echo $isOverdue ? '1' : '0'; ?>"
                             data-method="<?php echo strtolower($req['return_method']); ?>"
                             data-search="<?php echo htmlspecialchars($searchHaystack); ?>">
                            
                            <!-- Header -->
                            <div class="return-header">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="return-number">Return #<?php echo $req['return_id']; ?></span>
                                    <span class="return-tag">Rental #<?php echo $req['rental_id']; ?></span>
                                    <?php if (!empty($req['order_id'])): ?>
                                        <span class="return-tag">Order #<?php echo $req['order_id']; ?></span>
                                    <?php endif; ?>
                                    <span class="return-date">Requested <?php echo date('M j, Y, g:i a', strtotime($req['request_date'])); ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isOverdue): ?>
                                        <span class="badge-clean badge-danger">Overdue (<?php echo $req['days_overdue']; ?>d)</span>
                                    <?php endif; ?>

                                    <?php if ($status === 'pending'): ?>
                                        <span class="badge-clean badge-pending">Pending Handover</span>
                                    <?php elseif ($status === 'received'): ?>
                                        <span class="badge-clean badge-received">Received (Awaiting Inspection)</span>
                                    <?php elseif ($status === 'completed'): ?>
                                        <span class="badge-clean badge-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="badge-clean badge-neutral"><?php echo ucfirst($status); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Renter Info Strip -->
                            <div class="renter-strip">
                                <div><strong>Renter:</strong> <?php echo htmlspecialchars($req['renter_firstname'] . ' ' . $req['renter_lastname']); ?></div>
                                <div><strong>Email:</strong> <?php echo htmlspecialchars($req['renter_email']); ?></div>
                                <?php if (!empty($req['renter_phone'])): ?>
                                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($req['renter_phone']); ?></div>
                                <?php endif; ?>
                                <div><strong>Method:</strong> <?php echo $req['return_method'] === 'dropoff' ? 'Drop-off / Meet-up' : 'Courier Pickup'; ?></div>
                                <div><strong>Rental:</strong> <?php echo $req['rental_weeks']; ?> wk<?php echo $req['rental_weeks'] > 1 ? 's' : ''; ?></div>
                            </div>

                            <!-- Meet-up & Drop-off Details Strip -->
                            <?php if (!empty($parsed['dropoff_location']) || !empty($parsed['dropoff_notes']) || !empty($parsed['pickup_address'])): ?>
                                <div class="meetup-callout">
                                    <?php if ($req['return_method'] === 'dropoff'): ?>
                                        <?php if (stripos($parsed['dropoff_location'], 'campus') !== false): ?>
                                            <span class="meetup-tag">Campus Meet-up</span>
                                        <?php else: ?>
                                            <span class="meetup-tag">Drop-off Place</span>
                                        <?php endif; ?>
                                        
                                        <span><strong>Location:</strong> <?php echo htmlspecialchars($parsed['dropoff_location']); ?></span>
                                        
                                        <?php if (!empty($parsed['dropoff_notes'])): ?>
                                            <span class="ms-md-2 meetup-note-text">
                                                <strong>Note:</strong> <?php echo htmlspecialchars($parsed['dropoff_notes']); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="meetup-tag">Courier Pickup</span>
                                        <span><strong>Date:</strong> <?php echo htmlspecialchars($parsed['pickup_date']); ?> (<?php echo htmlspecialchars($parsed['pickup_time']); ?>)</span>
                                        <span><strong>Address:</strong> <?php echo htmlspecialchars($parsed['pickup_address']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Item Body -->
                            <div class="return-item-body">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo !empty($req['cover_image']) ? htmlspecialchars($req['cover_image']) : 'img/default-book-cover.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($req['book_title']); ?>" 
                                         class="item-cover"
                                         onerror="this.src='img/default-book-cover.jpg'">
                                    <div>
                                        <div class="item-title"><?php echo htmlspecialchars($req['book_title']); ?></div>
                                        <div class="item-meta">
                                            by <?php echo htmlspecialchars($req['book_author']); ?>
                                            <?php if (!empty($req['ISBN'])): ?> · ISBN: <?php echo htmlspecialchars($req['ISBN']); ?><?php endif; ?>
                                            · Stock: <?php echo $req['stock']; ?>
                                        </div>
                                        <div class="mt-1 d-flex align-items-center gap-2">
                                            <span class="item-fee">Fee: ₱<?php echo number_format((float)$req['rental_price'], 2); ?></span>
                                            <?php if ($status === 'completed' && !empty($req['book_condition'])): ?>
                                                <span class="badge-clean badge-neutral ms-1">Condition: <?php echo ucfirst($req['book_condition']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <?php if ($status === 'pending'): ?>
                                        <!-- Step 1: Mark received upon physical book handover -->
                                        <form action="rental_request.php" method="POST" class="d-inline" onsubmit="return confirm('Confirm receipt of book physical handover? You will inspect condition next.');">
                                            <input type="hidden" name="action" value="receive_return">
                                            <input type="hidden" name="return_id" value="<?php echo $req['return_id']; ?>">
                                            <button type="submit" class="btn-clean-success">
                                                Receive Book
                                            </button>
                                        </form>
                                    <?php elseif ($status === 'received'): ?>
                                        <!-- Step 2: Inspect & finalize condition assessment -->
                                        <button type="button" class="btn-clean-primary" onclick="openInspectModal(<?php echo htmlspecialchars($modalData, ENT_QUOTES, 'UTF-8'); ?>)">
                                            Inspect & Complete
                                        </button>
                                    <?php endif; ?>

                                    <!-- View Details Button -->
                                    <button type="button" class="btn-clean-outline" onclick="openDetailsModal(<?php echo htmlspecialchars($modalData, ENT_QUOTES, 'UTF-8'); ?>)">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ======================================================== -->
    <!-- Inspect Return Modal (Clean, Simple & Modern) -->
    <!-- ======================================================== -->
    <div class="modal fade" id="inspectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                <div class="modal-header py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 1rem; color: #0f172a;">Inspect & Finalize Return</h5>
                        <div class="text-muted" style="font-size: 0.76rem;" id="inspectSubtitle">Return #</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                </div>
                <form action="rental_request.php" method="POST" id="inspectForm">
                    <input type="hidden" name="action" value="inspect_return">
                    <input type="hidden" name="return_id" id="inspect_return_id" value="">
                    
                    <div class="modal-body px-4 py-3">
                        <!-- Book Preview -->
                        <div class="d-flex align-items-center gap-3 p-2-5 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <img src="" id="inspect_cover" alt="Book" style="width: 44px; height: 58px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; flex-shrink: 0;" onerror="this.src='img/default-book-cover.jpg'">
                            <div style="flex: 1; min-width: 0;">
                                <div class="fw-bold text-truncate" id="inspect_book_title" style="font-size: 0.88rem; color: #0f172a;"></div>
                                <div class="text-muted" id="inspect_book_author" style="font-size: 0.77rem;"></div>
                                <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                    Renter: <span id="inspect_renter_name" class="fw-semibold text-dark"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Condition Selector Buttons (Text-First, No bloat) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-2" style="font-size: 0.78rem; color: #0f172a; text-transform: uppercase;">
                                Condition Assessment
                            </label>
                            <div class="condition-btn-group">
                                <button type="button" class="condition-btn" data-condition="excellent" onclick="setCondition('excellent')">
                                    Excellent
                                </button>
                                <button type="button" class="condition-btn active" data-condition="good" onclick="setCondition('good')">
                                    Good
                                </button>
                                <button type="button" class="condition-btn" data-condition="fair" onclick="setCondition('fair')">
                                    Fair
                                </button>
                                <button type="button" class="condition-btn" data-condition="damaged" onclick="setCondition('damaged')">
                                    Damaged
                                </button>
                            </div>
                            <input type="hidden" name="book_condition" id="book_condition_input" value="good">
                            <div class="text-muted mt-1" id="condition_desc" style="font-size: 0.74rem;">
                                Good: Normal gentle wear, complete pages, intact binding.
                            </div>
                        </div>

                        <!-- Damage Description (shown for fair / damaged or custom) -->
                        <div class="mb-3" id="damage_desc_wrap">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">
                                Damage Notes (if any)
                            </label>
                            <input type="text" class="form-control form-control-sm" name="damage_description" id="damage_description" placeholder="e.g. Spine crease, torn page 12, water mark" style="font-size: 0.82rem;">
                        </div>

                        <!-- Fee Breakdown -->
                        <div class="p-3 mb-3" style="background: #fafbfc; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <div class="fw-bold mb-2" style="font-size: 0.78rem; color: #0f172a; text-transform: uppercase;">
                                Fee Breakdown
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label text-muted mb-1" style="font-size: 0.75rem;">Damage Fee (₱)</label>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="damage_fee" id="damage_fee_input" value="0.00" oninput="calcTotalFees()">
                                </div>
                                <div class="col-4">
                                    <label class="form-label text-muted mb-1" style="font-size: 0.75rem;">Late Fee (₱)</label>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm bg-light" name="late_fee" id="late_fee_input" value="0.00" readonly>
                                </div>
                                <div class="col-4">
                                    <label class="form-label text-muted mb-1" style="font-size: 0.75rem;">Other Fee (₱)</label>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="additional_fee" id="additional_fee_input" value="0.00" oninput="calcTotalFees()">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-semibold text-dark" style="font-size: 0.82rem;">Total Fees to Apply:</span>
                                <span class="fw-bold text-danger" style="font-size: 0.95rem;">₱<span id="total_fees_display">0.00</span></span>
                            </div>
                        </div>

                        <!-- Notes / Remarks -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">
                                Inspection Remarks (Optional)
                            </label>
                            <textarea class="form-control form-control-sm" name="notes" id="inspect_notes" rows="2" placeholder="Internal remarks regarding this return..." style="font-size: 0.82rem;"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm text-white fw-bold px-3">
                            Complete Return & Restock (+1)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- View Details Modal (Clean, Informative & Modern) -->
    <!-- ======================================================== -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                <div class="modal-header py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 1.05rem; color: #0f172a;" id="detTitle">Return Details</h5>
                            <span id="detStatusBadge" class="badge-clean badge-pending py-0">Pending</span>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.76rem;" id="detSubtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                </div>
                <div class="modal-body px-4 py-3" style="font-size: 0.82rem;">
                    
                    <!-- Connected Progress Stepper -->
                    <div class="det-stepper-wrap">
                        <div class="det-stepper-bar">
                            <div class="det-stepper-fill" id="detStepperFill"></div>
                        </div>
                        <div class="det-stepper">
                            <!-- Step 1 -->
                            <div class="det-step completed" id="detStep1">
                                <div class="det-step-bubble">1</div>
                                <div class="det-step-name">Requested</div>
                                <div class="det-step-time" id="detStepTime1">—</div>
                            </div>
                            <!-- Step 2 -->
                            <div class="det-step" id="detStep2">
                                <div class="det-step-bubble">2</div>
                                <div class="det-step-name">Book Handover</div>
                                <div class="det-step-time" id="detStepTime2">Pending</div>
                            </div>
                            <!-- Step 3 -->
                            <div class="det-step" id="detStep3">
                                <div class="det-step-bubble">3</div>
                                <div class="det-step-name">Inspection & Close</div>
                                <div class="det-step-time" id="detStepTime3">Pending</div>
                            </div>
                        </div>
                    </div>

                    <!-- Book Information Card -->
                    <div class="det-card-section">
                        <div class="det-sec-label">Book Information</div>
                        <div class="d-flex align-items-start gap-3">
                            <img src="" id="det_cover" alt="Book" style="width: 52px; height: 72px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; flex-shrink: 0;" onerror="this.src='img/default-book-cover.jpg'">
                            <div style="flex: 1; min-width: 0;">
                                <div class="fw-bold text-truncate" id="det_book_title" style="font-size: 0.92rem; color: #0f172a;"></div>
                                <div class="text-muted mb-2" id="det_book_author" style="font-size: 0.78rem;"></div>
                                <div class="d-flex flex-wrap gap-2" style="font-size: 0.75rem;">
                                    <span class="badge bg-light text-dark border">ISBN: <strong id="det_isbn"></strong></span>
                                    <span class="badge bg-light text-dark border">Stock: <strong id="det_stock"></strong></span>
                                    <span class="badge bg-light text-dark border">Period: <strong id="det_weeks"></strong></span>
                                    <span class="badge bg-light text-dark border">Fee: ₱<strong id="det_fee"></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Handover Destination & Meet-up Notes -->
                    <div class="det-card-section">
                        <div class="det-sec-label">
                            <span>Handover & Meet-up Destination</span>
                            <span id="detMethodBadge" class="badge bg-light text-dark border"></span>
                        </div>
                        <div style="line-height: 1.45;">
                            <div>
                                <span class="text-muted">Place:</span>
                                <strong class="text-dark" id="det_loc_text"></strong>
                            </div>
                        </div>

                        <!-- Highlighted Meet-up Note Box -->
                        <div id="det_notes_wrap" class="det-meetup-box mt-2" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-uppercase" style="font-size: 0.72rem; color: #b45309; letter-spacing: 0.5px;">Student Meet-up Note</span>
                                <span class="badge bg-white text-dark border" style="font-size: 0.68rem;">Campus Handover</span>
                            </div>
                            <div class="fw-semibold text-dark" style="font-size: 0.83rem;" id="det_notes_text"></div>
                            <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                Present or scan the buyer's return QR during meet-up for immediate verification.
                            </div>
                        </div>
                    </div>

                    <!-- Customer Profile Section -->
                    <div class="det-card-section">
                        <div class="det-sec-label">Renter Details</div>
                        <div class="row g-2">
                            <div class="col-sm-5">
                                <div class="text-muted" style="font-size: 0.72rem;">Name</div>
                                <div class="fw-semibold text-dark" id="det_renter_name"></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted" style="font-size: 0.72rem;">Email</div>
                                <div class="text-truncate" id="det_renter_email" style="font-size: 0.78rem;"></div>
                            </div>
                            <div class="col-sm-3">
                                <div class="text-muted" style="font-size: 0.72rem;">Phone</div>
                                <div id="det_renter_phone" style="font-size: 0.78rem;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Assessment Breakdown (Shown when completed) -->
                    <div id="det_completed_panel" class="det-card-section" style="display: none;">
                        <div class="det-sec-label">Condition & Fee Settlement</div>
                        <div class="det-fee-grid text-center mb-2">
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Condition</div>
                                <div class="fw-bold text-dark" id="det_cond"></div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Damage Fee</div>
                                <div class="fw-bold text-danger">₱<span id="det_dam_fee">0.00</span></div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Late Fee</div>
                                <div class="fw-bold text-danger">₱<span id="det_late_fee">0.00</span></div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Additional</div>
                                <div class="fw-bold text-danger">₱<span id="det_add_fee">0.00</span></div>
                            </div>
                        </div>
                        <div id="det_remarks_wrap" class="p-2 border rounded" style="background: #ffffff; display: none;">
                            <span class="text-muted" style="font-size: 0.72rem;">Remarks:</span>
                            <span class="text-dark" id="det_remarks" style="font-size: 0.78rem;"></span>
                        </div>
                    </div>

                </div>

                <!-- Footer with Contextual Actions -->
                <div class="modal-footer px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Close</button>
                    
                    <!-- Contextual Form: Receive Book if pending -->
                    <form action="rental_request.php" method="POST" id="det_receive_form" style="display: none;" onsubmit="return confirm('Confirm receipt of book physical handover?');">
                        <input type="hidden" name="action" value="receive_return">
                        <input type="hidden" name="return_id" id="det_receive_return_id" value="">
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                            Receive Book Handover
                        </button>
                    </form>

                    <!-- Contextual Button: Inspect Book if received -->
                    <button type="button" class="btn btn-warning btn-sm text-white fw-bold px-3" id="det_inspect_btn" style="display: none;" onclick="switchFromDetailsToInspect()">
                        Inspect & Complete Return
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live Search & Tab Filtering
            const searchInput = document.getElementById('searchReturns');
            const methodFilter = document.getElementById('methodFilter');
            const tabButtons = document.querySelectorAll('.nav-tab-link');
            const countDisplay = document.getElementById('returnCountDisplay');
            const cards = document.querySelectorAll('.return-card');

            let currentTab = 'all';

            function filterCards() {
                const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
                const selectedMethod = methodFilter ? methodFilter.value : '';
                let visibleCount = 0;

                cards.forEach(card => {
                    const status = card.getAttribute('data-status');
                    const isOverdue = card.getAttribute('data-is-overdue') === '1';
                    const method = card.getAttribute('data-method');
                    const searchData = card.getAttribute('data-search') || '';

                    let matchTab = false;
                    if (currentTab === 'all') matchTab = true;
                    else if (currentTab === 'pending') matchTab = (status === 'pending');
                    else if (currentTab === 'received') matchTab = (status === 'received');
                    else if (currentTab === 'completed') matchTab = (status === 'completed');
                    else if (currentTab === 'overdue') matchTab = isOverdue;

                    const matchMethod = (!selectedMethod || method === selectedMethod);
                    const matchQuery = (!query || searchData.includes(query));

                    if (matchTab && matchMethod && matchQuery) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (countDisplay) {
                    countDisplay.textContent = `Showing ${visibleCount} request${visibleCount !== 1 ? 's' : ''}`;
                }
            }

            // Tab click handlers
            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentTab = this.getAttribute('data-tab');
                    filterCards();
                });
            });

            // Input handlers
            if (searchInput) searchInput.addEventListener('input', filterCards);
            if (methodFilter) methodFilter.addEventListener('change', filterCards);

            // ========================================================
            // Condition Assessment Helper in Inspect Modal
            // ========================================================
            const conditionDescriptions = {
                'excellent': 'Excellent: Flawless, like new with crisp pages and no markings.',
                'good': 'Good: Normal gentle wear, complete pages, intact binding.',
                'fair': 'Fair: Noticeable page creases or slight edge scuffs, fully readable.',
                'damaged': 'Damaged: Torn/missing pages, water stain, or broken binding (subject to damage fee).'
            };

            window.setCondition = function(cond) {
                document.getElementById('book_condition_input').value = cond;
                document.querySelectorAll('.condition-btn').forEach(btn => {
                    if (btn.getAttribute('data-condition') === cond) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

                const descEl = document.getElementById('condition_desc');
                if (descEl) descEl.textContent = conditionDescriptions[cond] || '';

                // Suggested damage fee based on condition
                const damFeeInput = document.getElementById('damage_fee_input');
                if (damFeeInput) {
                    if (cond === 'damaged') {
                        if (parseFloat(damFeeInput.value) === 0) damFeeInput.value = '100.00';
                    } else if (cond === 'fair') {
                        if (parseFloat(damFeeInput.value) === 0) damFeeInput.value = '30.00';
                    } else {
                        damFeeInput.value = '0.00';
                    }
                }
                calcTotalFees();
            };

            window.calcTotalFees = function() {
                const dam = parseFloat(document.getElementById('damage_fee_input')?.value || 0) || 0;
                const late = parseFloat(document.getElementById('late_fee_input')?.value || 0) || 0;
                const add = parseFloat(document.getElementById('additional_fee_input')?.value || 0) || 0;
                const total = dam + late + add;
                const display = document.getElementById('total_fees_display');
                if (display) display.textContent = total.toFixed(2);
            };

            // Open Inspect Modal
            const inspectModalEl = document.getElementById('inspectModal');
            const inspectBsModal = inspectModalEl ? new bootstrap.Modal(inspectModalEl) : null;

            window.openInspectModal = function(data) {
                document.getElementById('inspect_return_id').value = data.return_id;
                document.getElementById('inspectSubtitle').textContent = `Return #${data.return_id} · Rental #${data.rental_id}`;
                document.getElementById('inspect_cover').src = data.cover_image || 'img/default-book-cover.jpg';
                document.getElementById('inspect_book_title').textContent = data.book_title;
                document.getElementById('inspect_book_author').textContent = data.book_author ? `by ${data.book_author}` : '';
                document.getElementById('inspect_renter_name').textContent = data.renter_name;
                
                // Overdue & Late fee
                const lateInput = document.getElementById('late_fee_input');
                if (lateInput) {
                    lateInput.value = data.late_fee || '0.00';
                }

                // Reset inputs
                setCondition('good');
                document.getElementById('damage_fee_input').value = '0.00';
                document.getElementById('additional_fee_input').value = '0.00';
                document.getElementById('damage_description').value = '';
                document.getElementById('inspect_notes').value = '';
                calcTotalFees();

                if (inspectBsModal) inspectBsModal.show();
            };

            // Open Details Modal
            const detailsModalEl = document.getElementById('detailsModal');
            const detailsBsModal = detailsModalEl ? new bootstrap.Modal(detailsModalEl) : null;
            let currentDetailsData = null;

            window.switchFromDetailsToInspect = function() {
                if (detailsBsModal) detailsBsModal.hide();
                setTimeout(() => {
                    if (currentDetailsData) openInspectModal(currentDetailsData);
                }, 250);
            };

            window.openDetailsModal = function(data) {
                currentDetailsData = data;
                
                // Header
                document.getElementById('detTitle').textContent = `Return #${data.return_id}`;
                document.getElementById('detSubtitle').textContent = `Order #${data.order_id} · Rental #${data.rental_id} · Requested: ${data.request_date}`;
                
                // Status badge in header
                const statusBadge = document.getElementById('detStatusBadge');
                if (data.is_overdue) {
                    statusBadge.className = 'badge-clean badge-danger py-0';
                    statusBadge.textContent = `Overdue (${data.days_overdue}d)`;
                } else if (data.status === 'pending') {
                    statusBadge.className = 'badge-clean badge-pending py-0';
                    statusBadge.textContent = 'Pending Handover';
                } else if (data.status === 'received') {
                    statusBadge.className = 'badge-clean badge-received py-0';
                    statusBadge.textContent = 'Received (Awaiting Inspection)';
                } else if (data.status === 'completed') {
                    statusBadge.className = 'badge-clean badge-completed py-0';
                    statusBadge.textContent = 'Completed & Restocked';
                } else {
                    statusBadge.className = 'badge-clean badge-neutral py-0';
                    statusBadge.textContent = data.status_text;
                }

                // 3-Step Stepper logic
                const fillBar = document.getElementById('detStepperFill');
                const step1 = document.getElementById('detStep1');
                const step2 = document.getElementById('detStep2');
                const step3 = document.getElementById('detStep3');
                
                document.getElementById('detStepTime1').textContent = data.request_date.split(',')[0];

                if (data.status === 'pending') {
                    fillBar.style.width = '20%';
                    step1.className = 'det-step completed';
                    step2.className = 'det-step active';
                    step3.className = 'det-step';
                    document.getElementById('detStepTime2').textContent = 'Pending Handover';
                    document.getElementById('detStepTime3').textContent = 'Pending';
                } else if (data.status === 'received') {
                    fillBar.style.width = '60%';
                    step1.className = 'det-step completed';
                    step2.className = 'det-step completed';
                    step3.className = 'det-step active';
                    document.getElementById('detStepTime2').textContent = data.received_date ? data.received_date.split(',')[0] : 'Received';
                    document.getElementById('detStepTime3').textContent = 'Inspecting';
                } else if (data.status === 'completed') {
                    fillBar.style.width = '100%';
                    step1.className = 'det-step completed';
                    step2.className = 'det-step completed';
                    step3.className = 'det-step completed';
                    document.getElementById('detStepTime2').textContent = data.received_date ? data.received_date.split(',')[0] : 'Received';
                    document.getElementById('detStepTime3').textContent = data.completed_date ? data.completed_date.split(',')[0] : 'Restocked';
                }

                // Book info
                document.getElementById('det_cover').src = data.cover_image || 'img/default-book-cover.jpg';
                document.getElementById('det_book_title').textContent = data.book_title;
                document.getElementById('det_book_author').textContent = data.book_author ? `by ${data.book_author}` : '';
                document.getElementById('det_isbn').textContent = data.ISBN;
                document.getElementById('det_stock').textContent = data.stock;
                document.getElementById('det_weeks').textContent = `${data.rental_weeks} wk${data.rental_weeks > 1 ? 's' : ''}`;
                document.getElementById('det_fee').textContent = data.rental_price;

                // Handover destination & note
                const methodBadge = document.getElementById('detMethodBadge');
                const locText = document.getElementById('det_loc_text');
                const notesWrap = document.getElementById('det_notes_wrap');
                const notesText = document.getElementById('det_notes_text');

                if (data.return_method.toLowerCase() === 'dropoff') {
                    if (data.dropoff_location && data.dropoff_location.toLowerCase().includes('campus')) {
                        methodBadge.textContent = 'Campus Meet-up';
                        methodBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle';
                    } else {
                        methodBadge.textContent = 'Drop-off';
                        methodBadge.className = 'badge bg-light text-dark border';
                    }
                    locText.textContent = data.dropoff_location || 'Campus Meet-up';
                    
                    if (data.dropoff_notes && data.dropoff_notes.trim().length > 0) {
                        notesWrap.style.display = 'block';
                        notesText.textContent = data.dropoff_notes;
                    } else {
                        notesWrap.style.display = 'none';
                    }
                } else {
                    methodBadge.textContent = 'Courier Pickup';
                    methodBadge.className = 'badge bg-info-subtle text-info-emphasis border border-info-subtle';
                    locText.textContent = `${data.pickup_address} (Scheduled: ${data.pickup_date} · ${data.pickup_time})`;
                    notesWrap.style.display = 'none';
                }

                // Customer info
                document.getElementById('det_renter_name').textContent = data.renter_name;
                const emailEl = document.getElementById('det_renter_email');
                emailEl.textContent = data.renter_email;
                
                const phoneEl = document.getElementById('det_renter_phone');
                if (data.renter_phone && data.renter_phone !== '—') {
                    phoneEl.textContent = data.renter_phone;
                } else {
                    phoneEl.textContent = 'Not provided';
                }

                // Completed panel
                const compPanel = document.getElementById('det_completed_panel');
                if (data.status === 'completed') {
                    compPanel.style.display = 'block';
                    document.getElementById('det_cond').textContent = data.book_condition;
                    document.getElementById('det_dam_fee').textContent = data.damage_fee;
                    document.getElementById('det_late_fee').textContent = data.late_fee;
                    document.getElementById('det_add_fee').textContent = data.additional_fee;
                    
                    const remarksWrap = document.getElementById('det_remarks_wrap');
                    const remarksText = document.getElementById('det_remarks');
                    const desc = [data.damage_description, data.notes].filter(Boolean).join(' · ');
                    if (desc) {
                        remarksWrap.style.display = 'block';
                        remarksText.textContent = desc;
                    } else {
                        remarksWrap.style.display = 'none';
                    }
                } else {
                    compPanel.style.display = 'none';
                }

                // Contextual footer buttons
                const receiveForm = document.getElementById('det_receive_form');
                const inspectBtn = document.getElementById('det_inspect_btn');
                
                if (data.status === 'pending') {
                    receiveForm.style.display = 'inline-block';
                    document.getElementById('det_receive_return_id').value = data.return_id;
                    inspectBtn.style.display = 'none';
                } else if (data.status === 'received') {
                    receiveForm.style.display = 'none';
                    inspectBtn.style.display = 'inline-block';
                } else {
                    receiveForm.style.display = 'none';
                    inspectBtn.style.display = 'none';
                }

                if (detailsBsModal) detailsBsModal.show();
            };
        });
    </script>
</body>
</html>