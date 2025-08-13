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

if (!isset($_GET['time']) || $_GET['time'] === '') {
    echo json_encode(['success' => false, 'error' => 'Missing notification time']);
    exit;
}

$userID = $_SESSION['userinfo_ID'];
$notifTime = mysqli_real_escape_string($conn, $_GET['time']);

// Get current last_read_notification
$res = mysqli_query($conn, "SELECT last_read_notification FROM userinfo WHERE userinfo_ID = '$userID'");
$row = mysqli_fetch_assoc($res);
$lastRead = $row ? $row['last_read_notification'] : '2000-01-01 00:00:00';

// Update only if this notification is newer
if (strtotime($notifTime) > strtotime($lastRead)) {
    mysqli_query($conn, "UPDATE userinfo 
        SET last_read_notification = '$notifTime' 
        WHERE userinfo_ID = '$userID'");
    $_SESSION['last_read_notification'] = $notifTime;
}

echo json_encode(['success' => true]);
?>
