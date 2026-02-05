<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];

$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);


$places_query = "SELECT COUNT(DISTINCT place_id) as count 
                FROM user_activities 
                WHERE user_id = $user_id 
                AND place_id IS NOT NULL";
$places_result = mysqli_query($conn, $places_query);
$places_count = mysqli_fetch_assoc($places_result)['count'];

$qr_query = "SELECT COUNT(*) as count 
            FROM user_activities 
            WHERE user_id = $user_id 
            AND activity_type = 'qr_scan'";
$qr_result = mysqli_query($conn, $qr_query);
$qr_count = mysqli_fetch_assoc($qr_result)['count'];


$achievements_query = "SELECT COUNT(*) as count 
                      FROM user_achievements 
                      WHERE user_id = $user_id";
$achievements_result = mysqli_query($conn, $achievements_query);
$achievements_count = mysqli_fetch_assoc($achievements_result)['count'];


$total_points = $user['points'] ?? 0;
$level = floor($total_points / 100) + 1; 
$current_level_points = $total_points % 100;
$points_needed = 100;

// Get completed challenges count
$challenges_query = "SELECT COUNT(*) as count 
                    FROM user_challenges 
                    WHERE user_id = $user_id 
                    AND status = 'completed'";
$challenges_result = mysqli_query($conn, $challenges_query);
$challenges_count = mysqli_fetch_assoc($challenges_result)['count'];


$display_name = 'User';
if (isset($user['username'])) {
    $display_name = $user['username'];
} elseif (isset($user['email'])) {
    $display_name = explode('@', $user['email'])[0];
} elseif (isset($user['first_name'])) {
    $display_name = $user['first_name'];
} elseif (isset($user['name'])) {
    $display_name = $user['name'];
}

echo json_encode([
    'success' => true,
    'display_name' => $display_name,
    'email' => $user['email'] ?? '',
    'total_points' => $total_points,
    'places_visited' => $places_count,
    'qr_scanned' => $qr_count,
    'achievements' => $achievements_count,
    'challenges_completed' => $challenges_count,
    'level' => $level,
    'level_progress' => [
        'current' => $current_level_points,
        'needed' => $points_needed
    ],
    'member_since' => $user['created_at'] ?? date('Y-m-d')
]);

mysqli_close($conn);
?>