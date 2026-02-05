<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"])) {
    echo "error: Unauthorized";
    exit;
}

$place_id = $_POST['place_id'] ?? '';
$name = $_POST['name'];
$description = $_POST['description'];
$history = $_POST['history'] ?? '';
$category = $_POST['category'];
$address = $_POST['address'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$visiting_hours = $_POST['visiting_hours'];
$entry_fee = $_POST['entry_fee'];
$email = $_POST['email'];
$website = $_POST['website_link'];
$status = $_POST['status'];

// Handle image upload
$main_image = '';
if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
    $target_dir = "../uploads/images/";
    $image_name = time() . '_' . basename($_FILES["main_image"]["name"]);
    $target_file = $target_dir . $image_name;
    
    if (move_uploaded_file($_FILES["main_image"]["tmp_name"], $target_file)) {
        $main_image = $image_name;
    }
}

// Update or Insert
if (!empty($place_id)) {
    // UPDATE existing place
    if (!empty($main_image)) {
        // Update with new image
        $sql = "UPDATE places SET name=?, description=?, history=?, category=?, address=?, 
                latitude=?, longitude=?, visiting_hours=?, entry_fee=?, email=?, website=?, 
                status=?, main_image=?, updated_at=NOW() WHERE place_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssddssssssi", $name, $description, $history, 
                               $category, $address, $latitude, $longitude, $visiting_hours, 
                               $entry_fee, $email, $website, $status, $main_image, $place_id);
    } else {
        // Update without changing image
        $sql = "UPDATE places SET name=?, description=?, history=?, category=?, address=?, 
                latitude=?, longitude=?, visiting_hours=?, entry_fee=?, email=?, website=?, 
                status=?, updated_at=NOW() WHERE place_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssssi", $name, $description, $history, 
                               $category, $address, $latitude, $longitude, $visiting_hours, 
                               $entry_fee, $email, $website, $status, $place_id);
    }
} else {
    // INSERT new place
    $sql = "INSERT INTO places (name, description, history, category, address, latitude, 
            longitude, visiting_hours, entry_fee, email, website, status, main_image, 
            created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssddssssss", $name, $description, $history, $category, 
                           $address, $latitude, $longitude, $visiting_hours, $entry_fee, 
                           $email, $website, $status, $main_image);
}

if (mysqli_stmt_execute($stmt)) {
    echo "success";
} else {
    echo "error: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>