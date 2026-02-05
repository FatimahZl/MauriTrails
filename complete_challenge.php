<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$challenge_id = intval($data['challenge_id']);

// Verify challenge belongs to user and is active
$check_query = "SELECT * FROM user_challenges 
                WHERE challenge_id = $challenge_id 
                AND user_id = $user_id 
                AND status = 'active'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Challenge not found or already completed'
    ]);
    exit;
}

$challenge = mysqli_fetch_assoc($check_result);
$points_reward = $challenge['points_reward'];

mysqli_begin_transaction($conn);

try {
    $update_challenge = "UPDATE user_challenges 
                        SET status = 'completed', 
                            completed_at = NOW() 
                        WHERE challenge_id = $challenge_id";
    
    if (!mysqli_query($conn, $update_challenge)) {
        throw new Exception('Failed to update challenge');
    }
    
    // Award points to user
    $update_points = "UPDATE users 
                     SET points = points + $points_reward 
                     WHERE user_id = $user_id";
    
    if (!mysqli_query($conn, $update_points)) {
        throw new Exception('Failed to award points');
    }
    
    // Log the activity
    $challenge_name = mysqli_real_escape_string($conn, $challenge['challenge_name']);
    $log_activity = "INSERT INTO user_activities 
                    (user_id, activity_type, description, points, created_at) 
                    VALUES 
                    ($user_id, 'challenge_completed', 'Completed challenge: $challenge_name', 
                     $points_reward, NOW())";
    
    if (!mysqli_query($conn, $log_activity)) {
        throw new Exception('Failed to log activity');
    }
    
    checkAndAwardAchievements($conn, $user_id);
    
 
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Challenge completed successfully!',
        'points_earned' => $points_reward
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error completing challenge: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);

function checkAndAwardAchievements($conn, $user_id) {
    // Check total completed challenges
    $count_query = "SELECT COUNT(*) as total FROM user_challenges 
                   WHERE user_id = $user_id AND status = 'completed'";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_completed = $count_row['total'];
    
    // Award achievements based on milestones
    $achievements = [
        1 => ['name' => 'First Steps', 'icon' => '👣'],
        5 => ['name' => 'Challenge Seeker', 'icon' => '🎯'],
        10 => ['name' => 'Challenge Master', 'icon' => '🏆'],
        25 => ['name' => 'Challenge Legend', 'icon' => '⭐'],
        50 => ['name' => 'Challenge Champion', 'icon' => '👑']
    ];
    
    if (isset($achievements[$total_completed])) {
        $achievement = $achievements[$total_completed];
        
        // Check if achievement already awarded
        $check_achievement = "SELECT * FROM user_achievements 
                            WHERE user_id = $user_id 
                            AND achievement_name = '" . mysqli_real_escape_string($conn, $achievement['name']) . "'";
        $achievement_result = mysqli_query($conn, $check_achievement);
        
        if (mysqli_num_rows($achievement_result) === 0) {
            $insert_achievement = "INSERT INTO user_achievements 
                                 (user_id, achievement_name, achievement_icon, earned_date) 
                                 VALUES 
                                 ($user_id, '" . mysqli_real_escape_string($conn, $achievement['name']) . "', 
                                  '" . mysqli_real_escape_string($conn, $achievement['icon']) . "', NOW())";
            mysqli_query($conn, $insert_achievement);
        }
    }
}
?>