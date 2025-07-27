<?php
session_start();
require 'db.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$classesPages = [
  'Admin-project.php',
  'team_proj.php',
  'Admin-teamproj.php',
  'Admin-create.php',
  'Admin-Createproj.php'
];

if (isset($_GET['proj_id']) && !empty($_GET['proj_id'])) {
    $proj_id = intval($_GET['proj_id']);
    $sql = "
    SELECT 
        p.team_name, 
        p.project_name AS project_project_name, 
        p.join_code,
        a.project_name AS assigned_project_name,
        a.ass_id,
        a.due_date
    FROM projects p
    LEFT JOIN assigned a ON p.proj_id = a.proj_id
    WHERE p.proj_id = ?
";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $proj_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
    } else {
        die("Project not found.");
    }

    $stmt->close();

    // Count submitted/pending statuses for each assignment
    $submissionCounts = [];
$ass_ids = array_filter(array_column($projects, 'ass_id'));

if (!empty($ass_ids)) {
    foreach ($ass_ids as $ass_id) {
        // Get the proj_id for this assignment
        $projIdStmt = $conn->prepare("SELECT proj_id FROM assigned WHERE ass_id = ?");
        $projIdStmt->bind_param("i", $ass_id);
        $projIdStmt->execute();
        $projIdStmt->bind_result($proj_id_for_ass);
        $projIdStmt->fetch();
        $projIdStmt->close();

        // Count total students in the project
        $totalStmt = $conn->prepare("SELECT COUNT(*) FROM project_members WHERE proj_id = ?");
        $totalStmt->bind_param("i", $proj_id_for_ass);
        $totalStmt->execute();
        $totalStmt->bind_result($total);
        $totalStmt->fetch();
        $totalStmt->close();

        // Count those who submitted for this assignment
        $submittedStmt = $conn->prepare("SELECT COUNT(DISTINCT userinfo_id) FROM student_submissions WHERE assigned_id = ?");
        $submittedStmt->bind_param("i", $ass_id);
        $submittedStmt->execute();
        $submittedStmt->bind_result($submitted);
        $submittedStmt->fetch();
        $submittedStmt->close();

        $submissionCounts[$ass_id] = [
            'submitted' => $submitted,
            'pending' => max(0, $total - $submitted)
        ];
    }
}

    }

 else {
    die("No project specified.");}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Team Project Details</title>
    <link rel="stylesheet" href="team_proj.css" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
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
      <a href="Admin.profile.php" class="<?= ($currentPage == 'Admin.profile.php') ? 'active' : '' ?>">
        <i class="fas fa-user"></i> Admin
      </a>
    </li>
    <li>
      <a href="#" class="<?= ($currentPage == '#') ? 'active' : '' ?>">
        <i class='bx bxs-bell'></i> Notification
      </a>
    </li>
    <li>
      <a href="Admin-Dashboard.php" class="<?= ($currentPage == 'Admin-Dashboard.php') ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="Admin-project.php" class="<?= in_array($currentPage, $classesPages) ? 'active' : '' ?>">
        <i class="fas fa-folder-open"></i> Classes
      </a>
    </li>
    <li>
      <a href="Admin-calendar.php" class="<?= ($currentPage == 'Admin-calendar.php') ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i> Calendar
      </a>
    </li>
    <li>
      <a href="Admin-forms.php" class="<?= ($currentPage == 'Admin-forms.php') ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> Forms
      </a>
    </li>
    <li>
      <a href="Admin-about.php" class="<?= ($currentPage == 'Admin-about.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> About Us
      </a>
    </li>
  </ul>
  <a href="Admin-login.php" class="logout <?= ($currentPage == 'Admin-login.php') ? 'active' : '' ?>">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</div>



    <div class="main-content">
        <div class="team_name">
            <h1><?= htmlspecialchars($projects[0]['team_name']) ?></h1>
            <p class="join-code">Join Code: <strong><?= htmlspecialchars($projects[0]['join_code']) ?></strong></p>
        </div>

        <div class="box">
            <h2>CLASS WORK</h2>

            <?php
            $hasAssignedWork = false;
            foreach ($projects as $project) {
                if (!empty($project['due_date'])) {
                    $hasAssignedWork = true;
                    break;
                }
            }
            ?>

            <?php if ($hasAssignedWork): ?>
                <?php foreach ($projects as $project): ?>
                    <?php if (!empty($project['due_date'])): ?>
                        <div class="inside">
                            <div class="inside-left">
                                <div class="title"><?= htmlspecialchars($project['assigned_project_name']) ?></div>
                                <div class="details"><?= htmlspecialchars($project['project_project_name']) ?></div>
                                <div class="due">
                                    <?= date("m/d/Y", strtotime($project['due_date'])) ?>
                                </div>
                            </div>
                            <div class="inside-right">
                                <div class="done">
                                    <a href="Admin-teamproj.php?ass_id=<?= $project['ass_id'] ?>&status=handed_in">
                                        <h4>Completed</h4>
                                        <div class="count">
                                            <?= $submissionCounts[$project['ass_id']]['submitted'] ?? 0 ?>
                                        </div>
                                    </a>
                                </div>
                                <div class="pending">
                                    <a href="Admin-teamproj.php?ass_id=<?= $project['ass_id'] ?>&status=pending">
                                        <h4>Pending</h4>
                                        <div class="count">
                                            <?= $submissionCounts[$project['ass_id']]['pending'] ?? 0 ?>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-work">No work has been assigned to this project yet.</p>
            <?php endif; ?>

            <button class="add-work"><a href="Admin-Createproj.php?proj_id=<?= $proj_id ?>">+ ADD WORK</a></button>
        </div>
    </div>
</div>

</body>
</html>
