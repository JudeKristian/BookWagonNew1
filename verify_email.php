<?php
session_start();
require_once 'includes/audit_logger.php';
include('connect.php');

$msg = "";
$msg_type = "";

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    $sql = "SELECT id, is_verified FROM users WHERE verification_token = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($user_id, $is_verified);
        
        if ($stmt->fetch()) {
            if ($is_verified == 1) {
                $msg = "This email is already verified. You can log in.";
                $msg_type = "info";
            } else {
                $stmt->close();
                // Update user to verified
                $update_sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $user_id);
                    if ($update_stmt->execute()) {
                        $msg = "Your email has been successfully verified! You can now log in.";
                        $msg_type = "success";
                        log_activity($user_id, 'Email Verified', 'User successfully verified their email address.');
                    } else {
                        $msg = "An error occurred during verification. Please try again.";
                        $msg_type = "danger";
                    }
                    $update_stmt->close();
                }
            }
        } else {
            $msg = "Invalid verification token. It may have expired or already been used.";
            $msg_type = "danger";
            $stmt->close();
        }
    }
} else {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - BookWagon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .verify-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; }
        .icon { font-size: 60px; margin-bottom: 20px; }
        .text-success { color: #28a745 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-info { color: #17a2b8 !important; }
    </style>
</head>
<body>

<div class="verify-container">
    <?php if ($msg_type === "success"): ?>
        <div class="icon text-success">✓</div>
        <h2 class="mb-3">Verified!</h2>
    <?php elseif ($msg_type === "danger"): ?>
        <div class="icon text-danger">✗</div>
        <h2 class="mb-3">Verification Failed</h2>
    <?php else: ?>
        <div class="icon text-info">ℹ</div>
        <h2 class="mb-3">Notice</h2>
    <?php endif; ?>
    
    <p class="lead mb-4"><?php echo htmlspecialchars($msg); ?></p>
    
    <a href="login.php" class="btn btn-primary w-100 p-3" style="background-color: #f8a100; border: none; font-weight: bold;">Go to Login</a>
</div>

</body>
</html>
