<?php
include("session.php");
include("connect.php");

// Build the WHERE clause based on filters
$where_clauses = array();
$params = array();

if(isset($_GET['min_price']) && $_GET['min_price'] != '') {
    $where_clauses[] = "b.price >= ?";
    $params[] = $_GET['min_price'];
}

if(isset($_GET['max_price']) && $_GET['max_price'] != '') {
    $where_clauses[] = "b.price <= ?";
    $params[] = $_GET['max_price'];
}

if(isset($_GET['popularity']) && $_GET['popularity'] != '') {
    $where_clauses[] = "b.popularity = ?";
    $params[] = $_GET['popularity'];
}

if(isset($_GET['genre']) && $_GET['genre'] != '') {
    $where_clauses[] = "b.genre LIKE ?";
    $params[] = '%' . $_GET['genre'] . '%';
}

if(isset($_GET['author']) && $_GET['author'] != '') {
    $where_clauses[] = "b.author LIKE ?";
    $params[] = '%' . $_GET['author'] . '%';
}

if(isset($_GET['theme']) && $_GET['theme'] != '') {
    $where_clauses[] = "b.theme LIKE ?";
    $params[] = '%' . $_GET['theme'] . '%';
}

// Filter for listing type (Both, Rent Only, Sale Only)
if(isset($_GET['listing_type']) && $_GET['listing_type'] != '') {
    $types = explode(',', $_GET['listing_type']);
    $type_clauses = [];
    foreach ($types as $t) {
        $t = trim($t);
        if ($t === 'both') {
            $type_clauses[] = "b.listing_type = 'both'";
        } elseif ($t === 'rent') {
            $type_clauses[] = "(b.listing_type = 'rent' OR b.listing_type = 'both')";
        } elseif ($t === 'sale') {
            $type_clauses[] = "(b.listing_type = 'sale' OR b.listing_type = 'both')";
        }
    }
    if (!empty($type_clauses)) {
        $where_clauses[] = "(" . implode(" OR ", $type_clauses) . ")";
    }
}

// Add filters for book type and condition
if(isset($_GET['book_type']) && $_GET['book_type'] != '') {
    $where_clauses[] = "b.book_type = ?";
    $params[] = $_GET['book_type'];
}

if(isset($_GET['condition']) && $_GET['condition'] != '') {
    $where_clauses[] = "b.`condition` = ?";
    $params[] = $_GET['condition'];
}

if(isset($_GET['search']) && $_GET['search'] != '') {
    $where_clauses[] = "(b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Default query
$query = "SELECT b.*, u.firstname, u.lastname, u.profile_picture 
          FROM books b
          JOIN users u ON b.user_id = u.id
          WHERE b.approval_status = 'approved'";

// Add WHERE if we have conditions
if(!empty($where_clauses)) {
    $query .= " AND " . implode(" AND ", $where_clauses);
}

// Add ORDER BY
if(isset($_GET['sort'])) {
    switch($_GET['sort']) {
        case 'price_asc':
            $query .= " ORDER BY b.price ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY b.price DESC";
            break;
        case 'newest':
            $query .= " ORDER BY b.created_at DESC";
            break;
        default:
            $query .= " ORDER BY b.title ASC";
    }
} else {
    // Default sorting
    $query .= " ORDER BY b.title ASC";
}

// Pagination
$books_per_page = 9;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $books_per_page;

// Add LIMIT for pagination
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $books_per_page;

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii';
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Custom styling matching BookWagon's warm brand theme
echo '<style>
    .book-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        overflow: hidden;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .book-card:hover {
        transform: translateY(-5px);
        border-color: #f8a100 !important;
        box-shadow: 0 12px 24px rgba(248, 161, 0, 0.12) !important;
    }
    .book-img-container {
        height: 270px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        background-color: #f8fafc;
        padding: 1.25rem;
    }
    .book-img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.08));
        transition: transform 0.3s ease;
    }
    .book-card:hover .book-img {
        transform: scale(1.05);
    }
    .book-badges-stack {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .card-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }
    .action-icon {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 6px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        transition: all 0.2s ease;
        color: #64748b;
    }
    .action-icon:hover {
        background: #f8a100;
        color: #fff;
        transform: scale(1.1);
    }
    .book-link {
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }
    .book-link:hover {
        text-decoration: none;
        color: inherit;
    }
    .seller-info {
        display: flex;
        align-items: center;
        margin-bottom: 6px;
        gap: 8px;
    }
    .seller-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        overflow: hidden;
        background-color: #fff7ed;
        color: #ea580c;
        border: 1px solid #fed7aa;
        text-align: center;
        line-height: 26px;
        font-weight: 700;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .seller-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .seller-name {
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .book-card-title {
        font-size: 1.02rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 2.6rem;
    }
    .book-card-author {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>';

// Display books
if ($result->num_rows > 0) {
    while($book = $result->fetch_assoc()) {
        $rating = rand(4, 5);
        $rent_price = floatval($book['rent_price'] ?? 0);
        $sale_price = floatval($book['price'] ?? 0);
        $listing_type = $book['listing_type'] ?? 'both';
        $condition_val = $book['condition'] ?? 'Good';
        
        // Handle cover image path
        if (!empty($book['cover_image'])) {
            if (strpos($book['cover_image'], 'uploads/covers/') === 0) {
                $cover_image = $book['cover_image'];
            } else {
                $cover_image = 'uploads/covers/' . $book['cover_image'];
            }
        } else {
            $cover_image = 'uploads/covers/default_book.jpg';
        }
        
        // Condition badge styling (Matching book_details.php)
        $cond_bg = '#fffbeb';
        $cond_color = '#d97706';
        $cond_border = '#fde68a';
        
        if ($condition_val === 'New' || $condition_val === 'Like New') {
            $cond_bg = '#ecfdf5';
            $cond_color = '#059669';
            $cond_border = '#a7f3d0';
        } elseif ($condition_val === 'Fair' || $condition_val === 'Poor') {
            $cond_bg = '#fff7ed';
            $cond_color = '#ea580c';
            $cond_border = '#fed7aa';
        }
        
        // Listing mode badge styling
        $mode_badge_html = '';
        if ($listing_type === 'both') {
            $mode_badge_html = '<span class="badge" style="background: rgba(248, 161, 0, 0.95); color: #fff; font-size: 0.68rem; font-weight: 700; border-radius: 4px;"><i class="fa-solid fa-bolt me-1"></i>Rent & Buy</span>';
        } elseif ($listing_type === 'rent') {
            $mode_badge_html = '<span class="badge" style="background: rgba(16, 185, 129, 0.95); color: #fff; font-size: 0.68rem; font-weight: 700; border-radius: 4px;"><i class="fa-solid fa-rotate me-1"></i>Rent Only</span>';
        } else {
            $mode_badge_html = '<span class="badge" style="background: rgba(15, 23, 42, 0.85); color: #fff; font-size: 0.68rem; font-weight: 700; border-radius: 4px;"><i class="fa-solid fa-tag me-1"></i>For Sale</span>';
        }

        // Seller info
        $sellerName = htmlspecialchars($book['firstname'] . ' ' . $book['lastname']);
        $sellerInitial = strtoupper(substr($book['firstname'] ?? 'S', 0, 1));
        $sellerAvatar = !empty($book['profile_picture']) ? $book['profile_picture'] : '';
        
        $avatarHtml = '';
        if (!empty($sellerAvatar) && file_exists($sellerAvatar)) {
            $avatarHtml = '<img src="' . htmlspecialchars($sellerAvatar) . '" alt="Seller" onerror="this.parentNode.innerHTML=\'' . $sellerInitial . '\'">';
        } else {
            $avatarHtml = $sellerInitial;
        }
        
        // Pricing block based on listing type
        $pricing_html = '';
        if ($listing_type === 'rent') {
            $pricing_html = '
                <div>
                    <div class="fw-bold" style="color: #ea580c; font-size: 1.18rem;">
                        ₱' . number_format($rent_price, 2) . '<span style="font-size: 0.75rem; color: #64748b; font-weight: 500;"> / week</span>
                    </div>
                    <div class="text-muted" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-shield-heart text-success me-1"></i>Deposit: ₱' . number_format($book['security_deposit'] ?? $book['book_value'] ?? 0, 2) . '
                    </div>
                </div>';
        } elseif ($listing_type === 'sale') {
            $pricing_html = '
                <div>
                    <div class="fw-bold" style="color: #0f172a; font-size: 1.18rem;">
                        ₱' . number_format($sale_price, 2) . '
                    </div>
                    <div class="text-muted" style="font-size: 0.72rem;">
                        Whole book purchase
                    </div>
                </div>';
        } else {
            $pricing_html = '
                <div>
                    <div class="fw-bold" style="color: #ea580c; font-size: 1.12rem;">
                        ₱' . number_format($rent_price, 2) . '<span style="font-size: 0.75rem; color: #64748b; font-weight: 500;"> / wk</span>
                    </div>
                    <div class="fw-semibold text-secondary" style="font-size: 0.75rem;">
                        or ₱' . number_format($sale_price, 2) . ' (Buy)
                    </div>
                </div>';
        }
        
        // Render card
        echo '
        <div class="col">
            <div class="card h-100 book-card">
                <a href="book_details.php?id=' . $book['book_id'] . '" class="book-link">
                    <div class="book-img-container">
                        <img src="' . htmlspecialchars($cover_image) . '" 
                            class="book-img" 
                            alt="' . htmlspecialchars($book['title']) . '" 
                            loading="lazy"
                            onerror="this.src=\'uploads/covers/default_book.jpg\'">
                            
                        <!-- Book Badges -->
                        <div class="book-badges-stack">
                            ' . $mode_badge_html . '
                            <span class="badge" style="background: ' . $cond_bg . '; color: ' . $cond_color . '; border: 1px solid ' . $cond_border . '; font-size: 0.68rem; font-weight: 700; border-radius: 4px;">
                                ' . htmlspecialchars($condition_val) . '
                            </span>
                        </div>

                        <div class="card-actions">
                            <div class="action-icon" title="Add to Favorites">
                                <i class="far fa-heart" data-book-id="' . $book['book_id'] . '"></i>
                            </div>
                            <div class="action-icon" title="Bookmark">
                                <i class="far fa-bookmark" data-book-id="' . $book['book_id'] . '"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <!-- Seller Information -->
                        <div class="seller-info">
                            <div class="seller-avatar">
                                ' . $avatarHtml . '
                            </div>
                            <div class="seller-name">' . $sellerName . '</div>
                            <i class="fa-solid fa-circle-check text-warning ms-auto" style="font-size: 0.72rem;" title="Verified Seller"></i>
                        </div>
                        
                        <div class="book-card-author">' . htmlspecialchars($book['author']) . '</div>
                        <h5 class="book-card-title">' . htmlspecialchars($book['title']) . '</h5>
                        
                        <!-- Rating -->
                        <div class="d-flex align-items-center mb-2" style="font-size: 0.8rem; color: #f59e0b; gap: 2px;">';
                        for($i = 1; $i <= 5; $i++) {
                            if($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star text-muted" style="opacity: 0.4;"></i>';
                            }
                        }
                        echo '
                            <span class="text-muted ms-1 small" style="font-size: 0.72rem;">(5.0)</span>
                        </div>
                        
                        <!-- Pricing Row -->
                        <div class="d-flex justify-content-between align-items-end pt-2 border-top">
                            ' . $pricing_html . '
                            <span class="btn btn-sm btn-outline-warning text-dark fw-bold px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">
                                View <i class="fa-solid fa-arrow-right ms-1"></i>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        </div>';
    }
} else {
    echo '<div class="col-12 text-center py-5">
            <div class="text-muted mb-2"><i class="fa-solid fa-book-open fs-1 text-warning opacity-50"></i></div>
            <h5 class="fw-bold text-dark">No books found matching your criteria</h5>
            <p class="text-muted small">Try adjusting your filters, price range, or search keywords</p>
          </div>';
}

$stmt->close();
?>