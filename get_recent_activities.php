<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];

// Get recent activities
$query = "SELECT 
            ua.*,
            p.name as place_name,
            DATE_FORMAT(ua.created_at, '%d %b %Y, %h:%i %p') as formatted_time
          FROM user_activities ua
          LEFT JOIN places p ON ua.place_id = p.place_id
          WHERE ua.user_id = $user_id
          ORDER BY ua.created_at DESC
          LIMIT 10";

$result = mysqli_query($conn, $query);

$activities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $description = $row['description'];
    
    // Format description based on activity type
    if (empty($description)) {
        switch($row['activity_type']) {
            case 'visit':
                $description = "Visited " . ($row['place_name'] ?? 'a place');
                break;
            case 'qr_scan':
                $description = "Scanned QR code at " . ($row['place_name'] ?? 'a location');
                break;
            case 'review':
                $description = "Left a review for " . ($row['place_name'] ?? 'a place');
                break;
            case 'photo':
                $description = "Uploaded a photo at " . ($row['place_name'] ?? 'a place');
                break;
            case 'challenge_completed':
                $description = "Completed a daily challenge";
                break;
            case 'challenge_received':
                $description = "Received a new daily challenge";
                break;
            default:
                $description = "Activity at " . ($row['place_name'] ?? 'unknown location');
        }
    }
    
    $activities[] = [
        'activity_id' => $row['activity_id'],
        'type' => $row['activity_type'],
        'description' => $description,
        'points' => $row['points'] ?? 0,
        'timestamp' => $row['formatted_time'],
        'raw_timestamp' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'activities' => $activities,
    'count' => count($activities)
]);

mysqli_close($conn);
?>