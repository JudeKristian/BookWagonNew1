<?php
include("session.php");
include("connect.php");
require_once("includes/audit_logger.php");

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

// Fetch current cart items with book details and seller details
$cartQuery = "SELECT c.*, b.title, b.author, b.price, b.rent_price, b.cover_image, 
                     b.security_deposit, b.book_value, b.stock, b.user_id AS seller_id, b.meetup_location,
                     u.firstname AS seller_first, u.lastname AS seller_last
              FROM cart c
              JOIN books b ON c.book_id = b.book_id
              JOIN users u ON b.user_id = u.id
              WHERE c.user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
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
            $uploadDir = 'uploads/ids/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileExt = strtolower(pathinfo($_FILES['valid_id']['name'], PATHINFO_EXTENSION));
            $fileName = 'id_' . $userId . '_' . time() . '.' . $fileExt;
            $targetFilePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['valid_id']['tmp_name'], $targetFilePath)) {
                $updateIdStmt = $conn->prepare("UPDATE users SET id_verified_status = 'pending', id_image_path = ? WHERE id = ?");
                $updateIdStmt->bind_param("si", $targetFilePath, $userId);
                $updateIdStmt->execute();
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
        $paymentStatus = 'paid';
        $paymentReceipt = 'QRPH-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $orderStatus = 'processing';
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
        
        // 2. Insert order items & handle book rentals
        $itemSql = "INSERT INTO order_items (order_id, book_id, seller_id, quantity, purchase_type, rental_weeks, unit_price, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'processing')";
        $itemStmt = $conn->prepare($itemSql);
        
        $rentalSql = "INSERT INTO book_rentals (user_id, book_id, seller_id, order_id, rental_date, due_date, rental_weeks, status, total_price) 
                      VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? WEEK), ?, 'active', ?)";
        $rentalStmt = $conn->prepare($rentalSql);
        
        $stockSql = "UPDATE books SET stock = GREATEST(0, stock - ?) WHERE book_id = ?";
        $stockStmt = $conn->prepare($stockSql);
        
        foreach ($cartItems as $item) {
            $bId = (int)$item['book_id'];
            $sId = (int)$item['seller_id'];
            $qty = (int)$item['quantity'];
            $pType = $item['purchase_type'];
            $rWeeks = ($pType === 'rent') ? (int)$item['rental_weeks'] : null;
            $uPrice = (float)$item['computed_unit_price'];
            
            // Insert order item: order_id(i), book_id(i), seller_id(i), quantity(i), purchase_type(s), rental_weeks(i), unit_price(d)
            $itemStmt->bind_param("iiiisid", $orderId, $bId, $sId, $qty, $pType, $rWeeks, $uPrice);
            $itemStmt->execute();
            
            // If rental, register active rental
            if ($pType === 'rent') {
                $rentalCost = $uPrice * $qty;
                $rentalWeeksVal = max(1, (int)$item['rental_weeks']);
                
                // book_rentals.seller_id references sellers(id), NOT users(id)
                $sellersTableId = 0;
                $sellerLookStmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ?");
                $sellerLookStmt->bind_param("i", $sId);
                $sellerLookStmt->execute();
                $sRes = $sellerLookStmt->get_result();
                if ($sRow = $sRes->fetch_assoc()) {
                    $sellersTableId = (int)$sRow['id'];
                } else {
                    // Fallback create seller profile if not present so FK constraint is satisfied
                    $uStmt = $conn->prepare("SELECT firstname, lastname, email FROM users WHERE id = ?");
                    $uStmt->bind_param("i", $sId);
                    $uStmt->execute();
                    $uData = $uStmt->get_result()->fetch_assoc() ?? [];
                    $shopName = ($uData['firstname'] ?? 'Seller') . "'s Store";
                    $first = $uData['firstname'] ?? 'Seller';
                    $last = $uData['lastname'] ?? 'Store';
                    $bEmail = $uData['email'] ?? ('seller' . $sId . '@bookwagon.com');
                    
                    $createS = $conn->prepare("INSERT INTO sellers (user_id, shop_name, first_name, last_name, business_email, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                    $createS->bind_param("issss", $sId, $shopName, $first, $last, $bEmail);
                    $createS->execute();
                    $sellersTableId = $conn->insert_id;
                }
                
                $rentalStmt->bind_param("iiiiisd", $userId, $bId, $sellersTableId, $orderId, $rentalWeeksVal, $rentalWeeksVal, $rentalCost);
                $rentalStmt->execute();
            }
            
            // Update stock
            $stockStmt->bind_param("ii", $qty, $bId);
            $stockStmt->execute();
        }
        
        // 3. Clear user cart
        $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearStmt->bind_param("i", $userId);
        $clearStmt->execute();
        
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
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['firstname'] ?? ''); ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['lastname'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="09XXXXXXXXX"
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
                                    <div class="option-name">QR Ph (GCash / Maya)</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Instant e-wallet demo</small>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">Demo</span>
                        </label>

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

    <!-- Simple QR Ph Modal -->
    <div class="modal fade" id="qrphModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content" style="border-radius: 10px; border: 1px solid var(--bw-border);">
                
                <div class="modal-header py-2 px-3">
                    <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">QR Ph Payment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center p-3">
                    <div class="text-muted" style="font-size: 0.78rem;">Total to Pay</div>
                    <div class="fw-bold mb-3" style="font-size: 1.5rem;" id="modalTotalAmount">
                        ₱<?php echo number_format($subtotal + $totalDeposit, 2); ?>
                    </div>

                    <!-- Clean QR image -->
                    <div class="mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=BOOKWAGON-PAYMENT-DEMO" 
                             alt="QR Code" style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px; width: 160px; height: 160px;">
                    </div>

                    <div class="text-muted mb-3" style="font-size: 0.75rem;">
                        Scan with GCash, Maya, or any banking app
                    </div>

                    <?php if ($totalDeposit > 0): ?>
                        <div class="text-start mb-3">
                            <label class="form-label" style="font-size: 0.78rem;" for="input_refund_mobile">
                                GCash / Maya Number (for Deposit Return):
                            </label>
                            <input type="tel" class="form-control form-control-sm" id="input_refund_mobile" 
                                   placeholder="09XXXXXXXXX" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                        </div>
                    <?php endif; ?>

                    <button type="button" class="btn btn-warning w-100 py-2 fw-semibold text-dark" 
                            style="background: #f8a100; border: none; font-size: 0.88rem;" 
                            onclick="executeSimulatedPayment()">
                        Simulate Payment (Demo)
                    </button>
                </div>

            </div>
        </div>
    </div>

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
            if (method === 'qrph') {
                btnText.textContent = 'Pay with QR Ph';
            } else if (method === 'cod') {
                btnText.textContent = 'Place Order (Cash)';
            } else {
                btnText.textContent = 'Place Order (Bank)';
            }
        }

        function handlePlaceOrderClick() {
            const form = document.getElementById('checkoutForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (selectedPayment === 'qrph') {
                const qrModal = new bootstrap.Modal(document.getElementById('qrphModal'));
                qrModal.show();
            } else {
                form.submit();
            }
        }

        function executeSimulatedPayment() {
            const refundInput = document.getElementById('input_refund_mobile');
            if (refundInput) {
                document.getElementById('hidden_refund_mobile').value = refundInput.value.trim();
            }
            document.getElementById('checkoutForm').submit();
        }
    </script>
</body>
</html>