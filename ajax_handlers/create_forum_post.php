<?php
session_start();
include("../connect.php");

// Return response function
function sendResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    sendResponse(false, 'You must be logged in to create a post');
}

// Get user ID from session
$userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

if (!$userId) {
    sendResponse(false, 'Invalid user session');
}

// Validate form data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Required fields
$requiredFields = ['title', 'content', 'category_id'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        sendResponse(false, ucfirst($field) . ' is required');
    }
}

// Sanitize and validate input
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$categoryId = (int)$_POST['category_id'];
$tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';

// Check if category exists
$catStmt = $conn->prepare("SELECT category_id FROM forum_categories WHERE category_id = ?");
$catStmt->bind_param("i", $categoryId);
$catStmt->execute();
if ($catStmt->get_result()->num_rows === 0) {
    $catStmt->close();
    sendResponse(false, 'Invalid category selected');
}
$catStmt->close();

// Insert the post with prepared statement
$insertStmt = $conn->prepare("INSERT INTO forum_posts (category_id, user_id, title, content, tags) VALUES (?, ?, ?, ?, ?)");
$insertStmt->bind_param("iisss", $categoryId, $userId, $title, $content, $tags);

if ($insertStmt->execute()) {
    $postId = $conn->insert_id;
    $insertStmt->close();
    sendResponse(true, 'Post created successfully', ['post_id' => $postId]);
} else {
    error_log("Failed to create post: " . $insertStmt->error);
    $insertStmt->close();
    sendResponse(false, 'Failed to create post. Please try again later.');
} 