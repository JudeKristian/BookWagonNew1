<?php
/**
 * BookWagon Centralized User Sidebar Component
 * Shared across customer account pages: account.php, cart.php, rented_books.php, collections.php, history.php, security.php
 */

if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}

// Gather user details from session if not already available
$sidebarUserFirst = $_SESSION['firstname'] ?? 'User';
$sidebarUserLast = $_SESSION['lastname'] ?? '';
$sidebarUserName = trim($sidebarUserFirst . ' ' . $sidebarUserLast);
$sidebarUserInitial = strtoupper(substr($sidebarUserFirst, 0, 1));
$sidebarUserPhoto = $_SESSION['profile_picture'] ?? '';
$sidebarUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

// Optional: get live cart count if $conn is available
$sidebarCartCount = 0;
if (isset($conn) && $sidebarUserId) {
    $cartStmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    if ($cartStmt) {
        $cartStmt->bind_param("i", $sidebarUserId);
        $cartStmt->execute();
        $cartRes = $cartStmt->get_result()->fetch_assoc();
        $sidebarCartCount = intval($cartRes['total'] ?? 0);
        $cartStmt->close();
    }
}
?>

<!-- User Sidebar Styles -->
<style>
    .bw-user-sidebar {
        background: #ffffff;
        border: 1px solid #edf2f7;
        border-radius: 14px;
        padding: 1.15rem 0.85rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: 24px;
    }

    /* Compact User Profile Header */
    .sidebar-profile-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 0.4rem 0.9rem;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .sidebar-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: linear-gradient(135deg, #f8a100, #ea580c);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .sidebar-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sidebar-user-meta {
        overflow: hidden;
        flex-grow: 1;
    }

    .sidebar-user-name {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
    }

    .sidebar-user-sub {
        font-size: 0.72rem;
        color: #94a3b8;
        margin: 0;
        line-height: 1.2;
    }

    /* Navigation Links */
    .bw-sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .bw-nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border-radius: 8px;
        color: #475569;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 500;
        transition: all 0.18s ease;
    }

    .bw-nav-link i {
        font-size: 0.95rem;
        width: 20px;
        text-align: center;
        color: #94a3b8;
        transition: color 0.18s ease;
    }

    .bw-nav-link:hover {
        background-color: #f8fafc;
        color: #0f172a;
    }

    .bw-nav-link:hover i {
        color: #f8a100;
    }

    /* Clean, Modern Active State */
    .bw-nav-link.active {
        background: #f8a100;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(248, 161, 0, 0.28);
    }

    .bw-nav-link.active i {
        color: #ffffff;
    }

    .bw-nav-badge {
        margin-left: auto;
        font-size: 0.7rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8a100;
        color: #ffffff;
        line-height: 1;
    }

    .bw-nav-link.active .bw-nav-badge {
        background: #ffffff;
        color: #d97706;
    }

    .bw-sidebar-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 6px 0;
    }
</style>

<div class="bw-user-sidebar">
    <!-- Compact Profile Header -->
    <div class="sidebar-profile-header">
        <div class="sidebar-avatar">
            <?php if (!empty($sidebarUserPhoto) && file_exists($sidebarUserPhoto)): ?>
                <img src="<?php echo htmlspecialchars($sidebarUserPhoto); ?>" alt="Profile">
            <?php else: ?>
                <span><?php echo $sidebarUserInitial; ?></span>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-meta">
            <h6 class="sidebar-user-name" title="<?php echo htmlspecialchars($sidebarUserName); ?>">
                <?php echo htmlspecialchars($sidebarUserName); ?>
            </h6>
            <span class="sidebar-user-sub">Personal Account</span>
        </div>
    </div>

    <!-- Navigation Items -->
    <div class="bw-sidebar-nav">
        <a href="account.php" class="bw-nav-link <?php echo ($currentPage == 'account.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i>
            <span>Account</span>
        </a>

        <a href="cart.php" class="bw-nav-link <?php echo ($currentPage == 'cart.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-shopping-cart"></i>
            <span>Cart</span>
            <?php if ($sidebarCartCount > 0): ?>
                <span class="bw-nav-badge"><?php echo $sidebarCartCount; ?></span>
            <?php endif; ?>
        </a>

        <a href="rented_books.php" class="bw-nav-link <?php echo ($currentPage == 'rented_books.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-book"></i>
            <span>Rented Books</span>
        </a>

        <a href="collections.php" class="bw-nav-link <?php echo ($currentPage == 'collections.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-bookmark"></i>
            <span>My Collections</span>
        </a>

        <a href="history.php" class="bw-nav-link <?php echo ($currentPage == 'history.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Order History</span>
        </a>

        <div class="bw-sidebar-divider"></div>

        <a href="security.php" class="bw-nav-link <?php echo ($currentPage == 'security.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Security Settings</span>
        </a>
    </div>
</div>
