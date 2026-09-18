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
    sendResponse(false, 'Please log in to like posts');
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

// Sanitize and validate input
$postId = (int)$_POST['post_id'];

// Check if post exists
$postStmt = $conn->prepare("SELECT post_id FROM forum_posts WHERE post_id = ?");
$postStmt->bind_param("i", $postId);
$postStmt->execute();
$postRes = $postStmt->get_result();

if ($postRes->num_rows === 0) {
    $postStmt->close();
    sendResponse(false, 'Invalid post');
}
$postStmt->close();

// Check if user already liked this post
$likeStmt = $conn->prepare("SELECT interaction_id FROM forum_user_interactions WHERE user_id = ? AND post_id = ? AND interaction_type = 'like'");
$likeStmt->bind_param("ii", $userId, $postId);
$likeStmt->execute();
$likeRes = $likeStmt->get_result();
$isLiked = ($likeRes->num_rows > 0);
$likeStmt->close();

if ($isLiked) {
    // User already liked this post, so unlike it
    $delStmt = $conn->prepare("DELETE FROM forum_user_interactions WHERE user_id = ? AND post_id = ? AND interaction_type = 'like'");
    $delStmt->bind_param("ii", $userId, $postId);
    $delStmt->execute();
    $delStmt->close();
    
    // Get new like count
    $cntStmt = $conn->prepare("SELECT COUNT(*) as likes FROM forum_user_interactions WHERE post_id = ? AND interaction_type = 'like'");
    $cntStmt->bind_param("i", $postId);
    $cntStmt->execute();
    $likes = $cntStmt->get_result()->fetch_assoc()['likes'];
    $cntStmt->close();
    
    sendResponse(true, 'Post unliked successfully', ['likes' => $likes, 'liked' => false]);
} else {
    // Add new like
    $insStmt = $conn->prepare("INSERT INTO forum_user_interactions (user_id, post_id, interaction_type) VALUES (?, ?, 'like')");
    $insStmt->bind_param("ii", $userId, $postId);
    
    if ($insStmt->execute()) {
        $insStmt->close();
        
        // Get new like count
        $cntStmt = $conn->prepare("SELECT COUNT(*) as likes FROM forum_user_interactions WHERE post_id = ? AND interaction_type = 'like'");
        $cntStmt->bind_param("i", $postId);
        $cntStmt->execute();
        $likes = $cntStmt->get_result()->fetch_assoc()['likes'];
        $cntStmt->close();
        
        sendResponse(true, 'Post liked successfully', ['likes' => $likes, 'liked' => true]);
    } else {
        error_log("Failed to like post: " . $insStmt->error);
        $insStmt->close();
        sendResponse(false, 'Failed to like post. Please try again later.');
    }
} 