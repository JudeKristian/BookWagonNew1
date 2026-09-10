<?php
session_start();
require_once 'includes/audit_logger.php'; // ensure this is here just in case

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bookwagon_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("System error: Unable to connect to the database.");
}
require_once 'includes/GoogleAuthenticator.php';
require_once 'includes/audit_logger.php';

// Check if user is in the middle of 2FA verification
if (!isset($_SESSION['pending_2fa_verification']) || !isset($_SESSION['temp_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['code']);
    
    // Get the user's secret and login_count from the database
    $stmt = $conn->prepare("SELECT google2fa_secret, login_count, usertype, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['temp_user_id']);
    $stmt->execute();
    $stmt->bind_result($secret, $login_count_db, $usertype_db, $status_db);
    $user_found = $stmt->fetch();
    $stmt->close();
    
    // Block suspended users
    if ($user_found && $status_db === 'suspended') {
        $error = 'Your account has been suspended. Please contact support.';
        $user_found = false;
    }
    
    if ($user_found && !empty($secret)) {
        $ga = new GoogleAuthenticator();
        $checkResult = $ga->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance
        
        if ($checkResult) {
            $prev_count = (int)$login_count_db;
            $new_count = $prev_count + 1;
            
            // Increment login count in database
            $update_stmt = $conn->prepare("UPDATE users SET login_count = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_count, $_SESSION['temp_user_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Success! Log them in fully.
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
            
            log_activity($_SESSION['user_id'], '2FA Login', 'User successfully passed 2FA.');
            
            // Log Login History
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
            $hist_sql = "INSERT INTO login_history (user_id, ip_address, device_info, status) VALUES (?, ?, ?, 'success')";
            if ($hstmt = $conn->prepare($hist_sql)) {
                $hstmt->bind_param("iss", $_SESSION['user_id'], $ip, $ua);
                $hstmt->execute();
                $hstmt->close();
            }
            
            // First time login (prev_count == 0) -> welcome.php, otherwise -> dashboard.php
            if ($prev_count === 0) {
                header('Location: welcome.php');
            } elseif (isset($_SESSION['redirect_after_login'])) {
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
    <style>
        body { 
            background-color: #f8f9fa; 
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
            border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            cursor: pointer;
            color: #adb5bd;
            font-size: 24px;
            text-decoration: none;
            line-height: 1;
            transition: color 0.2s;
        }
        .close-btn:hover { color: #212529; }
        h3 {
            font-weight: 700;
            color: #212529;
            margin-bottom: 10px;
            font-size: 22px;
        }
        .subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .code-inputs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 10px;
        }
        
        .code-inputs input {
            width: 50px;
            height: 60px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            border: 1.5px solid #dee2e6;
            border-radius: 8px;
            background-color: #fff;
            transition: all 0.2s;
            color: #212529;
            padding: 0;
        }
        
        .code-inputs input:focus {
            outline: none;
            border-color: #212529;
            box-shadow: 0 0 0 1px #212529;
        }
        
        .resend-text {
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .resend-text a {
            color: #212529;
            font-weight: 600;
            text-decoration: none;
        }
        
        .btn-confirm {
            background-color: #111;
            color: #fff;
            height: 54px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.2s;
        }
        .btn-confirm:hover {
            background-color: #000;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="verify-modal">
        <a href="login.php" class="close-btn">&times;</a>
        <h3>Two-Factor Authentication</h3>
        <p class="subtitle">Enter the verification code generated by<br>your authenticator app</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2" style="font-size: 14px; border-radius: 8px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="verify_2fa.php" method="POST" id="verifyForm">
            <!-- Hidden input to store the final 6 digit code -->
            <input type="hidden" name="code" id="finalCode">
            
            <div class="code-inputs" id="inputs">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" autofocus>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
            </div>
            
            <div class="resend-text">
                Having trouble? <a href="#">Contact Support</a>
            </div>
            
            <button type="submit" name="verify_code" class="btn btn-confirm">Confirm</button>
        </form>
    </div>

    <script>
        const inputs = document.querySelectorAll('.code-inputs input');
        const form = document.getElementById('verifyForm');
        const finalCode = document.getElementById('finalCode');

        // Handle Paste
        inputs[0].addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').substring(0, 6);
            for (let i = 0; i < pastedData.length; i++) {
                if (inputs[i]) {
                    inputs[i].value = pastedData[i];
                }
            }
            if (pastedData.length > 0) {
                const focusIndex = Math.min(pastedData.length, 5);
                inputs[focusIndex].focus();
            }
        });

        // Handle input typing and backspace
        inputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                // Ensure only numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value !== '') {
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '') {
                    if (index > 0) {
                        inputs[index - 1].focus();
                    }
                }
            });
        });

        // Intercept form submit
        form.addEventListener('submit', function(e) {
            let code = '';
            inputs.forEach(input => {
                code += input.value;
            });
            finalCode.value = code;
        });
    </script>
</body>
</html>
