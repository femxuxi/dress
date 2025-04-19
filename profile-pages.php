<?php
// profile.php - Generic profile page that redirects to the appropriate profile page based on user type
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_type = get_user_type();

switch ($user_type) {
    case 'admin':
        redirect('admin/profile.php');
        break;
    case 'designer':
        redirect('designer/profile.php');
        break;
    case 'customer':
        redirect('customer/profile.php');
        break;
    default:
        redirect('login.php');
}
?>

<?php
// customer/profile.php
require_once '../config.php';
check_access(['customer']);

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get customer profile data
$stmt = $conn->prepare("
    SELECT cp.*, u.email 
    FROM customer_profiles cp
    JOIN users u ON cp.user_id = u.user_id
    WHERE cp.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $wedding_date = !empty($_POST['wedding_date']) ? $_POST['wedding_date'] : NULL;
    
    // Begin transaction
    $conn->begin_transaction();
    try {
        // Handle profile image upload if a new image is provided
        $profile_image = $profile['profile_image']; // Keep existing image by default
        if (!empty($_FILES["profile_image"]["name"])) {
            $upload_result = upload_image($_FILES["profile_image"], "../uploads/profiles/");
            if ($upload_result["status"]) {
                $profile_image = $upload_result["filename"];
            } else {
                throw new Exception($upload_result["message"]);
            }
        }
        
        // Update profile information
        $stmt = $conn->prepare("
            UPDATE customer_profiles 
            SET first_name = ?, last_name = ?, phone = ?, address = ?, wedding_date = ?, profile_image = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $address, $wedding_date, $profile_image, $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Update session and refresh page
        $success_message = "Profile updated successfully!";
        
        // Refresh profile data
        $stmt = $conn->prepare("
            SELECT cp.*, u.email 
            FROM customer_profiles cp
            JOIN users u ON cp.user_id = u.user_id
            WHERE cp.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Dress Maria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include '../includes/customer_header.php'; ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/customer_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">My Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <?php if (!empty($profile['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars('../' . $profile['profile_image']); ?>" class="img-fluid rounded-circle mb-3" alt="Profile Image" style="max-width: 150px; height: auto;">
                                <?php else: ?>
                                    <img src="../uploads/default-profile.jpg" class="img-fluid rounded-circle mb-3" alt="Default Profile" style="max-width: 150px; height: auto;">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h4><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h4>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($profile['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <form action="profile.php" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name*</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name*</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number*</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($profile['address']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="wedding_date" class="form-label">Wedding Date</label>
                                <input type="date" class="form-control" id="wedding_date" name="wedding_date" value="<?php echo htmlspecialchars($profile['wedding_date']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image">
                                <div class="form-text">Leave empty to keep current image</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// designer/profile.php
require_once '../config.php';
check_access(['designer']);

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get designer profile data
$stmt = $conn->prepare("
    SELECT dp.*, u.email 
    FROM designer_profiles dp
    JOIN users u ON dp.user_id = u.user_id
    WHERE dp.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $phone = sanitize_input($_POST['phone']);
    $specialization = sanitize_input($_POST['specialization']);
    $experience_years = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : NULL;
    $bio = sanitize_input($_POST['bio']);
    
    // Begin transaction
    $conn->begin_transaction();
    try {
        // Handle profile image upload if a new image is provided
        $profile_image = $profile['profile_image']; // Keep existing image by default
        if (!empty($_FILES["profile_image"]["name"])) {
            $upload_result = upload_image($_FILES["profile_image"], "../uploads/profiles/");
            if ($upload_result["status"]) {
                $profile_image = $upload_result["filename"];
            } else {
                throw new Exception($upload_result["message"]);
            }
        }
        
        // Update profile information
        $stmt = $conn->prepare("
            UPDATE designer_profiles 
            SET first_name = ?, last_name = ?, phone = ?, specialization = ?, experience_years = ?, bio = ?, profile_image = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ssssissi", $first_name, $last_name, $phone, $specialization, $experience_years, $bio, $profile_image, $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Update session and refresh page
        $success_message = "Profile updated successfully!";
        
        // Refresh profile data
        $stmt = $conn->prepare("
            SELECT dp.*, u.email 
            FROM designer_profiles dp
            JOIN users u ON dp.user_id = u.user_id
            WHERE dp.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Dress Maria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include '../includes/designer_header.php'; ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/designer_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">My Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <?php if (!empty($profile['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars('../' . $profile['profile_image']); ?>" class="img-fluid rounded-circle mb-3" alt="Profile Image" style="max-width: 150px; height: auto;">
                                <?php else: ?>
                                    <img src="../uploads/default-profile.jpg" class="img-fluid rounded-circle mb-3" alt="Default Profile" style="max-width: 150px; height: auto;">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h4><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h4>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($profile['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <form action="profile.php" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name*</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name*</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number*</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="specialization" class="form-label">Specialization</label>
                                <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($profile['specialization']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="experience_years" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control" id="experience_years" name="experience_years" min="0" value="<?php echo htmlspecialchars($profile['experience_years']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image">
                                <div class="form-text">Leave empty to keep current image</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// admin/profile.php
require_once '../config.php';
check_access(['admin']);

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get admin profile data
$stmt = $conn->prepare("
    SELECT ap.*, u.email 
    FROM admin_profiles ap
    JOIN users u ON ap.user_id = u.user_id
    WHERE ap.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $phone = sanitize_input($_POST['phone']);
    
    // Update profile information
    $stmt = $conn->prepare("
        UPDATE admin_profiles 
        SET first_name = ?, last_name = ?, phone = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
        
        // Refresh profile data
        $stmt = $conn->prepare("
            SELECT ap.*, u.email 
            FROM admin_profiles ap
            JOIN users u ON ap.user_id = u.user_id
            WHERE ap.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
    } else {
        $error_message = "Update failed: " . $conn->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Dress Maria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <?php include '../includes/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Admin Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h4>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                <p><strong>Role:</strong> Administrator</p>
                            </div>
                        </div>
                        
                        <form action="profile.php" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name*</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name*</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
