<?php
// Database connection parameters
$servername = "localhost";
$username = "root";  // replace with your database username
$password = "";      // replace with your database password
$dbname = "bookwagon_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set authorized admin application context for database intrusion detection triggers
if (session_status() === PHP_SESSION_ACTIVE && (!empty($_SESSION['admin_id']) || !empty($_SESSION['admin_loggedin']))) {
    $adminId = intval($_SESSION['admin_id'] ?? 1);
    $conn->query("SET @app_authorized = 1, @app_user_id = $adminId");
}
?>