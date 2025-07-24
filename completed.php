<?php
session_start();
include 'log1.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$connection = new mysqli("localhost", "root", "", "projectmanagement");

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$adminID = $_SESSION['admininfoID'];

$sql = "
    SELECT 
        a.ass_id,
        a.project_name AS assigned_proj_name, 
        p.project_name AS proj_name, 
        ai.INSTRUCTOR,
        s.username,
        s.status
    FROM assigned a
    JOIN projects p ON a.proj_id = p.proj_id
    JOIN admininfo ai ON p.admininfoID = ai.admininfoID
    JOIN assignment_students s ON s.assigned_id = a.ass_id
    WHERE ai.admininfoID = ? AND s.status = 'Completed'
";

$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $adminID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DreamBoard Completed</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="completed.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <script src="script.js" defer></script>
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
            <li class="user"><a href="profile.php"><i class="fas fa-user"></i>User</a></li>
            <li>
            <a href="#"><i class='bx bxs-bell'></i> Notification</a>
        </li>
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="Projects.php"><i class="fas fa-folder-open"></i>Class Works</a></li>
            <li><a href="calendar (1).php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="forms.php"><i class="fas fa-clipboard-list"></i> Forms</a></li>
            <li><a href="about.php"><i class="fas fa-users"></i> About Us</a></li>
        </ul>
        <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>


<div class="main-content">
    <h1>Projects</h1>

    <div class="content1">

    <div class="assigned">
        <h2 class="assbtn"><a href="Projects.php">Assigned</a></h2>
        <div class="donebox"><h2 id="doneBtn">Completed</h2></div>
    </div>

    <div class="assigned-proj">
        <?php
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
        <div class="content">
    <form method="POST" action="update_status.php">
        <input type="hidden" name="ass_id" value="<?= (int)$row['ass_id'] ?>">
        <div class="card-content">
            <a href="content.php?ass_id=<?= (int)$row['ass_id'] ?>">
                <div class="project-details">
                    <h3 class="project-title"><?= htmlspecialchars($row['assigned_proj_name']) ?></h3>
                    <p class="code"><strong><?= htmlspecialchars($row['proj_name']) ?></strong></p>
                    <p class="instructor"><?= htmlspecialchars($row['INSTRUCTOR']) ?></p>
                </div>
            </a>
            <div class="status-box">
                <select name="status" onchange="this.form.submit()" class="status-dropdown">
                    <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $row['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
        </div>
    </form>
</div>        <?php
            endwhile;
        else:
            echo "<p>No completed projects found.</p>";
        endif;
        $connection->close();
        ?>
    </div>
</div>
    </div>
</div>
</body>
</html>
