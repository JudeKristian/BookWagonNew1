<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "connect.php";
require_once "includes/notification_helper.php";

$userId = $_SESSION['id'];
$itemId = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$message = "";
$messageType = "danger";

// Fetch item details and verify ownership
$itemQuery = "SELECT oi.*, b.title, b.cover_image, o.first_name as buyer_name, o.phone as buyer_phone 
              FROM order_items oi 
              JOIN books b ON oi.book_id = b.book_id 
              JOIN orders o ON oi.order_id = o.order_id 
              WHERE oi.item_id = ? AND oi.seller_id = ?";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("ii", $itemId, $userId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Invalid request or unauthorized access.");
}

if ($item['status'] !== 'pending_meetup') {
    $message = "This item is not pending a meetup.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['condition_photo']) && $item['status'] === 'pending_meetup') {
    if ($_FILES['condition_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/conditions/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $maxFileSize = 5 * 1024 * 1024; // 5MB limit
        if ($_FILES['condition_photo']['size'] > $maxFileSize) {
            $message = "Photo exceeds maximum file size of 5MB.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['condition_photo']['tmp_name']);
            finfo_close($finfo);

            $ext = strtolower(pathinfo($_FILES['condition_photo']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($ext, $allowedExts) && in_array($mime, $allowedMimes)) {
                $fileName = 'condition_' . $itemId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['condition_photo']['tmp_name'], $targetPath)) {
                    chmod($targetPath, 0644);
                    $token = bin2hex(random_bytes(16)); // Generate 32 char token
                    
                    $updStmt = $conn->prepare("UPDATE order_items SET initial_condition_photo = ?, seller_handover_token = ? WHERE item_id = ?");
                    $updStmt->bind_param("ssi", $targetPath, $token, $itemId);
                    if ($updStmt->execute()) {
                        // Refresh data
                        $item['initial_condition_photo'] = $targetPath;
                        $item['seller_handover_token'] = $token;
                        $message = "Photo uploaded and QR Code generated!";
                        $messageType = "success";
                    }
                } else {
                    $message = "Failed to save file.";
                }
            } else {
                $message = "Invalid file type. Only JPG, PNG, WEBP allowed.";
            }
        }
    } else {
        $message = "Error uploading file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiate Handover - Bookwagon</title>
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
                    <h3 class="fw-bold">Initiate Handover</h3>
                    <p class="text-muted">You are handing over <strong><?php echo htmlspecialchars($item['title']); ?></strong> to <strong><?php echo htmlspecialchars($item['buyer_name']); ?></strong>.</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (empty($item['seller_handover_token'])): ?>
                    <!-- Step 1: Upload Photo -->
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-camera me-2"></i> <strong>Required:</strong> Please take a clear photo of the book's current condition before handing it over. This protects you in case of damage disputes.
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Condition Photo</label>
                            <input type="file" class="form-control" name="condition_photo" accept="image/*" required capture="environment">
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold">Upload & Generate QR Code</button>
                    </form>

                <?php else: ?>
                    <!-- Step 2: Show QR Code -->
                    <div class="text-center">
                        <div class="alert alert-success">
                            <i class="fa-solid fa-check-circle me-2"></i> Condition photo uploaded successfully!
                        </div>
                        <h5 class="fw-bold mb-3">Ask the Buyer to scan this QR Code</h5>
                        
                        <?php
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $qrLink = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/BookwagonNew/renter_handover.php?token=" . urlencode($item['seller_handover_token']);
                        ?>
                        <div class="qr-container mb-4">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qrLink); ?>" alt="QR Code" class="img-fluid">
                        </div>
                        
                        <div class="text-muted small mb-4">
                            Once they scan this and confirm the condition on their phone, they will show you a new QR Code. <br><strong>Scan their QR Code to finalize the transaction!</strong>
                        </div>
                        
                        <a href="seller_dashboard.php" class="btn btn-light w-100 border">Back to Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
