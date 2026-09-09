<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Redirect if not logged in
if (!isset($_SESSION['id']) || empty($userId)) {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($orderId <= 0) {
    header("Location: account.php");
    exit();
}

// Fetch order details
$orderQuery = "SELECT o.*, 
               (SELECT GROUP_CONCAT(DISTINCT CONCAT(u2.firstname, ' ', u2.lastname) SEPARATOR ', ') 
                FROM order_items oi2 
                JOIN books b2 ON oi2.book_id = b2.book_id 
                JOIN users u2 ON b2.user_id = u2.id 
                WHERE oi2.order_id = o.order_id) AS sellers
               FROM orders o
               WHERE o.order_id = ? AND o.user_id = ?";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("ii", $orderId, $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    header("Location: account.php");
    exit();
}

$order = $orderResult->fetch_assoc();

// Fetch order items
$itemsQuery = "SELECT oi.*, b.title, b.author, b.cover_image, b.price, b.rent_price
               FROM order_items oi
               JOIN books b ON oi.book_id = b.book_id
               WHERE oi.order_id = ?";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$hasRentals = false;
$itemsSubtotal = 0;
foreach ($orderItems as $item) {
    if ($item['purchase_type'] === 'rent') {
        $hasRentals = true;
    }
    $itemsSubtotal += ($item['unit_price'] * $item['quantity']);
}

// Payment method display formatting
$paymentDisplay = 'Unknown';
$isPaid = ($order['payment_status'] === 'paid');

if ($order['payment_method'] === 'qrph') {
    $paymentDisplay = 'QR Ph (GCash / Maya)';
} elseif ($order['payment_method'] === 'cod') {
    $paymentDisplay = 'Cash on Delivery / Meet-up';
} elseif ($order['payment_method'] === 'pickup') {
    $paymentDisplay = 'Campus Meet-up';
} elseif ($order['payment_method'] === 'bank') {
    $paymentDisplay = 'Bank Transfer';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #<?php echo $orderId; ?> - BookWagon</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bw-primary: #f8a100;
            --bw-primary-hover: #e09000;
            --bw-dark: #1e293b;
            --bw-muted: #64748b;
            --bw-border: #e9ecef;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #fafbfc;
            color: var(--bw-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .confirm-card {
            background: #ffffff;
            border: 1px solid var(--bw-border);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .status-header {
            text-align: center;
            padding: 20px 0 10px 0;
        }

        .check-circle {
            width: 54px;
            height: 54px;
            background: #ecfdf5;
            color: #059669;
            border: 1.5px solid #a7f3d0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }

        .order-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .meta-label {
            font-size: 0.75rem;
            color: var(--bw-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--bw-dark);
        }

        .item-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-thumb {
            width: 46px;
            height: 62px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--bw-border);
            background: #f8fafc;
            flex-shrink: 0;
        }

        .item-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--bw-dark);
            margin-bottom: 3px;
        }

        .item-sub {
            font-size: 0.78rem;
            color: var(--bw-muted);
        }

        .item-price {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--bw-dark);
            white-space: nowrap;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            font-size: 0.86rem;
            color: var(--bw-muted);
            margin-bottom: 8px;
        }

        .summary-line.total {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--bw-dark);
            border-top: 1px solid var(--bw-border);
            padding-top: 10px;
            margin-top: 10px;
            margin-bottom: 0;
        }

        .btn-action-primary {
            background: var(--bw-primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.88rem;
            border-radius: 6px;
            padding: 10px 18px;
            border: none;
            text-decoration: none;
            display: inline-block;
            transition: background 0.15s ease;
        }

        .btn-action-primary:hover {
            background: var(--bw-primary-hover);
            color: #ffffff;
        }

        .btn-action-secondary {
            background: #ffffff;
            color: var(--bw-dark);
            border: 1px solid #cbd5e1;
            font-weight: 600;
            font-size: 0.88rem;
            border-radius: 6px;
            padding: 10px 18px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease;
        }

        .btn-action-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: var(--bw-dark);
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <?php include("include/user_header.php"); ?>

    <div class="container my-4 flex-grow-1" style="max-width: 720px;">
        
        <div class="confirm-card">
            
            <!-- Status Header -->
            <div class="status-header">
                <div class="check-circle">✓</div>
                <h4 class="fw-bold mb-1">Thank You! Your Order is Placed</h4>
                <p class="text-muted mb-0" style="font-size: 0.85rem;">
                    Order <strong>#<?php echo $orderId; ?></strong> has been received and is now being processed.
                </p>
            </div>

            <!-- Key Meta Information -->
            <div class="order-meta-grid">
                <div>
                    <div class="meta-label">Date Placed</div>
                    <div class="meta-value"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></div>
                </div>
                <div>
                    <div class="meta-label">Payment Method</div>
                    <div class="meta-value">
                        <?php echo htmlspecialchars($paymentDisplay); ?>
                        <?php if ($isPaid): ?>
                            <span class="badge bg-success ms-1" style="font-size: 0.68rem;">Paid</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Total Amount</div>
                    <div class="meta-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                <?php if (!empty($order['payment_receipt'])): ?>
                    <div>
                        <div class="meta-label">Transaction Ref</div>
                        <div class="meta-value" style="font-family: monospace; font-size: 0.82rem;">
                            <?php echo htmlspecialchars($order['payment_receipt']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Delivery Address -->
            <div class="mb-4">
                <div class="text-muted fw-semibold mb-1" style="font-size: 0.78rem; text-transform: uppercase;">
                    Recipient & Delivery
                </div>
                <div style="font-size: 0.88rem;">
                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong> 
                    • <?php echo htmlspecialchars($order['phone']); ?>
                </div>
                <div class="text-muted" style="font-size: 0.84rem;">
                    <?php echo htmlspecialchars($order['address'] . ', ' . $order['city']); ?>
                </div>
                <?php if (!empty($order['notes'])): ?>
                    <div class="text-muted mt-1" style="font-size: 0.8rem; font-style: italic;">
                        Notes: <?php echo htmlspecialchars($order['notes']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Items Ordered -->
            <div class="mb-3">
                <div class="text-muted fw-semibold mb-2" style="font-size: 0.78rem; text-transform: uppercase;">
                    Items Summary
                </div>
                
                <?php foreach ($orderItems as $item): ?>
                    <div class="item-row">
                        <img src="<?php echo htmlspecialchars(!empty($item['cover_image']) ? (strpos($item['cover_image'], 'uploads/') === 0 ? $item['cover_image'] : 'uploads/covers/' . $item['cover_image']) : 'uploads/covers/default_book.jpg'); ?>" 
                             alt="Cover" class="item-thumb" onerror="this.src='uploads/covers/default_book.jpg'">
                        
                        <div class="flex-grow-1">
                            <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="item-sub">
                                <?php if ($item['purchase_type'] === 'rent'): ?>
                                    <span class="badge bg-warning text-dark me-1" style="font-size: 0.68rem;">Rent (<?php echo (int)$item['rental_weeks']; ?> wks)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary text-white me-1" style="font-size: 0.68rem;">Buy</span>
                                <?php endif; ?>
                                Qty: <?php echo (int)$item['quantity']; ?>
                            </div>
                        </div>

                        <div class="item-price">
                            ₱<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cost Summary Breakdown -->
            <div class="p-3 mb-4" style="background: #f8fafc; border-radius: 8px;">
                <div class="summary-line">
                    <span>Items Subtotal</span>
                    <span class="fw-semibold text-dark">₱<?php echo number_format($itemsSubtotal, 2); ?></span>
                </div>
                <?php 
                $depositTotal = max(0, $order['total_amount'] - $itemsSubtotal - (float)$order['shipping_fee']);
                if ($depositTotal > 0): 
                ?>
                    <div class="summary-line text-success">
                        <span>Security Deposit (Refundable upon return)</span>
                        <span class="fw-semibold">₱<?php echo number_format($depositTotal, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-line">
                    <span>Delivery / Shipping</span>
                    <span class="fw-semibold text-dark">
                        <?php echo ($order['shipping_fee'] > 0) ? '₱' . number_format($order['shipping_fee'], 2) : 'Free'; ?>
                    </span>
                </div>
                <div class="summary-line total">
                    <span>Total Paid</span>
                    <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2">
                <a href="rentbooks.php" class="btn-action-secondary">
                    ← Continue Browsing
                </a>

                <div class="d-flex gap-2">
                    <?php if ($hasRentals): ?>
                        <a href="rented_books.php?tab=rentals" class="btn-action-secondary">
                            My Rented Books
                        </a>
                    <?php endif; ?>
                    <a href="history.php" class="btn-action-primary">
                        View Order History →
                    </a>
                </div>
            </div>

        </div>

    </div>

    <!-- Global Footer -->
    <?php include("include/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>