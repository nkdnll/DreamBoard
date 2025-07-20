<?php
session_start();
require 'db.php';

if (isset($_GET['proj_id']) && !empty($_GET['proj_id'])) {
    $proj_id = intval($_GET['proj_id']);
    $sql = "
        SELECT 
            p.team_name, 
            p.project_name AS project_project_name, 
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
        $inClause = implode(',', array_fill(0, count($ass_ids), '?'));
        $count_sql = "
            SELECT 
                assigned_id,
                status,
                COUNT(*) AS count 
            FROM assignment_students 
            WHERE assigned_id IN ($inClause)
            GROUP BY assigned_id, status
        ";

        $count_stmt = $conn->prepare($count_sql);
        if ($count_stmt) {
            $count_stmt->bind_param(str_repeat('i', count($ass_ids)), ...$ass_ids);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();

            while ($row = $count_result->fetch_assoc()) {
                $aid = $row['assigned_id'];
                $status = strtolower($row['status']);
                $count = $row['count'];

                if (!isset($submissionCounts[$aid])) {
                    $submissionCounts[$aid] = ['submitted' => 0, 'pending' => 0];
                }

                // Accept both 'completed' and 'submitted' as completed
                if (in_array($status, ['submitted', 'completed'])) {
                    $submissionCounts[$aid]['submitted'] += $count;
                } else {
                    $submissionCounts[$aid]['pending'] += $count;
                }
            }

            $count_stmt->close();
        }
    }

} else {
    die("No project specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Team Project Details</title>
    <link rel="stylesheet" href="team_proj.css" />
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
            <li class="user"><a href="Admin.profile.php"><i class="fas fa-user"></i> User</a></li>
            <li><a href="Admin-Dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="Admin-project.php"><i class="fas fa-folder-open"></i> Project</a></li>
            <li><a href="Admin-calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="Admin-forms.php"><i class="fas fa-clipboard-list"></i> Forms</a></li>
            <li><a href="Admin-about.php"><i class="fas fa-users"></i> About Us</a></li>
        </ul>
        <a href="Admin-login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="team_name">
            <h1><?= htmlspecialchars($projects[0]['team_name']) ?></h1>
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
