=<?php
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
       a.ass_id,
       admininfo.INSTRUCTOR AS admin_name
FROM assigned a
JOIN projects p ON a.proj_id = p.proj_id
JOIN admininfo ON p.admininfoID = admininfo.admininfoID
JOIN assignment_students ast ON ast.assigned_id = a.ass_id
JOIN userinfo u ON u.userinfo_ID = ast.userinfo_id
WHERE ast.userinfo_id = '$userID'
  AND ast.grade IS NOT NULL

";


$res = mysqli_query($conn, $sql_grades);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $notifications[] = [
            'message' => "{$row['admin_name']} submitted a grade ({$row['grade']}) for '{$row['project_name']}'",
            'time' => $row['activity_time'],
            'link' => "content.php?ass_id={$row['ass_id']}"
        ];
    }
}

/* 2️⃣ New Project Assigned */
$sql_new_projects = "
    SELECT a.created_at AS activity_time,
       a.project_name,
       a.ass_id,
       admininfo.INSTRUCTOR AS admin_name
FROM assigned a
JOIN projects p ON a.proj_id = p.proj_id
JOIN admininfo ON p.admininfoID = admininfo.admininfoID
JOIN assignment_students ast ON ast.assigned_id = a.ass_id
WHERE ast.userinfo_id = '$userID'

";
$res = mysqli_query($conn, $sql_new_projects);
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = [
        'message' => "{$row['admin_name']} assigned a new project: '{$row['project_name']}'",
        'time' => $row['activity_time'],
        'link' => "content.php?ass_id={$row['ass_id']}"
    ];
}

/* 3️⃣ Admin Comments / Replies */
$sql_comments = "
    SELECT c.created_at AS activity_time,
       a.project_name,
       c.comment_text,
       c.ass_id,
       admininfo.INSTRUCTOR AS admin_name
FROM comments c
JOIN assigned a ON c.ass_id = a.ass_id
JOIN projects p ON a.proj_id = p.proj_id
JOIN admininfo ON p.admininfoID = admininfo.admininfoID
JOIN assignment_students ast ON ast.assigned_id = a.ass_id
WHERE ast.userinfo_id = '$userID'
  AND c.user_type = 'admin'

";
$res = mysqli_query($conn, $sql_comments);
while ($row = mysqli_fetch_assoc($res)) {
    $comment_preview = strlen($row['comment_text']) > 50 ? substr($row['comment_text'], 0, 50) . "..." : $row['comment_text'];
    $notifications[] = [
        'message' => "{$row['admin_name']} replied on '{$row['project_name']}': \"{$comment_preview}\"",
        'time' => $row['activity_time'],
        'link' => "content.php?ass_id={$row['ass_id']}"
    ];
}

/* Mark unread count */
$resLastRead = mysqli_query($conn, "SELECT last_read_notification FROM userinfo WHERE userinfo_ID = '$userID'");
$rowLastRead = mysqli_fetch_assoc($resLastRead);
$lastRead = $rowLastRead ? $rowLastRead['last_read_notification'] : '2000-01-01 00:00:00';
$_SESSION['last_read_notification'] = $lastRead;

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
      <a href="profile.php">
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
      <a href="dashboard.php">
        <i class="fas fa-th-large"></i> Dashboard
      </a>
    </li>
    <li>
        <a href="Projects.php">
            <i class="fas fa-folder-open"></i> Class Works
        </a>
    </li>
    <li>
      <a href="calendar (1).php">
        <i class="fas fa-calendar-alt"></i> Calendar
      </a>
    </li>
    <li>
      <a href="forms.php">
        <i class="fas fa-clipboard-list"></i> Forms
      </a>
    </li>
    <li>
      <a href="about.php">
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
                                    <a href="#" 
                                       class="single-mark-read" 
                                       data-time="<?= htmlspecialchars($note['time']) ?>" 
                                       data-link="<?= htmlspecialchars($note['link']) ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    const sidebarUnreadCount = document.getElementById('sidebar-unread-count');
    const notificationCount = document.getElementById('notification-count');
    const markReadBtn = document.getElementById('mark-read-btn');

    // Mark all as read
    markReadBtn.addEventListener('click', function() {
        fetch('user_mark_read.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                sidebarUnreadCount.style.display = 'none';
                notificationCount.textContent = '(0)';
                markReadBtn.disabled = true;
                markReadBtn.style.background = 'grey';
                markReadBtn.style.cursor = 'not-allowed';
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
            }
        })
        .catch(err => alert('Error marking notifications: ' + err.message));
    });

    // Mark single notification as read
    document.querySelectorAll('.single-mark-read').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationItem = this.closest('.notification-item');
            const notifTime = this.dataset.time;
            const redirectUrl = this.dataset.link;

            fetch(`user_single_mark_read.php?time=${encodeURIComponent(notifTime)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    notificationItem.classList.remove('unread');
                    let currentCount = parseInt(notificationCount.textContent.replace(/\D/g, '')) || 0;
                    if (currentCount > 0) {
                        currentCount--;
                        notificationCount.textContent = `(${currentCount})`;
                        sidebarUnreadCount.textContent = currentCount;
                        if (currentCount === 0) {
                            sidebarUnreadCount.style.display = 'none';
                            markReadBtn.disabled = true;
                            markReadBtn.style.background = 'grey';
                            markReadBtn.style.cursor = 'not-allowed';
                        }
                    }
                }
                setTimeout(() => window.location.href = redirectUrl, 100);
            })
            .catch(err => {
                alert('Error marking notification: ' + err.message);
                window.location.href = redirectUrl; // fallback
            });
        });
    });

    // Prevent cached version from showing after back navigation
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
});
</script>
</body>
</html>
