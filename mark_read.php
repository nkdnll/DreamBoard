<?php
session_start();
header('Content-Type: application/json');

// DB connection
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['admininfoID'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$adminID = $_SESSION['admininfoID'];

// Update last_read_time to now
date_default_timezone_set("Asia/Manila");
$now = date("Y-m-d H:i:s");

$sql = "UPDATE admininfo SET last_read_time = ? WHERE admininfoID = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $now, $adminID);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success) {
        echo json_encode(['success' => true]);
        exit();
    }
}

echo json_encode(['success' => false, 'error' => 'Failed to update']);
