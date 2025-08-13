<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userinfo_ID'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Connect to database (if you want to also mark in DB, optional)
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// -------------------- Mark all as read --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update session last_read_notification to current time
    $_SESSION['last_read_notification'] = date('Y-m-d H:i:s');
    echo json_encode(['success' => true]);
    exit;
}

// -------------------- Mark single notification as read --------------------
if (isset($_GET['time'])) {
    $clickedTime = $_GET['time'];

    if (!isset($_SESSION['last_read_notification']) || strtotime($clickedTime) > strtotime($_SESSION['last_read_notification'])) {
        $_SESSION['last_read_notification'] = $clickedTime;
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'No valid action']);
?>