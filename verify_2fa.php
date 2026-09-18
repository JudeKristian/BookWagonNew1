<?php
session_start();
require_once 'includes/audit_logger.php'; // ensure this is here just in case

// Database configuration
include('connect.php');
require_once 'includes/send_mail.php';
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bookwagon_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("System error: Unable to connect to the database.");
}
require_once 'includes/audit_logger.php';

// Check if user is in the middle of 2FA verification
if (!isset($_SESSION['pending_2fa_verification']) || !isset($_SESSION['temp_user_id'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['temp_email'] ?? '';

// Generate OTP if not exists
if (!isset($_SESSION['temp_2fa_otp'])) {
    $_SESSION['temp_2fa_otp'] = sprintf("%06d", mt_rand(1, 999999));
    
    // Send the OTP via email
    $firstname = $_SESSION['temp_firstname'] ?? 'User';
    send2FACode($email, $firstname, $_SESSION['temp_2fa_otp']);
}

$otp = $_SESSION['temp_2fa_otp'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['code'] ?? '');
    
    // Get the user's login_count from the database
    $stmt = $conn->prepare("SELECT login_count, usertype, status, recovery_codes FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['temp_user_id']);
    $stmt->execute();
    $stmt->bind_result($login_count_db, $usertype_db, $status_db, $recovery_codes_json);
    $user_found = $stmt->fetch();
    $stmt->close();
    
    // Block suspended users
    if ($user_found && $status_db === 'suspended') {
        $error = 'Your account has been suspended. Please contact support.';
        $user_found = false;
    }
    
    if ($user_found) {
        $valid_login = false;
        $used_recovery = false;
        
        // 1. Check if it's the normal OTP code
        if (isset($_POST['code']) && !empty($_POST['code'])) {
            $code = trim($_POST['code']);
            if ($code === $_SESSION['temp_2fa_otp']) {
                $valid_login = true;
            } else {
                $error = "Invalid verification code. Please try again.";
            }
        } 
        // 2. Check if it's a Recovery Code
        elseif (isset($_POST['recovery_code']) && !empty($_POST['recovery_code'])) {
            $recovery_code = trim($_POST['recovery_code']);
            $saved_codes = json_decode($recovery_codes_json, true) ?: [];
            
            if (in_array($recovery_code, $saved_codes)) {
                $valid_login = true;
                $used_recovery = true;
                
                // Remove the used code so it can't be used again
                $saved_codes = array_diff($saved_codes, [$recovery_code]);
                $new_codes_json = json_encode(array_values($saved_codes));
                
                $update_codes = $conn->prepare("UPDATE users SET recovery_codes = ? WHERE id = ?");
                $update_codes->bind_param("si", $new_codes_json, $_SESSION['temp_user_id']);
                $update_codes->execute();
                $update_codes->close();
            } else {
                $error = "Invalid recovery code. Please try again.";
            }
        } else {
            $error = "Please enter a code.";
        }

        if ($valid_login) {
            $prev_count = (int)$login_count_db;
            $new_count = $prev_count + 1;
            
            // Increment login count in database
            $update_stmt = $conn->prepare("UPDATE users SET login_count = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_count, $_SESSION['temp_user_id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Success! Log them in fully.
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['email'] = $_SESSION['temp_email'];
            $_SESSION['user'] = $_SESSION['temp_email'];
            $_SESSION['firstname'] = $_SESSION['temp_firstname'];
            $_SESSION['lastname'] = $_SESSION['temp_lastname'];
            $_SESSION['usertype'] = $usertype_db ?? 'user';
            $_SESSION['login_count'] = $new_count;

            // Clean up temp vars
            unset($_SESSION['pending_2fa_verification']);
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_firstname']);
            unset($_SESSION['temp_lastname']);
            unset($_SESSION['temp_usertype']);
            unset($_SESSION['temp_login_count']);
            unset($_SESSION['temp_2fa_otp']);

            $log_msg = $used_recovery ? 'User successfully logged in using a Recovery Code.' : 'User successfully passed 2FA.';
            log_activity($_SESSION['user_id'], '2FA Login', $log_msg);

            // Log Login History
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
            $hist_sql = "INSERT INTO login_history (user_id, ip_address, device_info, status) VALUES (?, ?, ?, 'success')";
            if ($hstmt = $conn->prepare($hist_sql)) {
                $hstmt->bind_param("iss", $_SESSION['user_id'], $ip, $ua);
                $hstmt->execute();
                $hstmt->close();
            }

            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
            } else {
                header('Location: home.php');
            }
            exit;
        } else {
            $error = "Invalid verification code. Please try again.";
        }
    } else {
        $error = "2FA is not configured for this account.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for icons -->
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

        .verify-modal {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            position: relative;
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
        
        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #6c757d;
            font-size: 0.9em;
            text-decoration: none;
            cursor: pointer;
        }
        
        .toggle-link:hover {
            color: #212529;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="verify-modal">
        <h2 class="text-center mb-2">Two-Factor Authentication</h2>
        <p class="text-center text-muted mb-4" id="mainSubtitle">We've sent a 6-digit verification code to your email (<?php echo htmlspecialchars($email); ?>). Please enter it below to continue.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Normal OTP Form -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="otpForm">
            <div class="mb-4">
                <label class="form-label fw-bold">Verification Code</label>
                <input type="text" name="code" class="form-control text-center" style="font-size: 1.5em; letter-spacing: 5px;" maxlength="6" autofocus placeholder="------">
            </div>
            
            <button type="submit" name="verify_code" class="btn btn-verify w-100 mb-2">Verify & Login</button>
            <a href="logout.php" class="btn btn-outline-secondary w-100">Cancel</a>
            
            <a onclick="toggleForms()" class="toggle-link">Can't access your email? Use a Recovery Code</a>
        </form>
        
        <!-- Recovery Code Form (Hidden by default) -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="recoveryForm" style="display: none;">
            <div class="mb-4">
                <label class="form-label fw-bold text-danger"><i class="fas fa-key me-2"></i>Recovery Code</label>
                <input type="text" name="recovery_code" class="form-control text-center" style="font-size: 1.2em; letter-spacing: 2px; font-family: monospace;" placeholder="XXXX-XXXX">
            </div>
            
            <button type="submit" name="verify_code" class="btn btn-danger w-100 mb-2 fw-bold">Use Recovery Code</button>
            <a onclick="toggleForms()" class="btn btn-outline-secondary w-100">Back to Email Code</a>
        </form>
    </div>
    
    <script>
        function toggleForms() {
            const otpForm = document.getElementById('otpForm');
            const recoveryForm = document.getElementById('recoveryForm');
            const subtitle = document.getElementById('mainSubtitle');
            
            if (otpForm.style.display === 'none') {
                otpForm.style.display = 'block';
                recoveryForm.style.display = 'none';
                subtitle.innerHTML = "We've sent a 6-digit verification code to your email (<?php echo htmlspecialchars($email); ?>). Please enter it below to continue.";
            } else {
                otpForm.style.display = 'none';
                recoveryForm.style.display = 'block';
                subtitle.innerHTML = "Enter one of your emergency recovery codes to regain access to your account. This will consume the code.";
            }
        }
    </script>
</body>

</html>