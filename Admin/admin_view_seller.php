<?php
// Initialize the session
session_start();
 
// Check if the user is logged in as admin, if not then redirect to admin login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Include database connection
require_once "db_connect.php";
require_once "../includes/notification_helper.php";

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: admin_sellers.php");
    exit;
}

$id = $_GET['id'];

// Fetch seller application details
$sql = "SELECT s.*, u.email, u.username, u.created_at as user_created_at 
        FROM sellers s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    // If no record found, redirect back to seller list
    header("location: admin_sellers.php");
    exit;
}

$seller = $result->fetch_assoc();

// Process approval/rejection actions
if(isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if($action == 'approve') {
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Update seller status to approved
            $update_seller = "UPDATE sellers SET status = 'approved' WHERE id = ?";
            $stmt = $conn->prepare($update_seller);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Update user type to seller
            $update_user = "UPDATE users SET usertype = 'seller' WHERE id = ?";
            $stmt = $conn->prepare($update_user);
            $stmt->bind_param("i", $seller['user_id']);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Seller application approved successfully!";
            
            // Send notification
            $notifContent = "Congratulations! Your seller application for '" . $seller['shop_name'] . "' has been approved. You can now start listing books.";
            sendNotification($conn, $seller['user_id'], $_SESSION['admin_id'] ?? 1, 'seller_approved', $notifContent);
            
            // Refresh seller data
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $seller = $result->fetch_assoc();
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif($action == 'reject') {
        // Update seller status to rejected
        $update_seller = "UPDATE sellers SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($update_seller);
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()) {
            $success_message = "Seller application rejected.";
            
            // Send notification
            $notifContent = "Your seller application for '" . $seller['shop_name'] . "' was not approved at this time. Please check your application status or contact support for more details.";
            sendNotification($conn, $seller['user_id'], $_SESSION['admin_id'] ?? 1, 'seller_rejected', $notifContent);
            
            // Refresh seller data
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $seller = $result->fetch_assoc();
        } else {
            $error_message = "Error updating record: " . $conn->error;
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Seller Application - BookWagon Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            color: white;
            padding: 15px 30px;
            margin-bottom: 30px;
        }
        .welcome-msg {
            margin: 0;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
        .logout-btn {
            background-color: #e74c3c;
            color: white;
        }
        .logout-btn:hover {
            background-color: #c0392b;
        }
        .dashboard-content {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #f4f4f4;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .seller-details {
            margin-top: 20px;
        }
        .detail-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        .detail-label {
            width: 200px;
            font-weight: 500;
            color: #555;
        }
        .detail-value {
            flex: 1;
        }
        .status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
        }
        .status-pending {
            background-color: #fef9e7;
            color: #f39c12;
        }
        .status-approved {
            background-color: #e9f7ef;
            color: #27ae60;
        }
        .status-rejected {
            background-color: #fdedec;
            color: #e74c3c;
        }
        .shop-logo {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        .btn-approve {
            background-color: #27ae60;
            color: white;
        }
        .btn-approve:hover {
            background-color: #219955;
        }
        .btn-reject {
            background-color: #e74c3c;
            color: white;
        }
        .btn-reject:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome-msg">View Seller Application</div>
        <div>
            <a href="admin_sellers.php" class="btn btn-back">Back to List</a>
            <a href="logout.php" class="btn logout-btn">Sign Out</a>
        </div>
    </div>
    
    <div class="dashboard-container">
        <div class="dashboard-content">
            <h2>Seller Application Details</h2>
            
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="seller-details">
                <div class="detail-section">
                    <div class="section-title">Application Status</div>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value">
                            <?php if($seller['status'] == 'pending'): ?>
                                <span class="status status-pending">Pending</span>
                            <?php elseif($seller['status'] == 'approved'): ?>
                                <span class="status status-approved">Approved</span>
                            <?php elseif($seller['status'] == 'rejected'): ?>
                                <span class="status status-rejected">Rejected</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Application Date:</div>
                        <div class="detail-value"><?php echo date('F d, Y - h:i A', strtotime($seller['created_at'])); ?></div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="section-title">Shop Information</div>
                    <div class="detail-row">
                        <div class="detail-label">Shop Name:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['shop_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Shop Logo:</div>
                        <div class="detail-value">
                            <?php if(!empty($seller['shop_logo']) && file_exists($seller['shop_logo'])): ?>
                                <img src="<?php echo $seller['shop_logo']; ?>" alt="Shop Logo" class="shop-logo">
                            <?php else: ?>
                                <span>No logo uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="section-title">Business Information</div>
                    <div class="detail-row">
                        <div class="detail-label">Seller Type:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['seller_type']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Business Name:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['business_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Owner Name:</div>
                        <div class="detail-value">
                            <?php 
                                $fullName = $seller['first_name'];
                                if(!empty($seller['middle_name'])) {
                                    $fullName .= ' ' . $seller['middle_name'];
                                }
                                $fullName .= ' ' . $seller['last_name'];
                                echo htmlspecialchars($fullName); 
                            ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Location:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['location']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['address']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Zip Code:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['zip_code']); ?></div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="section-title">Contact Information</div>
                    <div class="detail-row">
                        <div class="detail-label">Business Email:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['business_email']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Business Phone:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['business_phone']); ?></div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="section-title">User Account Information</div>
                    <div class="detail-row">
                        <div class="detail-label">User Email:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['email']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Username:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($seller['username']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Account Created:</div>
                        <div class="detail-value"><?php echo date('F d, Y', strtotime($seller['user_created_at'])); ?></div>
                    </div>
                </div>
                
                <?php if($seller['status'] == 'pending'): ?>
                    <div class="actions">
                        <form method="post" action="" style="display: inline;">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Are you sure you want to approve this seller?')">Approve Application</button>
                        </form>
                        
                        <form method="post" action="" style="display: inline;">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-reject" onclick="return confirm('Are you sure you want to reject this seller?')">Reject Application</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>