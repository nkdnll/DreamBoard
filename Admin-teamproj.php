<?php
session_start();
include 'log1.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$classesPages = [
  'Admin-project.php',
  'team_proj.php',
  'Admin-teamproj.php',
  'Admin-create.php',
  'Admin-Createproj.php'
];

$connection = new mysqli("localhost", "root", "", "projectmanagement");
if ($connection->connect_error) die("Connection failed: " . $connection->connect_error);

$ass_id = isset($_GET['ass_id']) ? (int)$_GET['ass_id'] : null;
$students = [];

// Insert Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'], $_POST['ass_id'], $_POST['recipient_id'])) {
    $txt = trim($_POST['comment_text']);
    $aid = (int)$_POST['ass_id'];
    $rid = (int)$_POST['recipient_id'];
    
if (isset($_SESSION['admininfoID'])) {
    $author = $_SESSION['admininfoID'];
    $type = 'admin';
} else {
    $author = $_SESSION['userinfo_ID'];
    $type = 'student';
}
    if ($txt !== '' && $aid) {
        $stmt = $connection->prepare("INSERT INTO comments (ass_id, recipient_id, userinfo_id, user_type, comment_text, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiss", $aid, $rid, $author, $type, $txt);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

if ($ass_id) {
    // Load students
    $stmt = $connection->prepare("
    SELECT u.userinfo_ID, CONCAT(u.FIRSTNAME, ' ', u.MIDDLENAME, ' ', u.LASTNAME) fullname,
           s.status, s.grade
    FROM assignment_students s
    JOIN userinfo u ON s.userinfo_ID = u.userinfo_ID
    WHERE s.assigned_id = ?

    UNION

    SELECT u.userinfo_ID, CONCAT(u.FIRSTNAME, ' ', u.MIDDLENAME, ' ', u.LASTNAME) fullname,
           NULL AS status, NULL AS grade
    FROM project_members pm
    JOIN assigned a ON a.proj_id = pm.proj_id
    JOIN userinfo u ON u.userinfo_ID = pm.userinfo_id
    WHERE a.ass_id = ?
");
$stmt->bind_param("ii", $ass_id, $ass_id); // ✅ Correct: matches 2 placeholders

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[$row['userinfo_ID']] = [
            'fullname' => $row['fullname'],
            'status' => $row['status'],
            'grade' => $row['grade'],
            'submissions' => [],
        ];
    }
    $stmt->close();

    // Load submissions
    $stmt = $connection->prepare("SELECT userinfo_id, file_name, file_path, uploaded_at FROM student_submissions WHERE assigned_id=?");
    $stmt->bind_param("i", $ass_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($file = $result->fetch_assoc()) {
        $uid = $file['userinfo_id'];
        $file['file_size'] = file_exists($file['file_path']) ? filesize($file['file_path']) : 0;
        if (isset($students[$uid])) $students[$uid]['submissions'][] = $file;
    }
    $stmt->close();
}
$project_name = 'Project'; // default fallback

if ($ass_id) {
    $stmt = $connection->prepare("SELECT project_name FROM assigned WHERE ass_id = ?");
    $stmt->bind_param("i", $ass_id);
    $stmt->execute();
    $stmt->bind_result($project_name);
    $stmt->fetch();
    $stmt->close();
}
$totalCount = 0;
$completedCount = 0;

if ($ass_id) {
    // Count total students from project_members
    $stmt = $connection->prepare("
        SELECT COUNT(DISTINCT pm.userinfo_id)
        FROM project_members pm
        JOIN assigned a ON a.proj_id = pm.proj_id
        WHERE a.ass_id = ?
    ");
    $stmt->bind_param("i", $ass_id);
    $stmt->execute();
    $stmt->bind_result($totalCount);
    $stmt->fetch();
    $stmt->close();

    // Count students with a 'Completed' status in student_submissions
    $stmt = $connection->prepare("
        SELECT COUNT(DISTINCT ss.userinfo_id)
        FROM student_submissions ss
        WHERE ss.assigned_id = ?
    ");
    $stmt->bind_param("i", $ass_id);
    $stmt->execute();
    $stmt->bind_result($completedCount);
    $stmt->fetch();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Project View</title>
    <link rel="stylesheet" href="Admin-teamproj.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        .hidden { display: none; }
        .student-item { cursor: pointer; }
        .student-item.active { background: #f3f3f3; }
    </style>
</head>
<body>
<header>
    <div class="navbar">
        <img src="logo.png" width="110" height="70" alt="Logo">
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
        <div class="head">
        <h1><?= htmlspecialchars($project_name) ?></h1>
        <span style="font-size: 30px; font-weight: normal; color: #666;"> 
        <?= $completedCount ?> | <?= $totalCount ?>
         </span>
        </div>
        <div class="wrapper">
            <div class="container1">
                <h1>TEAM PROJECTS</h1><hr>
                <div class="title"><h2 class="NAME">NAME</h2><h2 class="STATUS">STATUS</h2></div><hr>
                <?php foreach ($students as $uid => $s): ?>
                    <div class="content student-item" data-uid="<?= $uid ?>">
                        <p class="account"><?= htmlspecialchars($s['fullname']) ?></p>
                        <p class="status"><?= htmlspecialchars($s['status']) ?></p>
                    </div><hr>
                <?php endforeach; ?>
            </div>

            <?php $first = true; foreach ($students as $uid => $s): ?>
                <?php
                    $stmt = $connection->prepare("
                        SELECT c.comment_text, c.user_type, c.created_at, c.userinfo_id, 
                            u.FIRSTNAME sf, u.MIDDLENAME sm, u.LASTNAME sl, 
                            a.INSTRUCTOR an 
                        FROM comments c 
                        LEFT JOIN userinfo u ON c.user_type='student' AND c.userinfo_id=u.userinfo_ID 
                        LEFT JOIN admininfo a ON c.user_type='admin' AND c.userinfo_id=a.admininfoID 
                        WHERE c.ass_id=? AND (c.recipient_id=? OR c.userinfo_id=?)
                        ORDER BY c.created_at ASC
                    ");
                    $stmt->bind_param("iii", $ass_id, $uid, $uid);
                $stmt->execute();
                $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                ?>
                <div class="container3 student-panel <?= $first ? '' : 'hidden' ?>" id="student-<?= $uid ?>">
                    <div class="student-submissions">
                        <h3><?= htmlspecialchars($s['fullname']) ?></h3>
                        <?php if ($s['submissions']): foreach ($s['submissions'] as $f): ?>
                            <p style="margin-left:20px">
                                📎 <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank"> <?= htmlspecialchars($f['file_name']) ?></a><br>
                                <small style="margin-left:25px">Size: <?= round($f['file_size']/1024, 2) ?> KB | Uploaded: <?= htmlspecialchars($f['uploaded_at']) ?></small>
                            </p>
                        <?php endforeach; else: ?>
                            <p style="margin-left:20px">No submissions</p>
                        <?php endif; ?>
                        <form method="POST" action="grade_submission.php" style="margin:10px 0 0 20px">
                            <input type="hidden" name="assigned_id" value="<?= $ass_id ?>">
                            <input type="hidden" name="userinfo_id" value="<?= $uid ?>">
                            <label for="grade_<?= $uid ?>" id="grade">Grade:</label>
                            <input type="number" class=grade name="grade" id="grade_<?= $uid ?>" min="0" max="100" value="<?= htmlspecialchars($s['grade'] ?? '') ?>" required>
                            <button type="submit" id="grade-submit">Submit Grade</button>
                        </form>
                    </div>

                    <div class="comment-box">
                        <div class="comment-header"><i class="fas fa-comments"></i> Conversation</div>
                        <?php if ($comments): foreach ($comments as $c): 
                            $uname = ($c['user_type'] === 'student') ? trim(($c['sf'] ?? '') . ' ' . ($c['sm'] ? $c['sm'] . ' ' : '') . ($c['sl'] ?? '')) ?: 'Student' : (trim($c['an'] ?? '') ?: ($_SESSION['admin_name'] ?? 'Admin'));
                        ?>
                            <div class="comment-item <?= htmlspecialchars($c['user_type']) ?>">
                                <div class="comment-username"><?= htmlspecialchars($uname) ?></div>
                                <div class="comment-text"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></div>
                                <div class="comment-time"><?= date('M d, Y h:i A', strtotime($c['created_at'])) ?></div>
                            </div>
                        <?php endforeach; else: ?>
                            <p>No comments yet.</p>
                        <?php endif; ?>
                        <form method="POST" class="comment-input">
                            <textarea name="comment_text" placeholder="Add a comment..." required></textarea>
                            <input type="hidden" name="ass_id" value="<?= $ass_id ?>">
                            <input type="hidden" name="recipient_id" value="<?= $uid ?>">
                            <button type="submit" title="Send comment">&#9658;</button>
                        </form>
                    </div>
                </div>
            <?php $first = false; endforeach; ?>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.student-item').forEach(item => {
    item.addEventListener('click', () => {
        const uid = item.dataset.uid;
        document.querySelectorAll('.student-item').forEach(i => i.classList.toggle('active', i === item));
        document.querySelectorAll('.student-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('student-' + uid).classList.remove('hidden');
    });
});
</script>
</body>
</html>
