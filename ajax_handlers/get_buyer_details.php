<?php
include("../session.php");
include("../connect.php");

header('Content-Type: application/json');

$userId = $_SESSION['id'] ?? 0;
$userType = $_SESSION['usertype'] ?? '';

if ($userType !== 'seller' || !$userId) {
    echo JSON_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$buyerId = isset($_GET['buyer_id']) ? (int)$_GET['buyer_id'] : 0;

if (!$buyerId) {
    echo JSON_encode(['success' => false, 'error' => 'No buyer specified']);
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        firstname, lastname, email, phone, 
        id_verified_status, id_image_path, profile_picture
    FROM users 
    WHERE id = ?
");
$stmt->bind_param("i", $buyerId);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'name' => trim($row['firstname'] . ' ' . $row['lastname']),
        'email' => $row['email'],
        'phone' => $row['phone'] ?: 'N/A',
        'id_verified_status' => $row['id_verified_status'],
        'id_image_path' => $row['id_image_path'],
        'profile_picture' => $row['profile_picture']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Buyer not found']);
}
