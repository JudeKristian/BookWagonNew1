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
    sendResponse(false, 'Please log in to like comments');
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
if (!isset($_POST['comment_id']) || empty($_POST['comment_id'])) {
    sendResponse(false, 'Comment ID is required');
}

// Sanitize and validate input
$commentId = (int)$_POST['comment_id'];

// Check if comment exists
$commentStmt = $conn->prepare("SELECT comment_id FROM forum_comments WHERE comment_id = ?");
$commentStmt->bind_param("i", $commentId);
$commentStmt->execute();
$commentRes = $commentStmt->get_result();

if ($commentRes->num_rows === 0) {
    $commentStmt->close();
    sendResponse(false, 'Invalid comment');
}
$commentStmt->close();

// Check if user already liked this comment
$likeStmt = $conn->prepare("SELECT interaction_id FROM forum_user_interactions WHERE user_id = ? AND comment_id = ? AND interaction_type = 'like'");
$likeStmt->bind_param("ii", $userId, $commentId);
$likeStmt->execute();
$likeRes = $likeStmt->get_result();
$isLiked = ($likeRes->num_rows > 0);
$likeStmt->close();

if ($isLiked) {
    // User already liked this comment, so unlike it
    $delStmt = $conn->prepare("DELETE FROM forum_user_interactions WHERE user_id = ? AND comment_id = ? AND interaction_type = 'like'");
    $delStmt->bind_param("ii", $userId, $commentId);
    $delStmt->execute();
    $delStmt->close();
    
    // Get new like count
    $cntStmt = $conn->prepare("SELECT COUNT(*) as likes FROM forum_user_interactions WHERE comment_id = ? AND interaction_type = 'like'");
    $cntStmt->bind_param("i", $commentId);
    $cntStmt->execute();
    $likes = $cntStmt->get_result()->fetch_assoc()['likes'];
    $cntStmt->close();
    
    sendResponse(true, 'Comment unliked successfully', ['likes' => $likes]);
} else {
    // Add new like
    $insStmt = $conn->prepare("INSERT INTO forum_user_interactions (user_id, comment_id, interaction_type) VALUES (?, ?, 'like')");
    $insStmt->bind_param("ii", $userId, $commentId);
    
    if ($insStmt->execute()) {
        $insStmt->close();
        
        // Get new like count
        $cntStmt = $conn->prepare("SELECT COUNT(*) as likes FROM forum_user_interactions WHERE comment_id = ? AND interaction_type = 'like'");
        $cntStmt->bind_param("i", $commentId);
        $cntStmt->execute();
        $likes = $cntStmt->get_result()->fetch_assoc()['likes'];
        $cntStmt->close();
        
        sendResponse(true, 'Comment liked successfully', ['likes' => $likes]);
    } else {
        error_log("Failed to like comment: " . $insStmt->error);
        $insStmt->close();
        sendResponse(false, 'Failed to like comment. Please try again later.');
    }
}