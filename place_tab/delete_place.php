<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("location: ../dashboard.php");
    exit;
}

if (!isset($_GET['place_id'])) {
    header("location: ../dashboard.php");
    exit;
}

$place_id = $_GET['place_id'];

// Get the image filename before deleting
$sql = "SELECT main_image FROM places WHERE place_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $place_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$place = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Delete the place from database
$sql = "DELETE FROM places WHERE place_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $place_id);

if (mysqli_stmt_execute($stmt)) {
    // Optionally delete the image file
    if ($place && $place['main_image']) {
        $image_path = "../uploads/images/" . $place['main_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    header("location: ../dashboard.php?msg=deleted");
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    header("location: ../dashboard.php?error=failed");
}
exit;
?>