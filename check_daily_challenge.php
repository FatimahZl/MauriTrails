<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];

$query = "SELECT * FROM user_challenges 
          WHERE user_id = $user_id 
          AND status = 'active' 
          AND DATE(created_at) = CURDATE()";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $challenge = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'has_challenge' => true,
        'challenge' => $challenge
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_challenge' => false
    ]);
}

mysqli_close($conn);
?>