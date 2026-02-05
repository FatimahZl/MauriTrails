<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

// Check if place_id is provided
if (!isset($_GET['place_id'])) {
    echo json_encode(['success' => false, 'message' => 'Place ID not provided']);
    exit;
}

$place_id = intval($_GET['place_id']);

// Query the database
$sql = "SELECT * FROM places WHERE place_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $place_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($place = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'place' => $place]);
} else {
    echo json_encode(['success' => false, 'message' => 'Place not found with ID: ' . $place_id]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>