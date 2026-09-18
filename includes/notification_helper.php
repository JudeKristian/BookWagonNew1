<?php
/**
 * Sends a notification to a user.
 * 
 * @param PDO|mysqli $conn The database connection (supports both PDO and mysqli)
 * @param int $user_id The recipient user ID
 * @param int $sender_id The sender user ID (Admin ID, or another user's ID)
 * @param string $type The notification type (e.g., 'product_approved', 'new_order')
 * @param string $content The text content of the notification
 * @return bool True on success, false on failure
 */
function sendNotification($conn, $user_id, $sender_id, $type, $content) {
    if (!$conn || !$user_id || !$sender_id || empty($type) || empty($content)) {
        return false;
    }

    try {
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type, content, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            return $stmt->execute([$user_id, $sender_id, $type, $content]);
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, type, content, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            if ($stmt) {
                $stmt->bind_param("iiss", $user_id, $sender_id, $type, $content);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
    }
    
    return false;
}
?>
