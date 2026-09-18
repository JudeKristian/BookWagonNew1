<?php
// ajax_handlers/book_handler.php
include("../session.php");
include("../connect.php");

header('Content-Type: application/json');

$response = [
    'available' => false,
    'stock'     => 0,
    'message'   => ''
];

$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
}

try {
    switch ($action) {
        case 'check_availability':
            $bookId = intval($_POST['book_id'] ?? 0);

            // Check book availability
            $stmt = $conn->prepare("SELECT stock FROM books WHERE book_id = ?");
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $bookData = $result->fetch_assoc();
                $response['available'] = $bookData['stock'] > 0;
                $response['stock'] = (int)$bookData['stock'];
                $response['message'] = $response['available'] 
                    ? "Book is available" 
                    : "Book is out of stock";
            } else {
                $response['message'] = "Book not found";
            }
            break;

        case 'get_pricing':
            $bookId = intval($_POST['book_id'] ?? 0);
            $purchaseType = $_POST['purchase_type'] ?? 'buy';

            $stmt = $conn->prepare("SELECT price, rent_price FROM books WHERE book_id = ?");
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $bookData = $result->fetch_assoc();
                $response['success'] = true;
                $response['price'] = $purchaseType == 'buy' 
                    ? $bookData['price'] 
                    : $bookData['rent_price'];
            } else {
                $response['success'] = false;
                $response['message'] = "Book not found";
            }
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit();