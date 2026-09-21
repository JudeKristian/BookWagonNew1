<?php
// Automatically forward legacy or root requests to the official Admin Dashboard
header("Location: Admin/admin_dashboard.php");
exit();
?>
