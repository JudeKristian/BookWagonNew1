<?php
session_start();
require_once 'includes/audit_logger.php';

// Database configuration
include('connect.php');
require_once 'includes/send_mail.php';

// Check if user is in the middle of 2FA setup
if (!isset($_SESSION['pending_2fa_setup']) || !isset($_SESSION['temp_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$email = $_SESSION['temp_email'];

// Generate OTP if not exists
if (!isset($_SESSION['temp_2fa_otp'])) {
    $_SESSION['temp_2fa_otp'] = sprintf("%06d", mt_rand(1, 999999));
    
    // Send the OTP via email
    $firstname = $_SESSION['temp_firstname'] ?? 'User';
    send2FACode($email, $firstname, $_SESSION['temp_2fa_otp']);
}

$otp = $_SESSION['temp_2fa_otp'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['code']);
    
    // Verify the OTP
    if ($code === $_SESSION['temp_2fa_otp']) {
        // Generate 5 recovery codes
        $recovery_codes = [];
        for ($i = 0; $i < 5; $i++) {
            $recovery_codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4) . '-' . substr(bin2hex(random_bytes(4)), 0, 4));
        }
        $recovery_codes_json = json_encode($recovery_codes);
        
        // Success! Enable 2FA for user
        $stmt = $conn->prepare("UPDATE users SET is_2fa_enabled = 1, recovery_codes = ? WHERE id = ?");
        $stmt->bind_param("si", $recovery_codes_json, $_SESSION['temp_user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['recovery_codes'] = $recovery_codes;
            $_SESSION['2fa_setup_complete'] = true;
            
            // Setup complete. Log them in fully.
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['id'] = $_SESSION['temp_user_id'];
            $_SESSION['email'] = $_SESSION['temp_email'];
            $_SESSION['user'] = $_SESSION['temp_email'];
            $_SESSION['firstname'] = $_SESSION['temp_firstname'];
            $_SESSION['lastname'] = $_SESSION['temp_lastname'];
            $_SESSION['usertype'] = $_SESSION['temp_usertype'] ?? 'user';
            
            // Clean up temp vars
            unset($_SESSION['pending_2fa_setup']);
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_firstname']);
            unset($_SESSION['temp_lastname']);
            unset($_SESSION['temp_usertype']);
            unset($_SESSION['temp_login_count']);
            unset($_SESSION['temp_2fa_otp']);
            
            log_activity($_SESSION['user_id'], '2FA Setup', 'User successfully setup Email OTP 2FA with Recovery Codes.');
            
            // Log Login History
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
            $hist_sql = "INSERT INTO login_history (user_id, ip_address, device_info, status) VALUES (?, ?, ?, 'success')";
            if ($hstmt = $conn->prepare($hist_sql)) {
                $hstmt->bind_param("iss", $_SESSION['user_id'], $ip, $ua);
                $hstmt->execute();
                $hstmt->close();
            }
            
            $show_success_screen = true;
        } else {
            $error = "Database error. Please try again.";
        }
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Up Two-Factor Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: var(--bg-cream, #faebc8);
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .setup-container { 
            width: 100%;
            max-width: 420px; 
            margin: 50px auto; 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); 
            text-align: center; 
        }
        .btn-verify {
            background-color: #f8a100;
            color: #fff;
            height: 50px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.2s;
        }
        .btn-verify:hover {
            background-color: #e09000;
            color: #fff;
        }
        .form-control:focus {
            border-color: #f8a100;
            box-shadow: 0 0 0 0.2rem rgba(248, 161, 0, 0.25);
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <?php if(isset($show_success_screen) && $show_success_screen): ?>
            <h2 class="text-center mb-2">Setup Complete!</h2>
            <div class="alert alert-success mt-3" role="alert">
                Your account is now secured with Email Two-Factor Authentication.
            </div>
            
            <div class="text-start mt-4 mb-4" style="border: 2px solid #dc3545; border-radius: 8px; padding: 15px;">
                <h5 class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Save Your Recovery Codes</h5>
                <p class="small text-muted mb-3">If you ever lose access to your email, you can use these codes to log in. Each code can only be used ONCE. Please write them down or save them in a password manager.</p>
                <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; text-align: center; font-family: monospace; font-size: 1.2em; letter-spacing: 2px; font-weight: bold; color: #333;">
                    <?php foreach($_SESSION['recovery_codes'] as $rcode): ?>
                        <div class="mb-1"><?php echo $rcode; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <a href="home.php" class="btn btn-verify w-100 mb-3">I have saved my codes. Continue</a>
        <?php else: ?>
            <h2 class="text-center mb-2">Setup Two-Factor Authentication</h2>
            <p class="text-center text-muted mb-4">To secure your account, we require email verification. We've sent a 6-digit code to your email (<?php echo htmlspecialchars($email); ?>).</p>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label class="form-label fw-bold">Verification Code</label>
                    <input type="text" name="code" class="form-control text-center" style="font-size: 1.5em; letter-spacing: 5px;" maxlength="6" required autofocus placeholder="------">
                </div>
                
                <button type="submit" name="verify_code" class="btn btn-verify w-100 mb-3">Verify & Enable 2FA</button>
                <a href="security.php" class="btn btn-outline-secondary w-100">Cancel Setup</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
