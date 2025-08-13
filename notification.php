<?php
session_start();

// Cache control headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Database connection
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
include 'log1.php';

// Security checks
if (!$conn) die("Database connection failed: " . mysqli_connect_error());
if (!isset($_SESSION['Email'])) header("Location: Admin-login.php") && exit();

// Get admin info
$email = $_SESSION['Email'];
$query = "SELECT admininfoID, INSTRUCTOR, PROFILE_PIC FROM admininfo WHERE Email = '$email'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $_SESSION['admininfoID'] = $row['admininfoID'];
    $_SESSION['admin_name'] = $row['INSTRUCTOR'];
    $_SESSION['profile_pic'] = $row['PROFILE_PIC'];
}

$adminID = $_SESSION['admininfoID'];
date_default_timezone_set("Asia/Manila");

// Fetch notifications
$notifications = [];

// 1. File Uploads
$sql_uploads = "SELECT ss.uploaded_at AS activity_time, 
       CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS user_name,
       ss.file_name, a.project_name, a.ass_id
    FROM student_submissions ss
    JOIN userinfo u ON ss.userinfo_id = u.userinfo_ID
    JOIN assigned a ON ss.assigned_id = a.ass_id
    JOIN projects p ON p.proj_id = a.proj_id
    WHERE p.admininfoID = '$adminID'";
$res = mysqli_query($conn, $sql_uploads);
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = [
        'message' => "{$row['user_name']} uploaded '{$row['file_name']}' for '{$row['project_name']}'",
        'time' => $row['activity_time'],
        'link' => "Admin-teamproj.php?ass_id={$row['ass_id']}",
        'type' => 'file-upload'
    ];
}

// 2. Comments
$sql_comments = "SELECT c.created_at AS activity_time, 
       CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS user_name,
       c.comment_text, a.project_name, a.ass_id
    FROM comments c
    JOIN userinfo u ON c.userinfo_id = u.userinfo_ID
    JOIN assigned a ON c.ass_id = a.ass_id
    JOIN projects p ON p.proj_id = a.proj_id
    WHERE p.admininfoID = '$adminID'";
$res = mysqli_query($conn, $sql_comments);
while ($row = mysqli_fetch_assoc($res)) {
    $comment_preview = strlen($row['comment_text']) > 50 ? substr($row['comment_text'], 0, 50) . "..." : $row['comment_text'];
    $notifications[] = [
        'message' => "{$row['user_name']} commented on '{$row['project_name']}': \"{$comment_preview}\"",
        'time' => $row['activity_time'],
        'link' => "Admin-teamproj.php?ass_id={$row['ass_id']}",
        'type' => 'comment'
    ];
}

// 3. Project Joins
$sql_joins = "SELECT CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS user_name,
       p.project_name, MAX(a.created_at) AS activity_time, a.ass_id
    FROM project_members pm
    JOIN userinfo u ON pm.userinfo_id = u.userinfo_ID
    JOIN projects p ON pm.proj_id = p.proj_id
    LEFT JOIN assigned a ON a.proj_id = p.proj_id
    WHERE p.admininfoID = '$adminID'
    GROUP BY pm.id, pm.proj_id, pm.userinfo_id, user_name, p.project_name, a.ass_id";
$res = mysqli_query($conn, $sql_joins);
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = [
        'message' => "{$row['user_name']} joined project '{$row['project_name']}'",
        'time' => $row['activity_time'] ?? date("Y-m-d H:i:s"),
        'link' => isset($row['ass_id']) ? "Admin-teamproj.php?ass_id={$row['ass_id']}" : "#",
        'type' => 'project-join'
    ];
}

// Get last read time
$res = mysqli_query($conn, "SELECT last_read_time FROM admininfo WHERE admininfoID = '$adminID'");
$lastRead = '2000-01-01 00:00:00';
if ($res && mysqli_num_rows($res) > 0) {
    $lastRead = mysqli_fetch_assoc($res)['last_read_time'];
}

// Process notifications
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$grouped = [];
$unreadCount = 0;

foreach ($notifications as $note) {
    $dateKey = date('Y-m-d', strtotime($note['time']));
    $grouped[$dateKey][] = $note;
    if (strtotime($note['time']) > strtotime($lastRead)) $unreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="notification.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .notification-badge {
            background: red;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 12px;
            margin-left: 6px;
        }
        .mark-read-btn {
            margin-left: 15px;
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .mark-read-btn:disabled {
            background: grey;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <img src="logo.png" alt="Logo">
            <p>DreamBoard</p>
        </div>
    </header>

    <div class="container">
        <div class="sidebar">
            <ul>
                <li><a href="Admin.profile.php"><i class="fas fa-user"></i> Admin</a></li>
                <li>
                    <a href="notification.php" class="active">
                        <i class='bx bxs-bell'></i> Notification
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
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
            <div class="profile-box">
                <h2>
                    Notifications
                    <span id="notification-count">(<?= $unreadCount ?>)</span>
                    <button id="mark-read-btn" class="mark-read-btn" <?= $unreadCount === 0 ? 'disabled' : '' ?>>
                        Mark all as read
                    </button>
                </h2>

                <div class="notification-scrollable">
                    <?php if (!empty($grouped)): ?>
                        <?php foreach ($grouped as $date => $items): ?>
                            <h3 class="notification-date"><?= date('F d, Y', strtotime($date)) ?></h3>
                            <div class="notification-list">
                                <?php foreach ($items as $note): ?>
                                    <?php $isUnread = strtotime($note['time']) > strtotime($lastRead); ?>
                                    <div class="notification-item <?= $isUnread ? 'unread' : '' ?>">
                                        <div class="notification-icon">
                                            <?php switch($note['type']) {
                                                case 'file-upload': echo '<i class="bx bx-file"></i>'; break;
                                                case 'comment': echo '<i class="bx bx-comment"></i>'; break;
                                                case 'project-join': echo '<i class="bx bx-user-plus"></i>'; break;
                                            } ?>
                                        </div>
                                        <div class="notification-content">
                                            <a href="<?= $note['link'] ?>" 
                                                class="notification-link" 
                                                data-time="<?= htmlspecialchars($note['time']) ?>"
                                                data-type="<?= htmlspecialchars($note['type']) ?>">
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
        // Mark all as read
        document.getElementById('mark-read-btn').addEventListener('click', async function() {
            try {
                const response = await fetch('mark_read.php', { method: 'POST' });
                const data = await response.json();
                
                if (data.success) {
                    // Update UI
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    document.getElementById('notification-count').textContent = '(0)';
                    document.querySelector('.notification-badge')?.remove();
                    this.disabled = true;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Handle individual notification clicks
        document.querySelectorAll('.notification-link').forEach(link => {
            link.addEventListener('click', async function(e) {
                e.preventDefault();
                const url = this.href;
                const time = this.dataset.time;
                
                try {
                    // Mark as read
                    await fetch('mark_single_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `time=${encodeURIComponent(time)}`
                    });
                    
                    // Update UI
                    const item = this.closest('.notification-item');
                    if (item.classList.contains('unread')) {
                        item.classList.remove('unread');
                        updateUnreadCount();
                    }
                    
                    // Redirect
                    window.location.href = url;
                } catch (error) {
                    console.error('Error:', error);
                    window.location.href = url; // Redirect anyway
                }
            });
        });

        function updateUnreadCount() {
            const count = document.querySelectorAll('.notification-item.unread').length;
            document.getElementById('notification-count').textContent = `(${count})`;
            
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = count;
                if (count === 0) badge.remove();
            }
            
            if (count === 0) {
                document.getElementById('mark-read-btn').disabled = true;
            }
        }
    </script>
</body>
</html>