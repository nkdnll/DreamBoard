<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['userinfo_ID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userinfo_ID'];
date_default_timezone_set("Asia/Manila");

$notifications = [];

/* 1️⃣ Admin Grade Submitted */
$sql_grades = "
    SELECT ast.graded_at AS activity_time,
           a.project_name,
           ast.grade,
           ss.assigned_id AS ass_id
    FROM student_submissions ss
    JOIN assigned a ON ss.assigned_id = a.ass_id
    JOIN assignment_students ast ON ast.assigned_id = a.ass_id
    WHERE ast.userinfo_id = '$userID'
      AND ast.grade IS NOT NULL
";
$res = mysqli_query($conn, $sql_grades);
if (!$res) {
    // Add error logging
    error_log("Query failed: " . mysqli_error($conn));
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        $notifications[] = [
            'message' => "Admin submitted a grade ({$row['grade']}) for '{$row['project_name']}'",
            'time' => $row['activity_time'],
            'link' => "content.php?ass_id={$row['ass_id']}"
        ];
    }
}

/* 2️⃣ New Project Assigned */
$sql_new_projects = "
    SELECT a.created_at AS activity_time,
           a.project_name,
           a.ass_id
    FROM assigned a
    JOIN assignment_students ast ON ast.assigned_id = a.ass_id
    WHERE ast.userinfo_id = '$userID'
";
$res = mysqli_query($conn, $sql_new_projects);
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = [
        'message' => "New project assigned: '{$row['project_name']}'",
        'time' => $row['activity_time'],
        'link' => "content.php?ass_id={$row['ass_id']}"
    ];
}

/* 3️⃣ Admin Comments / Replies */
$sql_comments = "
    SELECT c.created_at AS activity_time,
           a.project_name,
           c.comment_text,
           c.ass_id
    FROM comments c
    JOIN assigned a ON c.ass_id = a.ass_id
    JOIN assignment_students ast ON ast.assigned_id = a.ass_id
    WHERE ast.userinfo_id = '$userID'
      AND c.user_type = 'admin'
";
$res = mysqli_query($conn, $sql_comments);
while ($row = mysqli_fetch_assoc($res)) {
    $comment_preview = strlen($row['comment_text']) > 50 ? substr($row['comment_text'], 0, 50) . "..." : $row['comment_text'];
    $notifications[] = [
        'message' => "Admin replied on '{$row['project_name']}': \"{$comment_preview}\"",
        'time' => $row['activity_time'],
        'link' => "content.php?ass_id={$row['ass_id']}"
    ];
}

/* Mark unread count */
$lastRead = isset($_SESSION['last_read_notification']) ? $_SESSION['last_read_notification'] : '2000-01-01 00:00:00';
$unreadCount = 0;
foreach ($notifications as $note) {
    if (strtotime($note['time']) > strtotime($lastRead)) {
        $unreadCount++;
    }
}

/* Sort by newest */
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

/* Group by date */
$grouped = [];
foreach ($notifications as $note) {
    $dateKey = date('Y-m-d', strtotime($note['time']));
    $grouped[$dateKey][] = $note;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Notifications</title>
    <link rel="stylesheet" href="user_notification.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
      .notification-badge {
          background: red;
          color: white;
          font-size: 12px;
          padding: 2px 6px;
          border-radius: 12px;
          vertical-align: top;
          margin-left: 6px;
      }
      .mark-read-btn {
          margin-left: 15px;
          padding: 6px 12px;
          background: #4CAF50;
          color: white;
          border: none;
          cursor: pointer;
          border-radius: 4px;
      }
    </style>
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
      <a href="profile.php" class="<?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
        <i class="fas fa-user"></i> User
      </a>
    </li>
    <li>
      <a href="user_notification.php">
    <i class='bx bxs-bell'></i> Notification
    <span id="sidebar-unread-count" class="notification-badge"><?= $unreadCount ?></span>
</a>
    </li>
    <li>
      <a href="dashboard.php" class="<?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Dashboard
      </a>
    </li>
    <li>
        <a href="Projects.php" class="<?= in_array($currentPage, ['Projects.php', 'content.php', 'completed.php']) ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i> Class Works
        </a>
    </li>
    <li>
      <a href="calendar (1).php" class="<?= ($currentPage == 'calendar (1).php') ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i> Calendar
      </a>
    </li>
    <li>
      <a href="forms.php" class="<?= ($currentPage == 'forms.php') ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> Forms
      </a>
    </li>
    <li>
      <a href="about.php" class="<?= ($currentPage == 'about.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> About Us
      </a>
    </li>
  </ul>
  <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
 

<div class="main-content">
    <div class="profile-box">
       <h2>
          Notifications
          <span id="notification-count">(<?= $unreadCount ?>)</span>
          <button id="mark-read-btn" 
        class="mark-read-btn" 
        <?= intval($unreadCount) === 0 ? 'disabled style="background: grey; cursor: not-allowed;"' : '' ?>>
            Mark all as read
            </button>

       </h2>
        <div class="notification-scrollable">
                    <?php if (!empty($grouped)): ?>
            <?php foreach ($grouped as $date => $items): ?>
                <h3 class="notification-date"><?= date('F d, Y', strtotime($date)) ?></h3>
                <div class="notification-list">
                    <?php foreach ($items as $note): ?>
                        <?php 
                        $type = '';
                        $colorClass = '';
                        if (strpos($note['message'], 'grade') !== false) {
                            $type = 'grade';
                            $colorClass = 'google-green';
                        } elseif (strpos($note['message'], 'New project') !== false) {
                            $type = 'project';
                            $colorClass = 'google-blue';
                        }
                        $isUnread = (strtotime($note['time']) > strtotime($lastRead));
                        ?>
                        <div class="notification-item <?= $type ?> <?= $colorClass ?> <?= $isUnread ? 'unread' : '' ?>">
                            <div class="notification-icon">
                                <?php if ($type == 'grade'): ?>
                                    <i class='bx bx-check-circle'></i>
                                <?php elseif ($type == 'project'): ?>
                                    <i class='bx bx-folder-plus'></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <a href="user_markread.php?time=<?= urlencode($note['time']) ?>&redirect=<?= urlencode($note['link']) ?>">
    <?= htmlspecialchars($note['message']) ?>
</a>
                                <div class="notification-time"><?= date('h:i A', strtotime($note['time'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class='bx bx-bell-off'></i>
                <p>No notifications found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<script>
document.getElementById('mark-read-btn').addEventListener('click', function() {
    fetch('user_mark_read.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('sidebar-unread-count').style.display = 'none';
            document.getElementById('notification-count').textContent = '(0)';
            const btn = document.getElementById('mark-read-btn');
            btn.disabled = true;
            btn.style.background = 'grey';
            btn.style.cursor = 'not-allowed';
            alert('All notifications marked as read.');
        }
    })
    .catch(error => {
        alert('Error marking notifications: ' + error.message);
    });
});
window.addEventListener("pageshow", function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

</script>
</div>
</body>
</html> 