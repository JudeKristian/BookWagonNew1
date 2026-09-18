<?php
/**
 * Audit Logger Utility
 * Records important user activities to the audit_logs table.
 */

function log_activity($user_id, $activity, $details = "") {
    // Determine which type of connection to use based on what's available
    global $conn, $pdo;
    
    // Resolve client IP address safely
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($ips[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip_address = $candidate;
        }
    }
    
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            // Using PDO (e.g., from connect.php)
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, activity, details, ip_address) VALUES (:user_id, :action, :activity, :details, :ip_address)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':action' => $activity,
                ':activity' => $activity,
                ':details' => $details,
                ':ip_address' => $ip_address
            ]);
        } elseif (isset($conn) && $conn instanceof mysqli) {
            // Using mysqli (e.g., from login.php/signup.php)
            $sql = "INSERT INTO audit_logs (user_id, action, activity, details, ip_address) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("issss", $user_id, $activity, $activity, $details, $ip_address);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // No DB connection found, log to file as fallback
            error_log("Audit Log [Fallback]: User $user_id - $activity - $details (IP: $ip_address)");
        }
    } catch (Exception $e) {
        // Silently fail but log to PHP error log to prevent breaking user flow
        error_log("Failed to insert audit log: " . $e->getMessage());
    }
}
?>
