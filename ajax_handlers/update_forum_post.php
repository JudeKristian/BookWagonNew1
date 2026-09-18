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
    sendResponse(false, 'You must be logged in to update a post');
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
$requiredFields = ['post_id', 'title', 'content', 'category_id'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        sendResponse(false, ucfirst($field) . ' is required');
    }
}

// Sanitize and validate input
$postId = (int)$_POST['post_id'];
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$categoryId = (int)$_POST['category_id'];
$tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';

// Check if post exists and user is the author
$postStmt = $conn->prepare("SELECT user_id FROM forum_posts WHERE post_id = ?");
$postStmt->bind_param("i", $postId);
$postStmt->execute();
$postRes = $postStmt->get_result();

if ($postRes->num_rows === 0) {
    $postStmt->close();
    sendResponse(false, 'Invalid post');
}

$postRow = $postRes->fetch_assoc();
$postStmt->close();

if ($postRow['user_id'] != $userId) {
    sendResponse(false, 'You do not have permission to edit this post');
}

// Check if category exists
$catStmt = $conn->prepare("SELECT category_id FROM forum_categories WHERE category_id = ?");
$catStmt->bind_param("i", $categoryId);
$catStmt->execute();
if ($catStmt->get_result()->num_rows === 0) {
    $catStmt->close();
    sendResponse(false, 'Invalid category selected');
}
$catStmt->close();

// Update the post with prepared statement
$updateStmt = $conn->prepare("UPDATE forum_posts SET category_id = ?, title = ?, content = ?, tags = ?, updated_at = NOW() WHERE post_id = ?");
$updateStmt->bind_param("isssi", $categoryId, $title, $content, $tags, $postId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    sendResponse(true, 'Post updated successfully');
} else {
    error_log("Failed to update post: " . $updateStmt->error);
    $updateStmt->close();
    sendResponse(false, 'Failed to update post. Please try again later.');
}