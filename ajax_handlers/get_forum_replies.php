<?php
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

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method');
}

// Required parameters
if (!isset($_GET['comment_id']) || empty($_GET['comment_id'])) {
    sendResponse(false, 'Comment ID is required');
}

// Sanitize and validate input
$commentId = (int)$_GET['comment_id'];

// Check if parent comment exists
$chkStmt = $conn->prepare("SELECT comment_id FROM forum_comments WHERE comment_id = ?");
$chkStmt->bind_param("i", $commentId);
$chkStmt->execute();
if ($chkStmt->get_result()->num_rows === 0) {
    $chkStmt->close();
    sendResponse(false, 'Invalid comment');
}
$chkStmt->close();

// Get replies with prepared statement
$repliesQuery = "SELECT fc.*, 
                u.firstname, 
                u.lastname, 
                u.email,
                u.profile_picture,
                (SELECT COUNT(*) FROM forum_user_interactions 
                 WHERE comment_id = fc.comment_id AND interaction_type = 'like') as likes
                FROM forum_comments fc
                LEFT JOIN users u ON fc.user_id = u.id
                WHERE fc.parent_id = ?
                ORDER BY fc.created_at ASC";

$stmtReplies = $conn->prepare($repliesQuery);
$stmtReplies->bind_param("i", $commentId);
$stmtReplies->execute();
$repliesResult = $stmtReplies->get_result();
$replies = [];

if ($repliesResult && $repliesResult->num_rows > 0) {
    while ($reply = $repliesResult->fetch_assoc()) {
        // Format the data
        $replies[] = [
            'comment_id' => $reply['comment_id'],
            'content' => nl2br(htmlspecialchars($reply['content'])),
            'firstname' => htmlspecialchars($reply['firstname']),
            'lastname' => htmlspecialchars($reply['lastname']),
            'profile_picture' => (!empty($reply['profile_picture']) && file_exists('../' . $reply['profile_picture'])) 
                ? htmlspecialchars($reply['profile_picture']) 
                : '',
            'likes' => $reply['likes'],
            'created_at' => time_elapsed_string($reply['created_at'])
        ];
    }
}

// Time elapsed function
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

sendResponse(true, 'Replies retrieved successfully', ['replies' => $replies]); 