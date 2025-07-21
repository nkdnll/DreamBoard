<?php
session_start();
require_once 'log1.php';
require 'db.php';

if (!isset($_SESSION['Email'])) {
    // Redirect to login or show error
    $_SESSION['admininfoID'] = $row['admininfoID'];
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM projects";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="Admin-project.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Admin Projects</title>
</head>
<body>
<header>
    <div class="navbar">
        <img src="logo.png" width="110px" height="70px" alt="Logo">
        <p>DreamBoard</p>
    </div>
</header>

<div class="container">
    <div class="sidebar">
        <ul>
            <li class="user"><a href="Admin.profile.php"><i class="fas fa-user"></i> Admin</a></li>
            <li>
            <a href="#"><i class='bx bxs-bell'></i> Notification</a>
        </li>
            <li><a href="Admin-Dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="Admin-project.php"><i class="fas fa-folder-open"></i> Classes</a></li>
            <li><a href="Admin-calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="Admin-forms.php"><i class="fas fa-clipboard-list"></i> Forms</a></li>
            <li><a href="Admin-about.php"><i class="fas fa-users"></i> About Us</a></li>
        </ul>
        <a href="Admin-login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <h1>Classes</h1>
        <div class="row">
            <?php while ($project = $result->fetch_assoc()): ?>
                <div class="project">
    <a href="team_proj.php?proj_id=<?= urlencode($project['proj_id']) ?>">
        <h2 class="title"><?= htmlspecialchars($project['project_name']) ?></h2>
        <p class="details"><?= htmlspecialchars($project['team_description']) ?></p>
        <hr>
        <h3 class="team-name"><?= htmlspecialchars($project['team_name']) ?></h3>
    </a>
    

    <!-- Delete project button -->
    <form method="POST" action="delete_project.php" style="display:inline;">
        <input type="hidden" name="proj_id" value="<?= htmlspecialchars($project['proj_id']) ?>">
        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this project?')">Delete</button>
    </form>
</div>
                
            <?php endwhile; ?>
        </div>

        <div class="btn">
            <a href="Admin-create.php">
                <h2>+Add Classes</h2>
            </a>
            
        </div>
    </div>
</div>
</body>
</html>