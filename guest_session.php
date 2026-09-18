<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_guest = false;

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    $is_guest = true;
    
    // Set some defaults so page logic doesn't crash
    // We don't write to $_SESSION here because that would make them "logged in" in other parts of the site
    // Instead we rely on $is_guest flag for local checks, but to avoid undefined index errors in views:
    $guest_user_id = 0;
    $guest_firstname = 'Guest';
    $guest_lastname = '';
    $guest_usertype = 'guest';
} else {
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
    
    $guest_user_id = $_SESSION['user_id'];
    $guest_firstname = $_SESSION['firstname'] ?? '';
    $guest_lastname = $_SESSION['lastname'] ?? '';
    $guest_usertype = $_SESSION['usertype'] ?? '';
}
?>
