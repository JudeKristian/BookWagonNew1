<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "db_connect.php";

$adminId = $_SESSION['admin_id'] ?? 1;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = intval($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        if ($action === 'approve_id') {
            $stmt = $conn->prepare("UPDATE users SET id_verified_status = 'verified', is_verified = 1 WHERE id = ?");
            $stmt->bind_param("i", $userId);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "User ID #$userId successfully verified.";
                // Log audit
                $details = "Admin approved KYC ID Verification for User ID: $userId.";
                $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, activity, details) VALUES (?, 'KYC_APPROVED', ?)");
                if ($logStmt) {
                    $logStmt->bind_param("is", $adminId, $details);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
            $stmt->close();
        } elseif ($action === 'reject_id') {
            $reason = trim($_POST['reject_reason'] ?? 'Invalid or blurry ID');
            // Reset the ID path and status
            $stmt = $conn->prepare("UPDATE users SET id_verified_status = 'unverified', id_image_path = NULL WHERE id = ?");
            $stmt->bind_param("i", $userId);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "User ID #$userId verification rejected.";
                // Log audit
                $details = "Admin rejected KYC ID Verification for User ID: $userId. Reason: $reason";
                $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, activity, details) VALUES (?, 'KYC_REJECTED', ?)");
                if ($logStmt) {
                    $logStmt->bind_param("is", $adminId, $details);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
            $stmt->close();
        }
    }
    header("Location: admin_kyc.php");
    exit();
}

// Fetch Pending ID Verifications
$pendingKyc = [];
$res = $conn->query("SELECT id, firstname, lastname, email, id_image_path, created_at FROM users WHERE id_verified_status = 'pending'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pendingKyc[] = $row;
    }
}

// Check for session messages
$successMsg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Verifications | Bookwagon Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Custom overrides specific to KYC cards */
        .kyc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; padding: 20px; }
        .kyc-card { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: #fff; transition: transform 0.2s; }
        .kyc-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .kyc-image { width: 100%; height: 220px; object-fit: cover; border-bottom: 1px solid var(--border); cursor: pointer; }
        .kyc-body { padding: 18px; }
        .kyc-actions { display: flex; gap: 10px; margin-top: 15px; }
        .kyc-actions button { flex: 1; border-radius: 8px; font-weight: 500; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 48px; color: var(--success); margin-bottom: 15px; opacity: 0.8; }
    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <div class="topbar-title">
                    <h1>ID Verifications</h1>
                    <p>Review and verify user identity documents (KYC)</p>
                </div>
            </div>
            <div class="topbar-date">
                <i class="fa-regular fa-calendar"></i>
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <div class="page-content">
            <?php if ($successMsg): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <div class="card-header-bar">
                    <h3><i class="fas fa-id-card" style="color: var(--primary); margin-right: 8px;"></i>Pending Submissions</h3>
                </div>
                
                <?php if (empty($pendingKyc)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h5>All Caught Up!</h5>
                        <p>There are no pending ID verifications at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="kyc-grid">
                        <?php foreach ($pendingKyc as $kyc): ?>
                            <div class="kyc-card">
                                <?php 
                                    $imgPath = $kyc['id_image_path'];
                                    // Ensure path works relative to the Admin directory
                                    if (!empty($imgPath) && strpos($imgPath, 'http') !== 0 && strpos($imgPath, '../') !== 0 && strpos($imgPath, '/') !== 0) {
                                        $imgPath = '../' . $imgPath;
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="ID Proof" class="kyc-image" onclick="viewImage(this.src)">
                                <div class="kyc-body">
                                    <h6 class="fw-bold mb-1" style="font-size: 16px;"><?php echo htmlspecialchars($kyc['firstname'] . ' ' . $kyc['lastname']); ?></h6>
                                    <p class="text-muted small mb-2"><i class="fa-regular fa-envelope me-1"></i><?php echo htmlspecialchars($kyc['email']); ?></p>
                                    <div class="kyc-actions">
                                        <form method="POST" style="flex: 1;" onsubmit="return confirm('Approve this ID?');">
                                            <input type="hidden" name="action" value="approve_id">
                                            <input type="hidden" name="user_id" value="<?php echo $kyc['id']; ?>">
                                            <button type="submit" class="btn btn-success w-100 btn-sm"><i class="fas fa-check"></i> Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-danger btn-sm" style="flex: 1;" onclick="openRejectModal(<?php echo $kyc['id']; ?>)"><i class="fas fa-times"></i> Reject</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject ID Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject_id">
                        <input type="hidden" name="user_id" id="rejectUserId">
                        <div class="mb-3">
                            <label class="form-label">Reason for rejection:</label>
                            <select name="reject_reason" class="form-select" required>
                                <option value="ID is blurry or unreadable">ID is blurry or unreadable</option>
                                <option value="ID is expired">ID is expired</option>
                                <option value="Name does not match account">Name does not match account</option>
                                <option value="Not a valid government/school ID">Not a valid government/school ID</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ID Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="fullImageView" src="" alt="Full ID" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewImage(src) {
            document.getElementById('fullImageView').src = src;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
        function openRejectModal(userId) {
            document.getElementById('rejectUserId').value = userId;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
    </script>
</body>
</html>
