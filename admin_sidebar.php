<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unreadCount = 0;
if (!isset($_SESSION['Email'])) return;

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) return;

$email = $_SESSION['Email'];

// Get admin ID and last read time
$res = mysqli_query($conn, "SELECT admininfoID, last_read_time FROM admininfo WHERE Email = '$email'");
if (!$res || mysqli_num_rows($res) === 0) return;

$row = mysqli_fetch_assoc($res);
$adminID = $row['admininfoID'];
$lastRead = $row['last_read_time'];

$notifications = [];

// 1. File Uploads
$sql_uploads = "SELECT ss.uploaded_at AS activity_time, 
       CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS user_name,
       ss.file_name, a.project_name, a.ass_id
    FROM student_submissions ss
    JOIN userinfo u ON ss.userinfo_id = u.userinfo_ID
    JOIN assigned a ON ss.assigned_id = a.ass_id
    JOIN projects p ON p.proj_id = a.proj_id
    WHERE p.admininfoID = '$adminID'";
$res1 = mysqli_query($conn, $sql_uploads);
while ($row1 = mysqli_fetch_assoc($res1)) {
    $notifications[] = [
        'time' => $row1['activity_time']
    ];
}

// 2. Comments
$sql_comments = "SELECT c.created_at AS activity_time
    FROM comments c
    JOIN userinfo u ON c.userinfo_id = u.userinfo_ID
    JOIN assigned a ON c.ass_id = a.ass_id
    JOIN projects p ON p.proj_id = a.proj_id
    WHERE p.admininfoID = '$adminID'";
$res2 = mysqli_query($conn, $sql_comments);
while ($row2 = mysqli_fetch_assoc($res2)) {
    $notifications[] = [
        'time' => $row2['activity_time']
    ];
}

// 3. Project Joins
$sql_joins = "SELECT MAX(a.created_at) AS activity_time
    FROM project_members pm
    JOIN userinfo u ON pm.userinfo_id = u.userinfo_ID
    JOIN projects p ON pm.proj_id = p.proj_id
    LEFT JOIN assigned a ON a.proj_id = p.proj_id
    WHERE p.admininfoID = '$adminID'
    GROUP BY pm.id, pm.proj_id, pm.userinfo_id";
$res3 = mysqli_query($conn, $sql_joins);
while ($row3 = mysqli_fetch_assoc($res3)) {
    $notifications[] = [
        'time' => $row3['activity_time']
    ];
}

// Count unread using same logic as notification.php
foreach ($notifications as $note) {
    if (strtotime($note['time']) > strtotime($lastRead)) {
        $unreadCount++;
    }
}
