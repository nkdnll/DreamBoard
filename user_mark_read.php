<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

if (!isset($_SESSION['userinfo_ID'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userID = $_SESSION['userinfo_ID'];
$latestTime = null;

// Get latest notification timestamp from all sources
$queries = [
    "SELECT MAX(ast.graded_at) AS t FROM student_submissions ss
     JOIN assigned a ON ss.assigned_id = a.ass_id
     JOIN assignment_students ast ON ast.assigned_id = a.ass_id
     WHERE ast.userinfo_id = '$userID' AND ast.grade IS NOT NULL",

    "SELECT MAX(a.created_at) AS t FROM assigned a
     JOIN assignment_students ast ON ast.assigned_id = a.ass_id
     WHERE ast.userinfo_id = '$userID'",

    "SELECT MAX(c.created_at) AS t FROM comments c
     JOIN assigned a ON c.ass_id = a.ass_id
     JOIN assignment_students ast ON ast.assigned_id = a.ass_id
     WHERE ast.userinfo_id = '$userID' AND c.user_type = 'admin'"
];

foreach ($queries as $sql) {
    $resQ = mysqli_query($conn, $sql);
    if ($resQ) {
        $rowQ = mysqli_fetch_assoc($resQ);
        if (!empty($rowQ['t']) && (is_null($latestTime) || strtotime($rowQ['t']) > strtotime($latestTime))) {
            $latestTime = $rowQ['t'];
        }
    }
}

// If no notifications found, use current time
if (is_null($latestTime)) {
    $latestTime = date('Y-m-d H:i:s');
}

// Update user’s last_read_notification
mysqli_query($conn, "UPDATE userinfo 
    SET last_read_notification = '$latestTime' 
    WHERE userinfo_ID = '$userID'");
$_SESSION['last_read_notification'] = $latestTime;

echo json_encode(['success' => true]);
?>
