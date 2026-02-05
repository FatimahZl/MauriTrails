<?php
session_start();
require_once("../config.php");

if(!isset($_SESSION["user_id"]) ||  $_SESSION["role"] !== "admin"){
  header("location:../admin_dashboard.php");
  exit;
}

if (!isset($_GET['user_id']) || !isset($_GET['action'])) {
    header("location:../admin_dashboard.php?error=missing_parameters");
    exit;
}

$user_id = $_GET["user_id"];
$action = $_GET["action"];

if($user_id == $_SESSION["user_id"]){
    header("location:../admin_dashboard.php?error=cannot_block_self");
    exit;
}

if($action !=='block' && $action !== 'unblock'){
    header("location:../admin_dashboard.php?error=invalid_action");
    exit;
}

$new_status = ($action === 'block') ? 'blocked' : 'active';

$query = "UPDATE users SET status = ? WHERE user_id = ?";

if($stmt = mysqli_prepare($conn, $query)){
  mysqli_stmt_bind_param($stmt,"si", $new_status, $user_id);

  if(mysqli_stmt_execute($stmt)){
    mysqli_stmt_close($stmt);
    header("location: admin_dashboard.php?success=user_". $action ."ed");
    exit;
    } else {
        mysqli_stmt_close($stmt);
        header("location: admin_dashboard.php?error=database_error");
        exit;
    }
} else {
    header("location: ../admin_dashboard.php?error=database_error");
    exit;
}

mysqli_close($conn);    
?>
