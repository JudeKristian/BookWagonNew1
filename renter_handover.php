<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "connect.php";

$userId = $_SESSION['id'];
$sellerToken = isset($_GET['token']) ? $_GET['token'] : '';
$message = "";
$messageType = "danger";

if (empty($sellerToken)) {
    die("Invalid Handover Token.");
}

// Fetch item based on token and ensure the logged in user is the buyer
$itemQuery = "SELECT oi.*, b.title, u.username as seller_name 
              FROM order_items oi 
              JOIN books b ON oi.book_id = b.book_id 
              JOIN orders o ON oi.order_id = o.order_id 
              JOIN users u ON oi.seller_id = u.id
              WHERE oi.seller_handover_token = ? AND o.user_id = ?";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("si", $sellerToken, $userId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Invalid request, unauthorized access, or the token is incorrect.");
}

if ($item['status'] !== 'pending_meetup') {
    $message = "This item is not pending a meetup or has already been handed over.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_condition']) && $item['status'] === 'pending_meetup') {
    $comments = trim($_POST['condition_comments'] ?? '');
    
    // Generate renter token
    $renterToken = bin2hex(random_bytes(16));
    
    $updStmt = $conn->prepare("UPDATE order_items SET renter_condition_comments = ?, renter_handover_token = ? WHERE item_id = ?");
    $updStmt->bind_param("ssi", $comments, $renterToken, $item['item_id']);
    
    if ($updStmt->execute()) {
        $item['renter_handover_token'] = $renterToken;
        $message = "Condition confirmed! Please show this QR code to the seller to finalize the handover.";
        $messageType = "success";
    } else {
        $message = "Error confirming condition.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Book Receipt - Bookwagon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .qr-container { padding: 20px; background: white; border: 2px dashed #cbd5e1; border-radius: 12px; display: inline-block; }
    </style>
</head>
<body>

<?php include "include/user_header.php"; ?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-success">Scan Successful</h3>
                    <p class="text-muted">You are receiving <strong><?php echo htmlspecialchars($item['title']); ?></strong> from <strong><?php echo htmlspecialchars($item['seller_name']); ?></strong>.</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (empty($item['renter_handover_token'])): ?>
                    <!-- Step 1: Confirm Condition -->
                    <div class="alert alert-info">
                        <i class="fa-solid fa-clipboard-check me-2"></i> Please inspect the book physically. Does it match the expected condition?
                    </div>
                    
                    <form method="POST">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmCondition" name="confirm_condition" required>
                            <label class="form-check-label fw-semibold" for="confirmCondition">
                                Yes, I confirm the book is in acceptable condition and I am receiving it now.
                            </label>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Any comments about the condition? (Optional)</label>
                            <textarea class="form-control" name="condition_comments" rows="2" placeholder="e.g. slight crease on the cover, but otherwise good."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">Confirm & Receive Book</button>
                    </form>

                <?php else: ?>
                    <!-- Step 2: Show QR Code #2 to Seller -->
                    <div class="text-center">
                        <h5 class="fw-bold mb-3">Final Step! Show this to the Seller</h5>
                        
                        <?php
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $qrLink = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/BookwagonNew/seller_confirm_handover.php?token=" . urlencode($item['renter_handover_token']);
                        ?>
                        <div class="qr-container mb-4">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qrLink); ?>" alt="QR Code" class="img-fluid">
                        </div>
                        
                        <div class="text-muted small mb-4">
                            The seller must scan this QR Code from their device to officially lock the escrow and complete the transaction.
                        </div>
                        
                        <a href="account.php" class="btn btn-light w-100 border">Go to My Account</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
