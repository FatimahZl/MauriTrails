<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];

// Get all challenges (active and completed from last 7 days)
$query = "SELECT * FROM user_challenges 
          WHERE user_id = $user_id 
          AND (status = 'active' OR (status = 'completed' AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)))
          ORDER BY 
            CASE status 
              WHEN 'active' THEN 1 
              WHEN 'completed' THEN 2 
              ELSE 3 
            END,
            created_at DESC";

$result = mysqli_query($conn, $query);

$challenges = [];
while ($row = mysqli_fetch_assoc($result)) {
   
    if ($row['status'] === 'active' && strtotime($row['expires_at']) < time()) {
        // Mark as expired
        $update_query = "UPDATE user_challenges 
                        SET status = 'expired' 
                        WHERE challenge_id = " . $row['challenge_id'];
        mysqli_query($conn, $update_query);
        $row['status'] = 'expired';
    }
    
    $challenges[] = $row;
}

echo json_encode([
    'success' => true,
    'challenges' => $challenges,
    'count' => count($challenges)
]);

mysqli_close($conn);
?>