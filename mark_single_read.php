<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");

if (!$conn) { echo json_encode(['success'=>false]); exit; }

$adminID = $_SESSION['admininfoID'];
$clickedTime = $_POST['time'] ?? null;

if ($clickedTime) {
    $res = mysqli_query($conn, "SELECT last_read_time FROM admininfo WHERE admininfoID='$adminID'");
    $row = mysqli_fetch_assoc($res);
    $lastReadTime = $row['last_read_time'];

    // Only update if clicked notification is older than current last read
    if (strtotime($clickedTime) > strtotime($lastReadTime)) {
        mysqli_query($conn, "UPDATE admininfo SET last_read_time='$clickedTime' WHERE admininfoID='$adminID'");
    }

    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'No time provided']);
}
?>