<?php
session_start();
require_once("../config.php");

if(!isset($_SESSION["user_id"]) ||  $_SESSION["role"] !== "admin"){
  header("location:../admin_dashboard.php");
  exit;
}

if(!isset($_GET['user_id'])){
  header("location:../admin_dashboard.php?error=missing_id");
  exit;
}

$user_id = $_GET["user_id"];

if($user_id == $_SESSION["user_id"]){
    header("location:../admin_dashboard.php?error=cannot_delete_self");
    exit;
}

$query="DELETE FROM users WHERE user_id = ?";

if($stmt = mysqli_prepare($conn,$query)){
  mysqli_stmt_bind_param($stmt,"i", $user_id);

  if(mysqli_stmt_execute($stmt)){
    mysqli_stmt_close($stmt);
    header("location:../admin_dashboard.php?success=user_deleted");
    exit;

  } else {
    mysqli_stmt_close($stmt);
    header("location:../admin_dashboard.php?error=database_error");
    exit;
  }
} else {
    header("location: ../admin_dashboard.php?error=prepare_failed ");
    exit;
}

mysqli_close($conn);
?>