<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Handle add to cart if coming from a book page
if (isset($_GET['book_id']) && isset($_GET['action']) && $_GET['action'] == 'add') {
    $bookId = $_GET['book_id'];
    $purchaseType = $_GET['purchase_type'] ?? 'buy'; // Default to buy
    $rentalWeeks = $_GET['rental_weeks'] ?? 1; // Default to 1 week
    
    error_log("Cart Add - Book ID: $bookId, Purchase Type: $purchaseType, Rental Weeks: $rentalWeeks");
    // First check if book exists and has stock available
    $bookStmt = $conn->prepare("SELECT stock FROM books WHERE book_id = ?");
    $bookStmt->bind_param("i", $bookId);
    $bookStmt->execute();
    $bookResult = $bookStmt->get_result();
    
    if ($bookResult->num_rows > 0) {
        $bookData = $bookResult->fetch_assoc();
        $availableStock = $bookData['stock'];
        
        // Check if book already in cart
        $checkStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND book_id = ? AND purchase_type = ?");
        $checkStmt->bind_param("iis", $userId, $bookId, $purchaseType);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Book already in cart, update quantity and rental weeks if there's enough stock
            $cartItem = $checkResult->fetch_assoc();
            $newQuantity = $cartItem['quantity'] + 1;
            
            // Check if the new quantity exceeds available stock
            if ($newQuantity <= $availableStock) {
                $updateStmt = $conn->prepare("UPDATE cart SET quantity = ?, rental_weeks = ? WHERE cart_id = ?");
                $updateStmt->bind_param("iii", $newQuantity, $rentalWeeks, $cartItem['cart_id']);
                $updateStmt->execute();
                
                // Success message
                $_SESSION['cart_message'] = "Item added to cart successfully!";
                $_SESSION['cart_message_type'] = "success";
            } else {
                // Not enough stock
                $_SESSION['cart_message'] = "Sorry, only {$availableStock} item(s) available in stock.";
                $_SESSION['cart_message_type'] = "warning";
            }
        } else {
            // Add new item to cart if stock is available
            if ($availableStock > 0) {
                $insertStmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity, purchase_type, rental_weeks) VALUES (?, ?, 1, ?, ?)");
                $insertStmt->bind_param("iisi", $userId, $bookId, $purchaseType, $rentalWeeks);
                $insertStmt->execute();
                
                // Success message
                $_SESSION['cart_message'] = "Item added to cart successfully!";
                $_SESSION['cart_message_type'] = "success";
            } else {
                // Out of stock
                $_SESSION['cart_message'] = "Sorry, this item is out of stock.";
                $_SESSION['cart_message_type'] = "danger";
            }
        }
    } else {
        // Book not found
        $_SESSION['cart_message'] = "Book not found.";
        $_SESSION['cart_message_type'] = "danger";
    }
    
    // Redirect to cart
    header("Location: cart.php");
    exit();
}

// Handle remove from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['cart_id'])) {
    $cartId = $_GET['cart_id'];
    
    $deleteStmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $cartId, $userId);
    $deleteStmt->execute();
    
    header("Location: cart.php");
    exit();
}

// Handle update cart item
if (isset($_POST['update_item']) && isset($_POST['cart_id'])) {
    $cartId = $_POST['cart_id'];
    $quantity = $_POST['quantity'];
    $purchaseType = $_POST['purchase_type'];
    $rentalWeeks = $purchaseType == 'rent' ? $_POST['rental_weeks'] : NULL;
    
    // Debug logging
    error_log("Updating cart item: ID=$cartId, Quantity=$quantity, Type=$purchaseType, Weeks=" . ($rentalWeeks ?? 'NULL'));
    
    // Get book_id from cart item
    $getCartStmt = $conn->prepare("SELECT book_id FROM cart WHERE cart_id = ? AND user_id = ?");
    $getCartStmt->bind_param("ii", $cartId, $userId);
    $getCartStmt->execute();
    $cartResult = $getCartStmt->get_result();
    
    if ($cartResult->num_rows > 0) {
        $cartData = $cartResult->fetch_assoc();
        $bookId = $cartData['book_id'];
        
        // Check available stock
        $stockStmt = $conn->prepare("SELECT stock FROM books WHERE book_id = ?");
        $stockStmt->bind_param("i", $bookId);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $stockData = $stockResult->fetch_assoc();
        $availableStock = $stockData['stock'];
        
        // Validate quantity against stock
        if ($quantity <= $availableStock) {
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ?, purchase_type = ?, rental_weeks = ? WHERE cart_id = ? AND user_id = ?");
            $updateStmt->bind_param("isiii", $quantity, $purchaseType, $rentalWeeks, $cartId, $userId);
            
            if ($updateStmt->execute()) {
                // Debug logging of success
                error_log("Successfully updated cart: cart_id=$cartId, rental_weeks=$rentalWeeks");
            } else {
                // Log error if update fails
                error_log("Error updating cart: " . $updateStmt->error);
                $_SESSION['cart_message'] = "Error updating cart. Please try again.";
                $_SESSION['cart_message_type'] = "danger";
            }
        } else {
            $_SESSION['cart_message'] = "Sorry, only {$availableStock} item(s) available in stock.";
            $_SESSION['cart_message_type'] = "warning";
        }
    }
    
    // Force reset of any cached cart data
    unset($_SESSION['cart_subtotal']);
    unset($_SESSION['cart_tax']);
    unset($_SESSION['cart_shipping']);
    unset($_SESSION['cart_total']);
    
    header("Location: cart.php");
    exit();
}

// After all the cart update/remove/add operations, fetch the latest cart data
// Fetch user's cart items
$cartStmt = $conn->prepare("SELECT c.*, b.title, b.author, b.cover_image, b.price, b.rent_price, b.stock 
                           FROM cart c 
                           JOIN books b ON c.book_id = b.book_id 
                           WHERE c.user_id = ? 
                           ORDER BY c.created_at DESC");
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartItems = $cartResult->fetch_all(MYSQLI_ASSOC);

// Debug log cart items
if (!empty($cartItems)) {
    foreach ($cartItems as $item) {
        error_log("Cart Item: ID={$item['cart_id']}, Book={$item['title']}, Type={$item['purchase_type']}, Weeks={$item['rental_weeks']}");
    }
}

// Calculate cart totals
$subtotal = 0;
$shipping = 0;
$discount = 0;

foreach ($cartItems as $item) {
    if ($item['purchase_type'] == 'buy') {
        $subtotal += $item['price'] * $item['quantity'];
    } else {
        $subtotal += $item['rent_price'] * $item['rental_weeks'] * $item['quantity'];
    }
}

// Calculate total (without tax and shipping for now)
// Shipping fee will be added only for Cash on Delivery at checkout
$total = $subtotal - $discount;

// Calculate amount needed for free shipping
$freeShippingThreshold = 50;
$amountForFreeShipping = max(0, $freeShippingThreshold - $subtotal);

// Store in session for cart page display
$_SESSION['cart_subtotal'] = $subtotal;
$_SESSION['cart_shipping'] = 0; // Will be determined at checkout based on payment method
$_SESSION['cart_total'] = $total;

// Store cart details in session for checkout page
$_SESSION['cart_details'] = [
    'subtotal' => $subtotal,
    'shipping' => 0, // Will be determined at checkout based on payment method
    'discount' => $discount,
    'total' => $total,
    'itemCount' => count($cartItems),
    'freeShippingThreshold' => $freeShippingThreshold,
    'amountForFreeShipping' => $amountForFreeShipping
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Shopping Cart - BookWagon</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
        <style>
        :root {
            --primary-color: #f8a100;
            --primary-dark: #d97706;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e9ecef;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            background-color: #f8fafc;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: rgba(0,0,0,0.05);
        }

        .dropdown-item:active {
            background-color: rgba(0,0,0,0.1);
        }

        .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        
        .navbar {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .navbar-brand img {
            height: 60px;
        }

        /* Cart Container & Item Cards */
        .cart-container {
            margin-bottom: 24px;
        }
        
        .cart-item {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 12px;
        }
        
        .cart-item-image-wrapper {
            width: 75px;
            height: 105px;
            flex-shrink: 0;
            border-radius: 6px;
            overflow: hidden;
            background: #f1f5f9;
            border: 1px solid #edf2f7;
        }
        
        .cart-item-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .cart-item-title {
            font-weight: 600;
            font-size: 0.98rem;
            color: var(--text-dark);
            margin-bottom: 3px;
            line-height: 1.35;
        }
        
        .cart-item-attr {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        
        .cart-item-price {
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text-dark);
        }

        .cart-item-price-sub {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Simple Segmented Toggle */
        .purchase-type-toggle {
            display: inline-flex;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background: #f8fafc;
        }
        
        .purchase-option {
            padding: 5px 14px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: background-color 0.15s, color 0.15s;
            user-select: none;
            white-space: nowrap;
        }
        
        .purchase-option:hover {
            color: var(--text-dark);
        }
        
        .purchase-option.active {
            background: #f8a100;
            color: #ffffff;
            font-weight: 600;
        }
        
        .rent-duration {
            margin-top: 6px;
            display: none;
        }
        
        .rent-duration.active {
            display: inline-block;
        }
        
        .rent-duration select {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.8rem;
            color: var(--text-dark);
            background-color: #ffffff;
        }
        
        .quantity-selector {
            width: 70px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-dark);
            background-color: #ffffff;
        }

        /* Order Summary Card */
        .cart-summary {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 24px;
        }
        
        .cart-summary h5 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 16px;
        }
        
        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.88rem;
            color: #475569;
        }
        
        .cart-summary-row.total-row {
            border-top: 1px solid var(--border-color);
            margin-top: 6px;
            padding-top: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .checkout-button {
            display: block;
            text-align: center;
            width: 100%;
            background: #f8a100;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 11px 16px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            margin-top: 16px;
            transition: background-color 0.15s ease;
        }
        
        .checkout-button:hover {
            background: #d97706;
            color: #ffffff;
        }
        
        .checkout-button.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Empty Cart State */
        .empty-cart-container {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 48px 20px;
            text-align: center;
        }
        
        .empty-cart-icon {
            font-size: 2.5rem;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        
        .empty-cart-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        
        .empty-cart-message {
            color: var(--text-muted);
            font-size: 0.88rem;
            max-width: 340px;
            margin: 0 auto 20px auto;
        }
        
        .continue-shopping-btn {
            display: inline-block;
            background: #f8a100;
            color: #ffffff !important;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        
        .continue-shopping-btn:hover {
            background: #d97706;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.2s;
        }
        
        .loading-overlay.active {
            visibility: visible;
            opacity: 1;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s infinite linear;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 991px) {
            .cart-summary {
                position: static;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Include Header -->
    <?php include("include/user_header.php"); ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar Column -->
            <div class="col-lg-3 col-md-4 mb-4">
                <?php include("include/user_sidebar.php"); ?>
            </div>
            
                        <!-- Main Content Column -->
            <div class="col-lg-9 col-md-8">
                <div class="mb-4">
                    <h4 class="fw-bold text-dark mb-1">Shopping Cart</h4>
                    <p class="text-muted small mb-0">
                        <?php echo count($cartItems); ?> <?php echo count($cartItems) === 1 ? 'item' : 'items'; ?> in your cart
                    </p>
                </div>

                <?php if (isset($_SESSION['cart_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['cart_message_type'] ?? 'info'; ?> alert-dismissible fade show mb-3 py-2 px-3 small" role="alert">
                    <?php echo htmlspecialchars($_SESSION['cart_message']); ?>
                    <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php 
                unset($_SESSION['cart_message']);
                unset($_SESSION['cart_message_type']);
                endif; 
                ?>

                <?php if (empty($cartItems)): ?>
                <div class="empty-cart-container">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5 class="empty-cart-title">Your cart is empty</h5>
                    <p class="empty-cart-message">Looks like you haven't added any books yet.</p>
                    <a href="rentbooks.php" class="continue-shopping-btn">Browse Books</a>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <!-- Cart Items Column -->
                    <div class="col-lg-8">
                        <div class="cart-container">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <div class="d-flex gap-3">
                                    <div class="cart-item-image-wrapper">
                                        <img src="<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                             class="cart-item-image"
                                             onerror="this.src='https://placehold.co/100x140?text=No+Cover'">
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <div>
                                                <h6 class="cart-item-title mb-1 text-truncate" style="max-width: 280px;" title="<?php echo htmlspecialchars($item['title']); ?>">
                                                    <?php echo htmlspecialchars($item['title']); ?>
                                                </h6>
                                                <div class="cart-item-attr">
                                                    by <?php echo htmlspecialchars($item['author']); ?>
                                                </div>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <?php if ($item['purchase_type'] == 'rent'): ?>
                                                    <div class="cart-item-price">
                                                        ₱<?php echo number_format($item['rent_price'] * $item['rental_weeks'], 2); ?>
                                                    </div>
                                                    <div class="cart-item-price-sub">
                                                        ₱<?php echo number_format($item['rent_price'], 2); ?>/wk × <?php echo $item['rental_weeks']; ?>w
                                                    </div>
                                                <?php else: ?>
                                                    <div class="cart-item-price">
                                                        ₱<?php echo number_format($item['price'], 2); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <form action="cart.php" method="post" class="mt-2">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            
                                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                                <!-- Quantity -->
                                                <div class="d-flex align-items-center gap-1">
                                                    <label class="small text-muted mb-0">Qty:</label>
                                                    <select name="quantity" class="form-select form-select-sm quantity-selector">
                                                        <?php 
                                                        $stockStmt = $conn->prepare("SELECT stock FROM books WHERE book_id = ?");
                                                        $stockStmt->bind_param("i", $item['book_id']);
                                                        $stockStmt->execute();
                                                        $stockResult = $stockStmt->get_result();
                                                        $stockData = $stockResult->fetch_assoc();
                                                        $maxStock = $stockData['stock'] ?? 10;
                                                        $maxQuantity = min(10, $maxStock);
                                                        
                                                        for ($i = 1; $i <= $maxQuantity; $i++): 
                                                        ?>
                                                        <option value="<?php echo $i; ?>" <?php echo ($item['quantity'] == $i) ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?>
                                                        </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>

                                                <!-- Option Toggle -->
                                                <div class="d-flex align-items-center gap-1">
                                                    <div class="purchase-type-toggle">
                                                        <div class="purchase-option <?php echo ($item['purchase_type'] == 'buy') ? 'active' : ''; ?>" 
                                                             data-type="buy" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                            Buy
                                                        </div>
                                                        <div class="purchase-option <?php echo ($item['purchase_type'] == 'rent') ? 'active' : ''; ?>" 
                                                             data-type="rent" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                            Rent
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="purchase_type" id="purchase_type_<?php echo $item['cart_id']; ?>" 
                                                           value="<?php echo $item['purchase_type']; ?>">
                                                </div>

                                                <!-- Duration (if Rent) -->
                                                <div class="rent-duration <?php echo ($item['purchase_type'] == 'rent') ? 'active' : ''; ?>" 
                                                     id="rent_duration_<?php echo $item['cart_id']; ?>">
                                                    <select name="rental_weeks" class="form-select form-select-sm">
                                                        <?php for ($i = 1; $i <= 16; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo ($item['rental_weeks'] == $i) ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?> wk<?php echo ($i > 1) ? 's' : ''; ?>
                                                        </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                <a href="cart.php?action=remove&cart_id=<?php echo $item['cart_id']; ?>" 
                                                   class="text-danger small text-decoration-none" 
                                                   onclick="return confirm('Remove this book from your cart?');">
                                                    <i class="fas fa-trash-alt me-1"></i> Remove
                                                </a>
                                                
                                                <button type="submit" name="update_item" class="btn btn-sm btn-light border py-0 px-2 text-muted" style="font-size: 0.78rem;">
                                                    Update
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Cart Summary Column -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h5>Order Summary</h5>
                            
                            <div class="cart-summary-row">
                                <span>Subtotal (<?php echo count($cartItems); ?> <?php echo count($cartItems) === 1 ? 'item' : 'items'; ?>)</span>
                                <span id="summarySubtotal" class="fw-semibold text-dark">₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            
                            <div class="cart-summary-row">
                                <span>Shipping Fee</span>
                                <span class="text-muted small">At checkout</span>
                            </div>
                            
                            <?php if ($discount > 0): ?>
                            <div class="cart-summary-row text-success">
                                <span>Discount</span>
                                <span>-₱<?php echo number_format($discount, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="cart-summary-row total-row">
                                <span>Total</span>
                                <span id="summaryTotal" class="text-dark">₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            
                            <a href="checkout.php" class="checkout-button <?php echo empty($cartItems) ? 'disabled' : ''; ?>">
                                Checkout
                            </a>

                            <div class="text-muted small mt-3 text-center" style="font-size: 0.8rem;">
                                <i class="fas fa-shield-alt text-success me-1"></i> Secure payment & COD available
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include("include/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Document ready handler
        document.addEventListener('DOMContentLoaded', function() {
            // Code was moved to the main script below
        });
    </script>
    <script>
    // Purchase type toggle and dynamic price updates
    document.addEventListener('DOMContentLoaded', function() {
        const purchaseOptions = document.querySelectorAll('.purchase-option');
        
        // Function to recalculate cart totals
        function recalculateCart() {
            let subtotal = 0;
            let discount = 0;
            
            // Loop through all cart items to calculate new subtotal
            document.querySelectorAll('.cart-item').forEach(item => {
                const cartId = item.querySelector('input[name="cart_id"]').value;
                const quantity = parseInt(item.querySelector('select[name="quantity"]').value);
                const purchaseType = document.getElementById(`purchase_type_${cartId}`).value;
                
                // Get price values (stored as data attributes or find them in the UI)
                let itemPrice = 0;
                if (purchaseType === 'buy') {
                    // Get buy price from the UI
                    const buyPriceText = item.querySelector('.purchase-option[data-type="buy"]').textContent;
                    itemPrice = parseFloat(buyPriceText.replace(/[^\d.]/g, ''));
                } else {
                    // Get rent price from the UI
                    const rentPriceText = item.querySelector('.purchase-option[data-type="rent"]').textContent;
                    const weeklyPrice = parseFloat(rentPriceText.replace(/[^\d.]/g, ''));
                    const rentalWeeks = parseInt(item.querySelector('select[name="rental_weeks"]').value);
                    itemPrice = weeklyPrice * rentalWeeks;
                }
                
                subtotal += itemPrice * quantity;
            });
            
            // Calculate total (no tax, no shipping fee now)
            const total = subtotal - discount;
            
            // Update UI with new totals
            const subtotalEl = document.getElementById('summarySubtotal') || document.querySelector('.cart-summary-row:nth-child(3) span:last-child');
            if (subtotalEl) {
                subtotalEl.textContent = subtotal > 0 ? `₱${subtotal.toFixed(2)}` : '₱0.00';
            }
                
            // Update total
            const totalEl = document.getElementById('summaryTotal') || document.querySelector('.cart-summary-row:nth-child(5) span:last-child');
            if (totalEl) {
                totalEl.textContent = `₱${total.toFixed(2)}`;
            }
            
            // Also update mobile summary if it exists
            const mobileSummary = document.querySelector('.cart-summary-mobile');
            if (mobileSummary) {
                mobileSummary.querySelector('.cart-summary-row span:last-child').textContent = 
                    `₱${subtotal.toFixed(2)}`;
            }
        }
        
        // Purchase type toggle handler
        purchaseOptions.forEach(option => {
            option.addEventListener('click', function() {
                const cartId = this.getAttribute('data-cart-id');
                const type = this.getAttribute('data-type');
                const purchaseTypeInput = document.getElementById(`purchase_type_${cartId}`);
                const rentDuration = document.getElementById(`rent_duration_${cartId}`);
                
                // Update active state
                document.querySelectorAll(`.purchase-option[data-cart-id="${cartId}"]`).forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update hidden input
                purchaseTypeInput.value = type;
                
                // Show/hide rent duration
                if (type === 'rent') {
                    rentDuration.classList.add('active');
                    
                    // Make sure rental weeks is set to a valid value when switching to rent
                    const rentalWeeksSelect = rentDuration.querySelector('select[name="rental_weeks"]');
                    if (rentalWeeksSelect && rentalWeeksSelect.value === '') {
                        rentalWeeksSelect.value = '1';
                    }
                    
                    console.log(`Switched to rent: cart_id=${cartId}, rental_weeks=${rentalWeeksSelect ? rentalWeeksSelect.value : 'unknown'}`);
                } else {
                    rentDuration.classList.remove('active');
                }
                
                // Recalculate cart totals for visual feedback
                recalculateCart();
                
                // Auto-submit the form when purchase type changes
                // Find the closest form and submit it
                const form = this.closest('form');
                if (form) {
                    // Show loading overlay
                    document.getElementById('loadingOverlay').classList.add('active');
                    
                    // Add a hidden input for update_item
                    const updateItemInput = document.createElement('input');
                    updateItemInput.type = 'hidden';
                    updateItemInput.name = 'update_item';
                    updateItemInput.value = '1';
                    form.appendChild(updateItemInput);
                    
                    // Add a small delay to allow the user to see the change before form submission
                    setTimeout(() => {
                        form.submit();
                    }, 300);
                }
            });
        });
        
        // Also add event listeners to quantity and rental weeks dropdowns
        document.querySelectorAll('select[name="quantity"], select[name="rental_weeks"]').forEach(select => {
            select.addEventListener('change', function() {
                // Recalculate cart totals for visual feedback
                recalculateCart();
                
                // Auto-submit the form when rental weeks or quantity changes
                // Find the closest form and submit it
                const form = this.closest('form');
                if (form) {
                    // Show loading overlay
                    document.getElementById('loadingOverlay').classList.add('active');
                    
                    // Make sure the rental_weeks value is properly set if this is a rental
                    const cartId = form.querySelector('input[name="cart_id"]').value;
                    const purchaseType = document.getElementById(`purchase_type_${cartId}`).value;
                    
                    if (purchaseType === 'rent') {
                        // Ensure the value is passed correctly
                        const rentalWeeks = this.value;
                        console.log(`Submitting form: cart_id=${cartId}, purchase_type=${purchaseType}, rental_weeks=${rentalWeeks}`);
                    }
                    
                    // Add a hidden input for update_item
                    const updateItemInput = document.createElement('input');
                    updateItemInput.type = 'hidden';
                    updateItemInput.name = 'update_item';
                    updateItemInput.value = '1';
                    form.appendChild(updateItemInput);
                    
                    // Add a small delay to allow the user to see the change before form submission
                    setTimeout(() => {
                        form.submit();
                    }, 300);
                }
            });
        });
        
        // Initialize all forms to show loading overlay on submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.add('active');
            });
        });
    });
    </script>
</body>
</html>
