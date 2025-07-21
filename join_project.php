<?php
session_start();
require 'db.php';

// ✅ Check if student is logged in
if (!isset($_SESSION['userinfo_ID'])) {
    die("You must be logged in to join a project.");
}

$userinfo_id = $_SESSION['userinfo_ID'];  // Already stored in login
$studentEmail = $_SESSION['Email'];

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $join_code = strtoupper(trim($_POST['join_code']));

    // ✅ STEP 1: Find the project by join code
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

            header("Location: Projects.php?joined=1");
            exit;
        } else {
            header("Location: Projects.php?joined=exists");
            exit;
        }

    } else {
        // Join code not found
        header("Location: Projects.php?joined=invalid");
        exit;
    }
}
?>
