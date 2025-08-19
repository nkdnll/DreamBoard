<?php
session_start();
include 'log1.php';
$conn = new mysqli("localhost", "root", "", "projectmanagement");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$assigned_id = $_POST['assigned_id'];
$userinfo_id = $_POST['userinfo_id'];
$grade = $_POST['grade'];

$stmt = $conn->prepare("
    UPDATE assignment_students
    SET grade = ?, graded_at = NOW()
    WHERE assigned_id = ? AND userinfo_ID = ?
");

$stmt->bind_param("iii", $grade, $assigned_id, $userinfo_id);

if ($stmt->execute()) {
    // Logging the grade update
    if (isset($_SESSION['Email'])) {
        $adminEmail = $_SESSION['Email'];
        $description = "Graded assignment ID $assigned_id for student ID $userinfo_id with score $grade.";
        logTransaction('admin', $adminEmail, 'GRADE_ASSIGNMENT', $description);
    }

    // Redirect back to the same student panel
    header("Location: Admin-teamproj.php?ass_id=$assigned_id&selected=$userinfo_id");
    exit();
} else {
    echo "Error saving grade: " . $conn->error;
}
?>
