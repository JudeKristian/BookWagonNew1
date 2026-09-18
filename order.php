<?php
$currentPage = 'order.php';
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Ensure only sellers can access this page
if ($userType !== 'seller') {
    header("Location: login.php");
    exit();
}

// Get seller record ID
$sellerDbId = $userId;
$sellerStmt = $conn->prepare("SELECT id, shop_name FROM sellers WHERE user_id = ? LIMIT 1");
$sellerStmt->bind_param("i", $userId);
$sellerStmt->execute();
$sellerData = $sellerStmt->get_result()->fetch_assoc();
if ($sellerData) {
    $sellerDbId = (int)$sellerData['id'];
    $shopName = $sellerData['shop_name'];
} else {
    $shopName = 'Seller';
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderItemId = (int)($_POST['order_item_id'] ?? 0);
    
    if ($action === 'update_status' && $orderId && isset($_POST['new_status'])) {
        $newStatus = trim($_POST['new_status']);
        
        $checkStmt = $conn->prepare("SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ? AND seller_id = ?");
        $checkStmt->bind_param("ii", $orderId, $userId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->fetch_assoc()['item_count'] > 0) {
            $updateStmt = $conn->prepare("UPDATE order_items SET status = ? WHERE order_id = ? AND seller_id = ?");
            $updateStmt->bind_param("sii", $newStatus, $orderId, $userId);
            
            if ($updateStmt->execute()) {
                $logStmt = $conn->prepare("INSERT INTO payment_logs (order_id, user_id, action, status, details) VALUES (?, ?, 'status_update', 'success', ?)");
                $details = "Seller updated status to: " . $newStatus;
                $logStmt->bind_param("iis", $orderId, $userId, $details);
                $logStmt->execute();
                
                $allItemsStmt = $conn->prepare("SELECT COUNT(DISTINCT status) as status_count FROM order_items WHERE order_id = ?");
                $allItemsStmt->bind_param("i", $orderId);
                $allItemsStmt->execute();
                if ($allItemsStmt->get_result()->fetch_assoc()['status_count'] == 1) {
                    $orderUpdateStmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
                    $orderUpdateStmt->bind_param("si", $newStatus, $orderId);
                    $orderUpdateStmt->execute();
                }
                $_SESSION['success_message'] = "Order #$orderId updated to " . ucfirst($newStatus) . ".";
            }
        }
        header("Location: order.php");
        exit();
    } 
    elseif ($action === 'update_item_status' && $orderItemId && isset($_POST['new_status'])) {
        $newStatus = trim($_POST['new_status']);
        $checkStmt = $conn->prepare("SELECT COUNT(*) as item_count FROM order_items WHERE item_id = ? AND seller_id = ?");
        $checkStmt->bind_param("ii", $orderItemId, $userId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->fetch_assoc()['item_count'] > 0) {
            $updateStmt = $conn->prepare("UPDATE order_items SET status = ? WHERE item_id = ?");
            $updateStmt->bind_param("si", $newStatus, $orderItemId);
            if ($updateStmt->execute()) {
                $_SESSION['success_message'] = "Item status updated to " . ucfirst($newStatus) . ".";
            }
        }
        header("Location: order.php");
        exit();
    } 
    elseif ($action === 'mark_as_shipped' && isset($_POST['item_id'])) {
        $itemId = (int)$_POST['item_id'];
        $checkStmt = $conn->prepare("SELECT order_id FROM order_items WHERE item_id = ? AND seller_id = ?");
        $checkStmt->bind_param("ii", $itemId, $userId);
        $checkStmt->execute();
        $itemRow = $checkStmt->get_result()->fetch_assoc();
        
        if ($itemRow) {
            $orderId = $itemRow['order_id'];
            $updateStmt = $conn->prepare("UPDATE order_items SET status = 'shipped' WHERE item_id = ?");
            $updateStmt->bind_param("i", $itemId);
            
            if ($updateStmt->execute()) {
                $updOrder = $conn->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id = ? AND order_status IN ('pending', 'processing')");
                $updOrder->bind_param("i", $orderId);
                $updOrder->execute();

                $logStmt = $conn->prepare("INSERT INTO payment_logs (order_id, user_id, action, status, details) VALUES (?, ?, 'item_shipped', 'success', ?)");
                $details = "Item #$itemId marked as shipped";
                $logStmt->bind_param("iis", $orderId, $userId, $details);
                $logStmt->execute();
                
                $_SESSION['success_message'] = "Item #$itemId marked as Shipped.";
            }
        }
        header("Location: order.php");
        exit();
    }
    elseif ($action === 'mark_as_delivered' && isset($_POST['item_id'])) {
        $itemId = (int)$_POST['item_id'];
        
        $checkStmt = $conn->prepare("
            SELECT oi.item_id, oi.order_id, oi.book_id, oi.purchase_type, oi.rental_weeks, oi.unit_price,
                   o.payment_method, o.payment_status
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE oi.item_id = ? AND oi.seller_id = ?
        ");
        $checkStmt->bind_param("ii", $itemId, $userId);
        $checkStmt->execute();
        $itemData = $checkStmt->get_result()->fetch_assoc();
        
        if ($itemData) {
            if (($itemData['payment_method'] == 'cod' || $itemData['payment_method'] == 'pickup') && $itemData['payment_status'] != 'paid') {
                $updatePaymentStmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', payment_date = NOW() WHERE order_id = ?");
                $updatePaymentStmt->bind_param("i", $itemData['order_id']);
                $updatePaymentStmt->execute();
            }
            
            $updateStmt = $conn->prepare("UPDATE order_items SET status = 'delivered' WHERE item_id = ?");
            $updateStmt->bind_param("i", $itemId);
            $updateStmt->execute();
            
            if ($itemData['purchase_type'] === 'rent') {
                $checkRental = $conn->prepare("SELECT rental_id FROM book_rentals WHERE order_id = ? AND book_id = ? LIMIT 1");
                $checkRental->bind_param("ii", $itemData['order_id'], $itemData['book_id']);
                $checkRental->execute();
                $existingRental = $checkRental->get_result()->fetch_assoc();
                
                if (!$existingRental) {
                    $createRentalStmt = $conn->prepare("
                        INSERT INTO book_rentals (user_id, book_id, seller_id, rental_date, due_date, rental_weeks, status, total_price, order_id)
                        VALUES ((SELECT user_id FROM orders WHERE order_id = ?), ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? WEEK), ?, 'active', ?, ?)
                    ");
                    $createRentalStmt->bind_param(
                        "iiiiidi", 
                        $itemData['order_id'], $itemData['book_id'], $sellerDbId, 
                        $itemData['rental_weeks'], $itemData['rental_weeks'], 
                        $itemData['unit_price'], $itemData['order_id']
                    );
                    $createRentalStmt->execute();
                } else {
                    $updRent = $conn->prepare("UPDATE book_rentals SET status = 'active' WHERE rental_id = ?");
                    $updRent->bind_param("i", $existingRental['rental_id']);
                    $updRent->execute();
                }
            }
            
            $allItemsStmt = $conn->prepare("SELECT COUNT(*) as uncompleted FROM order_items WHERE order_id = ? AND status != 'delivered'");
            $allItemsStmt->bind_param("i", $itemData['order_id']);
            $allItemsStmt->execute();
            if ($allItemsStmt->get_result()->fetch_assoc()['uncompleted'] == 0) {
                $updOrder = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ?");
                $updOrder->bind_param("i", $itemData['order_id']);
                $updOrder->execute();
            }
            
            $_SESSION['success_message'] = "Item #$itemId marked as Delivered.";
        }
        header("Location: order.php");
        exit();
    }
    elseif ($action === 'confirm_payment' && isset($_POST['order_id'])) {
        $orderId = (int)$_POST['order_id'];
        $checkStmt = $conn->prepare("
            SELECT o.order_id 
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.order_id = ? AND oi.seller_id = ?
            LIMIT 1
        ");
        $checkStmt->bind_param("ii", $orderId, $userId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->fetch_assoc()) {
            $updateStmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', payment_date = NOW() WHERE order_id = ?");
            $updateStmt->bind_param("i", $orderId);
            $updateStmt->execute();
            $_SESSION['success_message'] = "Order #$orderId payment marked as Paid.";
        }
        header("Location: order.php");
        exit();
    }
}

// -------------------------------------------------------------
// Dynamic Tab Counts for Seller
// -------------------------------------------------------------
$tabCountsQuery = "
    SELECT 
        COUNT(DISTINCT o.order_id) as total_count,
        COUNT(DISTINCT CASE WHEN oi.status IN ('pending', 'processing', 'pending_meetup') THEN o.order_id END) as pending_count,
        COUNT(DISTINCT CASE WHEN oi.status = 'shipped' THEN o.order_id END) as shipped_count,
        COUNT(DISTINCT CASE WHEN oi.status = 'delivered' THEN o.order_id END) as delivered_count,
        COUNT(DISTINCT CASE WHEN oi.purchase_type = 'rent' THEN o.order_id END) as rental_count,
        COUNT(DISTINCT CASE WHEN oi.purchase_type = 'buy' THEN o.order_id END) as buy_count
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE oi.seller_id = ?
";
$tcStmt = $conn->prepare($tabCountsQuery);
$tcStmt->bind_param("i", $userId);
$tcStmt->execute();
$tabCounts = $tcStmt->get_result()->fetch_assoc() ?: [
    'total_count' => 0, 'pending_count' => 0, 'shipped_count' => 0, 
    'delivered_count' => 0, 'rental_count' => 0, 'buy_count' => 0
];

// Handle Filtering
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

$whereClause = "oi.seller_id = ?";
$params = [$userId];
$paramTypes = "i";

if ($status_filter === 'pending') {
    $whereClause .= " AND oi.status IN ('pending', 'processing', 'pending_meetup')";
} elseif ($status_filter === 'shipped') {
    $whereClause .= " AND oi.status = 'shipped'";
} elseif ($status_filter === 'delivered') {
    $whereClause .= " AND oi.status = 'delivered'";
} elseif ($status_filter === 'cancelled') {
    $whereClause .= " AND oi.status = 'cancelled'";
}

if ($type_filter === 'rent') {
    $whereClause .= " AND oi.purchase_type = 'rent'";
} elseif ($type_filter === 'buy') {
    $whereClause .= " AND oi.purchase_type = 'buy'";
}

if ($date_filter === 'today') {
    $whereClause .= " AND DATE(o.order_date) = CURDATE()";
} elseif ($date_filter === 'last7days') {
    $whereClause .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'last30days') {
    $whereClause .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

if (!empty($search_query)) {
    $whereClause .= " AND (o.order_id LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ? OR o.email LIKE ? OR b.title LIKE ?)";
    $like = "%$search_query%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $paramTypes .= "sssss";
}

$query = "
    SELECT 
        o.order_id, 
        o.order_date, 
        o.payment_method, 
        o.payment_status, 
        o.order_status as main_order_status,
        o.first_name, 
        o.last_name, 
        o.email, 
        o.phone, 
        o.address, 
        o.city, 
        o.postal_code, 
        o.notes, 
        o.shipping_fee, 
        o.pickup_location,
        o.user_id,
        u.firstname as u_first, 
        u.lastname as u_last, 
        u.email as u_email, 
        u.phone as u_phone,
        u.id_verified_status,
        oi.item_id, 
        oi.book_id, 
        oi.quantity, 
        oi.purchase_type, 
        oi.rental_weeks, 
        oi.unit_price, 
        oi.status as item_status,
        b.title as book_title, 
        b.author as book_author, 
        b.ISBN, 
        b.cover_image, 
        b.price as book_price, 
        b.book_value
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    LEFT JOIN users u ON o.user_id = u.id
    JOIN books b ON oi.book_id = b.book_id
    WHERE $whereClause
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$orders = [];
foreach ($orderItems as $item) {
    $oid = $item['order_id'];
    if (!isset($orders[$oid])) {
        $customerName = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
        if (empty($customerName)) {
            $customerName = trim(($item['u_first'] ?? '') . ' ' . ($item['u_last'] ?? ''));
        }
        if (empty($customerName)) $customerName = 'Customer #' . $oid;

        $orders[$oid] = [
            'order_id' => $oid,
            'order_date' => $item['order_date'],
            'payment_method' => strtolower($item['payment_method'] ?? 'cod'),
            'payment_status' => strtolower($item['payment_status'] ?? 'pending'),
            'main_order_status' => strtolower($item['main_order_status'] ?? 'pending'),
            'shipping_fee' => (float)($item['shipping_fee'] ?? 0),
            'address' => $item['address'] ?? '',
            'city' => $item['city'] ?? '',
            'postal_code' => $item['postal_code'] ?? '',
            'notes' => $item['notes'] ?? '',
            'pickup_location' => $item['pickup_location'] ?? '',
            'customer' => [
                'id' => $item['user_id'] ?? 0,
                'name' => $customerName,
                'email' => !empty($item['email']) ? $item['email'] : ($item['u_email'] ?? ''),
                'phone' => !empty($item['phone']) ? $item['phone'] : ($item['u_phone'] ?? ''),
                'is_verified' => ($item['id_verified_status'] === 'verified')
            ],
            'items' => [],
            'seller_subtotal' => 0
        ];
    }

    $qty = max(1, (int)($item['quantity'] ?? 1));
    $itemSubtotal = (float)$item['unit_price'] * $qty;
    $pType = !empty($item['purchase_type']) ? $item['purchase_type'] : ($item['rental_weeks'] > 0 ? 'rent' : 'buy');

    $orders[$oid]['items'][] = [
        'item_id' => $item['item_id'],
        'book_id' => $item['book_id'],
        'book_title' => $item['book_title'],
        'book_author' => $item['book_author'],
        'isbn' => $item['ISBN'],
        'cover_image' => $item['cover_image'],
        'quantity' => $qty,
        'purchase_type' => $pType,
        'rental_weeks' => (int)($item['rental_weeks'] ?? 0),
        'unit_price' => (float)$item['unit_price'],
        'item_subtotal' => $itemSubtotal,
        'status' => strtolower($item['item_status'] ?? 'pending')
    ];

    $orders[$oid]['seller_subtotal'] += $itemSubtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Orders - BookWagon</title>
    
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (Important for sidebar & UI icons) -->
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

        /* Tabs & Filter Bar */
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
            text-decoration: none;
            white-space: nowrap;
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

        /* Order Card */
        .order-card {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .order-header {
            padding: 12px 18px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-number {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--bw-dark);
        }

        .order-date {
            font-size: 0.8rem;
            color: var(--bw-muted);
            margin-left: 8px;
        }

        .badge-clean {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: capitalize;
            display: inline-block;
        }

        .badge-paid { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-info { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* Buyer Info Strip */
        .buyer-strip {
            background: #fafbfc;
            border-bottom: 1px solid #f1f5f9;
            padding: 9px 18px;
            font-size: 0.8rem;
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

        /* Order Items */
        .order-items-list {
            padding: 12px 18px;
        }

        .item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 14px;
        }

        .item-row:last-child {
            border-bottom: none;
            padding-bottom: 2px;
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
            padding: 1px 5px;
            border-radius: 3px;
            background: #f1f5f9;
            color: #475569;
        }

        .item-price {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--bw-dark);
            text-align: right;
        }

        /* Clean Buttons */
        .btn-clean-primary {
            background: var(--bw-primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 5px 12px;
            border-radius: 5px;
            border: none;
            text-decoration: none;
            display: inline-block;
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
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease;
        }

        .btn-clean-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: var(--bw-dark);
        }

        .order-footer {
            background: #fafbfc;
            border-top: 1px solid #f1f5f9;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>
    <!-- Seller Sidebar (Retains necessary navigation icons) -->
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
                    <h2>Orders</h2>
                    <p>Manage customer book purchases and rentals for <?php echo htmlspecialchars($shopName); ?>.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="renter.php" class="btn-clean-outline">View Rentals</a>
                    <button onclick="window.print();" class="btn-clean-outline">Print</button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-number"><?php echo $tabCounts['total_count']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">To Ship</div>
                    <div class="stat-number <?php echo $tabCounts['pending_count'] > 0 ? 'text-danger' : ''; ?>">
                        <?php echo $tabCounts['pending_count']; ?>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">In Transit</div>
                    <div class="stat-number text-primary"><?php echo $tabCounts['shipped_count']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Delivered</div>
                    <div class="stat-number text-success"><?php echo $tabCounts['delivered_count']; ?></div>
                </div>
            </div>

            <!-- Tabs & Filter Bar -->
            <div class="filter-card">
                <div class="nav-tabs-clean">
                    <a href="order.php?status=all" class="nav-tab-link <?php echo ($status_filter === 'all' && $type_filter === 'all') ? 'active' : ''; ?>">
                        All Orders <span class="tab-count"><?php echo $tabCounts['total_count']; ?></span>
                    </a>
                    <a href="order.php?status=pending" class="nav-tab-link <?php echo ($status_filter === 'pending') ? 'active' : ''; ?>">
                        Pending Meet-up/Ship <span class="tab-count"><?php echo $tabCounts['pending_count']; ?></span>
                    </a>
                    <a href="order.php?status=shipped" class="nav-tab-link <?php echo ($status_filter === 'shipped') ? 'active' : ''; ?>">
                        In Transit <span class="tab-count"><?php echo $tabCounts['shipped_count']; ?></span>
                    </a>
                    <a href="order.php?status=delivered" class="nav-tab-link <?php echo ($status_filter === 'delivered') ? 'active' : ''; ?>">
                        Delivered <span class="tab-count"><?php echo $tabCounts['delivered_count']; ?></span>
                    </a>
                    <a href="order.php?type=rent" class="nav-tab-link <?php echo ($type_filter === 'rent') ? 'active' : ''; ?>">
                        Rentals <span class="tab-count"><?php echo $tabCounts['rental_count']; ?></span>
                    </a>
                    <a href="order.php?type=buy" class="nav-tab-link <?php echo ($type_filter === 'buy') ? 'active' : ''; ?>">
                        Purchases <span class="tab-count"><?php echo $tabCounts['buy_count']; ?></span>
                    </a>
                </div>

                <!-- Search & Date Filter -->
                <div class="filter-controls">
                    <form action="order.php" method="GET" class="row g-2 align-items-center">
                        <?php if ($status_filter !== 'all'): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <?php if ($type_filter !== 'all'): ?>
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                        <?php endif; ?>

                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search order number, customer name, book..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>

                        <div class="col-md-3">
                            <select name="date" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="last7days" <?php echo $date_filter === 'last7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="last30days" <?php echo $date_filter === 'last30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn-clean-primary w-100 text-center">Search</button>
                            <?php if (!empty($search_query) || $date_filter !== 'all' || $status_filter !== 'all' || $type_filter !== 'all'): ?>
                                <a href="order.php" class="btn-clean-outline" title="Reset Filters">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Feed -->
            <?php if (empty($orders)): ?>
                <div class="order-card p-5 text-center">
                    <div class="fw-bold mb-1" style="font-size: 0.95rem;">No Orders Found</div>
                    <p class="text-muted mb-3" style="font-size: 0.84rem;">There are no orders matching your current filters.</p>
                    <a href="order.php" class="btn-clean-outline">Reset Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php 
                        $isMeetup = !empty($order['pickup_location']) || $order['payment_method'] === 'pickup';
                    ?>
                    <div class="order-card" id="order-<?php echo $order['order_id']; ?>">
                        
                        <!-- Top Bar -->
                        <div class="order-header">
                            <div>
                                <span class="order-number">Order #<?php echo $order['order_id']; ?></span>
                                <span class="order-date">
                                    <?php echo date('M j, Y, g:i a', strtotime($order['order_date'])); ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <!-- Fulfillment Badge -->
                                <span class="badge-clean badge-info">
                                    <?php echo $isMeetup ? 'Campus Meet-up' : 'Courier Delivery'; ?>
                                </span>

                                <!-- Payment Badge -->
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="badge-clean badge-paid">Paid (<?php echo strtoupper($order['payment_method']); ?>)</span>
                                <?php else: ?>
                                    <span class="badge-clean badge-pending">Unpaid (<?php echo strtoupper($order['payment_method']); ?>)</span>
                                    <?php if ($order['payment_method'] === 'cod' || $order['payment_method'] === 'pickup'): ?>
                                        <form action="order.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="confirm_payment">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" class="btn-clean-outline text-success">Confirm Paid</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Quick Status Selector -->
                                <form action="order.php" method="POST" class="d-flex align-items-center">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <?php
                                        $seller_statuses = array_unique(array_column($order['items'], 'item_status'));
                                        $seller_order_status = (count($seller_statuses) === 1) ? $seller_statuses[0] : 'mixed';
                                    ?>
                                    <select name="new_status" class="form-select form-select-sm" style="width: 120px; font-size: 0.78rem;" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $seller_order_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="pending_meetup" <?php echo $seller_order_status === 'pending_meetup' ? 'selected' : ''; ?>>Pending Meet-up</option>
                                        <option value="processing" <?php echo $seller_order_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $seller_order_status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $seller_order_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $seller_order_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <?php if ($seller_order_status === 'mixed' || !in_array($seller_order_status, ['pending', 'pending_meetup', 'processing', 'shipped', 'delivered', 'cancelled'])): ?>
                                            <option value="<?php echo htmlspecialchars($seller_order_status); ?>" selected disabled>
                                                <?php echo $seller_order_status === 'mixed' ? 'Mixed Statuses' : ucfirst(str_replace('_', ' ', $seller_order_status)); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <!-- Buyer Info Strip -->
                        <div class="buyer-strip">
                            <div>
                                Buyer: <strong><?php echo htmlspecialchars($order['customer']['name']); ?></strong>
                                <?php if ($order['customer']['is_verified']): ?>
                                    <span class="badge bg-success ms-1" title="Identity Verified by Admin"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($order['customer']['phone'])): ?>
                                <div>Phone: <strong><?php echo htmlspecialchars($order['customer']['phone']); ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($order['customer']['email'])): ?>
                                <div>Email: <strong><?php echo htmlspecialchars($order['customer']['email']); ?></strong></div>
                            <?php endif; ?>
                            <div>
                                Location: <strong>
                                    <?php 
                                        if ($isMeetup && !empty($order['pickup_location'])) {
                                            echo htmlspecialchars($order['pickup_location']);
                                        } else {
                                            $dest = array_filter([$order['address'], $order['city'], $order['postal_code']]);
                                            echo !empty($dest) ? htmlspecialchars(implode(', ', $dest)) : 'Campus / Standard';
                                        }
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="order-items-list">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item-row">
                                    <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1;">
                                        <img src="<?php echo htmlspecialchars(!empty($item['cover_image']) ? (strpos($item['cover_image'], 'uploads/') === 0 ? $item['cover_image'] : 'uploads/covers/' . $item['cover_image']) : 'uploads/covers/default_book.jpg'); ?>" 
                                             alt="Cover" class="item-cover" onerror="this.src='uploads/covers/default_book.jpg'">
                                        <div style="min-width: 0;">
                                            <div class="item-title"><?php echo htmlspecialchars($item['book_title']); ?></div>
                                            <div class="item-meta">
                                                by <?php echo htmlspecialchars($item['book_author']); ?> •
                                                <span class="tag-type"><?php echo $item['purchase_type'] === 'rent' ? 'Rental (' . $item['rental_weeks'] . ' wk)' : 'Purchase'; ?></span>
                                                • Qty: <?php echo $item['quantity']; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price & Item Status -->
                                    <div class="text-end" style="min-width: 110px;">
                                        <div class="item-price">₱<?php echo number_format($item['item_subtotal'], 2); ?></div>
                                        <div class="mt-1">
                                            <span class="badge-clean badge-info">
                                                <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Action -->
                                    <div>
                                        <?php if ($item['status'] === 'pending_meetup'): ?>
                                            <button type="button" class="btn-clean-primary mb-1 text-center d-block text-decoration-none" style="background-color: var(--bw-primary); width: 100%; border: none;" data-bs-toggle="modal" data-bs-target="#sellerHandshakeModal" data-order-id="<?php echo $order['order_id']; ?>" data-item-id="<?php echo $item['item_id']; ?>" data-buyer-id="<?php echo $order['customer']['id']; ?>" onclick="initSellerHandshake(this)">
                                                <i class="fas fa-handshake"></i> Handshake
                                            </button>
                                            <button type="button" class="btn-clean-outline text-center" style="display: block; width: 100%;" onclick="showBuyerDetails(<?php echo $order['customer']['id']; ?>)">
                                                <i class="fas fa-id-card"></i> Buyer ID
                                            </button>
                                        <?php elseif ($item['status'] === 'pending' || $item['status'] === 'processing'): ?>
                                            <form action="order.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_as_shipped">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" class="btn-clean-primary">Ship Item</button>
                                            </form>
                                        <?php elseif ($item['status'] === 'shipped' || $item['status'] === 'shipped_pending_confirmation'): ?>
                                            <form action="order.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_as_delivered">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" class="btn-clean-outline text-success">Mark Delivered</button>
                                            </form>
                                        <?php elseif ($item['purchase_type'] === 'rent'): ?>
                                            <a href="renter.php" class="btn-clean-outline">View Rentals</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Footer -->
                        <div class="order-footer">
                            <div>
                                <?php if (!empty($order['notes'])): ?>
                                    <span class="text-muted">Note: <?php echo htmlspecialchars($order['notes']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted"><?php echo $isMeetup ? 'Campus Meet-up' : 'Door-to-door Courier'; ?></span>
                                <?php endif; ?>
                            </div>

                            <div>
                                <span class="text-muted me-2">Subtotal:</span>
                                <strong>₱<?php echo number_format($order['seller_subtotal'], 2); ?></strong>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- Buyer Details Modal -->
    <div class="modal fade" id="buyerDetailsModal" tabindex="-1" aria-labelledby="buyerDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="buyerDetailsModalLabel">Buyer Verification Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="buyerDetailsContent">
                    <!-- Content loaded via AJAX -->
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meet-up Handshake Modal -->
    <div class="modal fade" id="sellerHandshakeModal" tabindex="-1" aria-labelledby="sellerHandshakeModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="sellerHandshakeModalLabel">Meet-up Handover</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- Step 1: Show QR -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Step 1: Show this QR to the Buyer</h6>
                        <div id="sellerQrCode" class="d-inline-block p-3 border rounded mb-2 bg-white"></div>
                        <p class="text-muted small">The buyer must scan this to confirm the book's condition.</p>
                    </div>

                    <hr>

                    <!-- Step 2: Finalize -->
                    <div class="mt-4 text-start">
                        <h6 class="fw-bold mb-2">Step 2: Finalize Delivery</h6>
                        <p class="small text-muted mb-3">Once the buyer submits their condition check, upload a delivery photo (optional) and click complete.</p>
                        <form action="process_qr_handoff.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" id="shs_order_id">
                            <input type="hidden" name="item_id" id="shs_item_id">
                            <input type="hidden" name="buyer_id" id="shs_buyer_id">
                            
                            <label class="form-label small fw-semibold">Proof of Delivery Photo (Optional):</label>
                            <input type="file" name="condition_photo" class="form-control form-control-sm mb-3" accept="image/*">
                            
                            <button type="submit" name="finalize_action" value="delivered" class="btn w-100 fw-bold mb-2" style="background-color: var(--success-color); color: white; padding: 12px;">
                                <i class="fas fa-check-circle me-1"></i> Delivered (Complete Handover)
                            </button>
                        </form>
                    </div>

                    <!-- Manual Override -->
                    <div class="mt-4 pt-3 border-top text-start">
                        <p class="text-muted small mb-2"><i class="fas fa-info-circle"></i> Trouble scanning? Use manual override (bypasses Renter's condition check):</p>
                        <form action="process_qr_handoff.php" method="POST">
                            <input type="hidden" name="order_id" id="manual_shs_order_id">
                            <input type="hidden" name="item_id" id="manual_shs_item_id">
                            <input type="hidden" name="buyer_id" id="manual_shs_buyer_id">
                            <button type="submit" name="finalize_action" value="manual" class="btn btn-outline-secondary btn-sm w-100 fw-bold">Manually Confirm Book Given</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <script>
        // Buyer Details logic
        function showBuyerDetails(buyerId) {
            const contentDiv = document.getElementById('buyerDetailsContent');
            contentDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            
            fetch(`ajax_handlers/get_buyer_details.php?buyer_id=${buyerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <h4 class="mb-1">${data.name}</h4>
                            <p class="text-muted small mb-3">${data.email} | ${data.phone}</p>
                            <hr>
                            <h6 class="fw-bold mb-2">ID Verification Status</h6>
                        `;
                        
                        if (data.id_verified_status === 'verified') {
                            html += `<span class="badge bg-success mb-3">Verified</span>`;
                        } else if (data.id_verified_status === 'pending') {
                            html += `<span class="badge bg-warning text-dark mb-3">Verification Pending</span>`;
                        } else {
                            html += `<span class="badge bg-danger mb-3">Not Verified</span>`;
                        }
                        
                        if (data.id_image_path) {
                            html += `<div class="mt-2"><img src="${data.id_image_path}" class="img-fluid rounded" style="max-height: 250px; object-fit: contain;" alt="ID Document"></div>`;
                        } else {
                            html += `<div class="alert alert-secondary mt-2">No ID image uploaded.</div>`;
                        }
                        
                        contentDiv.innerHTML = html;
                    } else {
                        contentDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    }
                })
                .catch(err => {
                    contentDiv.innerHTML = `<div class="alert alert-danger">Error fetching details.</div>`;
                });
        }

        // QR Handshake Logic
        let sellerHtml5QrcodeScanner = null;
        let activeSellerOrderId = null;
        let activeSellerItemId = null;
        let activeSellerBuyerId = null;

        function initSellerHandshake(btn) {
            activeSellerOrderId = btn.getAttribute('data-order-id');
            activeSellerItemId = btn.getAttribute('data-item-id');
            activeSellerBuyerId = btn.getAttribute('data-buyer-id');
            
            document.getElementById('shs_order_id').value = activeSellerOrderId;
            document.getElementById('shs_item_id').value = activeSellerItemId;
            document.getElementById('shs_buyer_id').value = activeSellerBuyerId;
            
            document.getElementById('manual_shs_order_id').value = activeSellerOrderId;
            document.getElementById('manual_shs_item_id').value = activeSellerItemId;
            document.getElementById('manual_shs_buyer_id').value = activeSellerBuyerId;
            
            // Generate QR Code
            const qrContainer = document.getElementById('sellerQrCode');
            qrContainer.innerHTML = ''; 
            
            const qrData = JSON.stringify({
                action: 'handover',
                order_id: activeSellerOrderId,
                item_id: activeSellerItemId,
                role: 'seller'
            });
            
            new QRCode(qrContainer, {
                text: qrData,
                width: 220,
                height: 220
            });
            
            // Reset scan tab
            document.getElementById('seller-qr-result').style.display = 'none';
            document.getElementById('seller-qr-reader').style.display = 'block';
            
            // Auto switch to Scan Tab
            const scanTab = new bootstrap.Tab(document.getElementById('seller-scan-qr-tab'));
            scanTab.show();
            startSellerScanner();
        }

        function startSellerScanner() {
            if (!sellerHtml5QrcodeScanner) {
                sellerHtml5QrcodeScanner = new Html5QrcodeScanner(
                    "seller-qr-reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
                sellerHtml5QrcodeScanner.render(onSellerScanSuccess, onSellerScanError);
            }
        }

        function stopSellerScanner() {
            if (sellerHtml5QrcodeScanner) {
                sellerHtml5QrcodeScanner.clear();
                sellerHtml5QrcodeScanner = null;
            }
        }

        function onSellerScanSuccess(decodedText, decodedResult) {
            try {
                const data = JSON.parse(decodedText);
                if (data.cert === "BOOKWAGON_RENTER_HANDOVER" && data.order_id == activeSellerOrderId && data.item_id == activeSellerItemId) {
                    stopSellerScanner();
                    document.getElementById('shs_token').value = data.token;
                    document.getElementById('seller-qr-reader').style.display = 'none';
                    document.getElementById('seller-qr-result').style.display = 'block';
                } else {
                    alert("Invalid QR code. Please scan the renter's final QR code for this specific item.");
                }
            } catch (e) {
                alert("Invalid QR code format.");
            }
        }

        function onSellerScanError(errorMessage) {}

        document.getElementById('sellerHandshakeModal').addEventListener('hidden.bs.modal', function () {
            stopSellerScanner();
        });
    </script>
</body>
</html>