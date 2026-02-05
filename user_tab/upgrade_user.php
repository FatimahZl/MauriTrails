<?php 
session_start();
require_once("../config.php");

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin"){
  header("location:../index.php");
  exit;
}

if(!isset($_GET["user_id"])){
  header("location:../admin_dashboard.php?error=missing_user_id");
  exit;
} 

$user_id = $_GET["user_id"];

$query = "UPDATE users SET role = 'admin' WHERE user_id = ?";
if($stmt=mysqli_prepare($conn,$query)){
  mysqli_stmt_bind_param($stmt,"i",$user_id);

  if(mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("location:../admin_dashboard.php?success=user_upgraded");
    exit;
  } else {
    mysqli_stmt_close($stmt);
    header("location:../admin_dashboard.php?error=database_error");
    exit;
  }
  }else{
    header("location:../admin_dashboard.php?error=database_error");
    exit;
  }
  
  mysqli_close($conn);
?>