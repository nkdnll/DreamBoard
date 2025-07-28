<?php
session_start();
include 'log1.php';

$currentPage = basename($_SERVER['PHP_SELF']);

$connection = new mysqli("localhost", "root", "", "projectmanagement");
if ($connection->connect_error) die("Connection failed: " . $connection->connect_error);

$ass_id = isset($_GET['ass_id']) ? (int)$_GET['ass_id'] : null;
if (!$ass_id) die("No project selected.");

if (!isset($_SESSION['userinfo_ID'])) die("Access denied. Please log in.");
$userinfo_id = $_SESSION['userinfo_ID'];

// Fetch project info including admin ID
$sql = "SELECT a.project_name, a.instructions, a.points, a.due_date, 
               p.project_name AS parent_project_name, ai.INSTRUCTOR, ai.admininfoID
        FROM assigned a
        JOIN projects p ON a.proj_id = p.proj_id
        JOIN admininfo ai ON p.admininfoID = ai.admininfoID
        WHERE a.ass_id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $ass_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

$adminID = $project['admininfoID'] ?? null;
if (!$adminID) die("Admin ID not found.");

// ✅ Handle file upload 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['myFile']) && $_FILES['myFile']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); // Ensure upload dir exists

    $originalName = basename($_FILES['myFile']['name']);
    $safeName = time() . "_" . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $originalName);
    $targetPath = $uploadDir . $safeName;

    if (move_uploaded_file($_FILES['myFile']['tmp_name'], $targetPath)) {
        // Insert into student_submissions
        $stmt = $connection->prepare("INSERT INTO student_submissions (assigned_id, userinfo_id, file_name, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiss", $ass_id, $userinfo_id, $originalName, $targetPath);
        $stmt->execute();
        $stmt->close();

            // Fetch student username
    $userResult = $connection->query("SELECT FIRSTNAME, MIDDLENAME, LASTNAME FROM userinfo WHERE userinfo_ID = $userinfo_id");
    $userData = $userResult->fetch_assoc();
    $fullName = trim($userData['FIRSTNAME'] . ' ' . $userData['MIDDLENAME'] . ' ' . $userData['LASTNAME']);

    // Log the transaction
    logTransaction(
        'student',
        $fullName,
        'Submitted File',
        "Submitted '$originalName' for assignment ID $ass_id"
    );



        header("Location: content.php?ass_id=$ass_id");
        exit;
    } else {
        echo "<script>alert('File upload failed.');</script>";
    }
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    $commentText = trim($_POST['comment_text']);
    if ($commentText !== '') {
        $stmt = $connection->prepare("INSERT INTO comments (ass_id, recipient_id, userinfo_id, user_type, comment_text, created_at) VALUES (?, ?, ?, 'student', ?, NOW())");
        $stmt->bind_param("iiis", $ass_id, $adminID, $userinfo_id, $commentText);
        $stmt->execute();
        $stmt->close();
        header("Location: content.php?ass_id=$ass_id");
        exit();
    }
}

// Fetch attachments
$attachments = [];
$stmt = $connection->prepare("SELECT file_name, file_path FROM attachments WHERE assigned_id = ?");
$stmt->bind_param("i", $ass_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $attachments[] = $row;
$stmt->close();

// Fetch student submissions
$student_files = [];
$stmt = $connection->prepare("SELECT file_name, file_path FROM student_submissions WHERE assigned_id = ? AND userinfo_id = ?");
$stmt->bind_param("ii", $ass_id, $userinfo_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $student_files[] = $row;
$stmt->close();

// Fetch comments
$comments = [];
$stmt = $connection->prepare("
    SELECT c.comment_text, c.user_type, c.created_at,
           u.FIRSTNAME, u.MIDDLENAME, u.LASTNAME,
           a.INSTRUCTOR
    FROM comments c
    LEFT JOIN userinfo u ON c.user_type = 'student' AND c.userinfo_id = u.userinfo_ID
    LEFT JOIN admininfo a ON c.user_type = 'admin' AND c.userinfo_id = a.admininfoID
    WHERE c.ass_id = ? AND (c.recipient_id = ? OR c.userinfo_id = ?)
    ORDER BY c.created_at ASC
");
$stmt->bind_param("iii", $ass_id, $userinfo_id, $userinfo_id);

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['user_type'] === 'student') {
        $fullName = trim(($row['FIRSTNAME'] ?? '') . ' ' . ($row['MIDDLENAME'] ?? '') . ' ' . ($row['LASTNAME'] ?? '')) ?: 'Student';
    } else {
        $fullName = trim($row['INSTRUCTOR'] ?? '') ?: 'Admin';
    }
    $comments[] = [
        'comment_text' => $row['comment_text'],
        'user_type' => $row['user_type'],
        'created_at' => $row['created_at'],
        'username' => $fullName
    ];
}
$stmt->close();

// Fetch task status and grade
$studentTask = ['status' => null, 'grade' => null];
$stmt = $connection->prepare("SELECT status, grade FROM assignment_students WHERE assigned_id = ? AND userinfo_ID = ?");
$stmt->bind_param("ii", $ass_id, $userinfo_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentTask['status'] = $row['status'];
    $studentTask['grade'] = $row['grade'];
}
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DreamBoard - Project Content</title>
  <link rel="stylesheet" href="content.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .comment-section { margin-top: 20px; }
    .comment-box { background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto; }
    .comment-item { margin-bottom: 10px; }
    .comment-item.admin { background: #e3f2fd; padding: 5px; border-radius: 5px; }
    .comment-item.student { background: #fff3e0; padding: 5px; border-radius: 5px; }
    .timestamp { font-size: 0.8em; color: #777; }
    .comment-form { margin-top: 10px; display: flex; gap: 10px; }
    .comment-form input { flex: 1; padding: 8px; }
    .comment-form button { padding: 8px 12px; }
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
    <li class="user">
      <a href="profile.php" class="<?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
        <i class="fas fa-user"></i> User
      </a>
    </li>
    <li>
      <a href="#"><i class='bx bxs-bell'></i> Notification</a>
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
    <?php if ($project): ?>
      <div class="title">
        <h1><?= htmlspecialchars($project['project_name']) ?></h1>
      </div>
      <div class="details">
        <h2>Instructor: <?= htmlspecialchars($project['INSTRUCTOR']) ?></h2>
        <p>Posted under: <?= htmlspecialchars($project['parent_project_name']) ?></p>
        <p>Due: <?= htmlspecialchars($project['due_date']) ?></p>
        <p>Instructions:</p><?= $project['instructions'] ?>
      </div>

      <?php if ($attachments): ?>
        <div class="file">
          <h3>Project Attachments</h3>
          <?php foreach ($attachments as $att): ?>
            <p><a href="<?= htmlspecialchars($att['file_path']) ?>" target="_blank">📄 <?= htmlspecialchars($att['file_name']) ?></a></p>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>No attachments found.</p>
      <?php endif; ?>

      <hr>
      <div class="upload">
        <div class="task-score">
          <h2 class="task">Task</h2>
          <h2 class="score">
            <?= is_numeric($studentTask['grade']) ? htmlspecialchars($studentTask['grade']) : 'Not graded' ?>/<?= htmlspecialchars($project['points']) ?>
          </h2>
        </div>
        <form action="content.php?ass_id=<?= $ass_id ?>" method="POST" enctype="multipart/form-data">
          <div class="input-file" onclick="document.getElementById('myFile').click()">
            <span id="file-name">+ ADD WORK</span>
            <input type="file" name="myFile" id="myFile" style="display: none;" onchange="showFileName()">
          </div>
          <button class="submit" type="submit">Upload</button>
        </form>

        <?php if ($student_files): ?>
          <div class="student-submissions">
            <h3>Your Submissions</h3>
            <?php foreach ($student_files as $file): ?>
              <p><a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank">📎 <?= htmlspecialchars($file['file_name']) ?></a></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <hr>
      <div class="comment-section">
        <h3>Comments</h3>
        <div class="comment-box">
          <?php foreach ($comments as $com): ?>
            <div class="comment-item <?= $com['user_type'] === 'admin' ? 'admin' : 'student' ?>">
              <strong><?= htmlspecialchars($com['username']) ?>:</strong>
              <?= htmlspecialchars($com['comment_text']) ?>
              <div class="timestamp"><?= $com['created_at'] ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="POST" class="comment-form">
          <input type="text" name="comment_text" placeholder="Add a comment..." required>
          <button type="submit">Send</button>
        </form>
      </div>
    <?php else: ?>
      <p>Project not found or access denied.</p>
    <?php endif; ?>
  </div>
</div>
<script>
function showFileName() {
  const input = document.getElementById("myFile");
  const label = document.getElementById("file-name");
  label.textContent = input.files.length > 0 ? input.files[0].name : "+ ADD WORK";
}
</script>
</body>
</html>
