<?php
include("connect.php");

// Build the WHERE clause based on filters
$where_clauses = array();
$params = array();

if(isset($_GET['min_price']) && $_GET['min_price'] != '') {
    $where_clauses[] = "price >= ?";
    $params[] = $_GET['min_price'];
}

if(isset($_GET['max_price']) && $_GET['max_price'] != '') {
    $where_clauses[] = "price <= ?";
    $params[] = $_GET['max_price'];
}

if(isset($_GET['popularity']) && $_GET['popularity'] != '') {
    $popularity_values = explode(',', $_GET['popularity']);
    if (count($popularity_values) > 1) {
        $popularity_placeholders = implode(',', array_fill(0, count($popularity_values), '?'));
        $where_clauses[] = "popularity IN ($popularity_placeholders)";
        $params = array_merge($params, $popularity_values);
    } else {
        $where_clauses[] = "popularity = ?";
        $params[] = $_GET['popularity'];
    }
}

if(isset($_GET['genre']) && $_GET['genre'] != '') {
    $genre_values = explode(',', $_GET['genre']);
    if (count($genre_values) > 1) {
        $genre_placeholders = implode(',', array_fill(0, count($genre_values), '?'));
        $where_clauses[] = "genre IN ($genre_placeholders)";
        $params = array_merge($params, $genre_values);
    } else {
        $where_clauses[] = "genre = ?";
        $params[] = $_GET['genre'];
    }
}

if(isset($_GET['author']) && $_GET['author'] != '') {
    $author_values = explode(',', $_GET['author']);
    if (count($author_values) > 1) {
        $author_placeholders = implode(',', array_fill(0, count($author_values), '?'));
        $where_clauses[] = "author IN ($author_placeholders)";
        $params = array_merge($params, $author_values);
    } else {
        $where_clauses[] = "author = ?";
        $params[] = $_GET['author'];
    }
}

if(isset($_GET['theme']) && $_GET['theme'] != '') {
    $theme_values = explode(',', $_GET['theme']);
    if (count($theme_values) > 1) {
        $theme_placeholders = implode(',', array_fill(0, count($theme_values), '?'));
        $where_clauses[] = "theme IN ($theme_placeholders)";
        $params = array_merge($params, $theme_values);
    } else {
        $where_clauses[] = "theme = ?";
        $params[] = $_GET['theme'];
    }
}

// New filters for book type and condition
if(isset($_GET['book_type']) && $_GET['book_type'] != '') {
    $book_type_values = explode(',', $_GET['book_type']);
    if (count($book_type_values) > 1) {
        $book_type_placeholders = implode(',', array_fill(0, count($book_type_values), '?'));
        $where_clauses[] = "book_type IN ($book_type_placeholders)";
        $params = array_merge($params, $book_type_values);
    } else {
        $where_clauses[] = "book_type = ?";
        $params[] = $_GET['book_type'];
    }
}

if(isset($_GET['condition']) && $_GET['condition'] != '') {
    $condition_values = explode(',', $_GET['condition']);
    if (count($condition_values) > 1) {
        $condition_placeholders = implode(',', array_fill(0, count($condition_values), '?'));
        $where_clauses[] = "`condition` IN ($condition_placeholders)";
        $params = array_merge($params, $condition_values);
    } else {
        $where_clauses[] = "`condition` = ?";
        $params[] = $_GET['condition'];
    }
}

if(isset($_GET['listing_type']) && $_GET['listing_type'] != '') {
    $listing_type_values = explode(',', $_GET['listing_type']);
    if (count($listing_type_values) > 1) {
        $lt_placeholders = implode(',', array_fill(0, count($listing_type_values), '?'));
        $where_clauses[] = "listing_type IN ($lt_placeholders)";
        $params = array_merge($params, $listing_type_values);
    } else {
        $where_clauses[] = "listing_type = ?";
        $params[] = $_GET['listing_type'];
    }
}

if(isset($_GET['search']) && $_GET['search'] != '') {
    $where_clauses[] = "(title LIKE ? OR author LIKE ? OR description LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Count total books
$count_query = "SELECT COUNT(*) as total FROM books";

// Add WHERE if we have conditions
if(!empty($where_clauses)) {
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Prepare and execute the count query
$stmt = $conn->prepare($count_query);
if(!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_books = $row['total'];

// Pagination
$books_per_page = 9;
$total_pages = ceil($total_books / $books_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Ensure current page is valid
if($current_page < 1) {
    $current_page = 1;
} elseif($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Function to create pagination URL with current filters
function createPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'rentbooks.php?' . http_build_query($params);
}

// Output pagination UI if we have more than one page
if($total_pages > 1) {
    echo '<nav aria-label="Book pagination">
        <ul class="pagination justify-content-center">';
        if($current_page > 1) {
            echo '<li class="page-item">
                <a class="page-link" href="' . createPaginationUrl($current_page - 1) . '" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>';
        } else {
            echo '<li class="page-item disabled">
                <a class="page-link" href="#" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>';
        }
        
        // Page numbers
        $max_pages_to_show = 5;
        $start_page = max(1, $current_page - floor($max_pages_to_show / 2));
        $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);
        
        // Adjust start page if we're near the end
        if($end_page - $start_page + 1 < $max_pages_to_show) {
            $start_page = max(1, $end_page - $max_pages_to_show + 1);
        }
        
        // First page (if not included in the range)
        if($start_page > 1) {
            echo '<li class="page-item">
                <a class="page-link" href="' . createPaginationUrl(1) . '">1</a>
            </li>';
            
            if($start_page > 2) {
                echo '<li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>';
            }
        }
        
        // Page numbers
        for($i = $start_page; $i <= $end_page; $i++) {
            $active = ($i == $current_page) ? ' active' : '';
            echo '<li class="page-item' . $active . '">
                <a class="page-link" href="' . createPaginationUrl($i) . '">' . $i . '</a>
            </li>';
        }
        
        // Last page (if not included in the range)
        if($end_page < $total_pages) {
            if($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>';
            }
            
            echo '<li class="page-item">
                <a class="page-link" href="' . createPaginationUrl($total_pages) . '">' . $total_pages . '</a>
            </li>';
        }
        
        // Next button
        if($current_page < $total_pages) {
            echo '<li class="page-item">
                <a class="page-link" href="' . createPaginationUrl($current_page + 1) . '" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>';
        } else {
            echo '<li class="page-item disabled">
                <a class="page-link" href="#" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>';
        }
        
        echo '</ul>
        </nav>';
        
        // Display results count
        echo '<div class="text-center text-muted mt-2">
            Showing ' . (($current_page - 1) * $books_per_page + 1) . '-' . 
            min($current_page * $books_per_page, $total_books) . ' of ' . $total_books . ' books
        </div>';
    } else if($total_books > 0) {
        // If only one page, just show the results count
        echo '<div class="text-center text-muted mt-2">
            Showing all ' . $total_books . ' books
        </div>';
    }
    
    $conn->close();
    ?>
    //