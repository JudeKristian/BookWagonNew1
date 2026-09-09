<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Fetch return requests and rental history
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
        br.damage_description,
        br.book_condition,
        br.notes,
        b.title as book_title,
        b.author as book_author,
        b.cover_image,
        b.ISBN,
        sel.shop_name as seller_shop,
        COALESCE(NULLIF(sel.first_name, ''), s.firstname) as seller_firstname,
        COALESCE(NULLIF(sel.last_name, ''), s.lastname) as seller_lastname,
        COALESCE(NULLIF(sel.business_email, ''), s.email) as seller_email,
        COALESCE(NULLIF(sel.business_phone, ''), s.phone) as seller_phone,
        r.rental_weeks,
        r.total_price
    FROM book_returns br
    JOIN book_rentals r ON br.rental_id = r.rental_id
    JOIN books b ON br.book_id = b.book_id
    LEFT JOIN sellers sel ON br.seller_id = sel.id
    LEFT JOIN users s ON COALESCE(sel.user_id, b.user_id) = s.id
    WHERE br.user_id = ?
    ORDER BY br.request_date DESC
";

$returnsStmt = $conn->prepare($returnsQuery);
$returnsStmt->bind_param("i", $userId);
$returnsStmt->execute();
$returnsResult = $returnsStmt->get_result();
$returns = $returnsResult->fetch_all(MYSQLI_ASSOC);

// Fetch completed orders and rentals
$historyQuery = "
    SELECT 
        CASE 
            WHEN oi.purchase_type = 'rent' THEN 'rental'
            ELSE 'purchase'
        END as type,
        o.order_id as id,
        o.order_date as date,
        NULL as return_date,
        b.title,
        b.author,
        b.cover_image,
        oi.unit_price as total_price,
        oi.rental_weeks,
        o.order_status as status
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN books b ON oi.book_id = b.book_id
    WHERE o.user_id = ?
    ORDER BY date DESC
";

$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $userId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$history = $historyResult->fetch_all(MYSQLI_ASSOC);

// Determine active tab
$activeTab = $_GET['tab'] ?? 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order & Rental History - BookWagon</title>
    <!-- Bootstrap CSS -->
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/tab.css">
    
    <style>
        /* Similar styles to rented_books.php */
        :root {
            --primary-color: #f8a100;
            --secondary-color: #f8f9fa;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            background-color: #f8fafc;
        }

        .navbar {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand img {
            height: 60px;
        }

        .sidebar {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px 0;
            min-height: calc(100vh - 150px);
            position: sticky;
            top: 20px;
        }

        .sidebar-link {
            display: block;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background-color: rgba(0, 123, 255, 0.05);
            color: #4a6cf7;
            border-left: 3px solid #4a6cf7;
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .history-card {
            background-color: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .history-body {
            display: flex;
            padding: 15px;
        }

        .history-image {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-returned {
            background-color: #e8f5e9;
            color: #4caf50;
        }

        .status-completed {
            background-color: #e0f2f1;
            color: #009688;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #f44336;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ff9800;
        }

        .status-in-transit {
            background-color: #e3f2fd;
            color: #2196f3;
        }

        .empty-state {
            text-align: center;
            padding: 60px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .history-tabs {
            display: flex;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .history-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            color: #6c757d;
            text-decoration: none;
            position: relative;
        }

        .history-tab.active {
            color: #f8a100;
        }

        .history-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #f8a100;
        }

        /* Clean Status Badges */
        .badge-clean {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }

        .badge-clean.badge-pending {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-clean.badge-in-transit {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .badge-clean.badge-completed {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-clean.badge-cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-clean.badge-neutral {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* Return Details Modal Modern Styling */
        .det-modal-dialog {
            max-width: 580px;
        }

        .det-modal-content {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .det-stepper-wrap {
            position: relative;
            padding: 14px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
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
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 4px;
            transition: all 0.25s ease;
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
            color: #0f172a;
            line-height: 1.2;
        }

        .det-step-time {
            font-size: 0.68rem;
            color: #64748b;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .det-stepper-bar {
            position: absolute;
            top: 28px;
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
            transition: width 0.35s ease;
        }

        .det-card-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 13px 15px;
            margin-bottom: 12px;
        }

        .det-sec-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .det-meetup-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .det-fee-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
        }

        .btn-clean-outline {
            background: #ffffff;
            color: #0f172a;
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
            color: #0f172a;
        }
    </style>
</head>

<body>
    <?php include("include/user_header.php"); ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar Column -->
            <div class="col-lg-3 col-md-4 mb-4">
                <?php include("include/user_sidebar.php"); ?>
            </div>

            <!-- Main Content Column -->
            <div class="col-lg-9 col-md-8">
                <h2 class="mb-4" style="font-weight: 700; color: #0f172a;">Order & Rental History</h2>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- History Tabs -->
                <div class="history-tabs">
                    <a href="?tab=all" class="history-tab <?php echo $activeTab == 'all' ? 'active' : ''; ?>">
                        All History
                    </a>
                    <a href="?tab=purchases"
                        class="history-tab <?php echo $activeTab == 'purchases' ? 'active' : ''; ?>">
                        Purchases
                    </a>
                    <a href="?tab=rentals" class="history-tab <?php echo $activeTab == 'rentals' ? 'active' : ''; ?>">
                        Rentals
                    </a>
                    <a href="?tab=returns" class="history-tab <?php echo $activeTab == 'returns' ? 'active' : ''; ?>">
                        Returns
                    </a>
                </div>

                <!-- History Content -->
                <?php
                // Determine which items to display based on active tab
                $displayItems = [];
                $emptyMessage = "No history items found.";

                switch ($activeTab) {
                    case 'purchases':
                        $displayItems = array_filter($history, function ($item) {
                            return $item['type'] == 'purchase';
                        });
                        $emptyMessage = "You haven't completed any purchases yet.";
                        break;
                    case 'rentals':
                        $displayItems = array_filter($history, function ($item) {
                            return $item['type'] == 'rental';
                        });
                        $emptyMessage = "You have no completed or cancelled rentals.";
                        break;
                    case 'returns':
                        $displayItems = $returns;
                        $emptyMessage = "You have no return requests.";
                        break;
                    default: // 'all'
                        $displayItems = array_merge($history, $returns);
                        $emptyMessage = "You have no history items.";
                        break;
                }
                ?>

                <?php if (empty($displayItems)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <h4><?php echo $emptyMessage; ?></h4>
                        <p class="text-muted">Your past orders, rentals, and returns will appear here.</p>
                        <a href="rentbooks.php" class="btn btn-primary mt-3">Start Browsing</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($displayItems as $item): ?>
                        <div class="history-card">
                            <div class="history-header">
                                <div>
                                    <?php if (isset($item['type']) && $item['type'] == 'rental'): ?>
                                        <span class="text-muted">Rental</span>
                                    <?php elseif (isset($item['type']) && $item['type'] == 'purchase'): ?>
                                        <span class="text-muted">Purchase</span>
                                    <?php else: ?>
                                        <span class="text-muted">Return Request</span>
                                    <?php endif; ?>
                                    <span class="ms-2 order-number">
                                        #<?php echo $item['id'] ?? $item['return_id']; ?>
                                    </span>
                                </div>

                                <?php
                                // Determine status badge
                                $statusBadgeClass = '';
                                $statusText = '';

                                if (isset($item['status'])) {
                                    switch (strtolower($item['status'])) {
                                        case 'returned':
                                        case 'completed':
                                            $statusBadgeClass = 'status-completed';
                                            $statusText = 'Completed';
                                            break;
                                        case 'cancelled':
                                            $statusBadgeClass = 'status-cancelled';
                                            $statusText = 'Cancelled';
                                            break;
                                        case 'pending':
                                            $statusBadgeClass = 'status-pending';
                                            $statusText = 'Pending';
                                            break;
                                        case 'in_transit':
                                            $statusBadgeClass = 'status-in-transit';
                                            $statusText = 'In Transit';
                                            break;
                                        default:
                                            $statusBadgeClass = 'status-pending';
                                            $statusText = ucfirst($item['status']);
                                    }
                                } elseif (isset($item['return_id'])) {
                                    // For return requests
                                    switch (strtolower($item['status'])) {
                                        case 'pending':
                                            $statusBadgeClass = 'status-pending';
                                            $statusText = 'Pending';
                                            break;
                                        case 'in_transit':
                                            $statusBadgeClass = 'status-in-transit';
                                            $statusText = 'In Transit';
                                            break;
                                        case 'completed':
                                            $statusBadgeClass = 'status-completed';
                                            $statusText = 'Completed';
                                            break;
                                        default:
                                            $statusBadgeClass = 'status-pending';
                                            $statusText = ucfirst($item['status']);
                                    }
                                }
                                ?>

                                <div class="status-badge <?php echo $statusBadgeClass; ?>">
                                    <?php echo $statusText; ?>
                                </div>
                            </div>

                            <div class="history-body">
                                <img src="<?php echo $item['cover_image'] ?? ''; ?>"
                                    alt="<?php echo $item['title'] ?? $item['book_title']; ?>" class="history-image">

                                <div class="flex-grow-1">
                                    <h5 class="mb-2">
                                        <?php echo $item['title'] ?? $item['book_title']; ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        by <?php echo $item['author'] ?? $item['book_author']; ?>
                                    </p>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <?php if (isset($item['type']) && $item['type'] == 'rental'): ?>
                                                <p class="mb-1">
                                                    <strong>Rental Period:</strong>
                                                    <?php echo $item['rental_weeks']; ?>
                                                    week<?php echo $item['rental_weeks'] > 1 ? 's' : ''; ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (isset($item['return_id'])): ?>
                                                <p class="mb-1">
                                                    <strong>Return Method:</strong>
                                                    <?php echo ucfirst($item['return_method']); ?>
                                                </p>

                                                <?php if ($item['status'] == 'completed' && $item['book_condition']): ?>
                                                    <p class="mb-1">
                                                        <strong>Book Condition:</strong>
                                                        <span class="
                                                        <?php
                                                        switch ($item['book_condition']) {
                                                            case 'excellent':
                                                            case 'good':
                                                                echo 'text-success';
                                                                break;
                                                            case 'fair':
                                                                echo 'text-warning';
                                                                break;
                                                            case 'damaged':
                                                                echo 'text-danger';
                                                                break;
                                                            default:
                                                                echo '';
                                                        }
                                                        ?>">
                                                            <?php echo ucfirst($item['book_condition']); ?>
                                                        </span>
                                                    </p>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <p class="mb-1">
                                                <strong>Date:</strong>
                                                <?php
                                                echo isset($item['date'])
                                                    ? date('F j, Y', strtotime($item['date']))
                                                    : date('F j, Y', strtotime($item['request_date']));
                                                ?>
                                            </p>
                                        </div>

                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Total:</strong>
                                                ₱<?php echo number_format($item['total_price'], 2); ?>
                                            </p>

                                            <?php if (isset($item['return_id'])): ?>
                                                <?php
                                                $totalFees = 0;
                                                if (isset($item['late_fee']))
                                                    $totalFees += $item['late_fee'];
                                                if (isset($item['damage_fee']))
                                                    $totalFees += $item['damage_fee'];
                                                if (isset($item['additional_fee']))
                                                    $totalFees += $item['additional_fee'];

                                                if ($totalFees > 0):
                                                    ?>
                                                    <p class="mb-1">
                                                        <strong>Additional Fees:</strong>
                                                        <span class="text-danger">₱<?php echo number_format($totalFees, 2); ?></span>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($item['received_date']): ?>
                                                    <p class="mb-1">
                                                        <strong>Received:</strong>
                                                        <?php echo date('F j, Y', strtotime($item['received_date'])); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($item['completed_date']): ?>
                                                    <p class="mb-1">
                                                        <strong>Completed:</strong>
                                                        <?php echo date('F j, Y', strtotime($item['completed_date'])); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <!-- View Details Button for Returns -->
                                                <button type="button" class="btn-clean-outline return-details-btn mt-2"
                                                    data-bs-toggle="modal" data-bs-target="#returnDetailsModal"
                                                    data-return-id="<?php echo $item['return_id']; ?>"
                                                    data-rental-id="<?php echo $item['rental_id'] ?? ''; ?>"
                                                    data-book-title="<?php echo htmlspecialchars($item['book_title']); ?>"
                                                    data-book-author="<?php echo htmlspecialchars($item['book_author']); ?>"
                                                    data-cover-image="<?php echo $item['cover_image']; ?>"
                                                    data-isbn="<?php echo htmlspecialchars($item['ISBN'] ?? '—'); ?>"
                                                    data-rental-weeks="<?php echo $item['rental_weeks'] ?? '1'; ?>"
                                                    data-rental-price="<?php echo number_format($item['total_price'] ?? 0, 2); ?>"
                                                    data-seller-name="<?php echo htmlspecialchars(trim(($item['seller_firstname'] ?? '') . ' ' . ($item['seller_lastname'] ?? '')) ?: ($item['seller_shop'] ?? 'Book Owner')); ?>"
                                                    data-seller-shop="<?php echo htmlspecialchars($item['seller_shop'] ?? ''); ?>"
                                                    data-seller-email="<?php echo htmlspecialchars($item['seller_email'] ?? ''); ?>"
                                                    data-seller-phone="<?php echo htmlspecialchars($item['seller_phone'] ?? ''); ?>"
                                                    data-status="<?php echo $item['status']; ?>"
                                                    data-method="<?php echo $item['return_method']; ?>"
                                                    data-details="<?php echo htmlspecialchars($item['return_details']); ?>"
                                                    data-request-date="<?php echo $item['request_date']; ?>"
                                                    data-received-date="<?php echo $item['received_date']; ?>"
                                                    data-completed-date="<?php echo $item['completed_date']; ?>"
                                                    data-condition="<?php echo $item['book_condition']; ?>"
                                                    data-damage="<?php echo htmlspecialchars($item['damage_description'] ?? ''); ?>"
                                                    data-late-fee="<?php echo $item['late_fee']; ?>"
                                                    data-damage-fee="<?php echo $item['damage_fee']; ?>"
                                                    data-additional-fee="<?php echo $item['additional_fee']; ?>"
                                                    data-is-overdue="<?php echo $item['is_overdue']; ?>"
                                                    data-days-overdue="<?php echo $item['days_overdue']; ?>"
                                                    data-notes="<?php echo htmlspecialchars($item['notes'] ?? ''); ?>">
                                                    <i class="fas fa-eye"></i> View Return Details
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- ======================================================== -->
    <!-- View Return Request Details Modal (Clean, Informative & Modern) -->
    <!-- ======================================================== -->
    <div class="modal fade" id="returnDetailsModal" tabindex="-1" aria-labelledby="returnDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered det-modal-dialog">
            <div class="modal-content det-modal-content">
                <!-- Modal Header -->
                <div class="modal-header py-3 px-4" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 1.05rem; color: #0f172a;" id="returnDetailsModalLabel">Return Request Details</h5>
                            <span id="return_status_badge" class="badge-clean badge-pending">Pending</span>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.76rem;">
                            Return #<strong class="text-dark" id="return_request_id"></strong> &bull; 
                            Rental #<strong class="text-dark" id="return_rental_id"></strong> &bull; 
                            <span id="return_request_date"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
                </div>

                <div class="modal-body px-4 py-3" style="font-size: 0.82rem; background: #fafbfc;">
                    
                    <!-- Connected Progress Stepper -->
                    <div class="det-stepper-wrap">
                        <div class="det-stepper-bar">
                            <div class="det-stepper-fill" id="detStepperFill"></div>
                        </div>
                        <div class="det-stepper">
                            <!-- Step 1 -->
                            <div class="det-step completed" id="detStep1">
                                <div class="det-step-bubble"><i class="fas fa-check" style="font-size: 0.68rem;"></i></div>
                                <div class="det-step-name">Requested</div>
                                <div class="det-step-time" id="detStepTime1">—</div>
                            </div>
                            <!-- Step 2 -->
                            <div class="det-step" id="detStep2">
                                <div class="det-step-bubble" id="detStepBubble2">2</div>
                                <div class="det-step-name">Book Handover</div>
                                <div class="det-step-time" id="detStepTime2">Pending</div>
                            </div>
                            <!-- Step 3 -->
                            <div class="det-step" id="detStep3">
                                <div class="det-step-bubble" id="detStepBubble3">3</div>
                                <div class="det-step-name">Inspection & Settlement</div>
                                <div class="det-step-time" id="detStepTime3">Pending</div>
                            </div>
                        </div>
                    </div>

                    <!-- Contextual Status Tip Banner -->
                    <div id="status_tip_banner" class="alert py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.78rem; border-radius: 8px;">
                        <i id="status_tip_icon" class="fas fa-info-circle"></i>
                        <span id="status_tip_text"></span>
                    </div>

                    <!-- Book Information Card -->
                    <div class="det-card-section">
                        <div class="det-sec-label">Book Information</div>
                        <div class="d-flex align-items-start gap-3">
                            <img src="" id="return_book_image" alt="Book Cover" style="width: 52px; height: 72px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; flex-shrink: 0;" onerror="this.src='img/default-book-cover.jpg'">
                            <div style="flex: 1; min-width: 0;">
                                <div class="fw-bold text-truncate" id="return_book_title" style="font-size: 0.92rem; color: #0f172a;"></div>
                                <div class="text-muted mb-2" id="return_book_author" style="font-size: 0.78rem;"></div>
                                <div class="d-flex flex-wrap gap-2" style="font-size: 0.75rem;">
                                    <span class="badge bg-light text-dark border">ISBN: <strong id="return_isbn">—</strong></span>
                                    <span class="badge bg-light text-dark border">Period: <strong id="return_weeks">—</strong></span>
                                    <span class="badge bg-light text-dark border">Rental Fee: ₱<strong id="return_total_fee">0.00</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Handover Destination & Return Method Card -->
                    <div class="det-card-section">
                        <div class="det-sec-label">
                            <span>Handover & Return Method</span>
                            <span id="return_method_badge" class="badge bg-light text-dark border"></span>
                        </div>
                        <div id="return_method_content" style="line-height: 1.5;">
                            <!-- Injected by JavaScript -->
                        </div>

                        <!-- Highlighted Meet-up Note Box -->
                        <div id="return_notes_wrap" class="det-meetup-box mt-2" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-uppercase" style="font-size: 0.72rem; color: #b45309; letter-spacing: 0.5px;">
                                    <i class="fas fa-comment-alt me-1"></i> Meet-up / Handover Instructions
                                </span>
                                <span class="badge bg-white text-dark border" style="font-size: 0.68rem;">Handover Note</span>
                            </div>
                            <div class="fw-semibold text-dark" style="font-size: 0.83rem;" id="return_notes_text"></div>
                        </div>
                    </div>

                    <!-- Book Owner / Seller Contact Card -->
                    <div class="det-card-section">
                        <div class="det-sec-label">
                            <span>Book Owner / Seller Details</span>
                            <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">Rented From</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-5">
                                <div class="text-muted" style="font-size: 0.72rem;">Owner / Shop</div>
                                <div class="fw-semibold text-dark text-truncate" id="seller_name">Book Owner</div>
                                <div class="text-muted text-truncate" id="seller_shop_name" style="font-size: 0.72rem; display: none;"></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted" style="font-size: 0.72rem;">Email</div>
                                <div class="text-truncate">
                                    <a href="#" id="seller_email" class="text-decoration-none text-dark fw-semibold" style="font-size: 0.78rem;">—</a>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="text-muted" style="font-size: 0.72rem;">Phone</div>
                                <div class="text-truncate">
                                    <a href="#" id="seller_phone" class="text-decoration-none text-dark fw-semibold" style="font-size: 0.78rem;">—</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Condition Assessment & Fee Settlement Section (Shown when completed or assessed) -->
                    <div id="condition_assessment_card" class="det-card-section" style="display: none;">
                        <div class="det-sec-label">
                            <span>Condition Assessment & Settlement</span>
                            <span id="return_condition_badge" class="badge"></span>
                        </div>

                        <!-- Overdue Banner if overdue -->
                        <div id="overdue_alert_banner" class="alert alert-warning py-1 px-2 mb-2 d-flex align-items-center justify-content-between" style="font-size: 0.75rem; display: none;">
                            <span><i class="fas fa-exclamation-triangle text-warning me-1"></i> Returned Past Rental Due Date</span>
                            <span class="badge bg-danger text-white" id="overdue_days_text"></span>
                        </div>

                        <!-- Fee Grid -->
                        <div class="det-fee-grid text-center mb-2">
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Condition</div>
                                <div class="fw-bold text-dark" id="disp_condition">—</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Damage Fee</div>
                                <div class="fw-bold" id="disp_damage_fee">₱0.00</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Late Fee</div>
                                <div class="fw-bold" id="disp_late_fee">₱0.00</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem;">Additional</div>
                                <div class="fw-bold" id="disp_add_fee">₱0.00</div>
                            </div>
                        </div>

                        <!-- Total Fees Highlight if > 0 -->
                        <div id="total_fees_highlight" class="d-flex justify-content-between align-items-center p-2 rounded mb-2 border" style="background: #fef2f2; border-color: #fecaca !important; display: none;">
                            <span class="fw-semibold text-danger" style="font-size: 0.8rem;">Total Additional Charges:</span>
                            <span class="fw-bold text-danger" style="font-size: 0.95rem;" id="disp_total_fees">₱0.00</span>
                        </div>

                        <!-- Damage description and/or Seller Notes -->
                        <div id="assessment_notes_wrap" class="p-2 border rounded" style="background: #ffffff; display: none;">
                            <div class="text-muted fw-semibold mb-1" style="font-size: 0.7rem; text-transform: uppercase;">
                                <i class="fas fa-clipboard-check me-1"></i> Owner Inspection Remarks
                            </div>
                            <div class="text-dark" id="assessment_notes_text" style="font-size: 0.78rem;"></div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer px-4 py-2" style="background: #fafbfc; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-light btn-sm border px-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to safely parse JSON or return string
            function parseReturnDetails(detailsJson) {
                if (!detailsJson) return {};
                if (typeof detailsJson === 'object') return detailsJson;
                try {
                    return JSON.parse(detailsJson);
                } catch (e) {
                    try {
                        const detailsStr = detailsJson.replace(/\\"/g, '"');
                        return JSON.parse(detailsStr);
                    } catch (e2) {
                        return detailsJson;
                    }
                }
            }

            function formatShortDate(dtStr) {
                if (!dtStr) return '—';
                try {
                    const d = new Date(dtStr.replace(' ', 'T'));
                    if (isNaN(d.getTime())) return dtStr.split(' ')[0] || dtStr;
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                } catch (e) {
                    return dtStr;
                }
            }

            // Add click event to return details buttons
            document.querySelectorAll('.return-details-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const returnId = this.getAttribute('data-return-id') || '';
                    const rentalId = this.getAttribute('data-rental-id') || '—';
                    const bookTitle = this.getAttribute('data-book-title') || '';
                    const bookAuthor = this.getAttribute('data-book-author') || '';
                    const coverImage = this.getAttribute('data-cover-image') || 'img/default-book-cover.jpg';
                    const isbn = this.getAttribute('data-isbn') || '—';
                    const rentalWeeks = this.getAttribute('data-rental-weeks') || '1';
                    const rentalPrice = this.getAttribute('data-rental-price') || '0.00';
                    const sellerName = this.getAttribute('data-seller-name') || 'Book Owner';
                    const sellerShop = this.getAttribute('data-seller-shop') || '';
                    const sellerEmail = this.getAttribute('data-seller-email') || '';
                    const sellerPhone = this.getAttribute('data-seller-phone') || '';
                    const status = (this.getAttribute('data-status') || 'pending').toLowerCase();
                    const method = (this.getAttribute('data-method') || 'dropoff').toLowerCase();
                    const details = this.getAttribute('data-details') || '';
                    const requestDate = this.getAttribute('data-request-date') || '';
                    const receivedDate = this.getAttribute('data-received-date') || '';
                    const completedDate = this.getAttribute('data-completed-date') || '';
                    const condition = (this.getAttribute('data-condition') || '').toLowerCase();
                    const damage = this.getAttribute('data-damage') || '';
                    const lateFee = parseFloat(this.getAttribute('data-late-fee') || 0);
                    const damageFee = parseFloat(this.getAttribute('data-damage-fee') || 0);
                    const additionalFee = parseFloat(this.getAttribute('data-additional-fee') || 0);
                    const isOverdue = this.getAttribute('data-is-overdue') === '1' || this.getAttribute('data-is-overdue') === 'true';
                    const daysOverdue = parseInt(this.getAttribute('data-days-overdue') || 0, 10);
                    const notes = this.getAttribute('data-notes') || '';

                    const parsedDetails = parseReturnDetails(details);

                    // Populate header & book info
                    document.getElementById('return_request_id').textContent = returnId;
                    document.getElementById('return_rental_id').textContent = rentalId;
                    document.getElementById('return_request_date').textContent = requestDate ? `Requested on ${formatShortDate(requestDate)}` : '';
                    document.getElementById('return_book_title').textContent = bookTitle;
                    document.getElementById('return_book_author').textContent = bookAuthor ? `by ${bookAuthor}` : '';
                    document.getElementById('return_book_image').src = coverImage;
                    document.getElementById('return_isbn').textContent = isbn;
                    document.getElementById('return_weeks').textContent = `${rentalWeeks} wk${parseInt(rentalWeeks, 10) > 1 ? 's' : ''}`;
                    document.getElementById('return_total_fee').textContent = rentalPrice;

                    // 3-Step Stepper logic & Status Badge & Tip Banner
                    const fillBar = document.getElementById('detStepperFill');
                    const step1 = document.getElementById('detStep1');
                    const step2 = document.getElementById('detStep2');
                    const step3 = document.getElementById('detStep3');
                    const bubble2 = document.getElementById('detStepBubble2');
                    const bubble3 = document.getElementById('detStepBubble3');
                    const stepTime1 = document.getElementById('detStepTime1');
                    const stepTime2 = document.getElementById('detStepTime2');
                    const stepTime3 = document.getElementById('detStepTime3');

                    const statusBadge = document.getElementById('return_status_badge');
                    const tipBanner = document.getElementById('status_tip_banner');
                    const tipIcon = document.getElementById('status_tip_icon');
                    const tipText = document.getElementById('status_tip_text');

                    stepTime1.textContent = formatShortDate(requestDate);

                    if (status === 'pending') {
                        fillBar.style.width = '20%';
                        step1.className = 'det-step completed';
                        step2.className = 'det-step active';
                        step3.className = 'det-step';
                        bubble2.textContent = '2';
                        bubble3.textContent = '3';
                        stepTime2.textContent = 'Pending Handover';
                        stepTime3.textContent = 'Pending Inspection';

                        statusBadge.className = 'badge-clean badge-pending';
                        statusBadge.innerHTML = '<i class="fas fa-clock"></i> Pending Handover';

                        tipBanner.className = 'alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2';
                        tipIcon.className = 'fas fa-info-circle text-warning';
                        tipText.textContent = 'Handover in progress: Please bring the book to the agreed location. Once received by the owner, they will inspect and complete the return.';
                    } else if (status === 'received' || status === 'in_transit') {
                        fillBar.style.width = '60%';
                        step1.className = 'det-step completed';
                        step2.className = 'det-step completed';
                        step3.className = 'det-step active';
                        bubble2.innerHTML = '<i class="fas fa-check" style="font-size: 0.68rem;"></i>';
                        bubble3.textContent = '3';
                        stepTime2.textContent = formatShortDate(receivedDate) || 'Received';
                        stepTime3.textContent = 'Inspecting Condition';

                        statusBadge.className = 'badge-clean badge-in-transit';
                        statusBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Received & Inspecting';

                        tipBanner.className = 'alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2';
                        tipIcon.className = 'fas fa-info-circle text-primary';
                        tipText.textContent = 'Book received: The owner has confirmed physical receipt of the book and is currently performing condition inspection.';
                    } else if (status === 'completed') {
                        fillBar.style.width = '100%';
                        step1.className = 'det-step completed';
                        step2.className = 'det-step completed';
                        step3.className = 'det-step completed';
                        bubble2.innerHTML = '<i class="fas fa-check" style="font-size: 0.68rem;"></i>';
                        bubble3.innerHTML = '<i class="fas fa-check" style="font-size: 0.68rem;"></i>';
                        stepTime2.textContent = formatShortDate(receivedDate) || 'Received';
                        stepTime3.textContent = formatShortDate(completedDate) || 'Completed';

                        statusBadge.className = 'badge-clean badge-completed';
                        statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Return Completed';

                        tipBanner.className = 'alert alert-success py-2 px-3 mb-3 d-flex align-items-center gap-2';
                        tipIcon.className = 'fas fa-check-circle text-success';
                        tipText.textContent = 'Return completed: The book condition has been assessed and this rental cycle is successfully finalized.';
                    } else if (status === 'cancelled') {
                        fillBar.style.width = '0%';
                        step1.className = 'det-step';
                        step2.className = 'det-step';
                        step3.className = 'det-step';
                        stepTime2.textContent = 'Cancelled';
                        stepTime3.textContent = 'Cancelled';

                        statusBadge.className = 'badge-clean badge-cancelled';
                        statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Cancelled';

                        tipBanner.className = 'alert alert-danger py-2 px-3 mb-3 d-flex align-items-center gap-2';
                        tipIcon.className = 'fas fa-times-circle text-danger';
                        tipText.textContent = 'This return request was cancelled.';
                    } else {
                        fillBar.style.width = '20%';
                        statusBadge.className = 'badge-clean badge-neutral';
                        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        tipBanner.className = 'alert alert-secondary py-2 px-3 mb-3 d-flex align-items-center gap-2';
                        tipText.textContent = 'Return request status: ' + status;
                    }

                    // Return Method details
                    const methodBadge = document.getElementById('return_method_badge');
                    const methodContent = document.getElementById('return_method_content');
                    const notesWrap = document.getElementById('return_notes_wrap');
                    const notesText = document.getElementById('return_notes_text');

                    if (method === 'dropoff') {
                        let dropLoc = '';
                        let dropNote = '';
                        if (typeof parsedDetails === 'object' && parsedDetails) {
                            dropLoc = parsedDetails.dropoff_location || '';
                            dropNote = parsedDetails.dropoff_notes || '';
                        } else if (typeof parsedDetails === 'string') {
                            dropLoc = parsedDetails;
                        }

                        const isCampus = dropLoc.toLowerCase().includes('campus');
                        methodBadge.textContent = isCampus ? 'Campus Meet-up' : 'Drop-off';
                        methodBadge.className = isCampus 
                            ? 'badge bg-primary-subtle text-primary border border-primary-subtle' 
                            : 'badge bg-light text-dark border';

                        methodContent.innerHTML = `
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-map-marker-alt text-danger mt-1"></i>
                                <div>
                                    <span class="text-muted" style="font-size: 0.74rem;">Designated Location:</span>
                                    <div class="fw-semibold text-dark">${dropLoc || 'Campus Meet-up / Drop-off Point'}</div>
                                </div>
                            </div>
                        `;

                        if (dropNote && dropNote.trim().length > 0) {
                            notesWrap.style.display = 'block';
                            notesText.textContent = dropNote;
                        } else {
                            notesWrap.style.display = 'none';
                        }
                    } else if (method === 'pickup') {
                        methodBadge.textContent = 'Courier Pickup';
                        methodBadge.className = 'badge bg-info-subtle text-info-emphasis border border-info-subtle';

                        let pAddr = 'N/A';
                        let pDate = '';
                        let pTime = '';
                        let pNotes = '';

                        if (typeof parsedDetails === 'object' && parsedDetails) {
                            pAddr = parsedDetails.pickup_address || 'N/A';
                            pDate = parsedDetails.pickup_date || '';
                            pTime = parsedDetails.pickup_time || '';
                            pNotes = parsedDetails.pickup_notes || '';
                        }

                        methodContent.innerHTML = `
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <i class="fas fa-truck text-primary mt-1"></i>
                                <div>
                                    <span class="text-muted" style="font-size: 0.74rem;">Pickup Address:</span>
                                    <div class="fw-semibold text-dark">${pAddr}</div>
                                </div>
                            </div>
                            ${pDate || pTime ? `
                            <div class="d-flex align-items-center gap-2 text-muted mt-1" style="font-size: 0.76rem;">
                                <i class="far fa-calendar-alt"></i>
                                <span>Scheduled: <strong class="text-dark">${pDate || 'Pending'}</strong> ${pTime ? `· ${pTime}` : ''}</span>
                            </div>` : ''}
                        `;

                        if (pNotes && pNotes.trim().length > 0) {
                            notesWrap.style.display = 'block';
                            notesText.textContent = pNotes;
                        } else {
                            notesWrap.style.display = 'none';
                        }
                    } else {
                        methodBadge.textContent = method.charAt(0).toUpperCase() + method.slice(1);
                        methodBadge.className = 'badge bg-light text-dark border';
                        methodContent.innerHTML = `<div class="text-muted">Standard return handover</div>`;
                        notesWrap.style.display = 'none';
                    }

                    // Book Owner / Seller Contact Info
                    document.getElementById('seller_name').textContent = sellerName;
                    const sShopEl = document.getElementById('seller_shop_name');
                    if (sellerShop && sellerShop.trim().length > 0 && sellerShop.toLowerCase() !== sellerName.toLowerCase()) {
                        sShopEl.textContent = 'Store: ' + sellerShop;
                        sShopEl.style.display = 'block';
                    } else {
                        sShopEl.style.display = 'none';
                    }
                    
                    const sEmailLink = document.getElementById('seller_email');
                    if (sellerEmail && sellerEmail.trim().length > 0) {
                        sEmailLink.textContent = sellerEmail;
                        sEmailLink.href = 'mailto:' + sellerEmail;
                        sEmailLink.className = 'text-decoration-none text-primary fw-semibold';
                    } else {
                        sEmailLink.textContent = 'Not provided';
                        sEmailLink.removeAttribute('href');
                        sEmailLink.className = 'text-decoration-none text-muted';
                    }

                    const sPhoneLink = document.getElementById('seller_phone');
                    if (sellerPhone && sellerPhone.trim().length > 0 && sellerPhone !== '—') {
                        sPhoneLink.textContent = sellerPhone;
                        sPhoneLink.href = 'tel:' + sellerPhone;
                        sPhoneLink.className = 'text-decoration-none text-primary fw-semibold';
                    } else {
                        sPhoneLink.textContent = 'Not provided';
                        sPhoneLink.removeAttribute('href');
                        sPhoneLink.className = 'text-decoration-none text-muted';
                    }

                    // Condition Assessment & Fee Settlement Card
                    const condCard = document.getElementById('condition_assessment_card');
                    const totalFees = lateFee + damageFee + additionalFee;

                    if (status === 'completed' || condition || totalFees > 0 || isOverdue) {
                        condCard.style.display = 'block';

                        // Condition badge
                        const condBadge = document.getElementById('return_condition_badge');
                        if (condition) {
                            condBadge.textContent = condition.charAt(0).toUpperCase() + condition.slice(1);
                            if (condition === 'excellent') condBadge.className = 'badge bg-success';
                            else if (condition === 'good') condBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
                            else if (condition === 'fair') condBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            else if (condition === 'damaged') condBadge.className = 'badge bg-danger';
                            else condBadge.className = 'badge bg-secondary';
                            document.getElementById('disp_condition').textContent = condition.charAt(0).toUpperCase() + condition.slice(1);
                        } else {
                            condBadge.textContent = 'Assessed';
                            condBadge.className = 'badge bg-light text-dark border';
                            document.getElementById('disp_condition').textContent = 'Completed';
                        }

                        // Overdue status
                        const overdueBanner = document.getElementById('overdue_alert_banner');
                        if (isOverdue || daysOverdue > 0) {
                            overdueBanner.style.display = 'flex';
                            document.getElementById('overdue_days_text').textContent = `${daysOverdue} day${daysOverdue !== 1 ? 's' : ''} overdue`;
                        } else {
                            overdueBanner.style.display = 'none';
                        }

                        // Fee values
                        const damEl = document.getElementById('disp_damage_fee');
                        damEl.textContent = '₱' + damageFee.toFixed(2);
                        damEl.className = damageFee > 0 ? 'fw-bold text-danger' : 'fw-bold text-muted';

                        const lateEl = document.getElementById('disp_late_fee');
                        lateEl.textContent = '₱' + lateFee.toFixed(2);
                        lateEl.className = lateFee > 0 ? 'fw-bold text-danger' : 'fw-bold text-muted';

                        const addEl = document.getElementById('disp_add_fee');
                        addEl.textContent = '₱' + additionalFee.toFixed(2);
                        addEl.className = additionalFee > 0 ? 'fw-bold text-danger' : 'fw-bold text-muted';

                        const totalHighlight = document.getElementById('total_fees_highlight');
                        if (totalFees > 0) {
                            totalHighlight.style.display = 'flex';
                            document.getElementById('disp_total_fees').textContent = '₱' + totalFees.toFixed(2);
                        } else {
                            totalHighlight.style.display = 'none';
                        }

                        // Remarks
                        const notesWrap2 = document.getElementById('assessment_notes_wrap');
                        const remarks = [damage, notes].filter(Boolean).join(' · ');
                        if (remarks && remarks.trim().length > 0) {
                            notesWrap2.style.display = 'block';
                            document.getElementById('assessment_notes_text').textContent = remarks;
                        } else {
                            notesWrap2.style.display = 'none';
                        }
                    } else {
                        condCard.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>