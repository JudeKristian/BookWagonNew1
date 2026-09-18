<?php
// Include connection and session files
include("../connect.php");
include("../session.php");

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to add a book'
    ]);
    exit;
}

// Get the current user's ID
$userId = $_SESSION['id'];

// Validate required fields
$required_fields = ['title', 'author', 'condition'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: $field"
        ]);
        exit;
    }
}

// Check if image was uploaded
if (!isset($_FILES['book_image']) || $_FILES['book_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Book image is required'
    ]);
    exit;
}

try {
    // Validate uploaded file
    $file = $_FILES['book_image'];
    
    // 1. Check size limit (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Book image exceeds maximum allowed size of 5MB');
    }
    
    // 2. Validate file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_exts)) {
        throw new Exception('Invalid file extension. Only JPG, JPEG, PNG, GIF, and WEBP are allowed');
    }
    
    // 3. Validate real server-side MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed_mimes)) {
            throw new Exception('Invalid image file format');
        }
    }

    // Process the uploaded image
    $uploadDir = '../uploads/books/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate a unique filename using only the safe extension
    $filename = uniqid('book_', true) . '.' . $ext;
    $uploadPath = $uploadDir . $filename;
    
    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to upload image');
    }
    chmod($uploadPath, 0644);
    
    // Get the relative path for storage in database
    $relativePath = 'uploads/books/' . $filename;
    
    // Extract other form data
    $title = $_POST['title'];
    $author = $_POST['author'];
    $condition = $_POST['condition'];
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $genre = isset($_POST['genre']) ? $_POST['genre'] : null;
    
    // Insert the book into the database
    $query = "INSERT INTO book_swaps (user_id, book_title, author, description, `condition`, image_path, genre) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $userId, $title, $author, $description, $condition, $relativePath, $genre);
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Book added successfully!',
            'book_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception("Failed to add book");
    }
    
} catch (Exception $e) {
    // Log the error (in a production environment)
    error_log('Error adding book: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add book: ' . $e->getMessage()
    ]);
}
?>