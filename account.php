<?php
include("session.php");
include("connect.php");

$userType = $_SESSION['usertype'] ?? ''; // Change to lowercase 'usertype'
$firstName = $_SESSION['firstname'] ?? ''; // Change to lowercase 'firstname'
$lastName = $_SESSION['lastname'] ?? ''; // Change to lowercase 'lastname'
$email = $_SESSION['email'] ?? '';


if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found in database
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

// Handle profile picture upload
if (isset($_POST['upload_picture'])) {
    $targetDir = "uploads/profile_pictures/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($_FILES["profile_picture"]["name"]);
    $targetFilePath = $targetDir . $userId . "_" . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (in_array($fileType, $allowTypes)) {
        // Upload file to server
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
            // Update profile picture path in database
            $updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $updateStmt->bind_param("si", $targetFilePath, $userId);
            $updateStmt->execute();
            
            // Refresh page to show updated picture
            header("Location: account.php");
            exit();
        } else {
            $uploadError = "Sorry, there was an error uploading your file.";
        }
    } else {
        $uploadError = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }
}

// Handle personal information update
if (isset($_POST['update_personal_info'])) {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $phone = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    $updateStmt = $conn->prepare("UPDATE users SET firstname = ?, middlename = ?, lastname = ?, username = ?, phone = ?, bio = ? WHERE id = ?");
    $updateStmt->bind_param("ssssssi", $firstname, $middlename, $lastname, $username, $phone, $bio, $userId);
    
    if ($updateStmt->execute()) {
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['username'] = $username;
        $_SESSION['success_message'] = "Personal information updated successfully!";
        header("Location: account.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating information: " . $conn->error;
    }
}

// Handle address update
if (isset($_POST['update_address'])) {
    $country = $_POST['country'] ?? '';
    $city_state = $_POST['city_state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $tax_id = $_POST['tax_id'] ?? '';
    
    $updateStmt = $conn->prepare("UPDATE users SET country = ?, city_state = ?, postal_code = ?, tax_id = ? WHERE id = ?");
    $updateStmt->bind_param("ssssi", $country, $city_state, $postal_code, $tax_id, $userId);
    
    if ($updateStmt->execute()) {
        $_SESSION['success_message'] = "Address information updated successfully!";
        header("Location: account.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating address: " . $conn->error;
    }
}

// Default values if fields don't exist in database yet
$profilePicture = $user['profile_picture'] ?? 'images/default-profile.png';
$phone = $user['phone'] ?? '';
$bio = $user['bio'] ?? '';
$country = $user['country'] ?? '';
$city_state = $user['city_state'] ?? '';
$postal_code = $user['postal_code'] ?? '';
$tax_id = $user['tax_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - BookWagon</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #f8a100;
            --primary-dark: #d97706;
            --primary-light: #fffbeb;
            --primary-border: #fef3c7;
            --text-dark: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #edf2f7;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-body);
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: rgba(0,0,0,0.05);
        }

        .dropdown-item:active {
            background-color: rgba(0,0,0,0.1);
        }

        .navbar {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            background-color: #ffffff;
        }
        
        .navbar-brand img {
            height: 60px;
        }
        .profile-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            padding: 24px 28px;
            margin-bottom: 24px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .profile-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.04);
            border-color: #e2e8f0;
        }
        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .profile-picture {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            margin-right: 20px;
        }
        .profile-picture-container {
            position: relative;
            display: inline-block;
        }
        .edit-picture {
            position: absolute;
            bottom: 0;
            right: 12px;
            background: #ffffff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            cursor: pointer;
            border: 2px solid #ffffff;
            transition: all 0.2s ease;
        }
        .edit-picture:hover {
            background: var(--primary-color);
            transform: scale(1.1);
        }
        .edit-picture i {
            font-size: 13px;
            color: #64748b;
            transition: color 0.2s ease;
        }
        .edit-picture:hover i {
            color: #ffffff;
        }
        .user-info {
            flex-grow: 1;
        }
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .section-title h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.01em;
        }
        .edit-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .edit-button:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            border-color: var(--primary-border);
        }
        .info-row {
            margin-bottom: 18px;
        }
        .info-label {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }
        .info-value {
            color: #1e293b;
            font-size: 0.96rem;
            font-weight: 600;
        }
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(248, 161, 0, 0.2);
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include("include/user_header.php"); ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar Column -->
            <div class="col-lg-3 col-md-4 mb-4">
                <?php include("include/user_sidebar.php"); ?>
            </div>
            
            <!-- Main Content Column -->
            <div class="col-lg-9 col-md-8">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Header Card -->
                <div class="profile-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <div class="profile-picture-container">
                                <?php if(!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 84px; height: 84px; font-size: 32px; background: linear-gradient(135deg, #f8a100, #ea580c); border: 3px solid #ffffff; box-shadow: 0 4px 14px rgba(248, 161, 0, 0.25);">
                                        <?php echo strtoupper(substr($user['firstname'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="edit-picture" data-bs-toggle="modal" data-bs-target="#uploadPictureModal" title="Change photo">
                                    <i class="fa-solid fa-camera"></i>
                                </div>
                            </div>
                            <div class="user-info ms-3">
                                <h2 class="mb-1" style="font-size: 1.45rem; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h2>
                                <p class="text-muted mb-1" style="font-size: 0.88rem;"><?php echo $bio ? htmlspecialchars($bio) : 'BookWagon Member'; ?></p>
                                <?php if ($city_state || $country): ?>
                                    <p class="text-muted mb-0" style="font-size: 0.82rem;"><i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo htmlspecialchars(trim($city_state . ', ' . $country, ', ')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <button class="edit-button" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Card -->
                <div class="profile-card">
                    <div class="section-title">
                        <h3>Personal Information</h3>
                        <button class="edit-button" data-bs-toggle="modal" data-bs-target="#editPersonalInfoModal">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                    </div>
                    
                    <div class="row info-row">
                        <div class="col-md-6">
                            <div class="info-label">First Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['firstname']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Last Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['lastname']); ?></div>
                        </div>
                    </div>
                    
                    <div class="row info-row">
                        <div class="col-md-6">
                            <div class="info-label">Email address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo $phone ? htmlspecialchars($phone) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div>
                        </div>
                    </div>
                    
                    <div class="row info-row mb-0">
                        <div class="col-md-6">
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo $user['username'] ? htmlspecialchars($user['username']) : '<span class="text-muted fst-italic">Not set</span>'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Bio</div>
                            <div class="info-value"><?php echo $bio ? htmlspecialchars($bio) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Address Card -->
                <div class="profile-card">
                    <div class="section-title">
                        <h3>Address</h3>
                        <button class="edit-button" data-bs-toggle="modal" data-bs-target="#editAddressModal">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                    </div>
                    
                    <div class="row info-row">
                        <div class="col-md-6">
                            <div class="info-label">Country</div>
                            <div class="info-value"><?php echo $country ? htmlspecialchars($country) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">City/State</div>
                            <div class="info-value"><?php echo $city_state ? htmlspecialchars($city_state) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div>
                        </div>
                    </div>
                    
                    <div class="row info-row mb-0">
                        <div class="col-md-6">
                            <div class="info-label">Postal Code</div>
                            <div class="info-value"><?php echo $postal_code ? htmlspecialchars($postal_code) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">TAX ID</div>
                            <div class="info-value"><?php echo $tax_id ? htmlspecialchars($tax_id) : '<span class="text-muted fst-italic">Not provided</span>'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Picture Modal -->
    <div class="modal fade" id="uploadPictureModal" tabindex="-1" aria-labelledby="uploadPictureModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadPictureModalLabel">Upload Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="account.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?php if (isset($uploadError)): ?>
                            <div class="alert alert-danger"><?php echo $uploadError; ?></div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Select Image</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" required>
                            <div class="form-text">Supported formats: JPG, JPEG, PNG, GIF</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_picture" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Personal Information Modal -->
    <div class="modal fade" id="editPersonalInfoModal" tabindex="-1" aria-labelledby="editPersonalInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPersonalInfoModalLabel">Edit Personal Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="account.php" method="post">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $user['firstname']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middlename" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middlename" name="middlename" value="<?php echo $user['middlename']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $user['lastname']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo $bio; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_personal_info" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAddressModalLabel">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="account.php" method="post">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="<?php echo $country; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="city_state" class="form-label">City/State</label>
                                <input type="text" class="form-control" id="city_state" name="city_state" value="<?php echo $city_state; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo $postal_code; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="tax_id" class="form-label">TAX ID</label>
                                <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?php echo $tax_id; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_address" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global BookWagon Footer -->
    <?php include("include/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script>
        // Do not initialize modals programmatically - use data attributes instead
        // Add event listener for edit picture button to avoid the backdrop error
        document.addEventListener('DOMContentLoaded', function() {
            var editPictureBtn = document.querySelector('.edit-picture');
            if (editPictureBtn) {
                editPictureBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var modalId = this.getAttribute('data-bs-target');
                    var myModal = document.querySelector(modalId);
                    if (myModal) {
                        myModal.classList.add('show');
                        myModal.style.display = 'block';
                        document.body.classList.add('modal-open');
                        
                        // Create backdrop manually
                        var backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                        
                        // Add close functionality to close buttons
                        var closeButtons = myModal.querySelectorAll('[data-bs-dismiss="modal"]');
                        closeButtons.forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                myModal.classList.remove('show');
                                myModal.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                backdrop.remove();
                            });
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>