<?php
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db_connect.php';
require_once '../includes/notification_helper.php';

// Keep this page compatible with databases created before product approvals existed.
$column = $conn->query("SHOW COLUMNS FROM books LIKE 'approval_status'");
if ($column && $column->num_rows === 0) {
    $conn->query("ALTER TABLE books ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER user_id");
}

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['book_id'])) {
    $bookId = (int) $_POST['book_id'];
    $newStatus = $_POST['action'] === 'approve' ? 'approved' : ($_POST['action'] === 'reject' ? 'rejected' : '');

    if ($newStatus !== '') {
        $bookStmt = $conn->prepare("SELECT b.title, b.user_id, u.firstname FROM books b JOIN users u ON u.id = b.user_id WHERE b.book_id = ? LIMIT 1");
        $bookStmt->bind_param('i', $bookId);
        $bookStmt->execute();
        $book = $bookStmt->get_result()->fetch_assoc();
        $bookStmt->close();

        if ($book) {
            $updateStmt = $conn->prepare("UPDATE books SET approval_status = ? WHERE book_id = ?");
            $updateStmt->bind_param('si', $newStatus, $bookId);
            if ($updateStmt->execute()) {
                $updateStmt->close();
                $notificationType = $newStatus === 'approved' ? 'product_approved' : 'product_rejected';
                $notificationText = $newStatus === 'approved'
                    ? "Your product '{$book['title']}' has been approved and is now visible in the marketplace."
                    : "Your product '{$book['title']}' was rejected by the admin. Please review the listing and contact support if you need help.";
                sendNotification($conn, (int) $book['user_id'], (int) ($_SESSION['admin_id'] ?? 1), $notificationType, $notificationText);
                $message = 'Product ' . $newStatus . ' successfully.';
            } else {
                $messageType = 'danger';
                $message = 'Unable to update the product approval status.';
                $updateStmt->close();
            }
        } else {
            $messageType = 'danger';
            $message = 'Product not found.';
        }
    }
}

$pendingResult = $conn->query("SELECT COUNT(*) AS cnt FROM books WHERE approval_status = 'pending'");
$pendingProducts = $pendingResult ? (int) $pendingResult->fetch_assoc()['cnt'] : 0;

$books = [];
$result = $conn->query("SELECT b.book_id, b.title, b.author, b.cover_image, b.approval_status, b.created_at, u.firstname, u.lastname, u.email FROM books b LEFT JOIN users u ON u.id = b.user_id ORDER BY FIELD(b.approval_status, 'pending', 'rejected', 'approved'), b.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Approval - BookWagon Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .approval-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .approval-card-header { padding: 20px 22px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .approval-card-header h2 { margin: 0; font-size: 18px; }
        .approval-table { width: 100%; border-collapse: collapse; }
        .approval-table th, .approval-table td { padding: 14px 18px; border-bottom: 1px solid var(--border); text-align: left; font-size: 13px; vertical-align: middle; }
        .approval-table th { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .approval-table tr:last-child td { border-bottom: 0; }
        .book-cell { display: flex; align-items: center; gap: 12px; min-width: 220px; }
        .book-cover { width: 42px; height: 56px; object-fit: cover; border-radius: 5px; background: var(--bg); }
        .status { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
        .status.pending { color: #92400e; background: #fef3c7; }
        .status.approved { color: #166534; background: #dcfce7; }
        .status.rejected { color: #991b1b; background: #fee2e2; }
        .actions { display: flex; gap: 8px; }
        .actions form { margin: 0; }
        .btn { border: 0; border-radius: 6px; padding: 7px 10px; color: #fff; cursor: pointer; font-size: 12px; }
        .btn-success { background: #16a34a; }
        .btn-danger { background: #dc2626; }
        .btn:hover { opacity: .88; }
        .empty { padding: 40px; text-align: center; color: var(--text-muted); }
        @media (max-width: 760px) { .approval-table { min-width: 700px; } .table-wrap { overflow-x: auto; } }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:12px;"><button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><h1>Products Approval</h1></div></div>
            <div class="topbar-date"><i class="fa-regular fa-calendar"></i> <?php echo date('l, F j, Y'); ?></div>
        </div>
        <div class="page-content">
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>" style="padding:12px 16px;margin-bottom:18px;border-radius:8px;background:<?php echo $messageType === 'success' ? '#ecfdf5' : '#fef2f2'; ?>;color:<?php echo $messageType === 'success' ? '#166534' : '#991b1b'; ?>;"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <div class="approval-card">
                <div class="approval-card-header"><h2>Seller product listings</h2><span class="status pending"><?php echo $pendingProducts; ?> pending</span></div>
                <div class="table-wrap">
                    <table class="approval-table">
                        <thead><tr><th>Product</th><th>Seller</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <div class="book-cell">
                                        <?php
                                        $coverPath = '../images/default-book.png';
                                        if (!empty($book['cover_image'])) {
                                            if (file_exists('../' . $book['cover_image'])) {
                                                $coverPath = '../' . $book['cover_image'];
                                            } elseif (file_exists('../images/boooks/' . $book['cover_image'])) {
                                                $coverPath = '../images/boooks/' . $book['cover_image'];
                                            } elseif (file_exists('../uploads/covers/' . $book['cover_image'])) {
                                                $coverPath = '../uploads/covers/' . $book['cover_image'];
                                            } elseif (file_exists('../images/' . $book['cover_image'])) {
                                                $coverPath = '../images/' . $book['cover_image'];
                                            }
                                        }
                                        ?>
                                        <img class="book-cover" src="<?php echo htmlspecialchars($coverPath); ?>" alt="">
                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(trim(($book['firstname'] ?? '') . ' ' . ($book['lastname'] ?? '')) ?: ($book['email'] ?? 'Unknown')); ?></td>
                                <td><?php echo htmlspecialchars(date('M j, Y', strtotime($book['created_at']))); ?></td>
                                <td><span class="status <?php echo htmlspecialchars($book['approval_status']); ?>"><?php echo htmlspecialchars($book['approval_status']); ?></span></td>
                                <td>
                                    <?php if ($book['approval_status'] === 'pending'): ?>
                                    <div class="actions">
                                        <form method="post"><input type="hidden" name="book_id" value="<?php echo (int) $book['book_id']; ?>"><button class="btn btn-success btn-sm" name="action" value="approve" type="submit"><i class="fa-solid fa-check"></i> Approve</button></form>
                                        <form method="post"><input type="hidden" name="book_id" value="<?php echo (int) $book['book_id']; ?>"><button class="btn btn-danger btn-sm" name="action" value="reject" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button></form>
                                    </div>
                                    <?php else: ?><span style="color:var(--text-muted);font-size:12px;">Reviewed</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$books): ?><tr><td colspan="5" class="empty">No products have been listed yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
