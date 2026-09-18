<?php
$currentPage = 'renter.php';
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Ensure only sellers can access this page
if ($userType !== 'seller') {
    header("Location: login.php");
    exit();
}

// Get the seller's record
$sellerStmt = $conn->prepare("SELECT id, shop_name FROM sellers WHERE user_id = ? LIMIT 1");
$sellerStmt->bind_param("i", $userId);
$sellerStmt->execute();
$sellerResult = $sellerStmt->get_result();
$sellerData = $sellerResult->fetch_assoc();

// If no seller found, exit
if (!$sellerData) {
    $_SESSION['error_message'] = "Seller profile not found.";
    header("Location: dashboard.php");
    exit();
}

$sellerDbId = (int)$sellerData['id'];
$shopName = $sellerData['shop_name'] ?? 'Seller';

// Fetch rental records with book and renter details
$rentalQuery = "
    SELECT 
        br.rental_id,
        br.rental_date,
        br.due_date,
        br.return_date,
        br.rental_weeks,
        br.status,
        br.total_price,
        br.order_id,
        br.book_condition,
        br.return_notes,
        b.book_id,
        b.title as book_title,
        b.author as book_author,
        b.cover_image,
        b.ISBN,
        u.firstname as renter_firstname,
        u.lastname as renter_lastname,
        u.email as renter_email,
        u.phone as renter_phone
    FROM book_rentals br
    JOIN books b ON br.book_id = b.book_id
    JOIN users u ON br.user_id = u.id
    WHERE (br.seller_id = ? OR br.seller_id = ?)
    ORDER BY br.rental_date DESC
";

$stmt = $conn->prepare($rentalQuery);
$stmt->bind_param("ii", $sellerDbId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$rentals = $result->fetch_all(MYSQLI_ASSOC);

// Calculate rental statistics
$totalRentals = count($rentals);
$activeRentals = 0;
$overdueRentals = 0;
$returnedRentals = 0;
$totalRentalRevenue = 0;

foreach ($rentals as $rental) {
    $totalRentalRevenue += (float)$rental['total_price'];
    $isOverdue = (strtolower($rental['status']) === 'active' && strtotime($rental['due_date']) < time());
    
    if ($isOverdue) {
        $overdueRentals++;
    }
    
    if (strtolower($rental['status']) === 'active') {
        $activeRentals++;
    } elseif (strtolower($rental['status']) === 'returned') {
        $returnedRentals++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentals - BookWagon</title>
    
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (for sidebar and UI elements) -->
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

        /* Rental Cards */
        .rental-card {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            margin-bottom: 14px;
            overflow: hidden;
            transition: border-color 0.15s ease;
        }

        .rental-card:hover {
            border-color: #cbd5e1;
        }

        .rental-header {
            padding: 11px 18px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .rental-number {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--bw-dark);
        }

        .rental-order-tag {
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
            color: var(--bw-muted);
            padding: 2px 7px;
            border-radius: 4px;
        }

        .rental-date {
            font-size: 0.78rem;
            color: var(--bw-muted);
            margin-left: 6px;
        }

        /* Badges */
        .badge-clean {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: capitalize;
            display: inline-block;
        }

        .badge-active { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-returned { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-info { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* Buyer / Renter Info Strip */
        .buyer-strip {
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

        .buyer-strip strong {
            color: var(--bw-dark);
            font-weight: 600;
        }

        /* Rental Item Body */
        .rental-item-body {
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

        .tag-type {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 3px;
            background: #fef3c7;
            color: #92400e;
        }

        .item-fee {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--bw-dark);
        }

        .rental-note-strip {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 7px 18px;
            font-size: 0.77rem;
            color: var(--bw-muted);
        }

        .rental-note-strip strong {
            color: var(--bw-dark);
        }

        /* Clean Buttons */
        .btn-clean-primary {
            background: var(--bw-primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 5px 13px;
            border-radius: 5px;
            border: none;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-clean-primary:hover {
            background: var(--bw-primary-hover);
            color: #ffffff;
        }

        .btn-clean-outline {
            background: #ffffff;
            color: var(--bw-dark);
            border: 1px solid #cbd5e1;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 4px 11px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-clean-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: var(--bw-dark);
        }

        /* Empty State */
        .empty-state {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
        }

        .empty-state h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--bw-dark);
            margin-bottom: 4px;
        }

        .empty-state p {
            font-size: 0.82rem;
            color: var(--bw-muted);
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Seller Sidebar -->
    <?php include("include/seller_sidebar.php"); ?>

    <!-- Main Content -->
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
                    <h2>Rentals</h2>
                    <p>Track active book rentals, return schedules, and process returns for <?php echo htmlspecialchars($shopName); ?>.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="order.php" class="btn-clean-outline">View Orders</a>
                    <button onclick="window.print();" class="btn-clean-outline">Print</button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Total Rentals</div>
                    <div class="stat-number"><?php echo $totalRentals; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Active Rentals</div>
                    <div class="stat-number"><?php echo $activeRentals; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Overdue</div>
                    <div class="stat-number <?php echo $overdueRentals > 0 ? 'text-danger' : ''; ?>"><?php echo $overdueRentals; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Rental Revenue</div>
                    <div class="stat-number text-success">₱<?php echo number_format($totalRentalRevenue, 2); ?></div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="filter-card">
                <div class="nav-tabs-clean">
                    <button type="button" class="nav-tab-link active" data-tab="all">
                        All <span class="tab-count"><?php echo $totalRentals; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="active">
                        Active <span class="tab-count"><?php echo $activeRentals; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="overdue">
                        Overdue <span class="tab-count"><?php echo $overdueRentals; ?></span>
                    </button>
                    <button type="button" class="nav-tab-link" data-tab="returned">
                        Returned <span class="tab-count"><?php echo $returnedRentals; ?></span>
                    </button>
                </div>

                <div class="filter-controls">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5 col-sm-12">
                            <input type="text" id="searchRentals" class="form-control form-control-sm" placeholder="Search by Rental ID, Order ID, Book, or Renter...">
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <select id="weeksFilter" class="form-select form-select-sm">
                                <option value="">All Rental Periods</option>
                                <option value="1">1 Week</option>
                                <option value="2-4">2 - 4 Weeks</option>
                                <option value="5+">5+ Weeks</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6 text-md-end">
                            <span id="rentalCountDisplay" class="text-muted" style="font-size: 0.8rem;">
                                Showing <?php echo $totalRentals; ?> rental<?php echo $totalRentals != 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rentals List -->
            <div id="rentalsContainer">
                <?php if (empty($rentals)): ?>
                    <div class="empty-state">
                        <h4>No Rental Records</h4>
                        <p>You do not have any active or past book rentals yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rentals as $rental): 
                        $status = strtolower($rental['status']);
                        $isOverdue = ($status === 'active' && strtotime($rental['due_date']) < time());
                        $daysOverdue = 0;
                        if ($isOverdue) {
                            $diff = time() - strtotime($rental['due_date']);
                            $daysOverdue = max(1, (int)floor($diff / (60 * 60 * 24)));
                        }
                        
                        $searchHaystack = strtolower(
                            $rental['rental_id'] . ' ' . 
                            $rental['order_id'] . ' ' . 
                            $rental['book_title'] . ' ' . 
                            $rental['book_author'] . ' ' . 
                            $rental['renter_firstname'] . ' ' . 
                            $rental['renter_lastname'] . ' ' . 
                            $rental['renter_email'] . ' ' . 
                            ($rental['ISBN'] ?? '')
                        );
                    ?>
                        <div class="rental-card"
                             data-rental-id="<?php echo $rental['rental_id']; ?>"
                             data-order-id="<?php echo $rental['order_id']; ?>"
                             data-status="<?php echo $status; ?>"
                             data-is-overdue="<?php echo $isOverdue ? '1' : '0'; ?>"
                             data-weeks="<?php echo (int)$rental['rental_weeks']; ?>"
                             data-search="<?php echo htmlspecialchars($searchHaystack); ?>">
                            
                            <!-- Header -->
                            <div class="rental-header">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="rental-number">Rental #<?php echo $rental['rental_id']; ?></span>
                                    <?php if (!empty($rental['order_id'])): ?>
                                        <span class="rental-order-tag">Order #<?php echo $rental['order_id']; ?></span>
                                    <?php endif; ?>
                                    <span class="rental-date">Rented <?php echo date('M j, Y, g:i a', strtotime($rental['rental_date'])); ?></span>
                                </div>
                                <div>
                                    <?php if ($status === 'return_pending'): ?>
                                        <span class="badge-clean badge-warning">Return Pending</span>
                                    <?php elseif ($isOverdue): ?>
                                        <span class="badge-clean badge-danger">Overdue (<?php echo $daysOverdue; ?>d)</span>
                                    <?php elseif ($status === 'active'): ?>
                                        <span class="badge-clean badge-active">Active</span>
                                    <?php elseif ($status === 'returned'): ?>
                                        <span class="badge-clean badge-returned">Returned</span>
                                    <?php else: ?>
                                        <span class="badge-clean badge-info"><?php echo ucfirst($status); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Renter Info Strip -->
                            <div class="buyer-strip">
                                <div><strong>Renter:</strong> <?php echo htmlspecialchars($rental['renter_firstname'] . ' ' . $rental['renter_lastname']); ?></div>
                                <div><strong>Email:</strong> <?php echo htmlspecialchars($rental['renter_email']); ?></div>
                                <?php if (!empty($rental['renter_phone'])): ?>
                                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($rental['renter_phone']); ?></div>
                                <?php endif; ?>
                                <div><strong>Period:</strong> <?php echo $rental['rental_weeks']; ?> wk<?php echo $rental['rental_weeks'] > 1 ? 's' : ''; ?></div>
                                <div>
                                    <strong>Due Date:</strong> 
                                    <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo date('M j, Y', strtotime($rental['due_date'])); ?>
                                    </span>
                                </div>
                                <?php if ($status === 'returned' && !empty($rental['return_date'])): ?>
                                    <div><strong>Returned:</strong> <?php echo date('M j, Y', strtotime($rental['return_date'])); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Rental Item Body -->
                            <div class="rental-item-body">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo !empty($rental['cover_image']) ? htmlspecialchars($rental['cover_image']) : 'img/default-book-cover.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($rental['book_title']); ?>" 
                                         class="item-cover"
                                         onerror="this.src='img/default-book-cover.jpg'">
                                    <div>
                                        <div class="item-title"><?php echo htmlspecialchars($rental['book_title']); ?></div>
                                        <div class="item-meta">
                                            by <?php echo htmlspecialchars($rental['book_author']); ?>
                                            <?php if (!empty($rental['ISBN'])): ?> · ISBN: <?php echo htmlspecialchars($rental['ISBN']); ?><?php endif; ?>
                                        </div>
                                        <div class="mt-1 d-flex align-items-center gap-2">
                                            <span class="tag-type">Rental</span>
                                            <span class="item-fee">Fee: ₱<?php echo number_format($rental['total_price'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="rental-actions d-flex align-items-center gap-2">
                                    <?php if ($status === 'active'): ?>
                                        <?php if ($isOverdue): ?>
                                            <button type="button" class="btn-clean-outline text-danger" data-bs-toggle="modal" data-bs-target="#overdueModal<?php echo $rental['rental_id']; ?>">
                                                Contact Renter
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($status === 'return_pending'): ?>
                                            <button type="button" class="btn-clean-primary" style="background-color: #f8a100;" data-bs-toggle="modal" data-bs-target="#scanReturnModal" onclick="initReturnScan(<?php echo $rental['rental_id']; ?>)">
                                                <i class="fas fa-qrcode"></i> Scan Return QR
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn-clean-outline text-muted" style="font-size: 0.75rem; padding: 4px 8px;" data-bs-toggle="modal" data-bs-target="#returnBookModal<?php echo $rental['rental_id']; ?>">
                                            Manual Return
                                        </button>
                                    <?php elseif ($status === 'returned'): ?>
                                        <div class="text-end">
                                            <div class="text-success" style="font-size: 0.8rem; font-weight: 600;">Returned</div>
                                            <?php if (!empty($rental['book_condition'])): ?>
                                                <div class="text-muted" style="font-size: 0.74rem;">
                                                    Condition: <?php echo ucfirst(htmlspecialchars($rental['book_condition'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Notes strip if return verification notes exist -->
                            <?php if (!empty($rental['return_notes'])): ?>
                                <div class="rental-note-strip">
                                    <strong>Return Note:</strong> <?php echo htmlspecialchars($rental['return_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Return Book Modal -->
                        <div class="modal fade" id="returnBookModal<?php echo $rental['rental_id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
                                <div class="modal-content" style="border-radius: 10px; border: 1px solid var(--bw-border); box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                                    <form action="process_rental.php" method="POST">
                                        <input type="hidden" name="action" value="mark_returned">
                                        <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                        
                                        <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #f1f5f9;">
                                            <h5 class="modal-title" style="font-size: 1rem; font-weight: 700; color: var(--bw-dark); margin: 0;">Mark Book as Returned</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                                        </div>
                                        
                                        <div class="modal-body p-4">
                                            <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid var(--bw-border); border-radius: 6px;">
                                                <div style="font-size: 0.88rem; font-weight: 600; color: var(--bw-dark);"><?php echo htmlspecialchars($rental['book_title']); ?></div>
                                                <div style="font-size: 0.78rem; color: var(--bw-muted); margin-top: 2px;">
                                                    Renter: <?php echo htmlspecialchars($rental['renter_firstname'] . ' ' . $rental['renter_lastname']); ?> · <?php echo $rental['rental_weeks']; ?> wk rental
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--bw-dark);">Book Condition on Return</label>
                                                <select name="book_condition" class="form-select form-select-sm" required>
                                                    <option value="good" selected>Good — Normal wear, all pages intact</option>
                                                    <option value="excellent">Excellent — Like new, pristine</option>
                                                    <option value="fair">Fair — Visible wear/creases, but readable</option>
                                                    <option value="damaged">Damaged — Marked, torn, or damaged</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-1">
                                                <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--bw-dark);">Return Remarks / Inspection Notes (Optional)</label>
                                                <textarea name="return_notes" rows="2" class="form-control form-control-sm" placeholder="e.g., Verified return inspection, book received in good condition..."></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="modal-footer py-2 px-4" style="border-top: 1px solid #f1f5f9;">
                                            <button type="button" class="btn-clean-outline" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn-clean-primary">Confirm Return</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Overdue Notice Modal -->
                        <div class="modal fade" id="overdueModal<?php echo $rental['rental_id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
                                <div class="modal-content" style="border-radius: 10px; border: 1px solid var(--bw-border); box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                                    <form action="process_rental.php" method="POST">
                                        <input type="hidden" name="action" value="contact_renter">
                                        <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                        
                                        <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #f1f5f9;">
                                            <h5 class="modal-title" style="font-size: 1rem; font-weight: 700; color: var(--bw-dark); margin: 0;">Overdue Notice</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                                        </div>
                                        
                                        <div class="modal-body p-4">
                                            <div class="p-3 mb-3" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; font-size: 0.82rem; color: #991b1b;">
                                                This rental is overdue by <strong><?php echo $daysOverdue; ?> day<?php echo $daysOverdue != 1 ? 's' : ''; ?></strong>.
                                            </div>
                                            <div style="font-size: 0.84rem; line-height: 1.6; color: var(--bw-dark);">
                                                <div><strong>Book:</strong> <?php echo htmlspecialchars($rental['book_title']); ?></div>
                                                <div><strong>Renter:</strong> <?php echo htmlspecialchars($rental['renter_firstname'] . ' ' . $rental['renter_lastname']); ?></div>
                                                <div><strong>Email:</strong> <?php echo htmlspecialchars($rental['renter_email']); ?></div>
                                                <div><strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($rental['due_date'])); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="modal-footer py-2 px-4" style="border-top: 1px solid #f1f5f9;">
                                            <button type="button" class="btn-clean-outline" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn-clean-primary" style="background: #dc2626;">Send Reminder</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Filter Empty State (Hidden by default) -->
            <div id="filterEmptyState" class="empty-state d-none">
                <h4>No matching rentals</h4>
                <p>No book rentals match your selected filter criteria.</p>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchRentals');
            const weeksFilter = document.getElementById('weeksFilter');
            const tabButtons = document.querySelectorAll('.nav-tab-link');
            const rentalCards = document.querySelectorAll('.rental-card');
            const countDisplay = document.getElementById('rentalCountDisplay');
            const filterEmptyState = document.getElementById('filterEmptyState');

            let currentTab = 'all';

            function applyFilters() {
                const query = (searchInput.value || '').trim().toLowerCase();
                const selectedWeeks = weeksFilter.value;
                let visibleCount = 0;

                rentalCards.forEach(card => {
                    const status = card.getAttribute('data-status');
                    const isOverdue = card.getAttribute('data-is-overdue') === '1';
                    const weeks = parseInt(card.getAttribute('data-weeks') || '0', 10);
                    const searchData = card.getAttribute('data-search') || '';

                    // Tab matching
                    let matchesTab = true;
                    if (currentTab === 'active') {
                        matchesTab = (status === 'active');
                    } else if (currentTab === 'overdue') {
                        matchesTab = isOverdue;
                    } else if (currentTab === 'returned') {
                        matchesTab = (status === 'returned');
                    }

                    // Weeks matching
                    let matchesWeeks = true;
                    if (selectedWeeks === '1') {
                        matchesWeeks = (weeks === 1);
                    } else if (selectedWeeks === '2-4') {
                        matchesWeeks = (weeks >= 2 && weeks <= 4);
                    } else if (selectedWeeks === '5+') {
                        matchesWeeks = (weeks >= 5);
                    }

                    // Search matching
                    let matchesSearch = true;
                    if (query) {
                        matchesSearch = searchData.includes(query);
                    }

                    if (matchesTab && matchesWeeks && matchesSearch) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Update count text
                if (countDisplay) {
                    countDisplay.textContent = 'Showing ' + visibleCount + ' rental' + (visibleCount !== 1 ? 's' : '');
                }

                // Empty state display
                if (filterEmptyState) {
                    if (visibleCount === 0 && rentalCards.length > 0) {
                        filterEmptyState.classList.remove('d-none');
                    } else {
                        filterEmptyState.classList.add('d-none');
                    }
                }
            }

            // Tab button clicks
            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentTab = this.getAttribute('data-tab');
                    applyFilters();
                });
            });

            // Input & select events
            if (searchInput) {
                searchInput.addEventListener('input', applyFilters);
            }
            if (weeksFilter) {
                weeksFilter.addEventListener('change', applyFilters);
            }

            // Optional: URL parameter support (e.g. ?tab=active or ?tab=overdue)
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab') || urlParams.get('status');
            if (tabParam) {
                const targetBtn = document.querySelector(`.nav-tab-link[data-tab="${tabParam}"]`);
                if (targetBtn) {
                    targetBtn.click();
                }
            }
        });
    </script>
    <!-- Scan Return Modal -->
    <div class="modal fade" id="scanReturnModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid var(--bw-border);">
                <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-size: 1rem; font-weight: 700;">Scan Renter's Return QR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopReturnScanner()" style="font-size: 0.75rem;"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="text-muted small mb-3">Scan the Return QR code presented by the Renter to finalize the return process.</p>
                    <div id="return-qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                    <div id="return-qr-result" class="mt-3" style="display: none;">
                        <div class="alert alert-success fw-semibold"><i class="fas fa-check-circle me-1"></i> QR Scanned! Verifying...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scripts -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5ReturnScanner = null;
        let activeReturnRentalId = null;

        function initReturnScan(rentalId) {
            activeReturnRentalId = rentalId;
            document.getElementById('return-qr-result').style.display = 'none';
            document.getElementById('return-qr-reader').style.display = 'block';
            
            if (!html5ReturnScanner) {
                html5ReturnScanner = new Html5QrcodeScanner(
                    "return-qr-reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
                html5ReturnScanner.render(onReturnScanSuccess, onReturnScanError);
            }
        }

        function stopReturnScanner() {
            if (html5ReturnScanner) {
                html5ReturnScanner.clear();
                html5ReturnScanner = null;
            }
        }

        function onReturnScanSuccess(decodedText, decodedResult) {
            try {
                const data = JSON.parse(decodedText);
                if (data.cert === "BOOKWAGON_RENTAL_RETURN_RECEIPT" && data.rental_id == activeReturnRentalId) {
                    stopReturnScanner();
                    document.getElementById('return-qr-reader').style.display = 'none';
                    document.getElementById('return-qr-result').style.display = 'block';
                    
                    // Redirect to process the return scanning
                    window.location.href = `process_return_seller.php?action=scan_return&rental_id=${data.rental_id}&token=${data.token}`;
                } else {
                    alert("Invalid QR code. Please ensure it is the correct Return QR code for this rental.");
                }
            } catch (e) {
                alert("Invalid QR code format.");
            }
        }

        function onReturnScanError(errorMessage) {}
    </script>
</body>
</html>