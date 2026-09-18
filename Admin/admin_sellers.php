<?php
session_start();
 
// Check if the user is logged in as admin, if not then redirect to admin login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: index.php");
    exit;
}

include("db_connect.php");

$userType = $_SESSION['usertype'] ?? '';
$firstName = $_SESSION['firstname'] ?? '';
$lastName = $_SESSION['lastname'] ?? '';
$email = $_SESSION['email'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $shopName = $_POST['shop_name'] ?? '';
    $sellerType = $_POST['seller_type'] ?? '';
    $businessName = $_POST['business_name'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $middleName = $_POST['middle_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $address = $_POST['address'] ?? '';
    $zipCode = $_POST['zip_code'] ?? '';
    $businessEmail = $_POST['business_email'] ?? '';
    $businessPhone = $_POST['business_phone'] ?? '';
    
    // File upload handling for shop logo
    $targetDir = "../uploads/shop_logos/";
    $shopLogo = "";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if(isset($_FILES["shop_logo"]) && $_FILES["shop_logo"]["error"] == 0) {
        $maxFileSize = 5 * 1024 * 1024; // 5MB limit
        if ($_FILES["shop_logo"]["size"] > $maxFileSize) {
            $error = "Logo file exceeds the maximum allowed size of 5MB.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["shop_logo"]["tmp_name"]);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileExt = strtolower(pathinfo($_FILES["shop_logo"]["name"], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($mime, $allowedMimes) && in_array($fileExt, $allowedExts)) {
                $safeName = "logo_" . intval($userId) . "_" . bin2hex(random_bytes(6)) . "." . $fileExt;
                $targetFilePath = $targetDir . $safeName;
                if (move_uploaded_file($_FILES["shop_logo"]["tmp_name"], $targetFilePath)) {
                    chmod($targetFilePath, 0644);
                    // Store relative to project root
                    $shopLogo = "uploads/shop_logos/" . $safeName;
                } else {
                    $error = "Failed to upload shop logo.";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
            }
        }
    }
    
    if (empty($error)) {
        // Create seller record in database
        $sql = "INSERT INTO sellers (user_id, shop_name, seller_type, business_name, first_name, last_name, middle_name, location, address, zip_code, business_email, business_phone, shop_logo, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssssssss", $userId, $shopName, $sellerType, $businessName, $firstName, $lastName, $middleName, $location, $address, $zipCode, $businessEmail, $businessPhone, $shopLogo);
        
        if ($stmt->execute()) {
            // Redirect to success page
            header("Location: ../seller_success.php");
            exit();
        } else {
            error_log("admin_sellers.php SQL error: " . $stmt->error);
            $error = "An error occurred while submitting the seller application. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - BookWagon</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/tab.css">
    <style>
        :root {
            --primary-color: #f8a100;
            --secondary-color: #f8f9fa;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            color: var(--text-dark);
            background-color: #fff;
        }
        
        /* Header styles */
        .navbar {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .navbar-brand img {
            height: 60px;
        }
        
        /* Seller Registration Form */
        .seller-registration {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .progress-steps::after {
            content: '';
            position: absolute;
            top: 14px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            background-color: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
        }
        
        .step.active .step-circle {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .step.completed .step-circle {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .step-label {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .required::after {
            content: '*';
            color: red;
            margin-left: 4px;
        }
        
        .form-info {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        
        .upload-box {
            border: 2px dashed #ddd;
            padding: 25px;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
            background-color: #f9f9f9;
            margin-bottom: 15px;
        }
        
        .upload-icon {
            font-size: 30px;
            color: #aaa;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #e09000;
            border-color: #e09000;
        }
        
        .notification-box {
            background-color: #e8f4fd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        
        .notification-box i {
            color: #3498db;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php 
    if ($userType == 'user') {
        include("../include/user_header.php");
    } elseif ($userType == 'seller') {
        include("../include/seller_header.php");
    }
    ?>

    <div class="container pt-3">
        <div class="seller-registration">
            <div class="progress-steps">
                <div class="step completed">
                    <div class="step-circle"><i class="fas fa-check"></i></div>
                    <div class="step-label">Shop Information</div>
                </div>
                <div class="step active">
                    <div class="step-circle">2</div>
                    <div class="step-label">Business Information</div>
                </div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <div class="step-label">Submit</div>
                </div>
            </div>
            
            <div class="notification-box mb-4">
                <i class="fas fa-info-circle"></i>
                This information will be used to ensure proper compliance to seller onboarding requirements to e-marketplace and invoicing purposes.
                <br>
                <small>Note: BookWagon will not re-use any invoices or tax documents due to incomplete or incorrect information provided by sellers.</small>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <!-- Shop Information Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Shop Information</h3>
                    
                    <div class="form-group">
                        <label for="shop_name" class="form-label required">Shop Name</label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" required>
                        <div class="form-info">This will be displayed to customers on BookWagon.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Shop Logo</label>
                        <div class="upload-box" id="logo-upload-box">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p>Click to upload or drag and drop</p>
                            <p class="form-info">Supported formats: JPG, PNG, GIF (Max: 2MB)</p>
                        </div>
                        <input type="file" class="form-control d-none" id="shop_logo" name="shop_logo" accept="image/*">
                        <div id="logo-preview" class="mt-2 text-center" style="display: none;">
                            <img id="preview-img" src="#" alt="Logo Preview" style="max-height: 150px; max-width: 100%;">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="remove-logo">Remove</button>
                        </div>
                    </div>
                </div>
                
                <!-- Business Information Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Business Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label required">Seller Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="seller_type" id="sole_proprietorship" value="Sole Proprietorship" required>
                            <label class="form-check-label" for="sole_proprietorship">
                                Sole Proprietorship
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="seller_type" id="corporation" value="Corporation / Partnership / Cooperative">
                            <label class="form-check-label" for="corporation">
                                Corporation / Partnership / Cooperative
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="seller_type" id="one_person" value="One Person Corporation">
                            <label class="form-check-label" for="one_person">
                                One Person Corporation
                            </label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="first_name" class="form-label required">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="last_name" class="form-label required">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_name" class="form-label required">Business Name/Trade Name</label>
                        <input type="text" class="form-control" id="business_name" name="business_name" required>
                        <div class="form-info">Please fill in your Business Name/Trade Name.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label required">General Location</label>
                        <select class="form-select" id="location" name="location" required>
                            <option value="">Select location</option>
                            <option value="Metro Manila">Metro Manila</option>
                            <option value="Davao City">Davao City</option>
                            <option value="Cebu City">Cebu City</option>
                            <option value="Baguio City">Baguio City</option>
                            <option value="Iloilo City">Iloilo City</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label required">Registered Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="zip_code" class="form-label required">Zip Code</label>
                        <input type="text" class="form-control" id="zip_code" name="zip_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_email" class="form-label required">Business Email</label>
                        <input type="email" class="form-control" id="business_email" name="business_email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_phone" class="form-label required">Business Phone Number</label>
                        <input type="tel" class="form-control" id="business_phone" name="business_phone" required>
                    </div>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I agree to these <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Data Privacy Policy</a>
                    </label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Shop logo upload preview
        document.addEventListener('DOMContentLoaded', function() {
            const logoUploadBox = document.getElementById('logo-upload-box');
            const logoInput = document.getElementById('shop_logo');
            const logoPreview = document.getElementById('logo-preview');
            const previewImg = document.getElementById('preview-img');
            const removeBtn = document.getElementById('remove-logo');
            
            logoUploadBox.addEventListener('click', function() {
                logoInput.click();
            });
            
            logoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        logoPreview.style.display = 'block';
                        logoUploadBox.style.display = 'none';
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            removeBtn.addEventListener('click', function() {
                logoInput.value = '';
                logoPreview.style.display = 'none';
                logoUploadBox.style.display = 'block';
            });
        });
        
        // Prevent back button after logout
        window.onload = function() {
            if(typeof window.history.pushState == 'function') {
                window.history.pushState({}, "Hide", location.href);
            }
            window.onpopstate = function() {
                window.history.go(1);
            };
        };
    </script>
</body>
</html>