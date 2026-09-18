<?php
include("session.php");
include("connect.php");
require_once("includes/audit_logger.php");
require_once("includes/notification_helper.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Redirect if not logged in
if (!isset($_SESSION['id']) || empty($userId)) {
    header("Location: login.php");
    exit();
}

// Fetch user profile for pre-filling form
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc() ?? [];

// Handle selected items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
    // Coming from cart.php
    $_SESSION['checkout_selected_items'] = array_map('intval', $_POST['selected_items']);
}

$selectedItems = $_SESSION['checkout_selected_items'] ?? [];

if (empty($selectedItems)) {
    $_SESSION['cart_message'] = "Please select items to checkout.";
    $_SESSION['cart_message_type'] = "warning";
    header("Location: cart.php");
    exit();
}

// Fetch current cart items with book details and seller details, filtered by selected items
$placeholders = str_repeat('?,', count($selectedItems) - 1) . '?';
$cartQuery = "SELECT c.*, b.title, b.author, b.price, b.rent_price, b.cover_image, 
                     b.security_deposit, b.book_value, b.stock, b.user_id AS seller_id, b.meetup_location,
                     u.firstname AS seller_first, u.lastname AS seller_last
              FROM cart c
              JOIN books b ON c.book_id = b.book_id
              JOIN users u ON b.user_id = u.id
              WHERE c.user_id = ? AND c.cart_id IN ($placeholders)";

$types = "i" . str_repeat('i', count($selectedItems));
$params = array_merge([$userId], $selectedItems);

$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param($types, ...$params);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartItems = [];
$subtotal = 0;
$totalDeposit = 0;
$itemCount = 0;

while ($item = $cartResult->fetch_assoc()) {
    $qty = (int)$item['quantity'];
    $isRent = ($item['purchase_type'] === 'rent');
    $weeks = $isRent ? max(1, (int)$item['rental_weeks']) : 1;
    
    if ($isRent) {
        $itemPrice = (float)$item['rent_price'] * $weeks;
        $depositVal = (float)($item['security_deposit'] > 0 ? $item['security_deposit'] : ($item['book_value'] > 0 ? $item['book_value'] : 0));
        $itemDeposit = $depositVal * $qty;
    } else {
        $itemPrice = (float)$item['price'];
        $itemDeposit = 0;
    }
    
    $lineTotal = $itemPrice * $qty;
    $subtotal += $lineTotal;
    $totalDeposit += $itemDeposit;
    $itemCount += $qty;
    
    $item['computed_unit_price'] = $itemPrice;
    $item['computed_line_total'] = $lineTotal;
    $item['computed_deposit'] = $itemDeposit;
    $cartItems[] = $item;
}

// If cart is empty, redirect back to cart
if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

$hasRental = false;
foreach($cartItems as $item) {
    if($item['purchase_type'] === 'rent') {
        $hasRental = true;
        break;
    }
}

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    // Address fields removed, set to empty or default
    $address = 'Meet-up';
    $city = '';
    $postalCode = '';
    $handoverMethod = 'pickup';
    $paymentMethod = $_POST['payment_method'] ?? 'qrph';
    $refundMobile = trim($_POST['refund_mobile'] ?? '');
    $userNotes = trim($_POST['notes'] ?? '');
    
    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($phone)) {
        $_SESSION['checkout_error'] = "Please fill in all required contact fields.";
        header("Location: checkout.php");
        exit();
    }
    
    // Handle ID Upload for Rentals
    if ($hasRental && (!isset($userData['id_verified_status']) || $userData['id_verified_status'] !== 'verified')) {
        if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['valid_id'];
            
            // 1. File size check (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $_SESSION['checkout_error'] = "Uploaded ID exceeds maximum allowed size of 5MB.";
                header("Location: checkout.php");
                exit();
            }

            // 2. Extension whitelist
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedIdExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (!in_array($fileExt, $allowedIdExts)) {
                $_SESSION['checkout_error'] = "Invalid ID file type. Only JPG, PNG, WEBP, and PDF are allowed.";
                header("Location: checkout.php");
                exit();
            }

            // 3. Server-side MIME type inspection
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowedIdMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                if (!in_array($mimeType, $allowedIdMimes)) {
                    $_SESSION['checkout_error'] = "Invalid ID document format.";
                    header("Location: checkout.php");
                    exit();
                }
            }

            $uploadDir = 'uploads/ids/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'id_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $targetFilePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                chmod($targetFilePath, 0644);
                $updateIdStmt = $conn->prepare("UPDATE users SET id_verified_status = 'pending', id_image_path = ? WHERE id = ?");
                $updateIdStmt->bind_param("si", $targetFilePath, $userId);
                $updateIdStmt->execute();
                $updateIdStmt->close();
            } else {
                $_SESSION['checkout_error'] = "Failed to upload ID. Please try again.";
                header("Location: checkout.php");
                exit();
            }
        } else {
            if (!isset($userData['id_verified_status']) || $userData['id_verified_status'] === 'unverified' || empty($userData['id_image_path'])) {
                 $_SESSION['checkout_error'] = "A valid ID is required for book rentals.";
                 header("Location: checkout.php");
                 exit();
            }
        }
    }
    
    $shippingFee = 0.00;
    $grandTotal = $subtotal + $totalDeposit + $shippingFee;
    
    // Payment Status & Reference logic
    if ($paymentMethod === 'qrph') {
        if (!isset($_FILES['payment_receipt_image']) || $_FILES['payment_receipt_image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['checkout_error'] = "Payment receipt image is required for QR Ph payments.";
            header("Location: checkout.php");
            exit();
        }

        $receiptFile = $_FILES['payment_receipt_image'];

        // 1. File size check (max 5MB)
        if ($receiptFile['size'] > 5 * 1024 * 1024) {
            $_SESSION['checkout_error'] = "Payment receipt exceeds maximum allowed size of 5MB.";
            header("Location: checkout.php");
            exit();
        }

        // 2. Extension check
        $fileExt = strtolower(pathinfo($receiptFile['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($fileExt, $allowedExts)) {
            $_SESSION['checkout_error'] = "Invalid receipt format. Only JPG, PNG, and WEBP are allowed.";
            header("Location: checkout.php");
            exit();
        }

        // 3. Server-side MIME check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mimeType = finfo_file($finfo, $receiptFile['tmp_name']);
            finfo_close($finfo);
            $allowedReceiptMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mimeType, $allowedReceiptMimes)) {
                $_SESSION['checkout_error'] = "Invalid receipt image format.";
                header("Location: checkout.php");
                exit();
            }
        }

        $uploadDir = 'uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'receipt_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        $targetFilePath = $uploadDir . $fileName;

        if (!move_uploaded_file($receiptFile['tmp_name'], $targetFilePath)) {
            $_SESSION['checkout_error'] = "Failed to upload payment receipt. Please try again.";
            header("Location: checkout.php");
            exit();
        }
        chmod($targetFilePath, 0644);

        $paymentStatus = 'pending_verification';
        $paymentReceipt = $targetFilePath;
        $orderStatus = 'pending';
        $paymentDate = date('Y-m-d H:i:s');
    } elseif ($paymentMethod === 'cod') {
        $paymentStatus = 'pending';
        $paymentReceipt = null;
        $orderStatus = 'pending';
        $paymentDate = null;
    } else { // bank
        $paymentStatus = 'awaiting_payment';
        $paymentReceipt = null;
        $orderStatus = 'pending';
        $paymentDate = null;
    }
    
    // Format notes with refund mobile if provided
    $fullNotes = $userNotes;
    if (!empty($refundMobile)) {
        $fullNotes = "[Deposit Refund: " . $refundMobile . "] " . $fullNotes;
    }
    if ($handoverMethod === 'pickup') {
        $fullNotes = "[Campus Meet-up] " . $fullNotes;
    }
    
    $conn->begin_transaction();
    try {
        // 1. Insert order
        $orderSql = "INSERT INTO orders (user_id, first_name, last_name, email, phone, address, city, postal_code, notes, payment_method, shipping_fee, payment_status, payment_date, payment_receipt, order_status, total_amount, order_date) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $orderStmt = $conn->prepare($orderSql);
        $orderStmt->bind_param("isssssssssdssssd", 
            $userId, $firstName, $lastName, $email, $phone, $address, $city, $postalCode, 
            $fullNotes, $paymentMethod, $shippingFee, $paymentStatus, $paymentDate, $paymentReceipt, 
            $orderStatus, $grandTotal
        );
        $orderStmt->execute();
        $orderId = $conn->insert_id;
        
        // 2. Insert order items
        $itemSql = "INSERT INTO order_items (order_id, book_id, seller_id, quantity, purchase_type, rental_weeks, unit_price, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_meetup')";
        $itemStmt = $conn->prepare($itemSql);
        
        $stockSql = "UPDATE books SET stock = GREATEST(0, stock - ?) WHERE book_id = ?";
        $stockStmt = $conn->prepare($stockSql);
        
        $notifiedSellers = [];
        foreach ($cartItems as $item) {
            $bId = (int)$item['book_id'];
            $sId = (int)$item['seller_id'];
            $qty = (int)$item['quantity'];
            $pType = $item['purchase_type'];
            $rWeeks = ($pType === 'rent') ? (int)$item['rental_weeks'] : 0;
            $uPrice = (float)$item['computed_unit_price'];
            
            // Insert order item: order_id(i), book_id(i), seller_id(i), quantity(i), purchase_type(s), rental_weeks(i), unit_price(d)
            $itemStmt->bind_param("iiiisid", $orderId, $bId, $sId, $qty, $pType, $rWeeks, $uPrice);
            $itemStmt->execute();

            // Update stock
            $stockStmt->bind_param("ii", $qty, $bId);
            $stockStmt->execute();
            
            // Send notification to seller (once per seller per order, ONLY if not pending verification)
            if (!in_array($sId, $notifiedSellers) && $paymentStatus !== 'pending_verification') {
                $notifContent = "You have received a new order! Please check your pending orders for details.";
                sendNotification($conn, $sId, $userId, 'new_order', $notifContent);
                $notifiedSellers[] = $sId;
            }
        }
        
        // 3. Clear selected user cart items
        $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
        $clearStmt->bind_param($types, ...$params);
        $clearStmt->execute();
        unset($_SESSION['checkout_selected_items']);
        
        // 4. Record audit log
        $activityDetails = "Placed Order #$orderId | Total: ₱" . number_format($grandTotal, 2) . " | Payment: " . strtoupper($paymentMethod);
        if ($paymentReceipt) {
            $activityDetails .= " (Ref: $paymentReceipt)";
        }
        log_activity($userId, 'Order Placed', $activityDetails);
        
        $conn->commit();
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $orderId);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['checkout_error'] = "Order processing failed: " . $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BookWagon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bw-primary: #f8a100;
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

        .checkout-box {
            background: #ffffff;
            border: 1px solid var(--bw-border);
            border-radius: 10px;
            padding: 20px 22px;
            margin-bottom: 16px;
        }

        .checkout-box-title {
            font-size: 0.98rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--bw-dark);
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
        }

        .form-control, .form-select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.88rem;
        }

        .form-control:focus {
            border-color: var(--bw-primary);
            box-shadow: 0 0 0 3px rgba(248, 161, 0, 0.15);
        }

        /* Clean option selection */
        .option-item {
            border: 1px solid var(--bw-border);
            border-radius: 8px;
            padding: 12px 14px;
            cursor: pointer;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            transition: border-color 0.15s ease;
        }

        .option-item:hover {
            border-color: #cbd5e1;
        }

        .option-item.active {
            border-color: var(--bw-primary);
            background: #fffdfa;
        }

        .option-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .option-left input[type="radio"] {
            accent-color: var(--bw-primary);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .option-name {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--bw-dark);
        }

        .option-price {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--bw-muted);
        }

        /* Order Summary */
        .summary-box {
            background: #ffffff;
            border: 1px solid var(--bw-border);
            border-radius: 10px;
            padding: 20px 22px;
            position: sticky;
            top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.86rem;
            color: #475569;
            margin-bottom: 10px;
        }

        .summary-row.total {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--bw-dark);
            border-top: 1px solid var(--bw-border);
            padding-top: 12px;
            margin-top: 12px;
            margin-bottom: 0;
        }

        .btn-submit-order {
            background: var(--bw-primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.92rem;
            border: none;
            border-radius: 6px;
            padding: 12px;
            width: 100%;
            margin-top: 16px;
            transition: background 0.15s ease;
        }

        .btn-submit-order:hover {
            background: #e09000;
            color: #ffffff;
        }

        /* Mini Item in Summary */
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.82rem;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item-title {
            font-weight: 600;
            color: var(--bw-dark);
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <?php include("include/user_header.php"); ?>

    <div class="container my-4 flex-grow-1" style="max-width: 960px;">
        
        <div class="mb-3">
            <h4 class="fw-bold mb-1">Checkout</h4>
            <div class="text-muted" style="font-size: 0.85rem;">Please review your details and confirm your order.</div>
        </div>

        <?php if (isset($_SESSION['checkout_error'])): ?>
            <div class="alert alert-danger py-2 mb-3" style="font-size: 0.85rem;">
                <?php echo htmlspecialchars($_SESSION['checkout_error']); unset($_SESSION['checkout_error']); ?>
            </div>
        <?php endif; ?>

        <form id="checkoutForm" action="checkout.php" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                
                <!-- Left Column -->
                <div class="col-lg-7">
                    
                    <!-- 1. Contact & Address -->
                    <div class="checkout-box">
                        <div class="checkout-box-title">1. Delivery Address</div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" for="first_name">First Name</label>
                                <input type="text" class="form-control bw-name" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['firstname'] ?? ''); ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text" class="form-control bw-name" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['lastname'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" class="form-control bw-phone" id="phone" name="phone" placeholder="0917 123 4567"
                                       value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-12 mt-3 text-muted" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-circle-info me-1"></i> Delivery address is not required as all transactions are peer-to-peer meet-ups at the seller's designated location.
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($hasRental && (!isset($userData['id_verified_status']) || $userData['id_verified_status'] !== 'verified')): ?>
                    <!-- ID Verification (Mandatory for renters) -->
                    <div class="checkout-box border-warning" style="background-color: #fffbeb;">
                        <div class="checkout-box-title text-warning-emphasis">
                            <i class="fa-solid fa-id-card me-2"></i> ID Verification Required
                        </div>
                        <div class="text-dark mb-3" style="font-size: 0.85rem;">
                            Since your cart contains rental items, you must upload a valid Student or Government ID. This is a one-time process.
                        </div>
                        
                        <?php if (isset($userData['id_verified_status']) && $userData['id_verified_status'] === 'pending'): ?>
                            <div class="alert alert-info py-2" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-clock me-1"></i> Your ID verification is currently pending approval. You may proceed with checkout.
                            </div>
                        <?php else: ?>
                            <div class="mb-2">
                                <label class="form-label" for="valid_id">Upload Valid ID Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="valid_id" name="valid_id" accept="image/*" required>
                                <div class="form-text" style="font-size: 0.75rem;">Accepted formats: JPG, PNG. Max size 5MB.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 3. Payment Method -->
                    <div class="checkout-box">
                        <div class="checkout-box-title">3. Payment Method</div>
                        
                        <label class="option-item active" id="label-pay-qrph">
                            <div class="option-left">
                                <input type="radio" name="payment_method" value="qrph" checked onchange="selectPaymentMethod('qrph', this)">
                                <div>
                                    <div class="option-name">QR Ph (E-Wallet)</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Upload payment receipt</small>
                                </div>
                            </div>
                        </label>
                        
                        <div id="qrph-upload-section" class="p-3 mb-3 border rounded" style="background-color: #f8f9fa;">
                            <div class="text-center mb-3">
                                <!-- Dummy Admin QR Code -->
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=BOOKWAGON-PAYMENT" 
                                     alt="BookWagon QR Code" style="max-width: 160px;" class="img-thumbnail rounded">
                                <p class="mt-2 mb-1 fw-bold text-dark">BookWagon Admin</p>
                                <p class="text-muted m-0" style="font-size: 0.85rem;">Scan the QR code to pay <strong>₱<?php echo number_format($subtotal + $totalDeposit, 2); ?></strong></p>
                            </div>
                            <hr>
                            <div class="mb-2">
                                <label class="form-label fw-semibold" for="payment_receipt_image" style="font-size: 0.9rem;">Upload Transfer Receipt <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="payment_receipt_image" name="payment_receipt_image" accept="image/*" required>
                                <div class="form-text" style="font-size: 0.75rem;">Please upload a screenshot of your successful transaction.</div>
                            </div>
                        </div>

                        <!-- Removed Cash on Delivery (COD) as requested -->

                        <label class="option-item" id="label-pay-bank">
                            <div class="option-left">
                                <input type="radio" name="payment_method" value="bank" onchange="selectPaymentMethod('bank', this)">
                                <div>
                                    <div class="option-name">Bank Transfer</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Manual transfer</small>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- 4. Notes (Optional) -->
                    <div class="checkout-box">
                        <div class="checkout-box-title">4. Notes (Optional)</div>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Special instructions or meetup details..."></textarea>
                    </div>

                </div>

                <!-- Right Column: Summary -->
                <div class="col-lg-5">
                    <div class="summary-box">
                        <div class="checkout-box-title mb-2">Order Summary</div>
                        
                        <!-- Items list -->
                        <div class="mb-3">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="summary-item">
                                    <div>
                                        <div class="summary-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                        <div class="text-muted" style="font-size: 0.74rem;">
                                            <?php echo ($item['purchase_type'] === 'rent') ? 'Rent (' . (int)$item['rental_weeks'] . ' wks)' : 'Buy'; ?> 
                                            × <?php echo (int)$item['quantity']; ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.70rem;">
                                            <i class="fa-solid fa-location-dot me-1"></i> Meet: <?php echo htmlspecialchars($item['meetup_location'] ?? 'Campus Meet-up'); ?>
                                        </div>
                                    </div>
                                    <div class="fw-semibold">₱<?php echo number_format($item['computed_line_total'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Lines -->
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span class="fw-semibold">₱<?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <?php if ($totalDeposit > 0): ?>
                            <div class="summary-row text-success">
                                <span>Security Deposit (Refundable)</span>
                                <span class="fw-semibold">₱<?php echo number_format($totalDeposit, 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-row">
                            <span>Handover</span>
                            <span class="fw-semibold text-success">Free (Meet-up)</span>
                        </div>

                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="grandTotalDisplay">
                                ₱<?php echo number_format($subtotal + $totalDeposit, 2); ?>
                            </span>
                        </div>

                        <button type="button" class="btn-submit-order" id="btnPlaceOrder" onclick="handlePlaceOrderClick()">
                            <span id="btnPlaceOrderText">Pay with QR Ph</span>
                        </button>
                    </div>
                </div>

            </div>

            <input type="hidden" name="refund_mobile" id="hidden_refund_mobile" value="">
        </form>
    </div>

    <!-- QR Modal Removed for Inline Flow -->

    <!-- Global Footer -->
    <?php include("include/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const subtotal = <?php echo $subtotal; ?>;
        const totalDeposit = <?php echo $totalDeposit; ?>;
        let currentShipping = 0;
        let selectedPayment = 'qrph';

        function updateShipping(cost, radioEl) {
            // Unused since handover method selector was removed
        }

        function selectPaymentMethod(method, radioEl) {
            selectedPayment = method;

            document.querySelectorAll('input[name="payment_method"]').forEach(el => {
                el.closest('.option-item').classList.remove('active');
            });
            radioEl.closest('.option-item').classList.add('active');

            const btnText = document.getElementById('btnPlaceOrderText');
            const uploadSection = document.getElementById('qrph-upload-section');
            const uploadInput = document.getElementById('payment_receipt_image');

            if (method === 'qrph') {
                btnText.textContent = 'Submit Order & Receipt';
                uploadSection.style.display = 'block';
                uploadInput.required = true;
            } else {
                btnText.textContent = 'Place Order';
                uploadSection.style.display = 'none';
                uploadInput.required = false;
            }
        }

        function handlePlaceOrderClick() {
            const form = document.getElementById('checkoutForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            form.submit();
        }


    </script>
</body>
</html>