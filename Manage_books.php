<?php
include("session.php");
include("connect.php");

// Secure upload function with strict validation
function direct_upload_image($file, $upload_dir = 'uploads/covers/') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // 1. File size check (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        error_log("Upload error: File exceeds 5MB limit.");
        return false;
    }
    
    // 2. Validate file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_extensions)) {
        error_log("Security Error: Invalid extension: " . $ext);
        return false;
    }
    
    // 3. Validate real server-side MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed_mimes)) {
            error_log("Security Error: Invalid MIME type: " . $mime);
            return false;
        }
    }
    
    // 4. Generate random safe filename without preserving client basename
    $filename = uniqid('book_', true) . '.' . $ext;
    $targetPath = $upload_dir . $filename;
    
    // 5. Move uploaded file and set safe permissions
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        chmod($targetPath, 0644);
        return $targetPath;
    }
    
    return false;
}

$userType = $_SESSION['usertype'] ?? '';
$userId = $_SESSION['id'] ?? 0;

// Ensure only sellers can access this page
if ($userType !== 'seller') {
    header("Location: login.php");
    exit();
}

// Add a function to create the new database fields if they don't exist
function ensure_pricing_fields_exist($conn) {
    $fields_to_add = [
        'base_rental_fee' => 'DECIMAL(10,2) DEFAULT NULL',
        'handling_fee' => 'DECIMAL(10,2) DEFAULT NULL',
        'condition_multiplier' => 'DECIMAL(5,2) DEFAULT NULL',
        'book_value' => 'DECIMAL(10,2) DEFAULT NULL',
        'listing_fee' => 'DECIMAL(10,2) DEFAULT NULL',
        'markup_percentage' => 'INT DEFAULT NULL',
        'listing_type' => "ENUM('both', 'sale', 'rent') DEFAULT 'both'",
        'security_deposit' => 'DECIMAL(10,2) DEFAULT 0.00',
        'seller_note' => 'TEXT DEFAULT NULL'
    ];
    
    // Check if fields exist
    $result = $conn->query("SHOW COLUMNS FROM books");
    $existing_fields = [];
    while($row = $result->fetch_assoc()) {
        $existing_fields[] = $row['Field'];
    }
    
    // Add missing fields
    foreach($fields_to_add as $field => $definition) {
        if (!in_array($field, $existing_fields)) {
            $conn->query("ALTER TABLE books ADD COLUMN $field $definition");
            error_log("Added field $field to books table");
        }
    }

    // Existing books remain visible; newly submitted books require approval.
    $approval_column = $conn->query("SHOW COLUMNS FROM books LIKE 'approval_status'");
    if ($approval_column && $approval_column->num_rows === 0) {
        $conn->query("ALTER TABLE books ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER user_id");
    }

    // Ensure genre and theme are wide enough for multi-tags
    @$conn->query("ALTER TABLE books MODIFY COLUMN genre VARCHAR(255) DEFAULT ''");
    @$conn->query("ALTER TABLE books MODIFY COLUMN theme VARCHAR(255) DEFAULT ''");

    // Ensure book_images table exists for Shopee-style condition photos
    @$conn->query("CREATE TABLE IF NOT EXISTS book_images (
        image_id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        image_type VARCHAR(50) DEFAULT 'additional',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_book (book_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

// Call the function to ensure fields exist
ensure_pricing_fields_exist($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which action is being performed
    if (isset($_POST['action'])) {
        
        // Add new book
        if ($_POST['action'] === 'add') {
            // Prepare and sanitize the data
            $title = mysqli_real_escape_string($conn, trim($_POST['title']));
            $author = mysqli_real_escape_string($conn, trim($_POST['author']));
            $isbn = mysqli_real_escape_string($conn, trim($_POST['isbn'] ?? ''));
            
            // Multiple genres handling (either array from badges or comma-separated string)
            if (isset($_POST['genres']) && is_array($_POST['genres'])) {
                $genre_arr = array_map(function($g) use ($conn) { return mysqli_real_escape_string($conn, trim($g)); }, $_POST['genres']);
                $genre = implode(', ', array_filter($genre_arr));
            } else {
                $genre = mysqli_real_escape_string($conn, trim($_POST['genre'] ?? ''));
            }

            // Multiple themes handling
            if (isset($_POST['themes']) && is_array($_POST['themes'])) {
                $theme_arr = array_map(function($t) use ($conn) { return mysqli_real_escape_string($conn, trim($t)); }, $_POST['themes']);
                $theme = implode(', ', array_filter($theme_arr));
            } else {
                $theme = mysqli_real_escape_string($conn, trim($_POST['theme'] ?? ''));
            }

            $book_type = mysqli_real_escape_string($conn, $_POST['book_type'] ?? 'Paperback');
            $condition = mysqli_real_escape_string($conn, $_POST['condition'] ?? 'Good');
            
            // Damage tags + damage text
            $damage_tags = '';
            if (isset($_POST['damage_tags']) && is_array($_POST['damage_tags'])) {
                $damage_tags = implode(', ', array_filter($_POST['damage_tags']));
            }
            $custom_damages = trim($_POST['damages'] ?? '');
            if (!empty($damage_tags) && !empty($custom_damages)) {
                $damages = mysqli_real_escape_string($conn, $damage_tags . ' - ' . $custom_damages);
            } elseif (!empty($damage_tags)) {
                $damages = mysqli_real_escape_string($conn, $damage_tags);
            } else {
                $damages = mysqli_real_escape_string($conn, $custom_damages);
            }

            $popularity = mysqli_real_escape_string($conn, $_POST['popularity'] ?? 'New Releases');
            $listing_type = in_array($_POST['listing_type'] ?? '', ['both', 'sale', 'rent']) ? $_POST['listing_type'] : 'both';
            
            $price = floatval($_POST['price'] ?? 0);
            $rent_price = floatval($_POST['rent_price'] ?? 0);
            $stock = max(1, intval($_POST['stock'] ?? 1));
            $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
            $meetup_location = mysqli_real_escape_string($conn, trim($_POST['meetup_location'] ?? 'Campus Meet-up'));
            $seller_note = mysqli_real_escape_string($conn, $_POST['seller_note'] ?? '');
            
            // Pricing strategy fields
            $base_rental_fee = floatval($_POST['base_rental_fee'] ?? 0);
            $handling_fee = 10.00; // Fixed value
            $condition_multiplier = floatval($_POST['condition_multiplier'] ?? 1.0);
            $book_value = floatval($_POST['book_value'] ?? 0);
            $listing_fee = 30.00; // Fixed value
            $markup_percentage = 30; // Fixed value
            $security_deposit = floatval($_POST['security_deposit'] ?? $book_value);
            
            // Handle primary cover image upload
            $cover_image = '';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $uploaded_image = direct_upload_image($_FILES['cover_image'], 'uploads/covers/');
                if ($uploaded_image) {
                    $cover_image = $uploaded_image;
                }
            }
            
            // Insert book into database with all fields
            $query = "INSERT INTO books (user_id, approval_status, title, author, ISBN, genre, theme, book_type, `condition`, damages, popularity, price, rent_price, stock, description, cover_image, base_rental_fee, handling_fee, condition_multiplier, book_value, listing_fee, markup_percentage, listing_type, security_deposit, seller_note, meetup_location) 
                      VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssssssssddissddddddsdss", 
                $userId, 
                $title, 
                $author, 
                $isbn, 
                $genre, 
                $theme, 
                $book_type, 
                $condition, 
                $damages, 
                $popularity, 
                $price, 
                $rent_price, 
                $stock, 
                $description, 
                $cover_image, 
                $base_rental_fee, 
                $handling_fee, 
                $condition_multiplier, 
                $book_value, 
                $listing_fee, 
                $markup_percentage,
                $listing_type,
                $security_deposit,
                $seller_note,
                $meetup_location);
            
            if ($stmt->execute()) {
                $new_book_id = $stmt->insert_id;
                
                // Handle Shopee-style additional documentation photos
                $photo_slots = [
                    'back_cover' => 'back',
                    'spine_cover' => 'spine',
                    'pages_cover' => 'pages',
                    'damage_cover' => 'damage',
                    'other_cover' => 'other',
                    'other_cover_2' => 'other'
                ];
                
                $img_stmt = $conn->prepare("INSERT INTO book_images (book_id, image_url, image_type) VALUES (?, ?, ?)");
                
                foreach ($photo_slots as $slot_name => $slot_type) {
                    if (isset($_FILES[$slot_name]) && $_FILES[$slot_name]['error'] === UPLOAD_ERR_OK) {
                        $uploaded_extra = direct_upload_image($_FILES[$slot_name], 'uploads/books/');
                        if ($uploaded_extra && $img_stmt) {
                            $img_stmt->bind_param("iss", $new_book_id, $uploaded_extra, $slot_type);
                            $img_stmt->execute();
                        }
                    }
                }
                if ($img_stmt) {
                    $img_stmt->close();
                }

                $stmt->close();
                header("Location: Manage_books.php?success=add");
                exit();
            } else {
                $error_message = "Error adding book: " . $stmt->error;
                $stmt->close();
            }
        }
        
        // Edit existing book
        elseif ($_POST['action'] === 'edit' && isset($_POST['book_id'])) {
            $book_id = intval($_POST['book_id']);
            
            // First check if this book belongs to the current user
            $check_query = "SELECT user_id, cover_image FROM books WHERE book_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $book_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 1) {
                $book_data = $check_result->fetch_assoc();
                
                // Verify ownership
                if ($book_data['user_id'] == $userId) {
                    // Prepare and sanitize the data
                    $title = mysqli_real_escape_string($conn, $_POST['title']);
                    $author = mysqli_real_escape_string($conn, $_POST['author']);
                    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
                    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
                    $theme = mysqli_real_escape_string($conn, $_POST['theme']);
                    // Add new fields
                    $book_type = mysqli_real_escape_string($conn, $_POST['book_type']);
                    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
                    $damages = mysqli_real_escape_string($conn, $_POST['damages']);
                    $popularity = mysqli_real_escape_string($conn, $_POST['popularity']);
                    $price = floatval($_POST['price']);
                    $rent_price = floatval($_POST['rent_price']);
                    $stock = intval($_POST['stock']);
                    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
                    $meetup_location = mysqli_real_escape_string($conn, trim($_POST['meetup_location'] ?? 'Campus Meet-up'));
                    
                    // Add debugging to check the description value
                    error_log("Description before DB update: " . $description);
                    
                    // Pricing strategy fields
                    $base_rental_fee = floatval($_POST['base_rental_fee'] ?? 0);
                    // Override with fixed values regardless of what was submitted
                    $handling_fee = 10.00; // Fixed value
                    $condition_multiplier = floatval($_POST['condition_multiplier'] ?? 1.0);
                    $book_value = floatval($_POST['book_value'] ?? 0);
                    // Override with fixed values regardless of what was submitted
                    $listing_fee = 30.00; // Fixed value
                    $markup_percentage = 30; // Fixed value
                    
                    // Simplified file upload approach for editing
                    $cover_image_query = "";
                    $cover_image = $book_data['cover_image']; // Keep existing cover by default
                    
                    if (!empty($_FILES['cover_image']['name'])) {
                        $new_cover = direct_upload_image($_FILES['cover_image']);
                        if (!empty($new_cover)) {
                            $cover_image = $new_cover;
                            $cover_image_query = ", cover_image = ?";
                        }
                    }
                    
                    // Update book in database
                    if (!empty($cover_image_query)) {
                        $query = "UPDATE books SET title = ?, author = ?, ISBN = ?, genre = ?, theme = ?, 
                                  book_type = ?, `condition` = ?, damages = ?, popularity = ?, 
                                  price = ?, rent_price = ?, stock = ?, description = ?, cover_image = ?,
                                  base_rental_fee = ?, handling_fee = ?, condition_multiplier = ?,
                                  book_value = ?, listing_fee = ?, markup_percentage = ?, meetup_location = ?
                                  WHERE book_id = ? AND user_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssssssssddissdddddssii", $title, $author, $isbn, $genre, $theme, 
                                          $book_type, $condition, $damages, $popularity, 
                                          $price, $rent_price, $stock, $description, $cover_image,
                                          $base_rental_fee, $handling_fee, $condition_multiplier,
                                          $book_value, $listing_fee, $markup_percentage, $meetup_location,
                                          $book_id, $userId);
                    } else {
                        $query = "UPDATE books SET title = ?, author = ?, ISBN = ?, genre = ?, theme = ?, 
                                  book_type = ?, `condition` = ?, damages = ?, popularity = ?, 
                                  price = ?, rent_price = ?, stock = ?, description = ?,
                                  base_rental_fee = ?, handling_fee = ?, condition_multiplier = ?,
                                  book_value = ?, listing_fee = ?, markup_percentage = ?, meetup_location = ?
                                  WHERE book_id = ? AND user_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssssssssddisdddddssii", $title, $author, $isbn, $genre, $theme, 
                                         $book_type, $condition, $damages, $popularity, 
                                         $price, $rent_price, $stock, $description,
                                         $base_rental_fee, $handling_fee, $condition_multiplier,
                                         $book_value, $listing_fee, $markup_percentage, $meetup_location,
                                         $book_id, $userId);
                    }
                    
                    if ($stmt->execute()) {
                        $success_message = "Book updated successfully!";
                    } else {
                        $error_message = "Error updating book: " . $stmt->error;
                    }
                    
                    $stmt->close();
                } else {
                    $error_message = "You don't have permission to edit this book.";
                }
            } else {
                $error_message = "Book not found.";
            }
            
            $check_stmt->close();
        }
        
        // Delete book
        elseif ($_POST['action'] === 'delete' && isset($_POST['book_id'])) {
            $book_id = intval($_POST['book_id']);
            
            // First check if this book belongs to the current user
            $check_query = "SELECT user_id FROM books WHERE book_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $book_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 1) {
                $book_data = $check_result->fetch_assoc();
                
                // Verify ownership
                if ($book_data['user_id'] == $userId) {
                    // Begin transaction for data consistency
                    $conn->begin_transaction();
                    
                    try {
                        // First delete related rental records
                        $delete_rentals_query = "DELETE FROM book_rentals WHERE book_id = ?";
                        $delete_rentals_stmt = $conn->prepare($delete_rentals_query);
                        $delete_rentals_stmt->bind_param("i", $book_id);
                        $delete_rentals_stmt->execute();
                        $delete_rentals_stmt->close();
                        
                        // Also delete related cart items
                        $delete_cart_query = "DELETE FROM cart WHERE book_id = ?";
                        $delete_cart_stmt = $conn->prepare($delete_cart_query);
                        $delete_cart_stmt->bind_param("i", $book_id);
                        $delete_cart_stmt->execute();
                        $delete_cart_stmt->close();
                        
                        // Also delete related order_items
                        $delete_order_items_query = "DELETE FROM order_items WHERE book_id = ?";
                        $delete_order_items_stmt = $conn->prepare($delete_order_items_query);
                        $delete_order_items_stmt->bind_param("i", $book_id);
                        $delete_order_items_stmt->execute();
                        $delete_order_items_stmt->close();

                        // Also delete related book_images
                        $delete_images_stmt = $conn->prepare("DELETE FROM book_images WHERE book_id = ?");
                        if ($delete_images_stmt) {
                            $delete_images_stmt->bind_param("i", $book_id);
                            $delete_images_stmt->execute();
                            $delete_images_stmt->close();
                        }
                        
                        // Now delete the book
                        $delete_book_query = "DELETE FROM books WHERE book_id = ? AND user_id = ?";
                        $delete_book_stmt = $conn->prepare($delete_book_query);
                        $delete_book_stmt->bind_param("ii", $book_id, $userId);
                        $delete_book_stmt->execute();
                        
                        if ($delete_book_stmt->affected_rows > 0) {
                            $conn->commit();
                            $success_message = "Book deleted successfully!";
                        } else {
                            $conn->rollback();
                            $error_message = "You don't have permission to delete this book.";
                        }
                        
                        $delete_book_stmt->close();
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error_message = "Error deleting book: " . $e->getMessage();
                    }
                } else {
                    $error_message = "You don't have permission to delete this book.";
                }
            } else {
                $error_message = "Book not found.";
            }
            
            $check_stmt->close();
        }
    }
}

// Fetch books owned by this user
$query = "SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch genre list for dropdown
$genre_query = "SELECT DISTINCT genre FROM books";
$genre_result = $conn->query($genre_query);
$genres = [];
while ($row = $genre_result->fetch_assoc()) {
    if (!empty($row['genre'])) {
        $genres[] = $row['genre'];
    }
}

// Fetch theme list for dropdown
$theme_query = "SELECT DISTINCT theme FROM books";
$theme_result = $conn->query($theme_query);
$themes = [];
while ($row = $theme_result->fetch_assoc()) {
    if (!empty($row['theme'])) {
        $themes[] = $row['theme'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - BookWagon</title>
    
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        
        .main-content {
            padding: 20px;
            min-height: 100vh;
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .table-responsive {
            background-color: white;
            border-radius: 0 0 12px 12px;
        }
        
        .table thead {
            background-color: #f8f9fa;
        }
        
        .action-dropdown {
            cursor: pointer;
        }
        
        .book-thumbnail {
            width: 50px;
            height: 70px;
            object-fit: cover;
            margin-right: 10px;
            border-radius: 6px;
        }
        
        /* Modal styles */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0;
        }
        
        .btn-primary {
            background-color: #f8a100;
            border-color: #f8a100;
        }
        
        .btn-primary:hover {
            background-color: #d97706;
            border-color: #d97706;
        }
        
        /* Grid view styles */
        .book-grid {
            display: flex;
            flex-wrap: wrap;
        }
        
        .object-fit-cover {
            object-fit: cover;
        }

        /* Modal Dialog Scroll & Fixed Footer */
        #addBookModal .modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 3.5rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #addBookModal .modal-body {
            overflow-y: auto !important;
            max-height: calc(100vh - 13rem);
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
        }
        #addBookModal .modal-body::-webkit-scrollbar {
            width: 8px;
        }
        #addBookModal .modal-body::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        #addBookModal .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        #addBookModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* 3-Step Wizard Navigation */
        .wizard-steps-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 24px;
            padding: 0 20px;
        }
        .wizard-steps-container::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }
        .wizard-step-node {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: transparent;
            border: none;
            cursor: pointer;
            outline: none;
        }
        .wizard-step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #cbd5e1;
            color: #64748b;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .wizard-step-node.active .wizard-step-circle {
            border-color: #f8a100;
            background: #f8a100;
            color: #fff;
            box-shadow: 0 0 0 5px rgba(248, 161, 0, 0.2);
        }
        .wizard-step-node.completed .wizard-step-circle {
            border-color: #10b981;
            background: #10b981;
            color: #fff;
        }
        .wizard-step-label {
            margin-top: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            transition: color 0.2s ease;
        }
        .wizard-step-node.active .wizard-step-label {
            color: #d97706;
        }
        .wizard-step-node.completed .wizard-step-label {
            color: #059669;
        }

        /* Shopee-style Photo Upload Grid */
        .photo-upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 14px;
        }
        .photo-slot {
            aspect-ratio: 3/4;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
            padding: 8px;
            text-align: center;
        }
        .photo-slot:hover {
            border-color: #f8a100;
            background: #fffbeb;
        }
        .photo-slot.has-image {
            border-style: solid;
            border-color: #e2e8f0;
            padding: 0;
            background: #000;
        }
        .photo-slot-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        .photo-slot.has-image .photo-slot-preview {
            display: block;
        }
        .photo-slot.has-image .photo-slot-placeholder {
            display: none;
        }
        .photo-slot-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 4;
        }
        .photo-slot:hover .photo-slot-overlay {
            opacity: 1;
        }
        .photo-slot-badge {
            position: absolute;
            top: 6px;
            left: 6px;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 7px;
            border-radius: 5px;
            z-index: 3;
            letter-spacing: 0.3px;
        }
        .badge-primary-cover {
            background: #f8a100;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        .badge-extra-photo {
            background: rgba(15, 23, 42, 0.7);
            color: #fff;
        }
        .photo-slot-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            margin-top: 6px;
            line-height: 1.2;
        }

        /* Tag Pills (Genre, Theme, Damages) */
        .tag-pills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }
        .tag-pill {
            padding: 6px 13px;
            font-size: 0.82rem;
            border-radius: 20px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .tag-pill:hover {
            border-color: #cbd5e1;
            background: #e2e8f0;
        }
        .tag-pill.active {
            background: #fff7ed;
            border-color: #f97316;
            color: #c2410c;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(249, 115, 22, 0.15);
        }

        /* Listing Mode Cards */
        .listing-mode-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .listing-mode-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s ease;
        }
        .listing-mode-card:hover {
            border-color: #cbd5e1;
        }
        .listing-mode-card.active {
            border-color: #f8a100;
            background: #fffbeb;
            box-shadow: 0 4px 12px rgba(248, 161, 0, 0.12);
        }
        .listing-mode-card i {
            font-size: 1.4rem;
            margin-bottom: 6px;
            display: block;
        }
        .pricing-section-box {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            background: #ffffff;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <!-- Include the fixed sidebar -->
    <?php include("include/seller_sidebar.php"); ?>

    <!-- Main Content wrapper matching the sidebar layout -->
    <div class="main-content">
        <div class="page-content">
                <!-- Alert Messages -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-4">Manage Books</h2>
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Book List</h5>
                                <div class="d-flex">
                                    <div class="btn-group me-3">
                                        <button class="btn btn-outline-secondary active" id="tableViewBtn">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" id="gridViewBtn">
                                            <i class="fas fa-th-large"></i>
                                        </button>
                                    </div>
                                    <div class="input-group me-3" style="width: 300px;">
                                        <input type="text" class="form-control" id="searchBooks" placeholder="Search books...">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                        <i class="fas fa-plus me-2"></i>Add New Book
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                                </th>
                                                <th>Book</th>
                                                <th>ISBN</th>
                                                <th>Genre</th>
                                                <th>Type</th>
                                                <th>Condition</th>
                                                <th>Price</th>
                                                <th>Rent Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($books)): ?>
                                            <tr>
                                                <td colspan="11" class="text-center py-5">
                                                    <p>No books found. Add some books to get started.</p>
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                                <?php foreach ($books as $book): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input book-select" value="<?php echo $book['book_id']; ?>">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php
                                                            $coverPath = 'images/default-book.png';
                                                            if (!empty($book['cover_image'])) {
                                                                if (file_exists($book['cover_image'])) {
                                                                    $coverPath = $book['cover_image'];
                                                                } elseif (file_exists('images/boooks/' . $book['cover_image'])) {
                                                                    $coverPath = 'images/boooks/' . $book['cover_image'];
                                                                } elseif (file_exists('uploads/covers/' . $book['cover_image'])) {
                                                                    $coverPath = 'uploads/covers/' . $book['cover_image'];
                                                                } elseif (file_exists('images/' . $book['cover_image'])) {
                                                                    $coverPath = 'images/' . $book['cover_image'];
                                                                }
                                                            }
                                                            ?>
                                                            <img src="<?php echo htmlspecialchars($coverPath); ?>" 
                                                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                                 class="book-thumbnail">
                                                            <div>
                                                                <div><?php echo htmlspecialchars($book['title']); ?></div>
                                                                <small class="text-muted">By <?php echo htmlspecialchars($book['author']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($book['ISBN'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                                    <!-- Display Book Type -->
                                                    <td><?php echo htmlspecialchars($book['book_type'] ?? 'Paperback'); ?></td>
                                                    <!-- Display Condition with tooltip for damages -->
                                                    <td>
                                                        <?php if (!empty($book['damages'])): ?>
                                                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($book['damages']); ?>">
                                                            <?php echo htmlspecialchars($book['condition'] ?? 'New'); ?> <i class="fas fa-info-circle text-warning"></i>
                                                        </span>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($book['condition'] ?? 'New'); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (($book['listing_type'] ?? 'both') === 'rent'): ?>
                                                            <span class="badge bg-light text-muted border">Rent Only</span>
                                                        <?php else: ?>
                                                            ₱<?php echo number_format($book['price'], 2); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (($book['listing_type'] ?? 'both') === 'sale'): ?>
                                                            <span class="badge bg-light text-muted border">Sale Only</span>
                                                        <?php else: ?>
                                                            ₱<?php echo number_format($book['rent_price'] ?? 0, 2); ?>/wk
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($book['stock'] <= 5): ?>
                                                            <span class="text-danger"><?php echo $book['stock']; ?></span>
                                                        <?php else: ?>
                                                            <?php echo $book['stock']; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (($book['approval_status'] ?? 'approved') === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        <?php elseif (($book['approval_status'] ?? 'approved') === 'rejected'): ?>
                                                            <span class="badge bg-danger" title="Rejected by Admin">Rejected</span>
                                                        <?php elseif ($book['stock'] > 0): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <a href="#" class="action-dropdown" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </a>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item edit-book" href="#" 
                                                                       data-bs-toggle="modal" 
                                                                       data-bs-target="#editBookModal" 
                                                                       data-book-id="<?php echo $book['book_id']; ?>">
                                                                        <i class="fas fa-edit me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item delete-book" href="#" 
                                                                       data-bs-toggle="modal" 
                                                                       data-bs-target="#deleteBookModal" 
                                                                       data-book-id="<?php echo $book['book_id']; ?>"
                                                                       data-book-title="<?php echo htmlspecialchars($book['title']); ?>">
                                                                        <i class="fas fa-trash me-2"></i>Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Book Grid View (Alternative to Table View) -->
                                <div class="row book-grid" id="bookGridView" style="display: none;">
                                    <?php if (empty($books)): ?>
                                    <div class="col-12 text-center py-5">
                                        <p>No books found. Add some books to get started.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($books as $book): ?>
                                        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                                            <div class="card h-100">
                                                <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                                                    <img src="<?php echo !empty($book['cover_image']) ? $book['cover_image'] : 'img/default-book-cover.jpg'; ?>" 
                                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                         class="w-100 h-100 object-fit-cover">
                                                    
                                                    <!-- Book Type Badge -->
                                                    <span class="position-absolute top-0 start-0 badge bg-info m-2">
                                                        <?php echo htmlspecialchars($book['book_type'] ?? 'Paperback'); ?>
                                                    </span>
                                                    
                                                    <!-- Condition Badge -->
                                                    <span class="position-absolute top-0 end-0 badge 
                                                        <?php 
                                                        $conditionClass = 'bg-success';
                                                        switch($book['condition'] ?? 'New') {
                                                            case 'New':
                                                                $conditionClass = 'bg-success';
                                                                break;
                                                            case 'Like New':
                                                                $conditionClass = 'bg-success';
                                                                break;
                                                            case 'Very Good':
                                                                $conditionClass = 'bg-info';
                                                                break;
                                                            case 'Good':
                                                                $conditionClass = 'bg-info';
                                                                break;
                                                            case 'Fair':
                                                                $conditionClass = 'bg-warning';
                                                                break;
                                                            case 'Poor':
                                                                $conditionClass = 'bg-danger';
                                                                break;
                                                        }
                                                        echo $conditionClass;
                                                        ?> m-2">
                                                        <?php echo htmlspecialchars($book['condition'] ?? 'New'); ?>
                                                    </span>
                                                </div>
                                                <div class="card-body">
                                                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($book['title']); ?></h5>
                                                    <p class="card-text text-muted mb-1">By <?php echo htmlspecialchars($book['author']); ?></p>
                                                    <p class="card-text mb-1">
                                                        <small class="text-muted">ISBN: <?php echo htmlspecialchars($book['ISBN'] ?? 'N/A'); ?></small>
                                                    </p>
                                                    <p class="card-text mb-1">
                                                        <small class="text-muted">Genre: <?php echo htmlspecialchars($book['genre']); ?></small>
                                                    </p>
                                                    
                                                    <!-- Damages Note (if any) -->
                                                    <?php if (!empty($book['damages'])): ?>
                                                    <p class="card-text mb-1">
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle"></i> 
                                                            <?php echo htmlspecialchars(mb_strimwidth($book['damages'], 0, 30, "...")); ?>
                                                        </small>
                                                    </p>
                                                    <?php endif; ?>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                        <div>
                                                            <?php if (($book['listing_type'] ?? 'both') === 'rent'): ?>
                                                                <p class="mb-0 fw-bold text-primary">Rent: ₱<?php echo number_format($book['rent_price'] ?? 0, 2); ?>/wk</p>
                                                                <small class="badge bg-light text-muted border">For Rent Only</small>
                                                            <?php elseif (($book['listing_type'] ?? 'both') === 'sale'): ?>
                                                                <p class="mb-0 fw-bold text-success">₱<?php echo number_format($book['price'], 2); ?></p>
                                                                <small class="badge bg-light text-muted border">For Sale Only</small>
                                                            <?php else: ?>
                                                                <p class="mb-0 fw-bold text-dark">₱<?php echo number_format($book['price'], 2); ?></p>
                                                                <small class="text-muted">Rent: ₱<?php echo number_format($book['rent_price'] ?? 0, 2); ?>/wk</small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <span class="badge <?php echo $book['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                                <?php echo $book['stock'] > 0 ? 'In Stock: ' . $book['stock'] : 'Out of Stock'; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer bg-white border-0">
                                                    <div class="d-flex justify-content-end">
                                                        <button class="btn btn-sm btn-outline-primary me-2 edit-book" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editBookModal" 
                                                                data-book-id="<?php echo $book['book_id']; ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger delete-book" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteBookModal" 
                                                                data-book-id="<?php echo $book['book_id']; ?>"
                                                                data-book-title="<?php echo htmlspecialchars($book['title']); ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Book Modal (3-Step Wizard) -->
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form id="addBookForm" action="Manage_books.php" method="POST" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="popularity" value="New Releases">

                <div class="modal-header border-bottom py-3 flex-shrink-0">
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background: #fff7ed; color: #f97316;">
                            <i class="fa-solid fa-book-medical fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="addBookModalLabel">Add New Book to Inventory</h5>
                            <small class="text-muted">Create a detailed listing for selling, renting, or both</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(88vh - 130px);">
                        <!-- Step Progress Indicator -->
                        <div class="wizard-steps-container">
                            <div class="wizard-step-node active" id="stepNode1" onclick="jumpToWizardStep(1)">
                                <div class="wizard-step-circle">1</div>
                                <div class="wizard-step-label">1. Book Details</div>
                            </div>
                            <div class="wizard-step-node" id="stepNode2" onclick="jumpToWizardStep(2)">
                                <div class="wizard-step-circle">2</div>
                                <div class="wizard-step-label">2. Photos & Condition</div>
                            </div>
                            <div class="wizard-step-node" id="stepNode3" onclick="jumpToWizardStep(3)">
                                <div class="wizard-step-circle">3</div>
                                <div class="wizard-step-label">3. Pricing & Stock</div>
                            </div>
                        </div>

                        <!-- STEP 1: Book Information -->
                        <div class="wizard-step-content" id="wizardStep1">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label for="title" class="form-label fw-semibold">Book Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-lg fs-6" id="title" name="title" placeholder="e.g. Atomic Habits, The Midnight Library" required>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-7">
                                            <label for="author" class="form-label fw-semibold">Author(s) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="author" name="author" placeholder="e.g. James Clear" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label for="isbn" class="form-label fw-semibold">ISBN <small class="text-muted fw-normal">(Optional)</small></label>
                                            <input type="text" class="form-control" id="isbn" name="isbn" placeholder="e.g. 9780593189641">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label for="book_type" class="form-label fw-semibold">Format / Book Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="book_type" name="book_type" required>
                                                <option value="Paperback" selected>Paperback</option>
                                                <option value="Hardcover">Hardcover</option>
                                                <option value="E-book">E-book</option>
                                                <option value="Audiobook">Audiobook</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="language" class="form-label fw-semibold">Language</label>
                                            <select class="form-select" id="language" name="language">
                                                <option value="English" selected>English</option>
                                                <option value="Filipino">Filipino / Tagalog</option>
                                                <option value="Bilingual">Bilingual (English/Filipino)</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label fw-semibold">Synopsis & Book Summary <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Briefly describe what this book is about..." required></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-5">
                                    <div class="p-3 bg-light rounded-3 border mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0 text-dark">
                                                <i class="fa-solid fa-tags text-warning me-1"></i> Genres <small class="text-muted fw-normal">(Select multiple)</small>
                                            </label>
                                            <span class="badge bg-secondary" id="selectedGenreCount">0 selected</span>
                                        </div>
                                        <p class="small text-muted mb-2">Click all tags that describe this book:</p>
                                        <div class="tag-pills-wrap" id="genrePillsContainer">
                                            <?php 
                                            $popular_genres = [
                                                'Fiction', 'Non-Fiction', 'Fantasy', 'Sci-Fi', 'Mystery', 
                                                'Thriller', 'Romance', 'Self-Help', 'Business', 'History', 
                                                'Biography', 'Psychology', 'Horror', 'Adventure', 'Cookbooks', 'Education'
                                            ];
                                            foreach ($popular_genres as $g): 
                                            ?>
                                                <span class="tag-pill" data-type="genre" data-val="<?php echo htmlspecialchars($g); ?>" onclick="toggleTagPill(this)">
                                                    <i class="fa-solid fa-plus tag-icon"></i> <?php echo htmlspecialchars($g); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="input-group input-group-sm mt-2">
                                            <input type="text" class="form-control" id="customGenreInput" placeholder="Add custom genre...">
                                            <button class="btn btn-outline-secondary" type="button" onclick="addCustomTag('genre')">Add</button>
                                        </div>
                                        <!-- Hidden container where checked inputs will live -->
                                        <div id="hiddenGenresContainer"></div>
                                    </div>

                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0 text-dark">
                                                <i class="fa-solid fa-bookmark text-warning me-1"></i> Themes & Tropes <small class="text-muted fw-normal">(Optional)</small>
                                            </label>
                                            <span class="badge bg-secondary" id="selectedThemeCount">0 selected</span>
                                        </div>
                                        <div class="tag-pills-wrap" id="themePillsContainer">
                                            <?php 
                                            $popular_themes = [
                                                'Young Adult', 'Personal Growth', 'Classic', 'Dystopian', 
                                                'Academic', 'Leadership', 'Memoir', 'Contemporary', 'True Crime', 'Philosophy'
                                            ];
                                            foreach ($popular_themes as $t): 
                                            ?>
                                                <span class="tag-pill" data-type="theme" data-val="<?php echo htmlspecialchars($t); ?>" onclick="toggleTagPill(this)">
                                                    <i class="fa-solid fa-plus tag-icon"></i> <?php echo htmlspecialchars($t); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="input-group input-group-sm mt-2">
                                            <input type="text" class="form-control" id="customThemeInput" placeholder="Add custom theme...">
                                            <button class="btn btn-outline-secondary" type="button" onclick="addCustomTag('theme')">Add</button>
                                        </div>
                                        <!-- Hidden container where checked theme inputs will live -->
                                        <div id="hiddenThemesContainer"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Photos & Condition Documentation -->
                        <div class="wizard-step-content" id="wizardStep2" style="display: none;">
                            <!-- Shopee-Style Photo Upload Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <i class="fa-solid fa-camera text-warning me-1"></i> Book Condition Photos (Shopee Style)
                                        </h6>
                                        <small class="text-muted">High quality photos prove book condition and protect both seller & renter</small>
                                    </div>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-shield-halved me-1"></i> Dispute Protection</span>
                                </div>

                                <div class="photo-upload-grid">
                                    <!-- Slot 1: Front Cover (Primary) -->
                                    <div class="photo-slot" id="slot_cover" onclick="triggerFileInput('file_cover')">
                                        <span class="photo-slot-badge badge-primary-cover">★ Front Cover *</span>
                                        <img src="" alt="Front Cover" class="photo-slot-preview" id="preview_cover">
                                        <div class="photo-slot-placeholder">
                                            <i class="fa-solid fa-image fs-3 text-secondary mb-1"></i>
                                            <div class="photo-slot-title">Front Cover</div>
                                            <small class="text-muted" style="font-size: 0.68rem;">Main Photo</small>
                                        </div>
                                        <div class="photo-slot-overlay">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" title="Change" onclick="event.stopPropagation(); triggerFileInput('file_cover')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle" title="Remove" onclick="event.stopPropagation(); removePhotoSlot('cover', 'file_cover')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="d-none" id="file_cover" name="cover_image" accept="image/*" onchange="handlePhotoUpload(this, 'cover')" required>
                                    </div>

                                    <!-- Slot 2: Back Cover -->
                                    <div class="photo-slot" id="slot_back" onclick="triggerFileInput('file_back')">
                                        <span class="photo-slot-badge badge-extra-photo">Back Cover</span>
                                        <img src="" alt="Back Cover" class="photo-slot-preview" id="preview_back">
                                        <div class="photo-slot-placeholder">
                                            <i class="fa-solid fa-book fs-3 text-secondary mb-1"></i>
                                            <div class="photo-slot-title">Back Cover</div>
                                            <small class="text-muted" style="font-size: 0.68rem;">Barcode/Blurb</small>
                                        </div>
                                        <div class="photo-slot-overlay">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" title="Change" onclick="event.stopPropagation(); triggerFileInput('file_back')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle" title="Remove" onclick="event.stopPropagation(); removePhotoSlot('back', 'file_back')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="d-none" id="file_back" name="back_cover" accept="image/*" onchange="handlePhotoUpload(this, 'back')">
                                    </div>

                                    <!-- Slot 3: Spine & Binding -->
                                    <div class="photo-slot" id="slot_spine" onclick="triggerFileInput('file_spine')">
                                        <span class="photo-slot-badge badge-extra-photo">Spine & Binding</span>
                                        <img src="" alt="Spine" class="photo-slot-preview" id="preview_spine">
                                        <div class="photo-slot-placeholder">
                                            <i class="fa-solid fa-lines-leaning fs-3 text-secondary mb-1"></i>
                                            <div class="photo-slot-title">Spine / Binding</div>
                                            <small class="text-muted" style="font-size: 0.68rem;">Creases check</small>
                                        </div>
                                        <div class="photo-slot-overlay">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" title="Change" onclick="event.stopPropagation(); triggerFileInput('file_spine')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle" title="Remove" onclick="event.stopPropagation(); removePhotoSlot('spine', 'file_spine')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="d-none" id="file_spine" name="spine_cover" accept="image/*" onchange="handlePhotoUpload(this, 'spine')">
                                    </div>

                                    <!-- Slot 4: Inside Pages & Edges -->
                                    <div class="photo-slot" id="slot_pages" onclick="triggerFileInput('file_pages')">
                                        <span class="photo-slot-badge badge-extra-photo">Inside Pages</span>
                                        <img src="" alt="Inside Pages" class="photo-slot-preview" id="preview_pages">
                                        <div class="photo-slot-placeholder">
                                            <i class="fa-solid fa-book-open fs-3 text-secondary mb-1"></i>
                                            <div class="photo-slot-title">Pages & Edges</div>
                                            <small class="text-muted" style="font-size: 0.68rem;">Paper color</small>
                                        </div>
                                        <div class="photo-slot-overlay">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" title="Change" onclick="event.stopPropagation(); triggerFileInput('file_pages')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle" title="Remove" onclick="event.stopPropagation(); removePhotoSlot('pages', 'file_pages')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="d-none" id="file_pages" name="pages_cover" accept="image/*" onchange="handlePhotoUpload(this, 'pages')">
                                    </div>

                                    <!-- Slot 5: Defects & Damage Proof -->
                                    <div class="photo-slot" id="slot_damage" onclick="triggerFileInput('file_damage')">
                                        <span class="photo-slot-badge badge-extra-photo">Damage Proof</span>
                                        <img src="" alt="Damage Proof" class="photo-slot-preview" id="preview_damage">
                                        <div class="photo-slot-placeholder">
                                            <i class="fa-solid fa-circle-exclamation fs-3 text-secondary mb-1"></i>
                                            <div class="photo-slot-title">Damage Close-up</div>
                                            <small class="text-muted" style="font-size: 0.68rem;">Flaws (if any)</small>
                                        </div>
                                        <div class="photo-slot-overlay">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" title="Change" onclick="event.stopPropagation(); triggerFileInput('file_damage')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle" title="Remove" onclick="event.stopPropagation(); removePhotoSlot('damage', 'file_damage')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="d-none" id="file_damage" name="damage_cover" accept="image/*" onchange="handlePhotoUpload(this, 'damage')">
                                    </div>

                                    <!-- Slot 6: Others / Extra Photo (No specific section) -->
                                    <div class="photo-slot" id="slot_other" onclick="triggerFileInput('file_other')">
                                        <span class="photo-slot-badge badge-extra-photo">Others</span>
                                        <img src="" alt="Others Photo" class="photo-slot-preview" id="preview_other">
                                        <div class="photo-slot-placeholder">
                                            <i class="fa-solid fa-camera-retro fs-3 text-secondary mb-1"></i>
                                            <div class="photo-slot-title">Others</div>
                                            <small class="text-muted" style="font-size: 0.68rem;">Extra / Free angle</small>
                                        </div>
                                        <div class="photo-slot-overlay">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" title="Change" onclick="event.stopPropagation(); triggerFileInput('file_other')">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle" title="Remove" onclick="event.stopPropagation(); removePhotoSlot('other', 'file_other')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="d-none" id="file_other" name="other_cover" accept="image/*" onchange="handlePhotoUpload(this, 'other')">
                                    </div>
                                </div>
                                <div class="mt-2 text-muted small">
                                    <i class="fa-regular fa-lightbulb text-warning me-1"></i> Front cover is mandatory. Adding the back cover, spine, pages, defects, or extra angles (others) helps your book rent and sell significantly faster!
                                </div>
                            </div>

                            <hr class="my-3 text-muted opacity-25">

                            <!-- Physical Condition & Damage Checklist -->
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="condition" class="form-label fw-semibold">Condition Rating <span class="text-danger">*</span></label>
                                    <select class="form-select" id="condition" name="condition" required onchange="onConditionChange()">
                                        <option value="New">New (Unread, shrink-wrap or perfect)</option>
                                        <option value="Like New">Like New (Mint, crisp spine, no marks)</option>
                                        <option value="Very Good">Very Good (Minimal shelf wear)</option>
                                        <option value="Good" selected>Good (Readable, mild cover/page wear)</option>
                                        <option value="Fair">Fair (Readable, obvious wear, marks or yellowing)</option>
                                        <option value="Poor">Poor (Heavily worn, loose binding or stained)</option>
                                    </select>
                                    <div class="alert alert-light border mt-2 py-2 px-3 small" id="conditionGuideText">
                                        <i class="fa-solid fa-circle-info text-info me-1"></i> Readable copy with signs of previous reading or mild shelf wear.
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label fw-semibold mb-2">Common Flaws Checklist <small class="text-muted fw-normal">(Tick any that apply)</small></label>
                                    <div class="tag-pills-wrap" id="damageChecklistWrap">
                                        <?php 
                                        $flaws = [
                                            'No noticeable damage', 'Creased spine', 'Yellowing / Foxing pages', 
                                            'Pen / Highlighter markings', 'Cover edge wear / crease', 'Water stain / wavy pages', 'Torn / dog-eared pages'
                                        ];
                                        foreach ($flaws as $flaw): 
                                        ?>
                                            <span class="tag-pill <?php echo $flaw === 'No noticeable damage' ? 'active' : ''; ?>" data-type="damage" data-val="<?php echo htmlspecialchars($flaw); ?>" onclick="toggleDamageTag(this)">
                                                <i class="fa-solid <?php echo $flaw === 'No noticeable damage' ? 'fa-check' : 'fa-plus'; ?> tag-icon"></i> <?php echo htmlspecialchars($flaw); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="hiddenDamagesContainer">
                                        <input type="hidden" name="damage_tags[]" value="No noticeable damage">
                                    </div>

                                    <div class="mt-2" id="damagesTextWrap">
                                        <label for="damages" class="form-label small fw-semibold text-muted">Additional Damage Details (Optional)</label>
                                        <textarea class="form-control" id="damages" name="damages" rows="2" placeholder="e.g. Minor pencil notes on chapters 1-3, front bottom corner has tiny fold."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Pricing Strategy & Inventory -->
                        <div class="wizard-step-content" id="wizardStep3" style="display: none;">
                            <!-- Listing Mode Selector -->
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Select Listing Option <span class="text-danger">*</span></label>
                                <div class="listing-mode-group">
                                    <div class="listing-mode-card active" id="modeCard_both" onclick="setListingMode('both')">
                                        <i class="fa-solid fa-bolt text-warning"></i>
                                        <div class="fw-bold text-dark">Both (Sell & Rent)</div>
                                        <small class="text-muted">Maximum reach: buyers can purchase or borrow</small>
                                        <input type="radio" name="listing_type" value="both" class="d-none" checked>
                                    </div>
                                    <div class="listing-mode-card" id="modeCard_sale" onclick="setListingMode('sale')">
                                        <i class="fa-solid fa-tag text-success"></i>
                                        <div class="fw-bold text-dark">For Sale Only</div>
                                        <small class="text-muted">Sell book directly to keep 100% of profit</small>
                                        <input type="radio" name="listing_type" value="sale" class="d-none">
                                    </div>
                                    <div class="listing-mode-card" id="modeCard_rent" onclick="setListingMode('rent')">
                                        <i class="fa-solid fa-rotate text-info"></i>
                                        <div class="fw-bold text-dark">For Rent Only</div>
                                        <small class="text-muted">Lend out repeatedly for steady income</small>
                                        <input type="radio" name="listing_type" value="rent" class="d-none">
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Strategy Boxes -->
                            <div class="row g-3 mb-3">
                                <!-- Sales Pricing Box -->
                                <div class="col-md-6" id="salePricingCol">
                                    <div class="pricing-section-box h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-tag me-1"></i> Sales Pricing Strategy</h6>
                                            <span class="badge bg-success-subtle text-success border border-success">One-Time Sale</span>
                                        </div>
                                        
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label for="book_value" class="form-label small fw-semibold">Book Market Value (₱)</label>
                                                <input type="number" class="form-control" id="book_value" name="book_value" step="0.01" min="0" value="250.00" oninput="calculatePrices()">
                                                <div class="form-text">Original SRP or estimated value</div>
                                            </div>
                                            <div class="col-6">
                                                <label for="listing_fee" class="form-label small fw-semibold">Platform Fee (₱)</label>
                                                <input type="number" class="form-control bg-light" id="listing_fee" name="listing_fee" value="30.00" readonly>
                                                <div class="form-text">Fixed platform listing fee</div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="markup_percentage" class="form-label small fw-semibold">Markup Rate (%)</label>
                                            <input type="number" class="form-control bg-light" id="markup_percentage" name="markup_percentage" value="30" readonly>
                                            <div class="form-text">Formula: (Book Value + Fee) × 1.30</div>
                                        </div>

                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="price" class="form-label fw-bold mb-0 text-dark">Final Whole Book Price (₱) <span class="text-danger">*</span></label>
                                                <small class="text-muted">Suggested: ₱<span id="suggested_price" class="fw-bold text-dark">0.00</span></small>
                                            </div>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white fw-bold">₱</span>
                                                <input type="number" class="form-control form-control-lg fs-5 fw-bold text-success" id="price" name="price" step="0.01" min="0" value="364.00" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="resetToSuggestedPrice('sale')">Auto</button>
                                            </div>
                                            <small class="text-muted">You can customize the price or keep the auto-suggested rate</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Pricing Box -->
                                <div class="col-md-6" id="rentPricingCol">
                                    <div class="pricing-section-box h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-rotate me-1"></i> Rental Pricing Strategy</h6>
                                            <span class="badge bg-primary-subtle text-primary border border-primary">Weekly Rental</span>
                                        </div>

                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label for="base_rental_fee" class="form-label small fw-semibold">Base Rental Fee (₱)</label>
                                                <input type="number" class="form-control" id="base_rental_fee" name="base_rental_fee" step="0.01" min="0" value="30.00" oninput="calculatePrices()">
                                                <div class="form-text">Your weekly earnings</div>
                                            </div>
                                            <div class="col-6">
                                                <label for="handling_fee" class="form-label small fw-semibold">Handling Fee (₱)</label>
                                                <input type="number" class="form-control bg-light" id="handling_fee" name="handling_fee" value="10.00" readonly>
                                                <div class="form-text">Fixed handling fee</div>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="condition_multiplier" class="form-label small fw-semibold">Condition Multiplier</label>
                                                <input type="number" class="form-control bg-light" id="condition_multiplier" name="condition_multiplier" step="0.01" value="0.90" readonly>
                                                <div class="form-text">Adjusted by condition</div>
                                            </div>
                                            <div class="col-6">
                                                <label for="security_deposit" class="form-label small fw-semibold">Security Deposit (₱)</label>
                                                <input type="number" class="form-control" id="security_deposit" name="security_deposit" step="0.01" value="250.00">
                                                <div class="form-text">Refundable protection</div>
                                            </div>
                                        </div>

                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label for="rent_price" class="form-label fw-bold mb-0 text-dark">Weekly Rent Price (₱) <span class="text-danger">*</span></label>
                                                <small class="text-muted">Suggested: ₱<span id="suggested_rent_price" class="fw-bold text-dark">0.00</span></small>
                                            </div>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white fw-bold">₱</span>
                                                <input type="number" class="form-control form-control-lg fs-5 fw-bold text-primary" id="rent_price" name="rent_price" step="0.01" min="0" value="36.00" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="resetToSuggestedPrice('rent')">Auto</button>
                                            </div>
                                            <small class="text-muted">Charged per 7-day lending cycle</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory & Seller Remarks -->
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label for="stock" class="form-label fw-bold text-dark">Inventory Stock <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary" type="button" onclick="adjustStock(-1)">-</button>
                                            <input type="number" class="form-control text-center fw-bold fs-5" id="stock" name="stock" min="1" value="1" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="adjustStock(1)">+</button>
                                        </div>
                                        <small class="text-muted mt-1 d-block">Number of available physical copies</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="p-3 bg-light rounded-3 border mb-3">
                                        <label for="meetup_location" class="form-label fw-bold text-dark">Designated Meet-up Location <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="meetup_location" name="meetup_location" placeholder="e.g. Main Library Entrance, Student Center Area" required>
                                        <small class="text-muted mt-1 d-block">Where buyers/renters can meet you to get the book</small>
                                    </div>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label for="seller_note" class="form-label fw-bold text-dark">Seller Note & Lending Guidelines <small class="text-muted fw-normal">(Optional)</small></label>
                                        <textarea class="form-control" id="seller_note" name="seller_note" rows="2" placeholder="e.g. Kept in smoke-free home, comes with clear plastic cover. Please avoid liquid spills."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wizard Navigation Footer -->
                    <div class="modal-footer bg-light border-top py-3 d-flex justify-content-between flex-shrink-0">
                        <div>
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary px-4" id="wizardPrevBtn" onclick="navigateWizardStep(-1)" style="display: none;">
                                <i class="fa-solid fa-arrow-left me-1"></i> Previous
                            </button>
                            <button type="button" class="btn btn-primary px-4 fw-bold" id="wizardNextBtn" onclick="navigateWizardStep(1)">
                                Next: Photos & Condition <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                            <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" id="wizardSubmitBtn" style="display: none;">
                                <i class="fa-solid fa-check me-1"></i> Publish Book Listing
                            </button>
                        </div>
                    </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editBookForm" action="manage_books.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="book_id" id="edit_book_id">
                    <div class="modal-body">
                        <!-- Form fields will be populated via JavaScript -->
                        <div class="spinner-border text-primary" role="status" id="editBookLoader">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div id="editBookContent" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_title" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="edit_title" name="title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_author" class="form-label">Author</label>
                                        <input type="text" class="form-control" id="edit_author" name="author" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_isbn" class="form-label">ISBN</label>
                                        <input type="text" class="form-control" id="edit_isbn" name="isbn" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_genre" class="form-label">Genre</label>
                                        <input type="text" class="form-control" id="edit_genre" name="genre" list="genreList" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_theme" class="form-label">Theme</label>
                                        <input type="text" class="form-control" id="edit_theme" name="theme" list="themeList" required>
                                    </div>
                                    <!-- New Field: Book Type -->
                                    <div class="mb-3">
                                        <label for="edit_book_type" class="form-label">Book Type</label>
                                        <select class="form-select" id="edit_book_type" name="book_type" required>
                                            <option value="Paperback">Paperback</option>
                                            <option value="Hardcover">Hardcover</option>
                                            <option value="E-book">E-book</option>
                                            <option value="Audiobook">Audiobook</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- New Field: Condition -->
                                    <div class="mb-3">
                                        <label for="edit_condition" class="form-label">Condition</label>
                                        <select class="form-select" id="edit_condition" name="condition" required onchange="updateEditConditionMultiplier(); calculateEditPrices();">
                                            <option value="New">New</option>
                                            <option value="Like New">Like New</option>
                                            <option value="Very Good">Very Good</option>
                                            <option value="Good">Good</option>
                                            <option value="Fair">Fair</option>
                                            <option value="Poor">Poor</option>
                                        </select>
                                    </div>
                                    <!-- New Field: Damages -->
                                    <div class="mb-3">
                                        <label for="edit_damages" class="form-label">Damages (if any)</label>
                                        <textarea class="form-control" id="edit_damages" name="damages" rows="2" placeholder="Describe any damages or defects..."></textarea>
                                    </div>
                                    
                                    <!-- Pricing Strategy Fields -->
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Pricing Strategy</h6>
                                        </div>
                                        <div class="card-body">
                                            <!-- Rental Pricing Fields -->
                                            <h6>Rental Pricing</h6>
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <label for="edit_base_rental_fee" class="form-label">Base Rental Fee (₱)</label>
                                                    <input type="number" class="form-control" id="edit_base_rental_fee" name="base_rental_fee" step="0.01" min="0" value="50" onchange="calculateEditPrices()">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit_handling_fee" class="form-label">Handling Fee (₱)</label>
                                                    <input type="number" class="form-control" id="edit_handling_fee" name="handling_fee" step="0.01" min="0" value="10" onchange="calculateEditPrices()" readonly>
                                                    <div class="form-text">Fixed handling fee</div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="edit_condition_multiplier" class="form-label">Condition Multiplier</label>
                                                    <input type="number" class="form-control" id="edit_condition_multiplier" name="condition_multiplier" step="0.01" min="0.5" value="1.2" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Sales Pricing Fields -->
                                            <h6>Sales Pricing</h6>
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <label for="edit_book_value" class="form-label">Book Value (₱)</label>
                                                    <input type="number" class="form-control" id="edit_book_value" name="book_value" step="0.01" min="0" value="200" onchange="calculateEditPrices()">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit_listing_fee" class="form-label">Listing Fee (₱)</label>
                                                    <input type="number" class="form-control" id="edit_listing_fee" name="listing_fee" step="0.01" min="0" value="30" onchange="calculateEditPrices()" readonly>
                                                    <div class="form-text">Fixed listing fee</div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="edit_markup_percentage" class="form-label">Markup (%)</label>
                                                    <input type="number" class="form-control" id="edit_markup_percentage" name="markup_percentage" step="1" min="0" max="100" value="30" onchange="calculateEditPrices()" readonly>
                                                    <div class="form-text">Fixed markup percentage</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_price" class="form-label">Price (₱)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="calculateEditPrices()">Recalculate</button>
                                        </div>
                                        <div class="form-text">Suggested: ₱<span id="edit_suggested_price">0.00</span></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_rent_price" class="form-label">Rent Price (₱)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_rent_price" name="rent_price" step="0.01" min="0" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="calculateEditPrices()">Recalculate</button>
                                        </div>
                                        <div class="form-text">Suggested: ₱<span id="edit_suggested_rent_price">0.00</span></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_stock" class="form-label">Stock</label>
                                        <input type="number" class="form-control" id="edit_stock" name="stock" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_cover_image" class="form-label">Cover Image</label>
                                        <input type="file" class="form-control" id="edit_cover_image" name="cover_image" accept="image/*">
                                        <div class="form-text">Leave empty to keep current image</div>
                                        <div id="current_cover_preview" class="mt-2"></div>
                                    </div>
                                    <!-- Popularity is hidden and will maintain its current value -->
                                    <input type="hidden" name="popularity" id="edit_popularity">
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="edit_meetup_location" class="form-label">Meet-up Location</label>
                                        <input type="text" class="form-control" id="edit_meetup_location" name="meetup_location" placeholder="e.g. Main Library Entrance" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_description" class="form-label">Description</label>
                                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Book Modal -->
    <div class="modal fade" id="deleteBookModal" tabindex="-1" aria-labelledby="deleteBookModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteBookModalLabel">Delete Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="delete-book-title"></span>"? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="manage_books.php" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="book_id" id="delete_book_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Datalists for edit form -->
    <datalist id="genreList">
        <?php foreach ($genres as $genre): ?>
            <option value="<?php echo htmlspecialchars($genre); ?>">
        <?php endforeach; ?>
    </datalist>
    
    <datalist id="themeList">
        <?php foreach ($themes as $theme): ?>
            <option value="<?php echo htmlspecialchars($theme); ?>">
        <?php endforeach; ?>
    </datalist>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fiction and Non-Fiction theme options
        const themeOptions = {
            "Fiction": [
                "Fantasy", 
                "Science Fiction", 
                "Mystery", 
                "Thriller", 
                "Suspense", 
                "Romance", 
                "Historical Fiction", 
                "Horror", 
                "Literary Fiction", 
                "Adventure", 
                "Dystopian", 
                "Paranormal", 
                "Magical Realism", 
                "Urban Fantasy", 
                "Contemporary Fiction", 
                "Crime Fiction", 
                "Drama", 
                "Western", 
                "Satire", 
                "Gothic Fiction"
            ],
            "Non-Fiction": [
                "Biography",
                "Autobiography",
                "Memoir",
                "Self-Help",
                "True Crime",
                "History",
                "Science",
                "Nature",
                "Travel",
                "Philosophy",
                "Psychology",
                "Religion/Spirituality",
                "Politics",
                "Essays",
                "Journalism",
                "Art",
                "Music",
                "Business",
                "Health & Wellness",
                "Cookbooks",
                "Education",
                "Parenting",
                "Sports",
                "Technology",
                "Finance"
            ]
        };
        
        // Function to update theme dropdown based on genre selection
        function updateThemeOptions(genreId, themeId) {
            const genreSelect = document.getElementById(genreId);
            const themeSelect = document.getElementById(themeId);
            
            // Clear current options
            themeSelect.innerHTML = '';
            
            const selectedGenre = genreSelect.value;
            
            if (selectedGenre === '') {
                // If no genre selected, disable theme dropdown
                themeSelect.disabled = true;
                themeSelect.innerHTML = '<option value="">Select Genre First</option>';
                return;
            }
            
            // Enable theme dropdown
            themeSelect.disabled = false;
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Theme';
            themeSelect.appendChild(defaultOption);
            
            // Add options based on selected genre
            const options = themeOptions[selectedGenre] || [];
            options.forEach(theme => {
                const option = document.createElement('option');
                option.value = theme;
                option.textContent = theme;
                themeSelect.appendChild(option);
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips for damage info icons
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Show/hide damages field based on condition selection
            const conditionSelect = document.getElementById('condition');
            const damagesField = document.getElementById('damages').closest('.mb-3');
            
            if (conditionSelect && damagesField) {
                // Initial state check
                updateDamagesVisibility(conditionSelect.value);
                
                // Listen for changes
                conditionSelect.addEventListener('change', function() {
                    updateDamagesVisibility(this.value);
                });
            }
            
            // Same for edit modal
            const editConditionSelect = document.getElementById('edit_condition');
            const editDamagesField = document.getElementById('edit_damages').closest('.mb-3');
            
            if (editConditionSelect && editDamagesField) {
                editConditionSelect.addEventListener('change', function() {
                    updateDamagesVisibility(this.value, true);
                });
            }
            
            // Function to show/hide damages field based on condition
            function updateDamagesVisibility(condition, isEdit = false) {
                const damagesField = isEdit 
                    ? document.getElementById('edit_damages').closest('.mb-3')
                    : document.getElementById('damages').closest('.mb-3');
                
                // Show damages field only for non-new conditions
                if (condition === 'New') {
                    damagesField.style.display = 'none';
                    if (isEdit) {
                        document.getElementById('edit_damages').value = '';
                    } else {
                        document.getElementById('damages').value = '';
                    }
                } else {
                    damagesField.style.display = 'block';
                }
            }
            
            // Edit Book Modal
            const editBookModal = document.getElementById('editBookModal');
            const editBookLoader = document.getElementById('editBookLoader');
            const editBookContent = document.getElementById('editBookContent');
            
            // Handle Edit Book button clicks
            editBookModal.addEventListener('show.bs.modal', function(event) {
                // Button that triggered the modal
                const button = event.relatedTarget;
                // Extract book ID
                const bookId = button.getAttribute('data-book-id');
                
                // Set the book ID in the form
                document.getElementById('edit_book_id').value = bookId;
                
                // Show loader
                editBookLoader.style.display = 'block';
                editBookContent.style.display = 'none';
                
                // Fetch book details via AJAX
                fetch('get_book.php?id=' + bookId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const book = data.book;
                            
                            // Populate form fields
                            document.getElementById('edit_title').value = book.title;
                            document.getElementById('edit_author').value = book.author;
                            document.getElementById('edit_isbn').value = book.ISBN || '';
                            
                            // Set genre and update theme options
                            const genreSelect = document.getElementById('edit_genre');
                            genreSelect.value = book.genre;
                            
                            // Set theme
                            const themeSelect = document.getElementById('edit_theme');
                            themeSelect.value = book.theme;
                            
                            // Set new fields
                            document.getElementById('edit_book_type').value = book.book_type || 'Paperback';
                            document.getElementById('edit_condition').value = book.condition || 'New';
                            document.getElementById('edit_damages').value = book.damages || '';
                            
                            // Update damages visibility based on condition
                            updateDamagesVisibility(book.condition || 'New', true);
                            
                            document.getElementById('edit_popularity').value = book.popularity || 'New Releases';
                            document.getElementById('edit_price').value = book.price;
                            document.getElementById('edit_rent_price').value = book.rent_price || 0;
                            document.getElementById('edit_stock').value = book.stock;
                            document.getElementById('edit_meetup_location').value = book.meetup_location || 'Campus Meet-up';
                            document.getElementById('edit_description').value = book.description;
                            
                            // Calculate pricing strategy fields based on existing prices
                            const price = parseFloat(book.price) || 0;
                            const rentPrice = parseFloat(book.rent_price) || 0;
                            const condition = book.condition || 'New';
                            
                            // Get condition multiplier from database or calculate based on condition
                            let conditionMultiplier = parseFloat(book.condition_multiplier) || 1.0;
                            if (!book.condition_multiplier) {
                                switch(condition) {
                                    case 'New': conditionMultiplier = 1.2; break;
                                    case 'Like New': conditionMultiplier = 1.1; break;
                                    case 'Very Good': conditionMultiplier = 1.0; break;
                                    case 'Good': conditionMultiplier = 0.9; break;
                                    case 'Fair': conditionMultiplier = 0.8; break;
                                    case 'Poor': conditionMultiplier = 0.7; break;
                                }
                            }
                            
                            // Set fixed values for handling fee, listing fee, and markup percentage
                            let handlingFee = 10; // Fixed value
                            let listingFee = 30;  // Fixed value
                            let markupPercentage = 30; // Fixed value
                            
                            // Get base rental fee from database or calculate
                            let baseRentalFee = parseFloat(book.base_rental_fee) || 0;
                            
                            // If the value doesn't exist in the database, calculate it
                            if (!book.base_rental_fee) {
                                // Calculate base rental fee from rent price
                                if (rentPrice > 0) {
                                    baseRentalFee = Math.max(5, (rentPrice / conditionMultiplier) - handlingFee);
                                } else {
                                    baseRentalFee = 50; // Default value
                                }
                            }
                            
                            // Get book value from database or calculate
                            let bookValue = parseFloat(book.book_value) || 0;
                            
                            // If the value doesn't exist in the database, calculate it
                            if (!book.book_value) {
                                // Calculate book value from price
                                if (price > 0) {
                                    const estimatedBaseValue = price / 1.3;
                                    bookValue = Math.max(10, estimatedBaseValue - listingFee);
                                } else {
                                    bookValue = 200; // Default value
                                }
                            }
                            
                            // Set the calculated values
                            document.getElementById('edit_base_rental_fee').value = baseRentalFee.toFixed(2);
                            document.getElementById('edit_handling_fee').value = handlingFee.toFixed(2);
                            document.getElementById('edit_condition_multiplier').value = conditionMultiplier.toFixed(2);
                            document.getElementById('edit_book_value').value = bookValue.toFixed(2);
                            document.getElementById('edit_listing_fee').value = listingFee.toFixed(2);
                            document.getElementById('edit_markup_percentage').value = markupPercentage;
                            
                            // Update the suggested prices
                            document.getElementById('edit_suggested_rent_price').textContent = rentPrice.toFixed(2);
                            document.getElementById('edit_suggested_price').textContent = price.toFixed(2);
                            
                            // Call updateEditConditionMultiplier to ensure multiplier is correctly set
                            updateEditConditionMultiplier();
                            
                            // Show current cover image preview if available
                            const coverPreview = document.getElementById('current_cover_preview');
                            if (book.cover_image) {
                                coverPreview.innerHTML = `
                                    <img src="${book.cover_image}" alt="Current cover" style="max-height: 100px; max-width: 100%;" class="img-thumbnail">
                                    <p class="mt-1 mb-0 small">Current cover image</p>
                                `;
                            } else {
                                coverPreview.innerHTML = '<p class="text-muted">No cover image</p>';
                            }
                            
                            // Hide loader and show content
                            editBookLoader.style.display = 'none';
                            editBookContent.style.display = 'block';
                        } else {
                            // Handle error
                            alert('Error loading book details: ' + data.message);
                            const modal = bootstrap.Modal.getInstance(editBookModal);
                            modal.hide();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading book details.');
                        const modal = bootstrap.Modal.getInstance(editBookModal);
                        modal.hide();
                    });
            });
            
            // Delete Book Modal
            const deleteBookModal = document.getElementById('deleteBookModal');
            deleteBookModal.addEventListener('show.bs.modal', function(event) {
                // Button that triggered the modal
                const button = event.relatedTarget;
                
                // Extract book info
                const bookId = button.getAttribute('data-book-id');
                const bookTitle = button.getAttribute('data-book-title');
                
                // Update the modal content
                document.getElementById('delete-book-title').textContent = bookTitle;
                document.getElementById('delete_book_id').value = bookId;
            });
            
            // Select All Checkbox
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    document.querySelectorAll('.book-select').forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                });
            }
            
            // Search functionality
            const searchBooks = document.getElementById('searchBooks');
            if (searchBooks) {
                searchBooks.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const bookRows = document.querySelectorAll('tbody tr');
                    const bookCards = document.querySelectorAll('.book-grid .col-md-6');
                    
                    // Search in table view
                    bookRows.forEach(row => {
                        // Skip empty state row
                        if (row.querySelector('td[colspan]')) return;
                        
                        const title = row.querySelector('.d-flex .text-muted')?.parentElement?.firstElementChild?.textContent.toLowerCase() || '';
                        const author = row.querySelector('.d-flex .text-muted')?.textContent.toLowerCase() || '';
                        const isbn = row.cells[2]?.textContent.toLowerCase() || '';
                        const genre = row.cells[3]?.textContent.toLowerCase() || '';
                        const type = row.cells[4]?.textContent.toLowerCase() || '';
                        const condition = row.cells[5]?.textContent.toLowerCase() || '';
                        
                        if (title.includes(searchTerm) || 
                            author.includes(searchTerm) || 
                            isbn.includes(searchTerm) || 
                            genre.includes(searchTerm) ||
                            type.includes(searchTerm) ||
                            condition.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Search in grid view
                    bookCards.forEach(card => {
                        const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                        const author = card.querySelector('.card-text.text-muted')?.textContent.toLowerCase() || '';
                        const isbn = card.querySelector('small:nth-of-type(1)')?.textContent.toLowerCase() || '';
                        const genre = card.querySelector('small:nth-of-type(2)')?.textContent.toLowerCase() || '';
                        const type = card.querySelector('.badge.bg-info')?.textContent.toLowerCase() || '';
                        const condition = card.querySelector('.badge:not(.bg-info):not(.bg-success):not(.bg-danger)')?.textContent.toLowerCase() || '';
                        
                        if (title.includes(searchTerm) || 
                            author.includes(searchTerm) || 
                            isbn.includes(searchTerm) || 
                            genre.includes(searchTerm) ||
                            type.includes(searchTerm) ||
                            condition.includes(searchTerm)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Check if there are any visible rows
                    const visibleRows = Array.from(bookRows).filter(row => row.style.display !== 'none');
                    const tbody = document.querySelector('tbody');
                    
                    // Show "No results found" if all rows are hidden
                    if (visibleRows.length === 0 && searchTerm !== '' && !document.getElementById('no-results-row')) {
                        const noResultsRow = document.createElement('tr');
                        noResultsRow.id = 'no-results-row';
                        noResultsRow.innerHTML = `
                            <td colspan="11" class="text-center py-3">
                                <p>No books found matching "${searchTerm}"</p>
                            </td>
                        `;
                        tbody.appendChild(noResultsRow);
                    } else if ((visibleRows.length > 0 || searchTerm === '') && document.getElementById('no-results-row')) {
                        document.getElementById('no-results-row').remove();
                    }
                });
            }
            
            // View toggling (Table/Grid)
            const tableViewBtn = document.getElementById('tableViewBtn');
            const gridViewBtn = document.getElementById('gridViewBtn');
            const tableView = document.querySelector('.table-responsive');
            const gridView = document.getElementById('bookGridView');

            if (tableViewBtn && gridViewBtn && tableView && gridView) {
                // Table view (default)
                tableViewBtn.addEventListener('click', function() {
                    tableView.style.display = 'block';
                    gridView.style.display = 'none';
                    tableViewBtn.classList.add('active');
                    gridViewBtn.classList.remove('active');
                    
                    // Save preference to localStorage
                    localStorage.setItem('bookwagon_view_preference', 'table');
                });
                
                // Grid view
                gridViewBtn.addEventListener('click', function() {
                    tableView.style.display = 'none';
                    gridView.style.display = 'flex';
                    gridViewBtn.classList.add('active');
                    tableViewBtn.classList.remove('active');
                    
                    // Save preference to localStorage
                    localStorage.setItem('bookwagon_view_preference', 'grid');
                });
                
                // Load saved preference (if any)
                const savedViewPreference = localStorage.getItem('bookwagon_view_preference');
                if (savedViewPreference === 'grid') {
                    gridViewBtn.click();
                }
            }
        });

        // Function to set condition multiplier based on selected condition
        function updateConditionMultiplier() {
            const conditionSelect = document.getElementById('condition');
            const multiplierInput = document.getElementById('condition_multiplier');
            
            // Set multiplier based on condition
            switch(conditionSelect.value) {
                case 'New':
                    multiplierInput.value = 1.2;
                    break;
                case 'Like New':
                    multiplierInput.value = 1.1;
                    break;
                case 'Very Good':
                    multiplierInput.value = 1.0;
                    break;
                case 'Good':
                    multiplierInput.value = 0.9;
                    break;
                case 'Fair':
                    multiplierInput.value = 0.8;
                    break;
                case 'Poor':
                    multiplierInput.value = 0.7;
                    break;
                default:
                    multiplierInput.value = 1.0;
            }
        }

        // ==================== ADD BOOK WIZARD JAVASCRIPT ====================
        let currentWizardStep = 1;

        function showWizardStep(step) {
            currentWizardStep = step;
            for (let i = 1; i <= 3; i++) {
                const stepContent = document.getElementById('wizardStep' + i);
                const stepNode = document.getElementById('stepNode' + i);
                if (stepContent) {
                    stepContent.style.display = (i === step) ? 'block' : 'none';
                }
                if (stepNode) {
                    stepNode.classList.remove('active', 'completed');
                    if (i < step) {
                        stepNode.classList.add('completed');
                        stepNode.querySelector('.wizard-step-circle').innerHTML = '<i class="fa-solid fa-check"></i>';
                    } else if (i === step) {
                        stepNode.classList.add('active');
                        stepNode.querySelector('.wizard-step-circle').textContent = i;
                    } else {
                        stepNode.querySelector('.wizard-step-circle').textContent = i;
                    }
                }
            }

            const prevBtn = document.getElementById('wizardPrevBtn');
            const nextBtn = document.getElementById('wizardNextBtn');
            const submitBtn = document.getElementById('wizardSubmitBtn');

            if (prevBtn) prevBtn.style.display = (step > 1) ? 'inline-block' : 'none';
            if (nextBtn) {
                nextBtn.style.display = (step < 3) ? 'inline-block' : 'none';
                if (step === 1) nextBtn.innerHTML = 'Next: Photos & Condition <i class="fa-solid fa-arrow-right ms-1"></i>';
                if (step === 2) nextBtn.innerHTML = 'Next: Pricing & Stock <i class="fa-solid fa-arrow-right ms-1"></i>';
            }
            if (submitBtn) submitBtn.style.display = (step === 3) ? 'inline-block' : 'none';
        }

        function validateWizardStep(step) {
            if (step === 1) {
                const title = document.getElementById('title');
                const author = document.getElementById('author');
                const desc = document.getElementById('description');
                if (!title || !title.value.trim()) {
                    alert('Please provide the book title.');
                    if (title) title.focus();
                    return false;
                }
                if (!author || !author.value.trim()) {
                    alert('Please provide the author name.');
                    if (author) author.focus();
                    return false;
                }
                if (!desc || !desc.value.trim()) {
                    alert('Please enter a brief description/synopsis.');
                    if (desc) desc.focus();
                    return false;
                }
            } else if (step === 2) {
                const fileCover = document.getElementById('file_cover');
                const preview = document.getElementById('preview_cover');
                if ((!fileCover || !fileCover.files || fileCover.files.length === 0) && (!preview || !preview.src || preview.src.trim() === '' || preview.src === window.location.href)) {
                    alert('Please upload at least the Front Cover photo (Slot 1).');
                    triggerFileInput('file_cover');
                    return false;
                }
            }
            return true;
        }

        function navigateWizardStep(delta) {
            const nextStep = currentWizardStep + delta;
            if (delta > 0 && !validateWizardStep(currentWizardStep)) {
                return;
            }
            if (nextStep >= 1 && nextStep <= 3) {
                showWizardStep(nextStep);
            }
        }

        function jumpToWizardStep(targetStep) {
            if (targetStep > currentWizardStep) {
                for (let s = currentWizardStep; s < targetStep; s++) {
                    if (!validateWizardStep(s)) return;
                }
            }
            showWizardStep(targetStep);
        }

        // Shopee-style photo upload handlers
        function triggerFileInput(id) {
            const input = document.getElementById(id);
            if (input) input.click();
        }

        function handlePhotoUpload(input, slotKey) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview_' + slotKey);
                    const slot = document.getElementById('slot_' + slotKey);
                    if (preview && slot) {
                        preview.src = e.target.result;
                        slot.classList.add('has-image');
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removePhotoSlot(slotKey, inputId) {
            const preview = document.getElementById('preview_' + slotKey);
            const slot = document.getElementById('slot_' + slotKey);
            const input = document.getElementById(inputId);
            if (preview) preview.src = '';
            if (slot) slot.classList.remove('has-image');
            if (input) input.value = '';
        }

        // Tag Pills for Genre & Theme
        function toggleTagPill(el) {
            el.classList.toggle('active');
            const isGenre = el.getAttribute('data-type') === 'genre';
            const icon = el.querySelector('.tag-icon');
            if (el.classList.contains('active')) {
                if (icon) icon.className = 'fa-solid fa-check tag-icon';
            } else {
                if (icon) icon.className = 'fa-solid fa-plus tag-icon';
            }
            syncHiddenTagInputs(isGenre ? 'genre' : 'theme');
        }

        function syncHiddenTagInputs(type) {
            const container = document.getElementById(type === 'genre' ? 'genrePillsContainer' : 'themePillsContainer');
            const hiddenContainer = document.getElementById(type === 'genre' ? 'hiddenGenresContainer' : 'hiddenThemesContainer');
            const countBadge = document.getElementById(type === 'genre' ? 'selectedGenreCount' : 'selectedThemeCount');
            
            if (!container || !hiddenContainer) return;
            const activePills = container.querySelectorAll('.tag-pill.active');
            hiddenContainer.innerHTML = '';
            
            activePills.forEach(pill => {
                const val = pill.getAttribute('data-val');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = (type === 'genre' ? 'genres[]' : 'themes[]');
                input.value = val;
                hiddenContainer.appendChild(input);
            });

            if (countBadge) {
                countBadge.textContent = activePills.length + ' selected';
            }
        }

        function addCustomTag(type) {
            const input = document.getElementById(type === 'genre' ? 'customGenreInput' : 'customThemeInput');
            if (!input) return;
            const val = input.value.trim();
            if (!val) return;
            
            const container = document.getElementById(type === 'genre' ? 'genrePillsContainer' : 'themePillsContainer');
            if (!container) return;
            
            let existing = null;
            container.querySelectorAll('.tag-pill').forEach(p => {
                if (p.getAttribute('data-val').toLowerCase() === val.toLowerCase()) existing = p;
            });
            
            if (existing) {
                if (!existing.classList.contains('active')) {
                    toggleTagPill(existing);
                }
            } else {
                const pill = document.createElement('span');
                pill.className = 'tag-pill active';
                pill.setAttribute('data-type', type);
                pill.setAttribute('data-val', val);
                pill.onclick = function() { toggleTagPill(this); };
                pill.innerHTML = `<i class="fa-solid fa-check tag-icon"></i> ${val}`;
                container.appendChild(pill);
                syncHiddenTagInputs(type);
            }
            input.value = '';
        }

        // Condition & Damage Handlers
        const conditionDescriptions = {
            'New': 'Brand new, unread copy in mint condition.',
            'Like New': 'Excellent condition, crisp pages, spine intact, no marks.',
            'Very Good': 'Lightly read, minimal shelf wear, complete and clean.',
            'Good': 'Readable copy with signs of previous reading or mild shelf wear.',
            'Fair': 'Noticeable cosmetic wear, yellowing or markings, but complete text.',
            'Poor': 'Heavy wear, stains or loose binding, but intact readable content.'
        };

        function onConditionChange() {
            updateConditionMultiplier();
            const cond = document.getElementById('condition').value;
            const guide = document.getElementById('conditionGuideText');
            if (guide && conditionDescriptions[cond]) {
                guide.innerHTML = `<i class="fa-solid fa-circle-info text-info me-1"></i> ${conditionDescriptions[cond]}`;
            }
            calculatePrices();
        }

        function toggleDamageTag(el) {
            const val = el.getAttribute('data-val');
            const container = document.getElementById('damageChecklistWrap');
            if (!container) return;
            
            if (val === 'No noticeable damage') {
                container.querySelectorAll('.tag-pill').forEach(p => {
                    p.classList.remove('active');
                    const icon = p.querySelector('.tag-icon');
                    if (icon) icon.className = 'fa-solid fa-plus tag-icon';
                });
                el.classList.add('active');
                const icon = el.querySelector('.tag-icon');
                if (icon) icon.className = 'fa-solid fa-check tag-icon';
            } else {
                container.querySelectorAll('.tag-pill').forEach(p => {
                    if (p.getAttribute('data-val') === 'No noticeable damage') {
                        p.classList.remove('active');
                        const icon = p.querySelector('.tag-icon');
                        if (icon) icon.className = 'fa-solid fa-plus tag-icon';
                    }
                });
                el.classList.toggle('active');
                const icon = el.querySelector('.tag-icon');
                if (el.classList.contains('active')) {
                    if (icon) icon.className = 'fa-solid fa-check tag-icon';
                } else {
                    if (icon) icon.className = 'fa-solid fa-plus tag-icon';
                }
            }
            
            const anyActive = container.querySelectorAll('.tag-pill.active').length > 0;
            if (!anyActive) {
                const noDmg = container.querySelector('[data-val="No noticeable damage"]');
                if (noDmg) {
                    noDmg.classList.add('active');
                    const icon = noDmg.querySelector('.tag-icon');
                    if (icon) icon.className = 'fa-solid fa-check tag-icon';
                }
            }

            const hiddenCont = document.getElementById('hiddenDamagesContainer');
            if (hiddenCont) {
                hiddenCont.innerHTML = '';
                container.querySelectorAll('.tag-pill.active').forEach(p => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'damage_tags[]';
                    inp.value = p.getAttribute('data-val');
                    hiddenCont.appendChild(inp);
                });
            }
        }

        // Listing Mode & Pricing Calculations
        function setListingMode(mode) {
            ['both', 'sale', 'rent'].forEach(m => {
                const card = document.getElementById('modeCard_' + m);
                if (card) {
                    card.classList.toggle('active', m === mode);
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) radio.checked = (m === mode);
                }
            });

            const saleCol = document.getElementById('salePricingCol');
            const rentCol = document.getElementById('rentPricingCol');
            const priceInp = document.getElementById('price');
            const rentPriceInp = document.getElementById('rent_price');

            if (mode === 'both') {
                if (saleCol) { saleCol.style.opacity = '1'; saleCol.style.pointerEvents = 'auto'; }
                if (rentCol) { rentCol.style.opacity = '1'; rentCol.style.pointerEvents = 'auto'; }
                if (priceInp) priceInp.required = true;
                if (rentPriceInp) rentPriceInp.required = true;
            } else if (mode === 'sale') {
                if (saleCol) { saleCol.style.opacity = '1'; saleCol.style.pointerEvents = 'auto'; }
                if (rentCol) { rentCol.style.opacity = '0.35'; rentCol.style.pointerEvents = 'none'; }
                if (priceInp) priceInp.required = true;
                if (rentPriceInp) rentPriceInp.required = false;
            } else if (mode === 'rent') {
                if (saleCol) { saleCol.style.opacity = '0.35'; saleCol.style.pointerEvents = 'none'; }
                if (rentCol) { rentCol.style.opacity = '1'; rentCol.style.pointerEvents = 'auto'; }
                if (priceInp) priceInp.required = false;
                if (rentPriceInp) rentPriceInp.required = true;
            }
        }

        function resetToSuggestedPrice(type) {
            if (type === 'sale') {
                const sug = document.getElementById('suggested_price').textContent;
                document.getElementById('price').value = parseFloat(sug) || 0;
            } else if (type === 'rent') {
                const sug = document.getElementById('suggested_rent_price').textContent;
                document.getElementById('rent_price').value = parseFloat(sug) || 0;
            }
        }

        function adjustStock(delta) {
            const stockInp = document.getElementById('stock');
            if (stockInp) {
                let val = parseInt(stockInp.value) || 1;
                val = Math.max(1, val + delta);
                stockInp.value = val;
            }
        }

        // Function to calculate prices based on the formulas
        function calculatePrices() {
            // Rental pricing inputs
            const baseRentalFee = parseFloat(document.getElementById('base_rental_fee').value) || 0;
            const handlingFee = parseFloat(document.getElementById('handling_fee').value) || 10;
            const conditionMultiplier = parseFloat(document.getElementById('condition_multiplier').value) || 1.0;
            
            // Sales pricing inputs
            const bookValue = parseFloat(document.getElementById('book_value').value) || 0;
            const listingFee = parseFloat(document.getElementById('listing_fee').value) || 30;
            const markupPercentage = parseFloat(document.getElementById('markup_percentage').value) || 30;
            const markup = markupPercentage / 100;
            
            // Calculate suggested prices
            const suggestedRentPrice = (baseRentalFee + handlingFee) * conditionMultiplier;
            const suggestedPrice = (bookValue + listingFee) * (1 + markup);
            
            // Update the displayed suggested prices
            const rentEl = document.getElementById('suggested_rent_price');
            const saleEl = document.getElementById('suggested_price');
            if (rentEl) rentEl.textContent = suggestedRentPrice.toFixed(2);
            if (saleEl) saleEl.textContent = suggestedPrice.toFixed(2);
            
            // Auto-update security deposit if user hasn't typed custom value
            const depositInp = document.getElementById('security_deposit');
            if (depositInp && (!depositInp.dataset.manual || depositInp.dataset.manual !== 'true')) {
                depositInp.value = bookValue.toFixed(2);
            }
        }

        // Call functions on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateConditionMultiplier();
            calculatePrices();
            showWizardStep(1);

            // Track if user manually changes security deposit
            const depositInp = document.getElementById('security_deposit');
            if (depositInp) {
                depositInp.addEventListener('input', function() {
                    this.dataset.manual = 'true';
                });
            }

            // Sync initial tags
            syncHiddenTagInputs('genre');
            syncHiddenTagInputs('theme');
            
            // Also set up the edit form
            const editConditionSelect = document.getElementById('edit_condition');
            if (editConditionSelect) {
                editConditionSelect.addEventListener('change', function() {
                    updateEditConditionMultiplier();
                    calculateEditPrices();
                });
            }
        });

        // Function to set condition multiplier for edit form
        function updateEditConditionMultiplier() {
            const conditionSelect = document.getElementById('edit_condition');
            const multiplierInput = document.getElementById('edit_condition_multiplier');
            
            // Set multiplier based on condition
            switch(conditionSelect.value) {
                case 'New':
                    multiplierInput.value = 1.2;
                    break;
                case 'Like New':
                    multiplierInput.value = 1.1;
                    break;
                case 'Very Good':
                    multiplierInput.value = 1.0;
                    break;
                case 'Good':
                    multiplierInput.value = 0.9;
                    break;
                case 'Fair':
                    multiplierInput.value = 0.8;
                    break;
                case 'Poor':
                    multiplierInput.value = 0.7;
                    break;
                default:
                    multiplierInput.value = 1.0;
            }
        }
        
        // Function to calculate prices for edit form
        function calculateEditPrices() {
            // Get rental pricing inputs
            const baseRentalFee = parseFloat(document.getElementById('edit_base_rental_fee').value) || 0;
            const handlingFee = parseFloat(document.getElementById('edit_handling_fee').value) || 0;
            const conditionMultiplier = parseFloat(document.getElementById('edit_condition_multiplier').value) || 1;
            
            // Get sales pricing inputs
            const bookValue = parseFloat(document.getElementById('edit_book_value').value) || 0;
            const listingFee = parseFloat(document.getElementById('edit_listing_fee').value) || 0;
            const markupPercentage = parseFloat(document.getElementById('edit_markup_percentage').value) || 0;
            const markup = markupPercentage / 100;
            
            // Calculate rental price: SP = (Base Rental Fee + Handling) × Condition Multiplier
            const suggestedRentPrice = (baseRentalFee + handlingFee) * conditionMultiplier;
            
            // Calculate sales price: SP = (Book Value + Listing Fee) × (1 + Markup)
            const suggestedPrice = (bookValue + listingFee) * (1 + markup);
            
            // Update the displayed suggested prices
            document.getElementById('edit_suggested_rent_price').textContent = suggestedRentPrice.toFixed(2);
            document.getElementById('edit_suggested_price').textContent = suggestedPrice.toFixed(2);
            
            // Update the actual input fields with the calculated values
            document.getElementById('edit_rent_price').value = suggestedRentPrice.toFixed(2);
            document.getElementById('edit_price').value = suggestedPrice.toFixed(2);
        }
    </script>
    
</body>
</html>