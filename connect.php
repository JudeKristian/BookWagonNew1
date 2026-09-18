<?php
// Database configuration
$host = 'localhost';
$dbname = 'bookwagon_db';
$username = 'root';
$password = '';

// 1. Create MySQLi connection ($conn)
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("MySQLi Connection Error: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}
$conn->set_charset("utf8");

// Product approval is required by the marketplace queries. Keep older databases compatible.
$approvalColumn = $conn->query("SHOW COLUMNS FROM books LIKE 'approval_status'");
if ($approvalColumn && $approvalColumn->num_rows === 0) {
    $conn->query("ALTER TABLE books ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER user_id");
}

// 2. Create PDO instance ($pdo)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Set authorized application context for intrusion detection triggers if authenticated
if (session_status() === PHP_SESSION_ACTIVE && (!empty($_SESSION['id']) || !empty($_SESSION['admin_id']) || !empty($_SESSION['loggedin']))) {
    $currentUserId = intval($_SESSION['id'] ?? ($_SESSION['admin_id'] ?? 0));
    $conn->query("SET @app_authorized = 1, @app_user_id = $currentUserId");
    if (isset($pdo)) {
        $pdo->query("SET @app_authorized = 1, @app_user_id = $currentUserId");
    }
}
?>
