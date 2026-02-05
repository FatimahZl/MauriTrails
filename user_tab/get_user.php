<?php
session_start();
require_once "../config.php"; 


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


if (!isset($_GET['user_id'])) {
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

$user_id = intval($_GET['user_id']);


$sql = "SELECT user_id, name, email, role, status FROM users WHERE user_id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_bind_result($stmt, $id, $name, $email, $role, $status);
        
        if (mysqli_stmt_fetch($stmt)) {

            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'status' => $status
                ]
            ]);
        } else {
            echo json_encode(['error' => 'User not found']);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'Database error']);
}

mysqli_close($conn);
?>