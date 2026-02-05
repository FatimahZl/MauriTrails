<?php
session_start();
require_once "../config.php";

header('Content-Type: application/json');


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}


$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$role = $_POST['role'] ?? 'admin'; 
$status = $_POST['status'];


if (empty($name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Name and Email are required']);
    exit;
}


$check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    exit;
}
mysqli_stmt_close($stmt);


if ($user_id > 0) {

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name=?, email=?, password=?, role=?, status=? WHERE user_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $hashed, $role, $status, $user_id);
    } else {
        $sql = "UPDATE users SET name=?, email=?, role=?, status=? WHERE user_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $role, $status, $user_id);
    }
} else {

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed, $role, $status);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database failure']);
}
exit;