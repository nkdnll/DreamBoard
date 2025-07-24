<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$connection = new mysqli("localhost", "root", "", "projectmanagement");

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$studentID = $_SESSION['userinfo_ID']; // ✅ get logged-in student

$sql = "
    SELECT 
        a.ass_id,
        a.project_name AS assigned_proj_name, 
        p.project_name AS proj_name, 
        ai.INSTRUCTOR,
        MIN(s.status) AS status
    FROM assigned a
    JOIN projects p ON a.proj_id = p.proj_id
    JOIN admininfo ai ON p.admininfoID = ai.admininfoID
    JOIN project_members pm ON pm.proj_id = p.proj_id
    LEFT JOIN assignment_students s ON s.assigned_id = a.ass_id AND s.userinfo_ID = pm.userinfo_id
    WHERE pm.userinfo_id = ?
    GROUP BY a.ass_id, a.project_name, p.project_name, ai.INSTRUCTOR
    HAVING status IS NULL OR status != 'Completed'
";

$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $studentID); // not adminID anymore


$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DreamBoard Profile</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="projects.css" />
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
            <li class="user"><a href="profile.php"><i class="fas fa-user"></i> User</a></li>
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
    <div class="main-head">
      <h1>Projects</h1>
        <?php if (isset($_GET['joined'])): ?>
    <div class="join-msg">
        <?php
        switch ($_GET['joined']) {
            case '1':
                echo "<p style='color:green;'>You’ve joined the project successfully!</p>";
                break;
            case 'exists':
                echo "<p style='color:orange;'>You already joined this project.</p>";
                break;
            case 'invalid':
                echo "<p style='color:red;'>Invalid join code. Please try again.</p>";
                break;
        }
        ?>
    </div>
<?php endif; ?>

         <!-- 🔐 Join Project via Code -->
    <div class="join-class">
        <h3>Join a Project </h3>
        <form method="POST" action="join_project.php">
            <input type="text" name="join_code" placeholder="Enter Join Code" required />
            <button type="submit">Join</button>
        </form>
    </div>

</div>
        <div class="content1">
            <div class="assigned">
                <h2>Assigned</h2>
                <div class="donebox">
                    <h2 id="doneBtn"><a href="completed.php">Completed</a></h2>
                </div>
            </div>

            <div class="assigned-proj">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="card-content-wrapper">
  <div class="card-content">
    
    <!-- Link only wraps the project info -->
    <a href="content.php?ass_id=<?= (int)$row['ass_id'] ?>" class="project-details-link">
      <div class="project-details">
        <h3 class="project-title"><?= htmlspecialchars($row['assigned_proj_name']) ?></h3>
        <p class="code"><strong><?= htmlspecialchars($row['proj_name']) ?></strong></p>
        <p class="instructor"><?= htmlspecialchars($row['INSTRUCTOR']) ?></p>
      </div>
    </a>

    <!-- Form stays outside the <a> -->
    <div class="status-box">
      <form method="POST" action="update-status.php">
        <input type="hidden" name="ass_id" value="<?= (int)$row['ass_id'] ?>">
        <select name="status" onchange="this.form.submit()" class="status-dropdown">
          <option value=""><?= htmlspecialchars($row['status'] ?? 'Not Set') ?></option>
          <option value="In Progress" <?= ($row['status'] == 'In Progress') ? 'selected' : '' ?>>In Progress</option>
          <option value="Completed" <?= ($row['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
        </select>
      </form>
    </div>

  </div>
</div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No assigned projects found.</p>
                <?php endif; ?>
                <?php $connection->close(); ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
