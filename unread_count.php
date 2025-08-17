<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unreadCount = 0;
if (!isset($_SESSION['userinfo_ID'])) return;

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) return;

$userID = $_SESSION['userinfo_ID'];

// Get last read time
$resLastRead = mysqli_query($conn, "SELECT last_read_notification FROM userinfo WHERE userinfo_ID = '$userID'");
$rowLastRead = mysqli_fetch_assoc($resLastRead);
$lastRead = $rowLastRead ? $rowLastRead['last_read_notification'] : '2000-01-01 00:00:00';

// Count unread
$sql = "
    SELECT COUNT(*) AS cnt FROM (
        SELECT ast.graded_at AS time
        FROM assigned a
        JOIN projects p ON a.proj_id = p.proj_id
        JOIN admininfo ON p.admininfoID = admininfo.admininfoID
        JOIN assignment_students ast ON ast.assigned_id = a.ass_id
        WHERE ast.userinfo_id = '$userID' AND ast.grade IS NOT NULL

        UNION ALL

        SELECT a.created_at
        FROM assigned a
        JOIN projects p ON a.proj_id = p.proj_id
        JOIN admininfo ON p.admininfoID = admininfo.admininfoID
        JOIN assignment_students ast ON ast.assigned_id = a.ass_id
        WHERE ast.userinfo_id = '$userID'

        UNION ALL

        SELECT c.created_at
        FROM comments c
        JOIN assigned a ON c.ass_id = a.ass_id
        JOIN projects p ON a.proj_id = p.proj_id
        JOIN admininfo ON p.admininfoID = admininfo.admininfoID
        JOIN assignment_students ast ON ast.assigned_id = a.ass_id
        WHERE ast.userinfo_id = '$userID' AND c.user_type = 'admin'
    ) AS all_notifs
    WHERE all_notifs.time > '$lastRead'
";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$unreadCount = $row['cnt'] ?? 0;
?>