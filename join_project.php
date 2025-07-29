<?php
session_start();
require 'db.php';

// ✅ Check if student is logged in
if (!isset($_SESSION['userinfo_ID'])) {
    die("You must be logged in to join a project.");
}

$userinfo_id = $_SESSION['userinfo_ID'];
$studentEmail = $_SESSION['Email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $join_code = strtoupper(trim($_POST['join_code']));

    // ✅ STEP 1: Find the project
    $stmt = $conn->prepare("SELECT proj_id FROM projects WHERE join_code = ?");
    $stmt->bind_param("s", $join_code);
    $stmt->execute();
    $stmt->bind_result($proj_id);

    if ($stmt->fetch()) {
        $stmt->close();

        // ✅ STEP 2: Check if already joined
        $check = $conn->prepare("SELECT COUNT(*) FROM project_members WHERE proj_id = ? AND userinfo_id = ?");
        $check->bind_param("ii", $proj_id, $userinfo_id);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();

        if ($exists == 0) {
            // ✅ STEP 3: Insert into project_members
            $insert = $conn->prepare("INSERT INTO project_members (proj_id, userinfo_id) VALUES (?, ?)");
            $insert->bind_param("ii", $proj_id, $userinfo_id);
            $insert->execute();
            $insert->close();

            // Get student name
$nameStmt = $conn->prepare("SELECT CONCAT(TRIM(FIRSTNAME), ' ', TRIM(LASTNAME)) AS full_name FROM userinfo WHERE userinfo_ID = ?");
$nameStmt->bind_param("i", $userinfo_id);
$nameStmt->execute();
$nameStmt->bind_result($fullName);
$nameStmt->fetch();
$nameStmt->close();

// Get assignments for this project
$getAssignments = $conn->prepare("SELECT ass_id FROM assigned WHERE proj_id = ?");
$getAssignments->bind_param("i", $proj_id);
$getAssignments->execute();
$result = $getAssignments->get_result();

while ($row = $result->fetch_assoc()) {
    $ass_id = $row['ass_id'];

    $checkExist = $conn->prepare("SELECT id FROM assignment_students WHERE assigned_id = ? AND userinfo_ID = ?");
    $checkExist->bind_param("ii", $ass_id, $userinfo_id);
    $checkExist->execute();
    $checkExist->store_result();

    if ($checkExist->num_rows === 0) {
        $insertStudent = $conn->prepare("INSERT INTO assignment_students (assigned_id, username, userinfo_ID, status) VALUES (?, ?, ?, 'Not Started')");
        $insertStudent->bind_param("isi", $ass_id, $fullName, $userinfo_id);
        $insertStudent->execute();
        $insertStudent->close();
    }

    $checkExist->close();
}
$getAssignments->close();

            header("Location: Projects.php?joined=1");
            exit;
        } else {
            header("Location: Projects.php?joined=exists");
            exit;
        }

    } else {
        header("Location: Projects.php?joined=invalid");
        exit;
    }
}
?>
