<?php
session_start();
require 'db.php';

if (!isset($_SESSION['Email'])) {
    header("Location: login.php");
    exit();
}

$proj_id = $_GET['proj_id'] ?? null;
if (!$proj_id) {
    die("Project ID missing.");
}

// Get project info
$projectQuery = "SELECT * FROM projects WHERE proj_id = ?";
$stmt = $conn->prepare($projectQuery);
$stmt->bind_param("i", $proj_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// Get assigned users
$assignedQuery = "SELECT assigned_students FROM assigned WHERE proj_id = ?";
$stmt = $conn->prepare($assignedQuery);
$stmt->bind_param("i", $proj_id);
$stmt->execute();
$assignedResult = $stmt->get_result();

$assignedUsernames = [];

if ($row = $assignedResult->fetch_assoc()) {
    $assigned_students_str = $row['assigned_students'];
    if (!empty($assigned_students_str)) {
        $assignedUsernames = array_map('trim', explode(',', $assigned_students_str));
    }
}
// After successful update
if ($stmt->execute()) {
    if (isset($_SESSION['Email'])) {
        require_once 'log1.php'; // ensure the log function is available

        $description = "Updated assigned users for project ID $proj_id: " . htmlspecialchars($_POST['usernames']);
        logTransaction('admin', $_SESSION['Email'], 'UPDATE_ASSIGNED_USERS', $description);
    }

    header("Location: Admin-project.php?status=updated");
    exit();
} else {
    echo "Error updating users.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DreamBoard Profile</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="update_user.css" />
</head>
<body>
<header>
    <div class="navbar">
        <img src="logo.png" alt="Logo" />
        <p>DreamBoard</p>
    </div>
</header>

<div class="container">
    <div class="sidebar">
        <ul>
            <li class="user">
                <a href="Admin.profile.php"><i class="fas fa-user"></i> User</a>
            </li>
            <li>
            <a href="#"><i class='bx bxs-bell'></i> Notification</a>
        </li>
            <li>
                <a href="Admin-Dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            </li>
            <li>
                <a href="Admin-project.php"><i class="fas fa-folder-open"></i>Class Works</a>
            </li>
            <li>
                <a href="Admin-calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a>
            </li>
            <li>
                <a href="Admin-forms.php"><i class="fas fa-clipboard-list"></i> Forms</a>
            </li>
            <li>
                <a href="Admin-about.php"><i class="fas fa-users"></i> About Us</a>
            </li>
        </ul>
        <a href="Admin-login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="profile-box">
            <h1>Add Assigned Users for <?= htmlspecialchars($project['project_name']) ?></h1>
            <div class="content">
                <!-- Wrapped form for better layout -->
                <div class="form-container">
                    <form method="POST" action="Admin-updateusernames.php">
                        <input type="hidden" name="proj_id" value="<?= $proj_id ?>">

                        <label>Assigned Usernames (comma-separated):</label>
                        <input type="text" name="usernames" value="<?= htmlspecialchars(implode(', ', $assignedUsernames)) ?>">

                        <button type="submit">Update Users</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
