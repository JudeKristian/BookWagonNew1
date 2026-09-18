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
    sendResponse(false, 'You must be logged in to comment');
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
$requiredFields = ['post_id', 'content'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        sendResponse(false, ucfirst($field) . ' is required');
    }
}

// Sanitize and validate input
$postId = (int)$_POST['post_id'];
$content = trim($_POST['content']);
$parentId = (isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) && (int)$_POST['parent_id'] > 0) ? (int)$_POST['parent_id'] : null;

// Check if post exists and is active
$postStmt = $conn->prepare("SELECT status FROM forum_posts WHERE post_id = ?");
$postStmt->bind_param("i", $postId);
$postStmt->execute();
$postRes = $postStmt->get_result();

if ($postRes->num_rows === 0) {
    $postStmt->close();
    sendResponse(false, 'Invalid post');
}

$postData = $postRes->fetch_assoc();
$postStmt->close();

if ($postData['status'] === 'closed') {
    sendResponse(false, 'This discussion is closed and cannot receive new comments');
}

// Check if parent comment exists if replying
if ($parentId) {
    $parentStmt = $conn->prepare("SELECT comment_id FROM forum_comments WHERE comment_id = ?");
    $parentStmt->bind_param("i", $parentId);
    $parentStmt->execute();
    if ($parentStmt->get_result()->num_rows === 0) {
        $parentStmt->close();
        sendResponse(false, 'Invalid parent comment');
    }
    $parentStmt->close();
}

// Insert the comment with prepared statement
$insertStmt = $conn->prepare("INSERT INTO forum_comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("iisi", $postId, $userId, $content, $parentId);

if ($insertStmt->execute()) {
    $commentId = $conn->insert_id;
    $insertStmt->close();
    sendResponse(true, 'Comment posted successfully', ['comment_id' => $commentId]);
} else {
    error_log("Failed to post comment: " . $insertStmt->error);
    $insertStmt->close();
    sendResponse(false, 'Failed to post comment. Please try again later.');
} 