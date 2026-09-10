<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Synchronize session user ID aliases for compatibility
if (isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    $_SESSION['id'] = $_SESSION['user_id'];
} elseif (isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
}

// --- 10-Minute Inactivity Timeout ---
$timeout_duration = 600; // 10 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // update last activity timestamp

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    // Store the requested page in session to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}
?>