<?php
include("connect.php");

// Initialize response array
$response = [
    'success' => false,
    'address' => null,
    'book_meetup' => null,
    'order_pickup' => null,
    'error' => null
];

// Check if seller_id is provided
if (!isset($_GET['seller_id']) || empty($_GET['seller_id'])) {
    $response['error'] = "Seller ID is required";
    echo json_encode($response);
    exit();
}

$sellerId = intval($_GET['seller_id']);
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$rentalId = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;

try {
    // Query to get seller address information
    $sellerQuery = $conn->prepare("
        SELECT 
            shop_name,
            business_name,
            first_name,
            last_name,
            location,
            address,
            zip_code
        FROM 
            sellers 
        WHERE 
            id = ?
    ");
    
    $sellerQuery->bind_param("i", $sellerId);
    $sellerQuery->execute();
    $result = $sellerQuery->get_result();
    
    if ($result->num_rows > 0) {
        $sellerData = $result->fetch_assoc();
        
        // Format the address details
        $response['success'] = true;
        $response['address'] = [
            'name' => !empty($sellerData['shop_name']) ? $sellerData['shop_name'] : $sellerData['business_name'],
            'address' => $sellerData['address'],
            'city' => $sellerData['location'],
            'province' => '',
            'postal_code' => $sellerData['zip_code'],
            'contact_person' => trim(($sellerData['first_name'] ?? '') . ' ' . ($sellerData['last_name'] ?? ''))
        ];
    } else {
        $response['error'] = "Seller not found";
    }

    // Fetch book meetup location if book_id is provided
    if ($bookId > 0) {
        $bookQuery = $conn->prepare("SELECT meetup_location FROM books WHERE book_id = ?");
        $bookQuery->bind_param("i", $bookId);
        $bookQuery->execute();
        $bookRes = $bookQuery->get_result();
        if ($bookRow = $bookRes->fetch_assoc()) {
            if (!empty($bookRow['meetup_location'])) {
                $response['book_meetup'] = $bookRow['meetup_location'];
            }
        }
    }

    // Fetch original order pickup/notes if rental_id is provided
    if ($rentalId > 0) {
        $rentalQuery = $conn->prepare("
            SELECT o.notes, o.pickup_location, o.address 
            FROM book_rentals br
            JOIN orders o ON br.order_id = o.order_id
            WHERE br.rental_id = ?
        ");
        $rentalQuery->bind_param("i", $rentalId);
        $rentalQuery->execute();
        $rentalRes = $rentalQuery->get_result();
        if ($rentalRow = $rentalRes->fetch_assoc()) {
            if (!empty($rentalRow['pickup_location'])) {
                $response['order_pickup'] = $rentalRow['pickup_location'];
            } elseif (!empty($rentalRow['notes'])) {
                $response['order_pickup'] = $rentalRow['notes'];
            }
        }
    }

} catch (Exception $e) {
    $response['error'] = "Database error: " . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?> 