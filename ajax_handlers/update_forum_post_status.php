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
    sendResponse(false, 'You must be logged in to perform this action');
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
if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
    sendResponse(false, 'Post ID is required');
}

if (!isset($_POST['status']) || empty($_POST['status'])) {
    sendResponse(false, 'Status is required');
}

// Sanitize and validate input
$postId = (int)$_POST['post_id'];
$status = trim($_POST['status']);

// Validate status value
$validStatuses = ['active', 'closed', 'hidden'];
if (!in_array($status, $validStatuses)) {
    sendResponse(false, 'Invalid status value');
}

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
    sendResponse(false, 'You do not have permission to modify this post');
}

// Update the post status with prepared statement
$updateStmt = $conn->prepare("UPDATE forum_posts SET status = ? WHERE post_id = ?");
$updateStmt->bind_param("si", $status, $postId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    sendResponse(true, 'Post status updated successfully');
} else {
    error_log("Failed to update post status: " . $updateStmt->error);
    $updateStmt->close();
    sendResponse(false, 'Failed to update post status. Please try again later.');
} 