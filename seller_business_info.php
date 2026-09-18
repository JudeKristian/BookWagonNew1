<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? '';
$firstName = $_SESSION['firstname'] ?? '';
$lastName = $_SESSION['lastname'] ?? '';
$email = $_SESSION['email'] ?? '';
$userId = $_SESSION['id'] ?? 0; // Changed from user_id to id

// Check if shop logo exists in session
if(!isset($_SESSION['temp_shop_logo'])) {
    // Redirect back to shop information page
    header("Location: start_selling.php");
    exit;
}

$shopLogo = $_SESSION['temp_shop_logo'];

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
    
    // Create seller record in database
    $sql = "INSERT INTO sellers (user_id, shop_name, seller_type, business_name, first_name, last_name, middle_name, location, address, zip_code, business_email, business_phone, shop_logo, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $stmt->bind_param("issssssssssss", $userId, $shopName, $sellerType, $businessName, $firstName, $lastName, $middleName, $location, $address, $zipCode, $businessEmail, $businessPhone, $shopLogo);
    
        if ($stmt->execute()) {
            // Clear the temporary session variable
            unset($_SESSION['temp_shop_logo']);
            
            // Redirect to success page
            header("Location: seller_success.php");
            exit();
        } else {
            $error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Information - BookWagon</title>
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
        
        .shop-logo-preview {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .shop-logo-preview img {
            max-height: 100px;
            max-width: 100%;
            border-radius: 5px;
        }
    </style>
</head>
<?php 
    if ($userType == 'user') {
        include("include/user_header.php");
    } elseif ($userType == 'seller') {
        include("include/seller_header.php");
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
            
            <div class="shop-logo-preview">
                <h5>Your Shop Logo</h5>
                <img src="<?php echo $shopLogo; ?>" alt="Shop Logo">
                <div class="mt-2">
                    <a href="start_selling.php" class="btn btn-sm btn-outline-secondary">Change Logo</a>
                </div>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Business Information Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Business Information</h3>
                    
                    <div class="form-group">
                        <label for="shop_name" class="form-label required">Shop Name</label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" required>
                        <div class="form-info">This will be displayed to customers on BookWagon.</div>
                    </div>
                    
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
                        <input type="tel" class="form-control bw-phone" id="business_phone" name="business_phone" required>
                    </div>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I agree to these <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Data Privacy Policy</a>
                    </label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="start_selling.php" class="btn btn-outline-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/bw-format.js"></script>
</body>
</html>