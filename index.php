<?php
// config.php - Database connection
$servername = "localhost";
$username = "u659181579_dbdressmaria";
$password = "Dressmaria.123";
$dbname = "u659181579_dressmaria";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_type() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

function check_access($allowed_types) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    $user_type = get_user_type();
    if (!in_array($user_type, $allowed_types)) {
        redirect('access_denied.php');
    }
}

function get_user_profile_data($conn, $user_id, $user_type) {
    $table = '';
    $id_field = '';
    
    switch($user_type) {
        case 'admin':
            $table = 'admin_profiles';
            $id_field = 'admin_id';
            break;
        case 'designer':
            $table = 'designer_profiles';
            $id_field = 'designer_id';
            break;
        case 'customer':
            $table = 'customer_profiles';
            $id_field = 'customer_id';
            break;
        default:
            return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    
    return $profile;
}

// File upload helper
function upload_image($file, $target_dir = "uploads/") {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["status" => false, "message" => "File is not an image."];
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return ["status" => false, "message" => "File is too large."];
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return ["status" => false, "message" => "Only JPG, JPEG, PNG & GIF files are allowed."];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["status" => true, "filename" => $target_file];
    } else {
        return ["status" => false, "message" => "There was an error uploading your file."];
    }
}
?>
