<?php
include("guest_session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$firstName = $_SESSION['firstname'] ?? '';
$lastName = $_SESSION['lastname'] ?? '';
$email = $_SESSION['email'] ?? '';
$currentUserId = $_SESSION['id'] ?? 0;

// Check if book_id is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: rentbooks.php");
    exit();
}

$book_id = intval($_GET['id']);

// Fetch book details with seller info
$query = "SELECT b.*, u.firstname, u.lastname, u.email, u.phone, u.profile_picture, u.id as seller_id
          FROM books b 
          LEFT JOIN users u ON b.user_id = u.id 
          WHERE b.book_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: rentbooks.php");
    exit();
}

$book = $result->fetch_assoc();
$stmt->close();

// Fetch additional condition documentation photos from book_images table
$additional_photos = [];
$photos_query = "SELECT image_id, image_url, image_type FROM book_images WHERE book_id = ? ORDER BY image_id ASC";
$photos_stmt = $conn->prepare($photos_query);
if ($photos_stmt) {
    $photos_stmt->bind_param("i", $book_id);
    $photos_stmt->execute();
    $photos_result = $photos_stmt->get_result();
    while ($row = $photos_result->fetch_assoc()) {
        $additional_photos[] = $row;
    }
    $photos_stmt->close();
}

// Format primary cover image
$primary_cover = 'uploads/covers/default_book.jpg';
if (!empty($book['cover_image'])) {
    if (strpos($book['cover_image'], 'uploads/') === 0) {
        $primary_cover = $book['cover_image'];
    } else {
        $primary_cover = 'uploads/covers/' . $book['cover_image'];
    }
}

// Build photo gallery array
$gallery_photos = [];
$gallery_photos[] = [
    'url' => $primary_cover,
    'type' => 'cover',
    'label' => 'Front Cover',
    'desc' => 'Primary Catalog Photo'
];

$photo_type_meta = [
    'back'   => ['label' => 'Back Cover', 'desc' => 'Barcode & Blurb'],
    'spine'  => ['label' => 'Spine & Binding', 'desc' => 'Crease & Binding Check'],
    'pages'  => ['label' => 'Inside Pages', 'desc' => 'Paper Condition & Margins'],
    'damage' => ['label' => 'Damage / Flaws', 'desc' => 'Defect Proof'],
    'other'  => ['label' => 'Others / Angle', 'desc' => 'Free Angle View']
];

foreach ($additional_photos as $p) {
    $img_url = $p['image_url'];
    if (strpos($img_url, 'uploads/') !== 0) {
        $img_url = 'uploads/books/' . $img_url;
    }
    $meta = $photo_type_meta[$p['image_type']] ?? [
        'label' => ucwords(str_replace('_', ' ', $p['image_type'])),
        'desc' => 'Documentation Photo'
    ];
    $gallery_photos[] = [
        'url' => $img_url,
        'type' => $p['image_type'],
        'label' => $meta['label'],
        'desc' => $meta['desc']
    ];
}

// Listing type & Pricing
$listing_type = $book['listing_type'] ?? 'both';
$rent_price = floatval($book['rent_price'] ?? 0);
$sale_price = floatval($book['price'] ?? 0);
$stock = intval($book['stock'] ?? 1);
$security_deposit = floatval($book['security_deposit'] ?? $book['book_value'] ?? 0);
$seller_note = trim($book['seller_note'] ?? '');
$condition_str = $book['condition'] ?? 'Good';

$can_rent = ($listing_type === 'both' || $listing_type === 'rent') && $rent_price > 0;
$can_buy = ($listing_type === 'both' || $listing_type === 'sale') && $sale_price > 0;

// Parse genres & themes
$genres_list = array_filter(array_map('trim', explode(',', $book['genre'] ?? '')));
$themes_list = array_filter(array_map('trim', explode(',', $book['theme'] ?? '')));

// Parse damages / flaw tags
$raw_damages = trim($book['damages'] ?? '');
$flaws_list = [];
if (!empty($raw_damages) && strtolower($raw_damages) !== 'none') {
    $flaws_list = array_filter(array_map('trim', explode(',', $raw_damages)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - BookWagon</title>
    
    <!-- Google Fonts: Playfair Display, Plus Jakarta Sans, and Inter for solid, crisp editorial readability -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- BookWagon Global Tab CSS -->
    <link rel="stylesheet" href="css/tab.css">
    
    <style>
        :root {
            --bw-brand: #f8a100;
            --bw-brand-hover: #e09000;
            --bw-brand-dark: #c2410c;
            --bw-brand-light: #fffbeb;
            --bw-brand-border: #fef3c7;
            --bw-text-primary: #111827;
            --bw-text-secondary: #4b5563;
            --bw-text-muted: #9ca3af;
            --bw-bg-gray: #f6f7f9;
            --bw-border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #ffffff;
            color: var(--bw-text-secondary);
            line-height: 1.6;
        }

        .editorial-page-container {
            max-width: 1360px;
            margin: 2rem auto 5rem;
            padding: 0 1.75rem;
        }

        /* LEFT COLUMN: Editorial Multi-Image Grid */
        .editorial-gallery-grid {
            display: grid;
            gap: 12px;
        }

        /* Top row: 2 images side-by-side (or 1 balanced image if single photo) */
        .gallery-row-top {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .gallery-row-top.single-photo {
            grid-template-columns: 1fr;
        }

        .gallery-row-top.single-photo .editorial-image-card {
            height: 480px;
        }

        /* Bottom row: 3 detail images side-by-side */
        .gallery-row-bottom {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .editorial-image-card {
            background-color: var(--bw-bg-gray);
            border-radius: 6px;
            height: 380px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: zoom-in;
            padding: 1rem;
            transition: all 0.25s ease;
        }

        .gallery-row-bottom .editorial-image-card {
            height: 220px;
        }

        .editorial-image-card:hover {
            background-color: #edf0f4;
        }

        .editorial-image-card img {
            max-width: 92%;
            max-height: 92%;
            object-fit: contain;
            transition: transform 0.35s ease;
            filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.08));
        }

        .editorial-image-card:hover img {
            transform: scale(1.04);
        }

        .editorial-photo-tag {
            position: absolute;
            bottom: 12px;
            left: 12px;
            font-size: 0.65rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            padding: 3px 8px;
            border-radius: 3px;
        }

        /* RIGHT COLUMN: Sticky Editorial Product Details */
        .editorial-details-sticky {
            position: sticky;
            top: 95px;
            padding-left: 2rem;
        }

        .editorial-season-badge {
            font-size: 0.72rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .editorial-season-badge .brand-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--bw-brand);
            display: inline-block;
        }

        /* Modern, Clean Book Title matching all BookWagon pages */
        .editorial-book-title {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 2.15rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
            line-height: 1.2;
            margin-bottom: 8px;
            -webkit-font-smoothing: antialiased;
        }

        .editorial-author-line {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 0.92rem;
            font-weight: 500;
            color: #64748b;
            letter-spacing: normal;
            margin-bottom: 18px;
        }

        .editorial-author-line .author-name {
            font-weight: 600;
            color: #1e293b;
        }

        /* Price & Installments / Deposit Note */
        .editorial-price-amount {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--bw-text-primary);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }

        .editorial-price-amount .unit {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--bw-text-muted);
        }

        .editorial-deposit-pill-line {
            font-size: 0.82rem;
            color: #6b7280;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .editorial-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--bw-brand-light);
            color: #b45309;
            border: 1px solid var(--bw-brand-border);
            border-radius: 4px;
            padding: 2px 8px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* SIZE-STYLE SELECTORS (Sleek Box Chips) */
        .editorial-selector-group {
            margin-bottom: 22px;
        }

        .editorial-selector-label-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--bw-text-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .editorial-guide-link {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--bw-text-muted);
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.5px;
            cursor: pointer;
        }

        .editorial-guide-link:hover {
            color: var(--bw-brand);
        }

        /* Duration / Option Chips (Like XS, S, M, L, XL, XXL) */
        .editorial-chips-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .editorial-chip-btn {
            flex: 1;
            min-width: 52px;
            padding: 9px 12px;
            background-color: #f3f4f6;
            border: 1px solid transparent;
            border-radius: 2px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #111827;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }

        .editorial-chip-btn:hover {
            background-color: #e5e7eb;
        }

        .editorial-chip-btn.active {
            background-color: #111827;
            color: #ffffff;
        }

        /* Primary Add To Bag Button */
        .editorial-btn-bag {
            width: 100%;
            background-color: #111827;
            color: #ffffff;
            border: none;
            border-radius: 3px;
            padding: 16px 24px;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .editorial-btn-bag:hover {
            background-color: var(--bw-brand);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(248, 161, 0, 0.35);
        }

        .editorial-btn-bag:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Delivery & Collect Micro-Notes (Matching reference) */
        .editorial-service-note {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.78rem;
            color: #4b5563;
            margin-bottom: 8px;
        }

        .editorial-service-note i {
            font-size: 0.9rem;
            color: #6b7280;
            width: 16px;
        }

        /* Collapsible Accordion Sections (Matching reference) */
        .editorial-accordion-wrap {
            margin-top: 32px;
            border-top: 1px solid var(--bw-border-color);
        }

        .editorial-accordion-item {
            border-bottom: 1px solid var(--bw-border-color);
        }

        .editorial-accordion-header {
            padding: 18px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-size: 0.76rem;
            font-weight: 800;
            color: var(--bw-text-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.2s;
        }

        .editorial-accordion-header:hover {
            color: var(--bw-brand);
        }

        .editorial-accordion-header i {
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }

        .editorial-accordion-header.expanded i {
            transform: rotate(180deg);
        }

        .editorial-accordion-content {
            padding-bottom: 18px;
            font-size: 0.84rem;
            color: #6b7280;
            line-height: 1.7;
            display: none;
        }

        .editorial-accordion-content.show {
            display: block;
        }

        /* Two-column spec list inside accordion */
        .editorial-spec-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed #f3f4f6;
            font-size: 0.8rem;
        }

        .editorial-spec-row:last-child {
            border-bottom: none;
        }

        .editorial-spec-key {
            color: #9ca3af;
            font-weight: 500;
        }

        .editorial-spec-val {
            color: var(--bw-text-primary);
            font-weight: 600;
            text-align: right;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .editorial-details-sticky {
                position: static;
                padding-left: 0;
                margin-top: 2.5rem;
            }
            .gallery-row-top {
                grid-template-columns: 1fr;
            }
            .gallery-row-bottom {
                grid-template-columns: repeat(2, 1fr);
            }
            .editorial-book-title {
                font-size: 1.95rem;
            }
        }
    </style>
</head>

<body>
    <!-- User Header & Navigation Tabs -->
    <?php include("include/user_header.php"); ?>
    <?php include('include/tab.php'); ?>

    <main class="editorial-page-container">
        <div class="row g-0">
            
            <!-- LEFT COLUMN: High-End Multi-Photo Editorial Grid -->
            <div class="col-lg-7 pe-lg-4">
                <div class="editorial-gallery-grid">
                    <!-- TOP ROW (2 Large Images) -->
                    <div class="gallery-row-top <?php echo count($gallery_photos) === 1 ? 'single-photo' : ''; ?>">
                        <!-- Image 1: Front Cover -->
                        <div class="editorial-image-card" onclick="openLightbox('<?php echo htmlspecialchars($gallery_photos[0]['url']); ?>')">
                            <img src="<?php echo htmlspecialchars($gallery_photos[0]['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 onerror="this.src='uploads/covers/default_book.jpg'">
                            <span class="editorial-photo-tag"><?php echo htmlspecialchars($gallery_photos[0]['label']); ?></span>
                        </div>

                        <!-- Image 2: Back Cover or Next Available -->
                        <?php if (isset($gallery_photos[1])): ?>
                            <div class="editorial-image-card" onclick="openLightbox('<?php echo htmlspecialchars($gallery_photos[1]['url']); ?>')">
                                <img src="<?php echo htmlspecialchars($gallery_photos[1]['url']); ?>" 
                                     alt="<?php echo htmlspecialchars($gallery_photos[1]['label']); ?>"
                                     onerror="this.src='uploads/covers/default_book.jpg'">
                                <span class="editorial-photo-tag"><?php echo htmlspecialchars($gallery_photos[1]['label']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- BOTTOM ROW (3 Detail Images: Spine, Pages, Damage / Others) -->
                    <?php if (count($gallery_photos) > 2): ?>
                        <div class="gallery-row-bottom">
                            <?php for ($i = 2; $i < min(5, count($gallery_photos)); $i++): ?>
                                <div class="editorial-image-card" onclick="openLightbox('<?php echo htmlspecialchars($gallery_photos[$i]['url']); ?>')">
                                    <img src="<?php echo htmlspecialchars($gallery_photos[$i]['url']); ?>" 
                                         alt="<?php echo htmlspecialchars($gallery_photos[$i]['label']); ?>"
                                         onerror="this.src='uploads/covers/default_book.jpg'">
                                    <span class="editorial-photo-tag"><?php echo htmlspecialchars($gallery_photos[$i]['label']); ?></span>
                                </div>
                            <?php endfor; ?>

                            <!-- If 6th photo exists, fill as extra or repeat -->
                            <?php if (isset($gallery_photos[5])): ?>
                                <div class="editorial-image-card" onclick="openLightbox('<?php echo htmlspecialchars($gallery_photos[5]['url']); ?>')">
                                    <img src="<?php echo htmlspecialchars($gallery_photos[5]['url']); ?>" 
                                         alt="<?php echo htmlspecialchars($gallery_photos[5]['label']); ?>"
                                         onerror="this.src='uploads/covers/default_book.jpg'">
                                    <span class="editorial-photo-tag"><?php echo htmlspecialchars($gallery_photos[5]['label']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN: Sticky Editorial Details Panel -->
            <div class="col-lg-5">
                <div class="editorial-details-sticky">
                    
                    <!-- Top Season Tag / Condition -->
                    <div class="editorial-season-badge">
                        <span class="brand-dot"></span>
                        <span><?php echo htmlspecialchars($book['popularity'] ?? 'NEW RELEASE'); ?> • CONDITION: <?php echo strtoupper(htmlspecialchars($condition_str)); ?></span>
                    </div>

                    <!-- Book Title -->
                    <h1 class="editorial-book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                    
                    <!-- Author & Genre -->
                    <div class="editorial-author-line">
                        by <span class="author-name"><?php echo htmlspecialchars($book['author']); ?></span>
                        <?php if (!empty($genres_list)): ?>
                            <span class="mx-1 text-muted">•</span>
                            <span><?php echo htmlspecialchars($genres_list[0]); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Pricing Display -->
                    <div class="editorial-price-amount" id="editorialPriceText">
                        <?php if ($can_rent): ?>
                            ₱<?php echo number_format($rent_price, 2); ?> <span class="unit">/ week</span>
                        <?php else: ?>
                            ₱<?php echo number_format($sale_price, 2); ?> <span class="unit">(Whole Book)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Deposit & Return Micro-badge line (Like Klarna/Afterpay in reference) -->
                    <div class="editorial-deposit-pill-line">
                        <?php if ($can_rent): ?>
                            <span>or ₱<?php echo number_format($rent_price, 2); ?> weekly with</span>
                            <span class="editorial-badge-pill">
                                <i class="fa-solid fa-shield-heart me-1"></i> ₱<?php echo number_format($security_deposit, 2); ?> Deposit
                            </span>
                            <span class="text-muted" style="font-size: 0.72rem;">(100% Refundable)</span>
                        <?php else: ?>
                            <span>Verified physical copy with instant ownership transfer.</span>
                        <?php endif; ?>
                    </div>

                    <!-- OPTION SELECTOR (Rent vs Buy Chips) -->
                    <?php if ($can_rent && $can_buy): ?>
                        <div class="editorial-selector-group">
                            <div class="editorial-selector-label-row">
                                <span>OPTION</span>
                            </div>
                            <div class="editorial-chips-row">
                                <div class="editorial-chip-btn active" id="chipModeRent" onclick="selectPurchaseMode('rent')">
                                    RENT BOOK
                                </div>
                                <div class="editorial-chip-btn" id="chipModeBuy" onclick="selectPurchaseMode('buy')">
                                    BUY WHOLE
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- DURATION SELECTOR (Chips: 1 WK, 2 WKS, 3 WKS, 4 WKS, 8 WKS) -->
                    <div class="editorial-selector-group" id="durationSelectorGroup" style="<?php echo !$can_rent ? 'display: none;' : ''; ?>">
                        <div class="editorial-selector-label-row">
                            <span>DURATION (WEEKS)</span>
                            <span class="editorial-guide-link" onclick="toggleAccordion('policyCollapse')">Deposit Guide</span>
                        </div>
                        <div class="editorial-chips-row">
                            <?php $duration_options = [1, 2, 3, 4, 8, 12]; ?>
                            <?php foreach ($duration_options as $idx => $w): ?>
                                <div class="editorial-chip-btn duration-chip <?php echo $w === 1 ? 'active' : ''; ?>" 
                                     data-weeks="<?php echo $w; ?>"
                                     onclick="selectDurationChip(<?php echo $w; ?>, this)">
                                    <?php echo $w; ?> <?php echo $w === 1 ? 'WK' : 'WKS'; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Hidden Input for Duration (Ensures cart_ajax.js works seamlessly) -->
                    <input type="hidden" name="rental_weeks" id="rental_weeks" value="1">

                    <!-- Live Calculated Upfront Summary -->
                    <div class="mb-3 py-2 px-3 bg-light rounded small text-muted" id="editorialSummaryRow" style="<?php echo !$can_rent ? 'display: none;' : ''; ?>">
                        <span class="fw-bold text-dark">Total Upfront:</span> 
                        <span class="text-danger fw-bold" id="editorialTotalUpfront">₱<?php echo number_format($rent_price + $security_deposit, 2); ?></span> 
                        <span class="ms-1" id="editorialSummaryBreakdown">(₱<?php echo number_format($rent_price, 2); ?> rent + ₱<?php echo number_format($security_deposit, 2); ?> deposit)</span>
                    </div>

                    <!-- Primary Action Button (Like ADD TO BAG in reference) -->
                    <?php if ($stock > 0): ?>
                        <button type="button" 
                                class="editorial-btn-bag add-to-cart-btn" 
                                id="editorialBagBtn"
                                data-book-id="<?php echo $book_id; ?>" 
                                data-purchase-type="<?php echo $can_rent ? 'rent' : 'buy'; ?>">
                            <i class="fa-solid fa-bag-shopping"></i> 
                            <span id="editorialBagBtnText"><?php echo $can_rent ? 'Rent Book' : 'Buy Book'; ?></span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="editorial-btn-bag" disabled>
                            <i class="fa-solid fa-ban"></i> Out of Stock
                        </button>
                    <?php endif; ?>

                    <!-- Service Notes (Matching reference express delivery & collect in store) -->
                    <div class="editorial-service-note">
                        <i class="fa-solid fa-handshake"></i>
                        <span>Direct dispatch by seller <strong><?php echo htmlspecialchars($book['firstname'] . ' ' . $book['lastname']); ?></strong>.</span>
                    </div>
                    <div class="editorial-service-note meetup-highlight" style="background-color: #fff3e0; padding: 15px; border-radius: 8px; border-left: 5px solid #ff9800; margin-top: 10px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fa-solid fa-location-dot" style="color: #ff9800; font-size: 1.4rem;"></i>
                        <span style="font-size: 1.1rem; color: #333;"><strong>MEET UP AT:</strong> <span style="font-size: 1.2rem; font-weight: 800; color: #d84315; text-transform: uppercase;"><?php echo htmlspecialchars($book['meetup_location'] ?? 'Campus Meet-up'); ?></span></span>
                    </div>
                    <div class="editorial-service-note">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Physical condition verified. 100% deposit refund upon documented return.</span>
                    </div>

                    <!-- Collapsible Accordion Sections (PRODUCT DESCRIPTION, DETAILS, COMMITMENT) -->
                    <div class="editorial-accordion-wrap">
                        
                        <!-- 1. PRODUCT DESCRIPTION -->
                        <div class="editorial-accordion-item">
                            <div class="editorial-accordion-header expanded" onclick="toggleAccordion('descCollapse', this)">
                                <span>Product Description</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="editorial-accordion-content show" id="descCollapse">
                                <?php if (!empty($book['description'])): ?>
                                    <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                                <?php else: ?>
                                    <p class="mb-0 text-muted fst-italic">No official description provided for this edition.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 2. PRODUCT DETAILS & SPECIFICATIONS -->
                        <div class="editorial-accordion-item">
                            <div class="editorial-accordion-header" onclick="toggleAccordion('detailsCollapse', this)">
                                <span>Product Details & Specs</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="editorial-accordion-content" id="detailsCollapse">
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">ISBN</span>
                                    <span class="editorial-spec-val"><?php echo htmlspecialchars(!empty($book['ISBN']) ? $book['ISBN'] : 'Unassigned'); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Book ID</span>
                                    <span class="editorial-spec-val">#BW-<?php echo str_pad($book['book_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Format</span>
                                    <span class="editorial-spec-val"><?php echo htmlspecialchars($book['book_type'] ?? 'Paperback'); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Condition Grade</span>
                                    <span class="editorial-spec-val"><?php echo htmlspecialchars($condition_str); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Stock Availability</span>
                                    <span class="editorial-spec-val"><?php echo $stock; ?> available copy</span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Meet-up Location</span>
                                    <span class="editorial-spec-val fw-bold text-dark"><?php echo htmlspecialchars($book['meetup_location'] ?? 'Campus Meet-up'); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Genres</span>
                                    <span class="editorial-spec-val"><?php echo htmlspecialchars($book['genre'] ?? 'General'); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Themes</span>
                                    <span class="editorial-spec-val"><?php echo htmlspecialchars($book['theme'] ?? 'General'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. CONDITION & INSPECTION REPORT -->
                        <div class="editorial-accordion-item">
                            <div class="editorial-accordion-header" onclick="toggleAccordion('conditionCollapse', this)">
                                <span>Condition & Proof Inspection</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="editorial-accordion-content" id="conditionCollapse">
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Rating Grade</span>
                                    <span class="editorial-spec-val fw-bold text-dark"><?php echo htmlspecialchars($condition_str); ?></span>
                                </div>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Reported Flaws</span>
                                    <span class="editorial-spec-val">
                                        <?php if (!empty($flaws_list)): ?>
                                            <?php echo htmlspecialchars(implode(', ', $flaws_list)); ?>
                                        <?php else: ?>
                                            No noticeable flaws reported
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if (!empty($seller_note)): ?>
                                    <div class="editorial-spec-row">
                                        <span class="editorial-spec-key">Seller Note</span>
                                        <span class="editorial-spec-val"><?php echo htmlspecialchars($seller_note); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="editorial-spec-row">
                                    <span class="editorial-spec-key">Condition Proofs</span>
                                    <span class="editorial-spec-val"><?php echo count($gallery_photos); ?> photos available in editorial gallery</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. OUR COMMITMENT & RETURN POLICY -->
                        <div class="editorial-accordion-item">
                            <div class="editorial-accordion-header" onclick="toggleAccordion('policyCollapse', this)">
                                <span>Our Commitment & Return Policy</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="editorial-accordion-content" id="policyCollapse">
                                <p class="mb-2"><strong>100% Refundable Deposit:</strong> Your security deposit of ₱<?php echo number_format($security_deposit, 2); ?> is immediately reimbursed once the book is returned and checked against the seller's condition photos.</p>
                                <p class="mb-2"><strong>Handling Standard:</strong> Please handle all copies with care. Avoid folding corners, liquid stains, or margin markings.</p>
                                <p class="mb-0">
                                    Have specific questions for the owner? 
                                    <a href="javascript:void(0)" class="text-warning fw-bold text-decoration-underline" data-bs-toggle="modal" data-bs-target="#contactSellerModal">
                                        Contact <?php echo htmlspecialchars($book['firstname']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Lightbox Modal for Photo Zoom -->
    <div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="" id="lightboxImg" class="img-fluid rounded shadow-lg" style="max-height: 88vh; background: #fff;" alt="Preview">
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Seller Modal -->
    <div class="modal fade" id="contactSellerModal" tabindex="-1" aria-labelledby="contactSellerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="contactSellerModalLabel">
                        Contact <?php echo htmlspecialchars(($book['firstname'] ?? '') . ' ' . ($book['lastname'] ?? '')); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="contactSellerForm">
                        <div class="mb-3">
                            <label for="message-subject" class="form-label small fw-bold">Subject</label>
                            <input type="text" class="form-control" id="message-subject" value="Inquiry about <?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="message-text" class="form-label small fw-bold">Message</label>
                            <textarea class="form-control" id="message-text" rows="4" placeholder="Ask questions about book availability, meet-up, or condition..."></textarea>
                        </div>
                    </form>
                    <div class="bg-light p-3 rounded border small">
                        <div class="fw-bold text-dark mb-1">Seller Direct Contacts:</div>
                        <div class="text-muted"><i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($book['email'] ?? 'N/A'); ?></div>
                        <?php if (!empty($book['phone'])): ?>
                            <div class="text-muted mt-1"><i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($book['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning text-dark fw-bold px-4" onclick="alert('Message sent to seller!'); const modal = bootstrap.Modal.getInstance(document.getElementById('contactSellerModal')); if(modal) modal.hide();">
                        Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- BookWagon Global Footer -->
    <?php include("include/footer.php"); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AJAX Cart Handler -->
    <script src="js/cart_ajax.js"></script>

    <!-- Interactive Editorial Controls -->
    <script>
        const rentPrice = <?php echo floatval($rent_price); ?>;
        const salePrice = <?php echo floatval($sale_price); ?>;
        const deposit = <?php echo floatval($security_deposit); ?>;
        let currentPurchaseType = '<?php echo $can_rent ? "rent" : "buy"; ?>';

        function openLightbox(imgUrl) {
            const modalImg = document.getElementById('lightboxImg');
            if (modalImg) modalImg.src = imgUrl;
            const modal = new bootstrap.Modal(document.getElementById('lightboxModal'));
            modal.show();
        }

        function toggleAccordion(id, headerEl) {
            const content = document.getElementById(id);
            if (!content) return;
            const isShown = content.classList.contains('show');
            content.classList.toggle('show');
            if (headerEl) {
                headerEl.classList.toggle('expanded', !isShown);
            }
        }

        function selectPurchaseMode(mode) {
            currentPurchaseType = mode;
            const chipRent = document.getElementById('chipModeRent');
            const chipBuy = document.getElementById('chipModeBuy');
            const durationGroup = document.getElementById('durationSelectorGroup');
            const summaryRow = document.getElementById('editorialSummaryRow');
            const priceText = document.getElementById('editorialPriceText');
            const bagBtn = document.getElementById('editorialBagBtn');
            const bagBtnText = document.getElementById('editorialBagBtnText');

            if (mode === 'rent') {
                if (chipRent) chipRent.classList.add('active');
                if (chipBuy) chipBuy.classList.remove('active');
                if (durationGroup) durationGroup.style.display = 'block';
                if (summaryRow) summaryRow.style.display = 'block';
                if (priceText) priceText.innerHTML = '₱' + rentPrice.toFixed(2) + ' <span class="unit">/ week</span>';
                if (bagBtn) bagBtn.dataset.purchaseType = 'rent';
                if (bagBtnText) bagBtnText.textContent = 'Rent Book';
                recalculateRental();
            } else {
                if (chipBuy) chipBuy.classList.add('active');
                if (chipRent) chipRent.classList.remove('active');
                if (durationGroup) durationGroup.style.display = 'none';
                if (summaryRow) summaryRow.style.display = 'none';
                if (priceText) priceText.innerHTML = '₱' + salePrice.toFixed(2) + ' <span class="unit">(Whole Book)</span>';
                if (bagBtn) bagBtn.dataset.purchaseType = 'buy';
                if (bagBtnText) bagBtnText.textContent = 'Buy Book';
            }
        }

        function selectDurationChip(weeks, chipEl) {
            document.querySelectorAll('.duration-chip').forEach(c => c.classList.remove('active'));
            if (chipEl) chipEl.classList.add('active');
            
            const weeksInput = document.getElementById('rental_weeks');
            if (weeksInput) weeksInput.value = weeks;
            
            recalculateRental();
        }

        function recalculateRental() {
            const weeksInput = document.getElementById('rental_weeks');
            const weeks = parseInt(weeksInput ? weeksInput.value : 1) || 1;
            const totalRent = rentPrice * weeks;
            const totalUpfront = totalRent + deposit;

            const upfrontEl = document.getElementById('editorialTotalUpfront');
            const breakdownEl = document.getElementById('editorialSummaryBreakdown');

            if (upfrontEl) upfrontEl.textContent = '₱' + totalUpfront.toFixed(2);
            if (breakdownEl) breakdownEl.textContent = '(₱' + totalRent.toFixed(2) + ' for ' + weeks + (weeks === 1 ? ' wk' : ' wks') + ' + ₱' + deposit.toFixed(2) + ' deposit)';
        }

        document.addEventListener('DOMContentLoaded', function() {
            recalculateRental();
        });
    </script>
</body>
</html>