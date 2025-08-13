<?php
session_start();

if (!isset($_SESSION['userinfo_ID'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) die("DB connection failed: ".mysqli_connect_error());

$userID = $_SESSION['userinfo_ID'];
date_default_timezone_set("Asia/Manila");

$time = $_GET['time'] ?? '';
$redirect = $_GET['redirect'] ?? 'user_notification.php';

if ($time) {
    $time = date('Y-m-d H:i:s', strtotime($time)); // normalize format

    // get last read from DB (more reliable than session)
    $res = mysqli_query($conn, "SELECT last_read_notification FROM userinfo WHERE userinfo_ID='$userID'");
    $row = mysqli_fetch_assoc($res);
    $lastRead = $row['last_read_notification'] ?? '2000-01-01 00:00:00';

    if (strtotime($time) > strtotime($lastRead)) {
        $sql = "UPDATE userinfo SET last_read_notification='$time' WHERE userinfo_ID='$userID'";
        mysqli_query($conn, $sql);
    }

    // Update session as well
    $_SESSION['last_read_notification'] = $time;
}

// Redirect to the original page safely
$redirect = filter_var($redirect, FILTER_SANITIZE_URL);
header("Location: $redirect");
exit();
?>