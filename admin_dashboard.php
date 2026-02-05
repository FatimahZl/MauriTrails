<?php
session_start();
require_once "config.php"; 


if (!isset($_SESSION["user_id"])) {
  header("location: index.php");
  exit;
}

$user_id = $_SESSION["user_id"];
$display_name = "User";


$sql = "SELECT name FROM users WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
  mysqli_stmt_bind_param($stmt, "i", $user_id);
  if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_bind_result($stmt, $name_from_db);
    if (mysqli_stmt_fetch($stmt)) {
      $display_name = $name_from_db;
    }
  }
  mysqli_stmt_close($stmt);
}

$total_users = 0;
$active_users = 0;
$blocked_users = 0;
$admin_users = 0;

$query = "SELECT COUNT(*) AS total FROM users";
$users_result = mysqli_query($conn, $query);
if ($users_result) {
  $row = mysqli_fetch_assoc($users_result);
  $total_users = $row['total'];
  mysqli_free_result($users_result);
}

$query = "SELECT COUNT(*) AS active FROM users WHERE status ='active'";
$users_result = mysqli_query($conn, $query);
if ($users_result) {
  $row = mysqli_fetch_assoc($users_result);
  $active_users = $row['active'];
  mysqli_free_result($users_result);
}

$query = "SELECT COUNT(*) AS blocked FROM users WHERE status ='blocked'";
$users_result = mysqli_query($conn, $query);
if ($users_result) {
  $row = mysqli_fetch_assoc($users_result);
  $blocked_users = $row['blocked'];
  mysqli_free_result($users_result);
}

$query = "SELECT COUNT(*) AS admins FROM users WHERE role ='admin'";
$users_result = mysqli_query($conn, $query);
if ($users_result) {
  $row = mysqli_fetch_assoc($users_result);
  $admin_users = $row['admins'];
  mysqli_free_result($users_result);
}

$search_term = "";
if (isset($_GET['search'])) {
  $search_term = mysqli_real_escape_string($conn, $_GET['search']);
}

$query = "SELECT * FROM places";
if (!empty($search_term)) {
  $query .= " WHERE name LIKE '%$search_term%' OR description LIKE '%$search_term%'";
}

$places_result = mysqli_query($conn, $query);


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MauriTrails Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,700,1,0" />
  <link
    href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
    rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/akar-icons-fonts@1.1.22/src/css/akar-icons.css">

  <style>
   * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      background: linear-gradient(45deg, #0b3842, #AA895F);
      color: #fdfdfd;
      min-height: 100vh;
      display: flex;
      place-items: center;
      overflow-x: hidden;
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    :root {
      --color-bg: #2d5760;
      --color-primary: #121926;
      --color-muted: #9fa4af;
      --color-hover: #e8ecf4;
    }
    p,
    h2 {
      margin: 0;
    }

    .header {
      background: #2d5760;
    }

    .logo-image{
      border-radius:15px;
    }

    .logo-title p {
      font-size: 30px;
      font-weight: 600;
      color: #fff;
    }

    .logo-title h2 {
      font-size: 25px;
      font-weight: 600;
      color: #fff;
    }

    .sidebar {
      position: fixed;
      overflow: hidden;
      top: 24px;
      left: 24px;
      bottom: 28px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 80px;
      border-radius: 18px;
      transition: 0.4s;
      background: #2d5760;
    }

    .sidebar button {
      border: 0;
      background: transparent;
      font-size: 22px;
      color: inherit;
      border-radius: 8px;
      font-family: inherit;
      cursor: pointer;
      transition: 0.3s;
    }

    .left,
    .right {
      position: absolute;
      top: 0;
      bottom: 0;
      transition: 0.4s;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .left {
      z-index: 1;
      left: 0;
      width: 80px;
      background: #2d5760;
    }

    .left a {
      text-decoration: none;
      display: contents;
    }

    .left img {
      width: 40px;
      margin: 24px 0 28px;
    }

    .left button {
      width: 44px;
      height: 44px;
      display: grid;
      place-items: center;
      color: #fff;
    }

    .left button:hover {
      background: var(--color-bg);
      color: #384251;
    }

    .left div:last-of-type {
      margin-top: auto;
      margin-bottom: 20px;
    }

    .right {
      left: 76px;
      height: 100%;
      position: relative;
    }

    .right-inner {
      position: absolute;
      inset: 8px;
      left: 4px;
      border-radius: 12px;
      background: var(--color-bg);
    }

    .right .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 24px 16px;
    }

    .right h2 {
      font-size: 16px;
      font-weight: 600;
    }

    .right h3 {
      font-size: 12px;
      font-weight: 500;
      color: var(--color-muted);
    }

    .right nav {
      padding: 0 12px;
    }

    .right button {
      padding: 0 12px;
      background: transparent;
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      height: 44px;
      font-size: 14px;
      color: #fff
    }

    .right button i:last-child {
      opacity: 0;
      font-size: 16px;
      margin-left: auto;
      transition: 0.3s;
    }

    .right button:hover {
      background: var(--color-hover);
      color: #384251;
    }

    .right button:hover i:last-child {
      opacity: 1;
      color: var(--color-muted);
    }

    .right button i {
      font-size: 18px;
    }

    .sidebar:hover {
      width: 300px;
    }

    .sidebar:hover .right {
      width: 225px;
    }

    nav a {
      text-decoration: none;
      color: inherit;
    }

    .submenu {
      position: relative;
      list-style: none;
      margin: 0;
      padding: 8px 0 8px 29px;
      font-size: 14px;
      cursor: pointer;
    }

    .submenu::before {
      content: "";
      position: absolute;
      top: 8px;
      left: 21px;
      bottom: 8px;
      width: 1px;
      background: var(--color-muted);
      opacity: 0.33;
    }

    .submenu li {
      white-space: nowrap;
      height: 36px;
      padding-left: 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: 0.3s;
    }

    .submenu li:hover {
      background: var(--color-hover);
      color: #384251;
      cursor: pointer;
    }

    .submenu .badge {
      font-size: 10px;
      padding: 3px 5px;
      border-radius: 4px;
      background: var(--color-primary);
      color: #ffffff;
      margin-right: 12px;
    }


    .main-content {
      margin-left: 104px;
      transition: margin-left 0.4s ease, width 0.4s ease;
      padding: 24px;
      width: calc(100% - 104px);
    }

    .main-content .btn {
      color: inherit;
      border-color: inherit;
    }

    .main-content .btn:hover {
      background: var(--color-hover);
      color: #384251;
      border-color: var(--color-hover);
    }


    .sidebar:hover~.main-content {
      margin-left: 324px;
      width: calc(100% - 324px);
    }

    .container-fluid {
      padding-left: 0 !important;
      margin-left: 0 !important;
    }


    /* Tabs */
    .tab {
      animation: fadeIn 0.3s ease;
    }

    .tab.hidden {
      display: none;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card {
      position: relative;
      overflow: hidden;
      width: 660px;
      height: 440px;
      border-radius: 16px;
      background: #2d5760;
      border: 8px solid #2d5760;
    }

    .card-bg {
      position: absolute;
      z-index: 2;
      top: 0;
      left: 0;
      bottom: 0;
      width: 50%;
      background: #0b3842;
      border-radius: 12px;
      translate: 0 0;
      transition: 0.65s ease-in-out;
    }

    .dashboard-container {
  padding: 20px 0;
  animation: fadeInDashboard 0.6s ease-out;
}

@keyframes fadeInDashboard {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Welcome Section */
.welcome-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  padding: 30px;
  background: linear-gradient(135deg, rgba(170, 137, 95, 0.15), rgba(45, 87, 96, 0.15));
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.dashboard-title {
  font-size: 36px;
  font-weight: 700;
  margin: 0;
  color: #fff;
  letter-spacing: -0.5px;
}

.username-highlight {
  background: linear-gradient(120deg, #AA895F, #FFD700);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.dashboard-subtitle {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.7);
  margin: 8px 0 0 0;
}

.dashboard-date {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  color: #AA895F;
  font-weight: 600;
  padding: 12px 20px;
  background: rgba(170, 137, 95, 0.2);
  border-radius: 12px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}

.stat-card {
  position: relative;
  padding: 28px;
  border-radius: 18px;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: inherit;
  opacity: 0.9;
  transition: opacity 0.3s;
  z-index: 0;
}

.stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.stat-card:hover::before {
  opacity: 1;
}

.stat-gradient-1 {
  background: linear-gradient(135deg, #0b3842, #1a5560);
}

.stat-gradient-2 {
  background: linear-gradient(135deg, #316975, #174a64);
}



.stat-gradient-4 {
  background: linear-gradient(135deg, #1a4d57, #0b3842);
}

.stat-icon {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 48px;
  opacity: 0.15;
  z-index: 1;
}

.stat-info {
  position: relative;
  z-index: 2;
}

.stat-number {
  font-size: 42px;
  font-weight: 800;
  margin: 0 0 8px 0;
  color: #fff;
  font-family: 'Poppins', sans-serif;
  letter-spacing: -1px;
}

.stat-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.stat-trend {
  position: absolute;
  bottom: 20px;
  right: 20px;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  z-index: 2;
}

.stat-trend.positive {
  background: rgba(76, 175, 80, 0.2);
  color: #4CAF50;
}

.stat-trend.negative {
  background: rgba(244, 67, 54, 0.2);
  color: #F44336;
}

/* Dashboard Content Grid */
.dashboard-content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
}

.dashboard-card {
  background: rgba(45, 87, 96, 0.6);
  border-radius: 18px;
  padding: 28px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.dashboard-card:hover {
  border-color: rgba(170, 137, 95, 0.3);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.card-header-dashboard {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.card-header-dashboard h3 {
  font-size: 20px;
  font-weight: 600;
  margin: 0;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 10px;
}

.view-all-btn {
  padding: 8px 16px;
  background: rgba(170, 137, 95, 0.2);
  color: #AA895F;
  border: 1px solid rgba(170, 137, 95, 0.3);
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.view-all-btn:hover {
  background: rgba(170, 137, 95, 0.3);
  border-color: #AA895F;
  transform: translateX(4px);
}



/* Activity Card */
.activity-list {
  max-height: 400px;
  overflow-y: auto;
}

.activity-list::-webkit-scrollbar {
  width: 6px;
}

.activity-list::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}

.activity-list::-webkit-scrollbar-thumb {
  background: rgba(170, 137, 95, 0.5);
  border-radius: 10px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  margin-bottom: 12px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  transition: all 0.3s;
  border: 1px solid transparent;
}

.activity-item:hover {
  background: rgba(170, 137, 95, 0.1);
  border-color: rgba(170, 137, 95, 0.3);
  transform: translateX(4px);
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #AA895F, #d4a574);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.activity-details {
  flex: 1;
}

.activity-title {
  margin: 0 0 4px 0;
  color: #fff;
  font-size: 14px;
  font-weight: 500;
}

.activity-title strong {
  color: #AA895F;
}

.activity-time {
  margin: 0;
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
}

.no-activity {
  text-align: center;
  padding: 60px 20px;
  color: rgba(255, 255, 255, 0.4);
}

.no-activity i {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.3;
}

.no-activity p {
  margin: 0;
  font-size: 14px;
}


/* Quick Actions */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.quick-action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.quick-action-btn:hover {
  background: rgba(170, 137, 95, 0.2);
  border-color: rgba(170, 137, 95, 0.5);
  transform: translateY(-4px);
}

.quick-action-btn i {
  font-size: 32px;
  color: #AA895F;
}

/* Responsive */
@media (max-width: 1200px) {
  .chart-card {
    grid-column: span 1;
  }
}

@media (max-width: 768px) {
  .welcome-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }
  
  .dashboard-title {
    font-size: 28px;
  }
  
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  
  .dashboard-content-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
}

    /* USER TAB */
    .user-card {
      padding: 20px;
      height: 180px;
      border: 8px solid #2d5760;
      border-radius: 16px;
      background-color: #2d5760;
    }

    .user-card-body {
      background-color: #2d5760;
    }

    /* PLACE TAB */
    .map-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(4px);
      z-index: 1060;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .map-modal.show {
      display: flex;
    }

    .map-container {
      background-color: #2d5760;
      border-radius: 16px;
      padding: 25px;
      width: 100%;
      max-width: 900px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .map-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .map-header h3 {
      margin: 0;
      color: #ffffff;
      font-size: 20px;
    }

    #map {
      width: 100%;
      height: 450px;
      border-radius: 12px;
      margin-bottom: 15px;
    }

    .map-info {
      padding: 15px;
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 14px;
      color: #ffffff;
    }

    .map-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    .map-actions button {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }


    .btn-confirm {
      background: 1e293b;
      color: #1e293b;
    }


    .btn-cancel {
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
    }

    .map-actions button:hover {
      transform: translateY(-2px);
    }

    /* QR Code Modal */
    .qr-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(5px);
      z-index: 2000;
      justify-content: center;
      align-items: center;
    }

    .qr-modal.show {
      display: flex;
    }

    .qr-content {
      background: #2d5760;
      border-radius: 20px;
      padding: 40px;
      text-align: center;
      max-width: 500px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }

    #qrCodeDisplay {
      background: #2d5760;
      padding: 20px;
      border-radius: 15px;
      display: inline-block;
      margin: 20px 0;
    }

    .qr-buttons {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 20px;
    }

    @media (max-width: 768px) {
  /* Hide sidebar on mobile */
  .sidebar {
    display: none;
  }

  /* Show mobile top navbar */
  .mobile-nav {
    display: block !important;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #2d5760;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  }

  /* Adjust main content for mobile */
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding-top: 80px; /* Space for fixed navbar */
    padding-left: 16px;
    padding-right: 16px;
  }

  /* Mobile navbar header */
  .mobile-nav-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .mobile-nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .mobile-nav-logo img {
    width: 35px;
    height: 35px;
    border-radius: 8px;
  }

  .mobile-nav-title {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    margin: 0;
  }

  /* Hamburger menu button */
  .mobile-menu-toggle {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    transition: background 0.3s;
  }

  .mobile-menu-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
  }

  /* Mobile navigation menu */
  .mobile-nav-menu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: #2d5760;
  }

  .mobile-nav-menu.active {
    max-height: 500px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }

  .mobile-nav-items {
    padding: 10px 0;
  }

  .mobile-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 20px;
    color: #fff;
    text-decoration: none;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    font-size: 16px;
    transition: background 0.3s;
    cursor: pointer;
  }

  .mobile-nav-item:hover,
  .mobile-nav-item:active {
    background: rgba(255, 255, 255, 0.1);
  }

  .mobile-nav-item i {
    font-size: 20px;
    width: 24px;
    text-align: center;
  }

  .mobile-nav-item .material-symbols-outlined {
    font-size: 24px;
  }

  .mobile-nav-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 10px 20px;
  }

  .mobile-user-info {
    padding: 15px 20px;
    background: rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
  }

  .mobile-user-info i {
    font-size: 18px;
  }

  .mobile-user-name {
    font-size: 14px;
    font-weight: 500;
  }

  /* Responsive adjustments for dashboard */
  .welcome-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
  }

  .dashboard-title {
    font-size: 24px !important;
  }

  .dashboard-date {
    font-size: 14px;
    padding: 8px 12px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .stat-card {
    padding: 20px;
  }

  .stat-number {
    font-size: 32px !important;
  }

  .dashboard-content-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }

  /* Responsive tables */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .table {
    min-width: 600px;
  }

  /* Responsive modals */
  .modal-dialog {
    margin: 10px;
  }

  .modal-lg {
    max-width: calc(100% - 20px);
  }

  /* User cards in mobile */
  .user-card {
    height: auto;
    min-height: 120px;
  }
}

/* Desktop - hide mobile nav */
@media (min-width: 769px) {
  .mobile-nav {
    display: none !important;
  }
}

/* Tablet adjustments */
@media (max-width: 1024px) and (min-width: 769px) {
  .sidebar {
    width: 70px;
  }

  .sidebar:hover {
    width: 260px;
  }

  .main-content {
    margin-left: 94px;
    width: calc(100% - 94px);
  }

  .sidebar:hover ~ .main-content {
    margin-left: 284px;
    width: calc(100% - 284px);
  }
}

/* Small mobile devices */
@media (max-width: 480px) {
  .main-content {
    padding-top: 70px;
    padding-left: 12px;
    padding-right: 12px;
  }

  .dashboard-title {
    font-size: 20px !important;
  }

  .stat-number {
    font-size: 28px !important;
  }

  .btn {
    padding: 8px 12px;
    font-size: 14px;
  }

  h1 {
    font-size: 24px;
  }

  .card {
    padding: 15px !important;
  }
}









  </style>
</head>

<body>
  <nav class="mobile-nav" style="display: none;">
  <div class="mobile-nav-header">
    <div class="mobile-nav-logo">
      <img src="uploads/images/logo.png" alt="MauriTrails Logo">
      <h1 class="mobile-nav-title">MauriTrails</h1>
    </div>
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
      <i class="fas fa-bars"></i>
    </button>
  </div>
  
  <div class="mobile-nav-menu" id="mobileNavMenu">
    <div class="mobile-user-info">
      <i class="ai-person"></i>
      <span class="mobile-user-name"><?php echo htmlspecialchars($display_name); ?></span>
    </div>
    
    <div class="mobile-nav-divider"></div>
    
    <div class="mobile-nav-items">
      <button class="mobile-nav-item" onclick="switchTab('home'); closeMobileMenu();">
        <i class="ai-dashboard"></i>
        <span>Dashboard</span>
      </button>
      
      <button class="mobile-nav-item" onclick="switchTab('place'); closeMobileMenu();">
        <i class="ai-location"></i>
        <span>Places</span>
      </button>
      

      <button class="mobile-nav-item" onclick="switchTab('users'); closeMobileMenu();">
        <i class="ai-person"></i>
        <span>Users</span>
      </button>
      
      <div class="mobile-nav-divider"></div>
      
      <a href="logout.php" class="mobile-nav-item">
        <i class="ai-link-out"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>
</nav>
  <aside class="sidebar">
    <div class="left">
      <img class="logo-image" src="uploads/images/logo.png" />
      <div>
        <a href="logout.php" title="Logout">
          <button>
            <i class="ai-link-out"></i>
          </button>
        </a>
      </div>
    </div>
    <div class="right">
      <div class="right-inner">
        <div class="header">
          <div class="logo-title">
            <p>MauriTrails</p>
            <h2>
              <i class="ai-person" style="font-size: 12px;"></i>
              <?php echo htmlspecialchars($display_name);  ?>
            </h2>
          </div>
        </div>
        <nav>
          <button class="tab-text" onclick="switchTab('home')">
            <i class="ai-dashboard"></i>
            <span>Dashboard</span>
          </button>
          <button class="tab-text" onclick="switchTab('place')">
            <i class="ai-location"></i>
            <span>Places</span>
          </button>
          <button class="tab-text" onclick="switchTab('users')">
            <i class="ai-person"></i>
            <span>Users</span>
          </button>
        </nav>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <!--TABS-->
    <!--DASHBOARD-->
    <div class="tab" id="tab-home">
      
  <div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="welcome-section">
      <div class="welcome-content">
        <h1 class="dashboard-title">Welcome back, <span class="username-highlight"><?php echo htmlspecialchars($display_name); ?></span></h1>
        <p class="dashboard-subtitle">Here's what's happening with MauriTrails today</p>
      </div>
      <div class="dashboard-date">
        <i class="ai-calendar"></i>
        <span id="currentDate"></span>
      </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card stat-gradient-1">
        <div class="stat-icon">
          <i class="ai-location"></i>
        </div>
        <div class="stat-info">
          <h3 class="stat-number" data-target="<?php echo mysqli_num_rows(mysqli_query($conn, 'SELECT * FROM places')); ?>">0</h3>
          <p class="stat-label">Total Places</p>
        </div>

      </div>

      <div class="stat-card stat-gradient-2">
        <div class="stat-icon">
          <i class="ai-person"></i>
        </div>
        <div class="stat-info">
          <h3 class="stat-number" data-target="<?php echo $total_users; ?>">0</h3>
          <p class="stat-label">Total Users</p>
        </div>

      </div>

      
      <div class="stat-card stat-gradient-4">
        <div class="stat-icon">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-info">
          <h3 class="stat-number" data-target="<?php echo $active_users; ?>">0</h3>
          <p class="stat-label">Active Users</p>
        </div>

      </div>
    </div>

    <!-- Charts and Activity Section -->
    <div class="dashboard-content-grid">
      

      <!-- Recent Activity -->
      <div class="dashboard-card activity-card">
        <div class="card-header-dashboard">
          <h3><i class="fas fa-clock"></i> Recent Activity</h3>
        </div>
        <div class="activity-list">
          <?php
          $activity_query = "SELECT name, created_at FROM places ORDER BY created_at DESC LIMIT 5";
          $activity_result = mysqli_query($conn, $activity_query);
          if(mysqli_num_rows($activity_result) > 0):
            while($activity = mysqli_fetch_assoc($activity_result)):
          ?>
          <div class="activity-item">
            <div class="activity-icon">
              <i class="ai-location"></i>
            </div>
            <div class="activity-details">
              <p class="activity-title">New place added: <strong><?php echo htmlspecialchars($activity['name']); ?></strong></p>
              <p class="activity-time"><?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?></p>
            </div>
          </div>
          <?php 
            endwhile;
          else:
          ?>
          <div class="no-activity">
            <i class="fas fa-inbox"></i>
            <p>No recent activity</p>
          </div>
          <?php endif; ?>
        </div>
      </div>

    

      <!-- Quick Actions -->
      <div class="dashboard-card quick-actions-card">
        <div class="card-header-dashboard">
          <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="quick-actions-grid">
          <button class="quick-action-btn" onclick="switchTab('place'); setTimeout(() => document.querySelector('[data-bs-target=\'#placeModal\']').click(), 100)">
            <i class="ai-location"></i>
            <span>Add Place</span>
          </button>
          <button class="quick-action-btn" onclick="switchTab('users'); setTimeout(() => document.querySelector('[data-bs-target=\'#userModal\']').click(), 100)">
            <i class="ai-person"></i>
            <span>Add User</span>
          </button>
          <button class="quick-action-btn" onclick="switchTab('place')">
            <i class="fas fa-list"></i>
            <span>View Places</span>
          </button>
          <button class="quick-action-btn" onclick="switchTab('users')">
            <i class="fas fa-users"></i>
            <span>Manage Users</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
    </div>

    <!--PLACES-->
    <div class="tab hidden" id="tab-place">
      <div class="container-fluid mt-4">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h1><i class="ai-location"></i> Manage Places</h1>
                <p class="text-muted">View and manage all places in Mauritius</p>
              </div>
              <div>
                <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#placeModal" onclick="clearplaceForm()">
                  <i class="fas fa-user-plus"></i> Add New Place
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4" style="height: auto; width: 100%; border: none; padding: 20px;">
          <div class="card-header bg-transparent border-0">
            <h5><i class="fas fa-filter"></i> Filter Places</h5>
          </div>
          <div class="card-body">
            <form action="" method="GET" class="row g-3">
              <div class="col-md-9">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search places by name or description" value="<?php echo htmlspecialchars($search_term); ?>">

              </div>
              <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn d-block w-100">Search</button>
              </div>
            </form>
<div class="table-responsive mt-4">
  <table class="table table-hover" style="color: white;">
    <thead>
      <tr>
        <th>Image</th>
        <th>Name</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($places_result) > 0): ?>
        <?php while ($place = mysqli_fetch_assoc($places_result)): ?>
          <tr>
            <td><img src="uploads/images/<?php echo htmlspecialchars($place['main_image']); ?>" alt=" " style="width: 100px; height: auto;"></td>
            <td><?php echo htmlspecialchars($place['name']); ?></td>
            <td>
              <!-- EDIT BUTTON -->
              <button class="btn btn-outline-primary btn-sm me-1"
                onclick="editPlace(<?php echo $place['place_id']; ?>)"
                title="Edit">
                <i class="fas fa-edit"></i>
              </button>

              <!-- VIEW DETAILS BUTTON -->
              <button class="btn btn-outline-info btn-sm me-1"
                data-bs-toggle="modal"
                data-bs-target="#placeDetailsModal"
                data-place-id="<?php echo htmlspecialchars($place['place_id']); ?>"
                data-name="<?php echo htmlspecialchars($place['name']); ?>"
                data-desc="<?php echo htmlspecialchars($place['description']); ?>"
                data-hist="<?php echo htmlspecialchars($place['history']); ?>"
                data-img="<?php echo htmlspecialchars($place['main_image']); ?>"
                data-category="<?php echo htmlspecialchars($place['category']); ?>"
                data-address="<?php echo htmlspecialchars($place['address']); ?>"
                data-latitude="<?php echo htmlspecialchars($place['latitude']); ?>"
                data-longitude="<?php echo htmlspecialchars($place['longitude']); ?>"
                data-visiting-hours="<?php echo htmlspecialchars($place['visiting_hours']); ?>"
                data-entry-fee="<?php echo htmlspecialchars($place['entry_fee']); ?>"
                data-email="<?php echo htmlspecialchars($place['email']); ?>"
                data-website="<?php echo htmlspecialchars($place['website']); ?>"
                data-status="<?php echo htmlspecialchars($place['status']); ?>"
                data-created-at="<?php echo htmlspecialchars($place['created_at']); ?>"
                data-updated-at="<?php echo htmlspecialchars($place['updated_at']); ?>"
                title="View Details">
                <i class="fas fa-eye"></i>
              </button>

              <!-- DELETE BUTTON -->
              <a href="place_tab/delete_place.php?place_id=<?php echo $place['place_id']; ?>"
                onclick="return confirm('Are you sure you want to delete this place?');"
                class="btn btn-outline-danger btn-sm"
                title="Delete">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="3" class="text-center">No places found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
          </div>
        </div>
      </div>


      <!--PLACE DETAILS MODAL-->
      <div class="modal fade" id="placeDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
          <div class="modal-content" style="background-color: #2d5760; color: white; border-radius: 15px;">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="modalPlaceName">Place Details</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
              <div class="row mb-4">
                <div class="col-12 text-center">
                  <img id="modalImage" src="" alt="Place Image" class="img-fluid rounded shadow" style="max-height: 400px; width: 100%; object-fit: cover;">
                </div>
              </div>

              <div class="details-content p-3 mb-3" style="background: rgba(0,0,0,0.2); border-radius: 10px;">
                <h4 class="mb-3"><i class="ai-location"></i> <span id="modalTitleText"></span></h4>
                <p id="modalDescription" style="line-height: 1.6; opacity: 0.9;"></p>
              </div>

              <div class="details-content p-3 mb-3" style="background: rgba(0,0,0,0.2); border-radius: 10px;">
                <h5 class="mb-3"><i class="fas fa-book"></i> History</h5>
                <p id="modalHistory" style="line-height: 1.6; opacity: 0.9;"></p>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="details-content p-3 h-100" style="background: rgba(0,0,0,0.2); border-radius: 10px;">
                    <h5 class="mb-3"><i class="fas fa-map-marker-alt"></i> Location</h5>
                    <p class="mb-2"><strong>Address:</strong><br><span id="modalAddress"></span></p>
                    <p class="mb-2"><strong>Category:</strong> <span id="modalCategory" class="badge bg-info"></span></p>
                    <p class="mb-0"><strong>Coordinates:</strong><br>
                      Latitude: <span id="modalLatitude"></span><br>
                      Longitude: <span id="modalLongitude"></span>
                    </p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="details-content p-3 h-100" style="background: rgba(0,0,0,0.2); border-radius: 10px;">
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Visit Information</h5>
                    <p class="mb-2"><strong>Visiting Hours:</strong><br><span id="modalVisitingHours"></span></p>
                    <p class="mb-2"><strong>Entry Fee:</strong> <span id="modalEntryFee"></span></p>
                    <p class="mb-0"><strong>Status:</strong> <span id="modalStatus" class="badge"></span></p>
                  </div>
                </div>
              </div>

              <div class="details-content p-3 mb-3" style="background: rgba(0,0,0,0.2); border-radius: 10px;">
                <h5 class="mb-3"><i class="fas fa-envelope"></i> Contact Information</h5>
                <p class="mb-2"><strong>Email:</strong> <a href="#" id="modalEmail" style="color: #90caf9;"></a></p>
                <p class="mb-0"><strong>Website:</strong> <a href="#" id="modalWebsite" target="_blank" style="color: #90caf9;"></a></p>
              </div>

              <div class="details-content p-3" style="background: rgba(0,0,0,0.2); border-radius: 10px;">
                <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Statistics</h5>
                <div class="row">
                  <div class="col-md-4">
                    <p class="mb-2"><strong>Created:</strong> <span id="modalCreatedAt"></span></p>
                  </div>
                  <div class="col-md-4">
                    <p class="mb-2"><strong>Last Updated:</strong> <span id="modalUpdatedAt"></span></p>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn" onclick="generateQRCode()">
                <i class="fas fa-qrcode"></i> Generate QR Code
              </button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!--ADD NEW PLACE MODAL-->
      <div class="modal fade" id="placeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content" style="background-color: #2d5760; color: white;">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="placeModalTitle">Add New Place</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="placeForm" action="place_tab/save_place.php" method="POST" enctype="multipart/form-data">
              <div class="modal-body">
                <input type="hidden" name="place_id" id="hdnPlaceID" value="">
                <input type="hidden" name="role" id="ddlRole" value="user">
                <input type="hidden" name="latitude" id="hdnLatitude" value="">
                <input type="hidden" name="longitude" id="hdnLongitude" value="">
                <div class="mb-3">
                  <label class="form-label">Place Name</label>
                  <input type="text" name="name" id="txtName" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea name="description" id="txtDescription" class="form-control" required></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label">History</label>
                  <textarea name="history" id="txtHistory" class="form-control"></textarea>
                </div>

                <div class="location-section">
                  <h3>Choose Location</h3>
                  <div class="location-buttons">
                    <button type="button" class="btn" id="location-btn" onclick="getCurrentLocation()">
                      📍 Get My Location
                    </button>

                    <button type="button" class="btn" id="map-btn" onclick="openMap()">
                      🗺️ Choose on Map
                    </button>
                  </div>

                  <div class="selected-location" id="selected-location"> <strong>Selected:</strong> Waiting for location selection...</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Category</label>
                  <select name="category" id="ddlCategory" class="form-select">
                    <option value="heritage" selected>Heritage Sites</option>
                    <option value="popular">Popular Places</option>
                    <option value="activities">Activities</option>
                    <option value="nature">Nature Spots</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">Address</label>
                  <input type="text" name="address" id="txtAddress" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Visiting Hours</label>
                  <textarea name="visiting_hours" id="txtVisitingHours" class="form-control" required></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label">Entry Fee</label>
                  <input type="text" name="entry_fee" id="txtEntryFee" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="text" name="email" id="txtEmail" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Website Link</label>
                  <textarea name="website_link" id="txtWebsiteLink" class="form-control" required></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label">Status</label>
                  <select name="status" id="ddlStatus" class="form-select">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">Upload Main Image</label>
                  <input type="file" name="main_image" id="txtImage" class="form-control" accept="image/*" required>

                </div>

                <div class="mb-3">
                  <label>Additional images</label>
                  <input type="file" name="additional_images[]" accept="image/*" multiple>
                </div>

                
                <div class="modal-footer">
                  <button type="button" class="btn " data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn" id="btnSaveUser">Add Place</button>
                </div>
              </div>
            </form>
          </div>
        </div>

      </div>
      <!-- Map Modal -->
      <div class="map-modal" id="map-modal">
        <div class="map-container">
          <div class="map-header">
            <h3>Choose Location on Map</h3>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="map-info" id="map-info">
            Click on the map to select a location
          </div>
          <div id="map" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #666;"></div>
          <div class="map-actions">
            <button class="btn-cancel" onclick="closeMap()">Cancel</button>
            <button class="btn-confirm" onclick="confirmLocation()">Confirm Location</button>
          </div>
        </div>
      </div>

      <!-- QR Code Modal -->
      <div class="qr-modal" id="qrModal">
        <div class="qr-content">
          <h3 style="color: white; margin-bottom: 20px;">
            <i class="fas fa-qrcode"></i> Place QR Code
          </h3>
          <div class="place-name" id="qrPlaceName" style="font-size: 20px; font-weight: 600; margin-bottom: 5px;"></div>
          <p class="text-muted" id="qrPlaceCategory" style="margin-bottom: 20px;"></p>
          
          <div id="qrCodeDisplay"></div>
          
          <div class="qr-buttons">
            <button class="btn btn-light" onclick="downloadQRCode()">
              <i class="fas fa-download"></i> Download
            </button>
            <button class="btn btn-light" onclick="printQRCode()">
              <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-secondary" onclick="closeQRModal()">
              <i class="fas fa-times"></i> Close
            </button>
          </div>
          
          <div style="margin-top: 20px;">
            <small class="text-muted">Scan this QR code to view place details</small>
          </div>
        </div>
      </div>
    </div>



    <!--ACTIVITIES-->
    <div class="tab hidden" id="tab-treasure">
      <h1>Manage Treasure Game</h1>
      <p>This tab is working.</p>
    </div>

    <!--USERS-->
    <div class="tab hidden" id="tab-users">
      <div class="container-fluid mt-4">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h1><i class="ai-location"></i> Manage Users</h1>
                <p class="text-muted">View and manage all users</p>
              </div>
              <div>
                <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#userModal" onclick="clearUserForm()">
                  <i class="fas fa-user-plus"></i> Add New Admin
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>


      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
          <div class="user-card  text-white">
            <div class="user-card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h4><?php echo $total_users; ?></h4>
                  <p class="mb-0">Total Users</p>
                </div>
                <div>
                  <i class="fas fa-users fa-2x"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <div class="user-card  text-white">
            <div class="user-card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h4><?php echo $active_users; ?></h4>
                  <p class="mb-0">Active Users</p>
                </div>
                <div>
                  <i class="fas fa-user-check fa-2x"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <div class="user-card text-white">
            <div class="user-card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h4><?php echo $blocked_users; ?></h4>
                  <p class="mb-0">Blocked Users</p>
                </div>
                <div>
                  <i class="fas fa-user-slash fa-2x"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <div class="user-card text-white">
            <div class="user-card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h4><?php echo $admin_users; ?></h4>
                  <p class="mb-0">Admin Users</p>
                </div>
                <div>
                  <i class="fas fa-user-shield fa-2x"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php
      $filter_role = $_GET['filter_role'] ?? '';
      $filter_status = $_GET['filter_status'] ?? '';
      $search_users = $_GET['search_users'] ?? '';

      $user_query = "SELECT * FROM users WHERE 1=1";
      if ($filter_role) $user_query .= " AND role='" . mysqli_real_escape_string($conn, $filter_role) . "'";
      if ($filter_status) $user_query .= " AND status='" . mysqli_real_escape_string($conn, $filter_status) . "'";
      if ($search_users) {
        $search = mysqli_real_escape_string($conn, $search_users);
        $user_query .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
      }
      $filtered_users_result = mysqli_query($conn, $user_query);
      ?>
      <!-- Filter and Search -->
      <div class="card mb-4" style="height: auto; width: 100%; border: none; padding: 20px;">
        <div class="card-body">
          <form method="GET" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">User Role</label>
              <select name="filter_role" class="form-select">
                <option value="">All Roles</option>
                <option value="user" <?= $filter_role == 'user' ? 'selected' : '' ?>>Users</option>
                <option value="admin" <?= $filter_role == 'admin' ? 'selected' : '' ?>>Admins</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">User Status</label>
              <select name="filter_status" class="form-select">
                <option value="">All Users</option>
                <option value="active" <?= $filter_status == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="blocked" <?= $filter_status == 'blocked' ? 'selected' : '' ?>>Blocked</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Search</label>
              <input type="text" name="search_users" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search_users) ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">&nbsp;</label>
              <button type="submit" class="btn d-block w-100" onclick="clearUserForm()">Filter</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Users Table -->
      <div class="card" style="height: auto; width: 100%; border: none; padding: 20px;">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover" style="color: white;">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($user = mysqli_fetch_assoc($filtered_users_result)): ?>
                  <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                      <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary' ?>"></span>
                      <?= ucfirst($user['role']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= $user['status'] == 'active' ? 'bg-success' : 'bg-warning' ?>">
                        <?= ucfirst($user['status']) ?>
                      </span>
                    </td>
                    <td>

                      <button class="btn btn-outline-primary btn-sm me-1"
                        onclick="editUser(<?= $user['user_id'] ?>)"
                        title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>


                      <?php if ($user['status'] == 'blocked'): ?>
                        <a href="user_tab/block_user.php?user_id=<?= $user['user_id'] ?>&action=unblock"
                          class="btn btn-outline-success btn-sm me-1"
                          title="Unblock User"
                          onclick="return confirm('Are you sure you want to unblock this user?')">
                          <i class="fas fa-unlock"></i>
                        </a>
                      <?php else: ?>
                        <a href="user_tab/block_user.php?user_id=<?= $user['user_id'] ?>&action=block"
                          class="btn btn-outline-warning btn-sm me-1"
                          title="Block User"
                          onclick="return confirm('Are you sure you want to block this user?')">
                          <i class="fas fa-lock"></i>
                        </a>
                      <?php endif; ?>
                      <a href="user_tab/delete_user.php?user_id=<?= $user['user_id'] ?>"
                        onclick="return confirm('Are you sure you want to permanently delete this user?');"
                        class="btn btn-outline-danger btn-sm me-1"
                        title="Delete User">
                        <i class="fas fa-x"></i>
                      </a>
                      <?php if ($user['role'] != 'admin'): ?>
                        <a href="upgrade_user.php?user_id=<?= $user['user_id'] ?>"
                          class="btn btn-outline-info btn-sm me-1"
                          title="Make Admin"
                          onclick="return confirm('Are you sure you want to make this user an admin?')">
                          <i class="fas fa-user-shield"></i>
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content" style="background-color: #2d5760; color: white;">
            <div class="modal-header">
              <h5 class="modal-title" id="userModalTitle">Add New User</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
              <div class="modal-body">
                <input type="hidden" name="user_id" id="hdnUserID" value="">

                <input type="hidden" name="role" id="ddlRole" value="user">

                <div class="mb-3">
                  <label class="form-label">Name </label>
                  <input type="text" name="name" id="user_txtName" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label">Email </label>
                  <input type="email" name="email" id="user_txtEmail" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label class="form-label" id="lblPassword">Password </label>
                  <input type="password" name="password" id="user_txtPassword" class="form-control">
                  <p class="form-text text-light opacity-75">Minimum 6 characters</p>
                </div>

                <div class="mb-3">
                  <label class="form-label">Status</label>
                  <select name="status" id="user_ddlStatus" class="form-select">
                    <option value="active" selected>Active</option>
                    <option value="blocked">Blocked</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn " data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn " id="btnSaveUser">Save User</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>

    // Mobile menu toggle functionality
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const mobileNavMenu = document.getElementById('mobileNavMenu');

if (mobileMenuToggle && mobileNavMenu) {
  mobileMenuToggle.addEventListener('click', function() {
    mobileNavMenu.classList.toggle('active');
    
    // Change icon between bars and times
    const icon = this.querySelector('i');
    if (mobileNavMenu.classList.contains('active')) {
      icon.className = 'fas fa-times';
    } else {
      icon.className = 'fas fa-bars';
    }
  });
}

// Function to close mobile menu
function closeMobileMenu() {
  if (mobileNavMenu) {
    mobileNavMenu.classList.remove('active');
    const icon = mobileMenuToggle.querySelector('i');
    if (icon) {
      icon.className = 'fas fa-bars';
    }
  }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
  const mobileNav = document.querySelector('.mobile-nav');
  if (mobileNav && 
      !mobileNav.contains(event.target) && 
      mobileNavMenu && 
      mobileNavMenu.classList.contains('active')) {
    closeMobileMenu();
  }
});

// Update switchTab function to close mobile menu on mobile
const originalSwitchTab = switchTab;
switchTab = function(name) {
  originalSwitchTab(name);
  closeMobileMenu();
};

// Prevent body scroll when mobile menu is open
if (mobileNavMenu) {
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.attributeName === 'class') {
        if (mobileNavMenu.classList.contains('active')) {
          document.body.style.overflow = 'hidden';
        } else {
          document.body.style.overflow = '';
        }
      }
    });
  });
  
  observer.observe(mobileNavMenu, {
    attributes: true
  });
}
    let currentPlaceData = null; 

    function switchTab(name) {
      document.querySelectorAll('.tab').forEach(t => t.classList.add('hidden'));
      const target = document.getElementById('tab-' + name);
      if (target) {
        target.classList.remove('hidden');
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      }

    }
// Initialize dashboard on page load
document.addEventListener('DOMContentLoaded', function() {
  // Set current date
  updateDate();
  
  // Animate stat numbers
  animateStatNumbers();
  

  
  // Animate progress bars
  animateProgressBars();
});

function updateDate() {
  const dateElement = document.getElementById('currentDate');
  const now = new Date();
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  dateElement.textContent = now.toLocaleDateString('en-US', options);
}

function animateStatNumbers() {
  const statNumbers = document.querySelectorAll('.stat-number');
  
  statNumbers.forEach(stat => {
    const target = parseInt(stat.getAttribute('data-target'));
    const duration = 2000; // 2 seconds
    const steps = 60;
    const stepValue = target / steps;
    let current = 0;
    
    const interval = setInterval(() => {
      current += stepValue;
      if (current >= target) {
        stat.textContent = target.toLocaleString();
        clearInterval(interval);
      } else {
        stat.textContent = Math.floor(current).toLocaleString();
      }
    }, duration / steps);
  });
}



    //---------PLACE TAB--------

    document.getElementById('placeForm').addEventListener('submit', function(e) {
      e.preventDefault(); 


      const formData = new FormData(this);


      fetch('place_tab/save_place.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(data => {

          if (data.includes("success")) {
            alert("Place added successfully!");
            location.reload(); 
          } else {
            alert("Error: " + data);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    });



    document.addEventListener('DOMContentLoaded', function() {
      const placeModal = document.getElementById('placeDetailsModal');

      placeModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;


        const placeId = button.getAttribute('data-place-id');
        const name = button.getAttribute('data-name');
        const description = button.getAttribute('data-desc');
        const history = button.getAttribute('data-hist');
        const imageName = button.getAttribute('data-img');
        const category = button.getAttribute('data-category');
        const address = button.getAttribute('data-address');
        const latitude = button.getAttribute('data-latitude');
        const longitude = button.getAttribute('data-longitude');
        const visitingHours = button.getAttribute('data-visiting-hours');
        const entryFee = button.getAttribute('data-entry-fee');
        const email = button.getAttribute('data-email');
        const website = button.getAttribute('data-website');
        const status = button.getAttribute('data-status');
        const createdAt = button.getAttribute('data-created-at');
        const updatedAt = button.getAttribute('data-updated-at');


        currentPlaceData = {
          place_id: placeId,
          name: name,
          description: description,
          history: history,
          category: category,
          address: address,
          latitude: latitude,
          longitude: longitude,
          visiting_hours: visitingHours,
          entry_fee: entryFee,
          email: email,
          website: website,
          status: status,
          main_image: imageName
        };


        document.getElementById('modalTitleText').textContent = name;
        document.getElementById('modalDescription').textContent = description || 'No description available';
        document.getElementById('modalHistory').textContent = history || 'No history information available';
        document.getElementById('modalImage').src = 'uploads/images/' + imageName;
        document.getElementById('modalAddress').textContent = address || 'Not specified';
        document.getElementById('modalCategory').textContent = category ? category.charAt(0).toUpperCase() + category.slice(1) : 'Not specified';
        document.getElementById('modalLatitude').textContent = latitude || 'N/A';
        document.getElementById('modalLongitude').textContent = longitude || 'N/A';
        document.getElementById('modalVisitingHours').textContent = visitingHours || 'Not specified';
        document.getElementById('modalEntryFee').textContent = entryFee || 'Not specified';
        
        const statusBadge = document.getElementById('modalStatus');
        statusBadge.textContent = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
        statusBadge.className = 'badge ' + (status === 'active' ? 'bg-success' : 'bg-secondary');
      
        const emailLink = document.getElementById('modalEmail');
        emailLink.textContent = email || 'Not provided';
        emailLink.href = email ? 'mailto:' + email : '#';
        
        const websiteLink = document.getElementById('modalWebsite');
        websiteLink.textContent = website || 'Not provided';
        websiteLink.href = website || '#';
        document.getElementById('modalCreatedAt').textContent = createdAt ? new Date(createdAt).toLocaleDateString() : 'N/A';
        document.getElementById('modalUpdatedAt').textContent = updatedAt ? new Date(updatedAt).toLocaleDateString() : 'N/A';
      });
    });

    function clearplaceForm() {
  document.getElementById('placeForm').reset();
  document.getElementById('hdnPlaceID').value = '';
  document.getElementById('placeModalTitle').innerText = 'Add New Place';
  document.getElementById('btnSaveUser').innerText = 'Add Place';
  document.getElementById('txtImage').required = true;
  selectedLocationData = null;
  document.getElementById('selected-location').innerHTML = '<strong>Selected:</strong> Waiting for location selection...';
}

// Edit place function
function editPlace(place_id) {
  fetch('get_place.php?place_id=' + place_id)
    .then(response => response.json())
    .then(data => {
      if (data.success) {

        
        // Fill form fields
        document.getElementById('hdnPlaceID').value = data.place.place_id;
        document.getElementById('txtName').value =  data.place.name;
        document.getElementById('txtDescription').value =  data.place.description;
        document.getElementById('txtHistory').value =  data.place.history || '';
        document.getElementById('ddlCategory').value =  data.place.category;
        document.getElementById('txtAddress').value =  data.place.address;
        document.getElementById('txtVisitingHours').value =  data.place.visiting_hours;
        document.getElementById('txtEntryFee').value =  data.place.entry_fee;
        document.getElementById('txtEmail').value =  data.place.email;
        document.getElementById('txtWebsiteLink').value =  data.place.website;
        document.getElementById('ddlStatus').value =  data.place.status;
        document.getElementById('hdnLatitude').value =  data.place.latitude;
        document.getElementById('hdnLongitude').value =  data.place.longitude;
        
        // Set location data
        selectedLocationData = {
          display_name:  data.place.address,
          lat: parseFloat( data.place.latitude),
          lon: parseFloat( data.place.longitude)
        };
        
        // Update location display
        document.getElementById('selected-location').innerHTML = `
          <strong>Selected:</strong> ${ data.place.address}<br>
          <small>Coordinates: ${ data.place.latitude}, ${ data.place.longitude}</small>
        `;
        
        // Update modal title and button
        document.getElementById('placeModalTitle').innerText = 'Edit Place';
        document.getElementById('btnSaveUser').innerText = 'Update Place';
        
        // Make image optional for edit
        document.getElementById('txtImage').required = false;
        
        // Show modal
        new bootstrap.Modal(document.getElementById('placeModal')).show();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load place data');
    });
}

    function generateQRCode() {
      if (!currentPlaceData) {
        alert('No place data available');
        return;
      }


      document.getElementById('qrCodeDisplay').innerHTML = '';
      

      document.getElementById('qrPlaceName').textContent = currentPlaceData.name;
      document.getElementById('qrPlaceCategory').textContent = currentPlaceData.category ? 
      currentPlaceData.category.charAt(0).toUpperCase() + currentPlaceData.category.slice(1) : '';
      const placeURL = 'https://mauritrails/view_place_details.php?place_id=' + currentPlaceData.id;


      new QRCode(document.getElementById("qrCodeDisplay"), {
        text: placeURL,
        width: 256,
        height: 256,
        colorDark: "#0b3842",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
      });


      document.getElementById('qrModal').classList.add('show');
    }

    function closeQRModal() {
      document.getElementById('qrModal').classList.remove('show');
      document.getElementById('qrCodeDisplay').innerHTML = '';
    }

    function downloadQRCode() {
      const canvas = document.querySelector('#qrCodeDisplay canvas');
      if (!canvas) {
        alert('QR code not generated');
        return;
      }
      
      const url = canvas.toDataURL('image/png');
      const link = document.createElement('a');
      const placeName = currentPlaceData.name.replace(/[^a-z0-9]/gi, '-').toLowerCase();
      link.download = 'qr-' + placeName + '.png';
      link.href = url;
      link.click();
     }


    let selectedLocationData = null;
    let map = null;
    let marker = null;
    let tempLocation = null;


    function initMap(lat = -20.3484, lng = 57.5522) { 
      if (map) {
        map.setView([lat, lng], 13);
        return;
      }

      map = L.map('map').setView([lat, lng], 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(map);


      map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;


        if (marker) {
          map.removeLayer(marker);
        }


        marker = L.marker([lat, lng]).addTo(map);


        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
          .then(response => response.json())
          .then(data => {
            tempLocation = {
              display_name: data.display_name,
              lat: lat,
              lon: lng
            };
            document.getElementById('map-info').innerHTML = `
              <strong>Selected:</strong> ${data.display_name}<br>
              <small>Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}</small>
            `;
          })
          .catch(error => {
            console.error('Error getting address:', error);
            tempLocation = {
              display_name: `Location (${lat.toFixed(4)}, ${lng.toFixed(4)})`,
              lat: lat,
              lon: lng
            };
            document.getElementById('map-info').innerHTML = `
              <strong>Selected:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}
            `;
          });
      });
    }


    function openMap() {
      document.getElementById('map-modal').classList.add('show');


      if (selectedLocationData) {
        initMap(selectedLocationData.lat, selectedLocationData.lon);
      } else {

        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            position => {
              initMap(position.coords.latitude, position.coords.longitude);
            },
            error => {
              console.log('Using default location');
              initMap();
            }
          );
        } else {
          initMap();
        }
      }


      setTimeout(() => {
        if (map) map.invalidateSize();
      }, 100);
    }


    function closeMap() {
      document.getElementById('map-modal').classList.remove('show');
      tempLocation = null;
      document.getElementById('map-info').innerHTML = 'Click on the map to select a location';
    }


    function confirmLocation() {
      if (!tempLocation) {
        alert('Please select a location on the map');
        return;
      }

      selectLocation(tempLocation);
      closeMap();
    }


    function getCurrentLocation() {
      if (!navigator.geolocation) {
        console.error('Geolocation is not supported by this browser');
        alert('Geolocation is not supported by your browser');
        return;
      }

      const btn = document.getElementById('location-btn');
      const selectedDiv = document.getElementById('selected-location');

      btn.disabled = true;
      btn.textContent = 'Getting location...';

      selectedDiv.innerHTML = '<div class="loading">Getting your location...</div>';
      selectedDiv.classList.add('show');

      navigator.geolocation.getCurrentPosition(

        position => {
          const latitude = position.coords.latitude;
          const longitude = position.coords.longitude;
          const accuracy = position.coords.accuracy;

          console.log(`Location: ${latitude}, ${longitude} (accuracy: ${accuracy}m)`);


          fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
            .then(response => response.json())
            .then(data => {
              selectLocation({
                display_name: data.display_name,
                lat: latitude,
                lon: longitude,
                accuracy: accuracy
              });
            })
            .catch(error => {
              console.error('Error getting address:', error);
              selectLocation({
                display_name: `Location (${latitude.toFixed(4)}, ${longitude.toFixed(4)})`,
                lat: latitude,
                lon: longitude,
                accuracy: accuracy
              });
            })
            .finally(() => {
              btn.disabled = false;
              btn.textContent = 'Get My Location';
            });
        },

        error => {
          let message = 'Error getting location';
          switch (error.code) {
            case error.PERMISSION_DENIED:
              message = 'User denied the request for geolocation';
              console.error("User denied the request for geolocation");
              break;
            case error.POSITION_UNAVAILABLE:
              message = 'Location information is unavailable';
              console.error("Location information is unavailable");
              break;
            case error.TIMEOUT:
              message = 'The request to get user location timed out';
              console.error("The request to get user location timed out");
              break;
            default:
              message = 'An unknown error occurred';
              console.error("An unknown error occurred");
          }
          selectedDiv.innerHTML = `<div style="color: #d32f2f;">${message}</div>`;
          btn.disabled = false;
          btn.textContent = 'Get My Location';
        },

        {
          enableHighAccuracy: true,
          timeout: 5000, 
          maximumAge: 0 
        }
      );
    }


    function selectLocation(location) {
      selectedLocationData = location;

      const selectedDiv = document.getElementById('selected-location');
      selectedDiv.innerHTML = `
        <strong>Selected:</strong> ${location.display_name}<br>
        <small>Coordinates: ${location.lat}, ${location.lon}</small>
        ${location.accuracy ? `<br><small>Accuracy: ${location.accuracy.toFixed(0)}m</small>` : ''}
      `;
      selectedDiv.classList.add('show');
    }



    //---------ACTIVITY TAB-------

    //---------USER TAB-----------

    function clearUserForm() {
      document.getElementById('userForm').reset();
      document.getElementById('hdnUserID').value = '';
      document.getElementById('userModalTitle').innerText = 'Add New Admin';
      document.getElementById('btnSaveUser').innerText = 'Save User';
      document.getElementById('user_txtPassword').required = true;
      document.getElementById('lblPassword').innerText = 'Password *';
    }


    document.getElementById('userForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('user_tab/save_user.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            const modalElement = document.getElementById('userModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();

            alert('User Saved!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => console.error('Error:', error));
    });


    function clearUserForm() {
      document.getElementById('userForm').reset();
      document.getElementById('hdnUserID').value = '';
      document.getElementById('user_ddlRole').value = 'user';
      document.getElementById('userModalTitle').innerText = 'Add New User';
      document.getElementById('btnSaveUser').innerText = 'Save User';
    }


    function editUser(userId) {
      fetch('user_tab/get_user.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('hdnUserID').value = data.user.user_id;
            document.getElementById('user_txtName').value = data.user.name;
            document.getElementById('user_txtEmail').value = data.user.email;
            document.getElementById('user_ddlRole').value = data.user.role; 
            document.getElementById('user_ddlStatus').value = data.user.status;

            document.getElementById('userModalTitle').innerText = 'Edit User';
            document.getElementById('btnSaveUser').innerText = 'Update User';

            new bootstrap.Modal(document.getElementById('userModal')).show();
          }
        });
    }


    document.getElementById('qrModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeQRModal();
      }
    });
  </script>
</body>

</html>