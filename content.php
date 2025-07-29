<?php
session_start();
include 'log1.php';

$connection = new mysqli("localhost", "root", "", "projectmanagement");
if ($connection->connect_error) die("Connection failed: " . $connection->connect_error);

$ass_id = isset($_GET['ass_id']) ? (int)$_GET['ass_id'] : null;
if (!$ass_id) die("No project selected.");

if (!isset($_SESSION['userinfo_ID'])) die("Access denied. Please log in.");
$userinfo_id = $_SESSION['userinfo_ID'];

// Get project info
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


// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['myFile'])) {
    if ($_FILES['myFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); // make sure the folder exists

        $originalName = basename($_FILES['myFile']['name']);
        $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $originalName);
        $filePath = $uploadDir . $uniqueName;

        if (move_uploaded_file($_FILES['myFile']['tmp_name'], $filePath)) {
            $stmt = $connection->prepare("INSERT INTO student_submissions (assigned_id, userinfo_id, file_name, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiss", $ass_id, $userinfo_id, $originalName, $filePath);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Refresh the page to reflect uploaded file
    header("Location: content.php?ass_id=$ass_id");
    exit;
}


// Turn in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['turn_in'])) {
    // Get the old status before updating
$oldStatusStmt = $connection->prepare("SELECT status FROM assignment_students WHERE assigned_id = ? AND userinfo_ID = ?");
$oldStatusStmt->bind_param("ii", $ass_id, $userinfo_id);
$oldStatusStmt->execute();
$oldResult = $oldStatusStmt->get_result()->fetch_assoc();
$oldStatus = $oldResult['status'] ?? 'Not Started';
$oldStatusStmt->close();

// Update the new status
$newStatus = 'Completed';
$updateStmt = $connection->prepare("UPDATE assignment_students SET status = ? WHERE assigned_id = ? AND userinfo_ID = ?");
$updateStmt->bind_param("sii", $newStatus, $ass_id, $userinfo_id);
$updateStmt->execute();
$updateStmt->close();

// Insert into status_logs
$logStmt = $connection->prepare("INSERT INTO status_logs (assigned_id, userinfo_id, old_status, new_status, changed_at) VALUES (?, ?, ?, ?, NOW())");
$logStmt->bind_param("iiss", $ass_id, $userinfo_id, $oldStatus, $newStatus);
$logStmt->execute();
$logStmt->close();


    $user = $connection->query("SELECT FIRSTNAME, MIDDLENAME, LASTNAME FROM userinfo WHERE userinfo_ID = $userinfo_id")->fetch_assoc();
    $fullName = trim($user['FIRSTNAME'] . ' ' . $user['MIDDLENAME'] . ' ' . $user['LASTNAME']);
    logTransaction('student', $fullName, 'Turned In Task', "Marked assignment ID $ass_id as submitted");

    header("Location: content.php?ass_id=$ass_id");
    exit;
}

// Comment
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

// Fetch data
function fetchData($connection, $query, $types = "", $params = []) {
    $stmt = $connection->prepare($query);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$attachments = fetchData($connection, "SELECT file_name, file_path FROM attachments WHERE assigned_id = ?", "i", [$ass_id]);
$student_files = fetchData($connection, "SELECT file_name, file_path FROM student_submissions WHERE assigned_id = ? AND userinfo_id = ?", "ii", [$ass_id, $userinfo_id]);

$studentTask = ['status' => null, 'grade' => null];
$taskResult = fetchData($connection, "SELECT status, grade FROM assignment_students WHERE assigned_id = ? AND userinfo_ID = ?", "ii", [$ass_id, $userinfo_id]);
if ($taskResult) $studentTask = $taskResult[0];

$comments = [];
$stmt = $connection->prepare("
    SELECT c.comment_text, c.user_type, c.created_at,
           u.FIRSTNAME, u.MIDDLENAME, u.LASTNAME,
           a.INSTRUCTOR
    FROM comments c
    LEFT JOIN userinfo u ON c.user_type = 'student' AND c.userinfo_id = u.userinfo_ID
    LEFT JOIN admininfo a ON c.user_type = 'admin' AND c.userinfo_id = a.admininfoID
    WHERE c.ass_id = ? AND (c.userinfo_id = ? OR c.recipient_id = ?)
    ORDER BY c.created_at ASC
");
$stmt->bind_param("iii", $ass_id, $userinfo_id, $userinfo_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $username = $row['user_type'] === 'student'
        ? trim(($row['FIRSTNAME'] ?? '') . ' ' . ($row['MIDDLENAME'] ?? '') . ' ' . ($row['LASTNAME'] ?? '')) ?: 'Student'
        : ($row['INSTRUCTOR'] ?? 'Admin');
    $comments[] = ['comment_text' => $row['comment_text'], 'user_type' => $row['user_type'], 'created_at' => $row['created_at'], 'username' => $username];
}

$statusCheck = fetchData($connection, "SELECT status FROM assignment_students WHERE assigned_id = ? AND userinfo_ID = ?", "ii", [$ass_id, $userinfo_id]);


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
    .input-file.disabled { pointer-events: none; opacity: 0.6; background: #ccc; }
  </style>
</head>
<body>
<header>
  <div class="navbar">
    <img src="logo.png" alt="Logo"><p>DreamBoard</p>
  </div>
</header>
<div class="container">
  <div class="sidebar">
    <ul>
      <li><a href="profile.php"><i class="fas fa-user"></i> User</a></li>
      <li><a href="#"><i class='bx bxs-bell'></i> Notification</a></li>
      <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
      <li><a href="Projects.php"><i class="fas fa-folder-open"></i> Class Works</a></li>
      <li><a href="calendar (1).php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
      <li><a href="forms.php"><i class="fas fa-clipboard-list"></i> Forms</a></li>
      <li><a href="about.php"><i class="fas fa-users"></i> About Us</a></li>
    </ul>
    <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main-content">
    <?php if ($project): ?>
      <div class="title"><h1><?= htmlspecialchars($project['project_name']) ?></h1></div>
      <div class="details">
        <h2>Instructor: <?= htmlspecialchars($project['INSTRUCTOR']) ?></h2>
        <p>Posted under: <?= htmlspecialchars($project['parent_project_name']) ?></p>
        <p>Due: <?= htmlspecialchars($project['due_date']) ?></p>
        <p>Instructions:</p><?= $project['instructions'] ?>
      </div>

      <div class="file">
        <h3>Project Attachments</h3>
        <?php if ($attachments): foreach ($attachments as $att): ?>
          <p><a href="<?= htmlspecialchars($att['file_path']) ?>" target="_blank">📄 <?= htmlspecialchars($att['file_name']) ?></a></p>
        <?php endforeach; else: ?>
          <p>No attachments found.</p>
        <?php endif; ?>
      </div>

      <hr>
      <div class="upload">
        <div class="task-score">
          <h2 class="task">Task</h2>
          <h2 class="score"><?= is_numeric($studentTask['grade']) ? $studentTask['grade'] : 'Not graded' ?>/<?= $project['points'] ?></h2>
        </div>

        <?php if (!empty($student_files)): ?>
          <div class="file">
            <h3>Your Uploaded Work</h3>
            <?php foreach ($student_files as $file): ?>
              <p><a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank">📄 <?= htmlspecialchars($file['file_name']) ?></a></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($studentTask['status'] === 'Completed'): ?>
  <div class="input-file disabled"><span>+ ADD WORK (Disabled)</span></div>
  <button class="submit" type="button" disabled>Turned In</button>
<?php else: ?>
  <form id="uploadForm" action="content.php?ass_id=<?= $ass_id ?>" method="POST" enctype="multipart/form-data">
    <div class="input-file" onclick="document.getElementById('myFile').click()">
      <span id="file-name">+ ADD WORK</span>
      <input type="file" name="myFile" id="myFile" style="display:none;" onchange="handleFileSelect()">
    </div>
  </form>

  <?php if (!empty($student_files)): ?>
    <form action="content.php?ass_id=<?= $ass_id ?>" method="POST">
      <input type="hidden" name="turn_in" value="1">
      <button class="submit" type="submit">Turn In</button>
    </form>
  <?php endif; ?>
<?php endif; ?>

      </div>

      <hr>
      <div class="comment-section">
        <h3>Comments</h3>
        <div class="comment-box">
          <?php foreach ($comments as $c): ?>
            <div class="comment-item <?= $c['user_type'] ?>">
              <strong><?= htmlspecialchars($c['username']) ?>:</strong> <?= htmlspecialchars($c['comment_text']) ?>
              <div class="timestamp"><?= $c['created_at'] ?></div>
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
function handleFileSelect() {
  // Prevent uploading if the file box is disabled
  const input = document.getElementById("myFile");
  const label = document.getElementById("file-name");
  const isDisabled = document.querySelector(".input-file").classList.contains("disabled");

  if (isDisabled) return; // ⛔ stop if already submitted

  if (input.files.length > 0) {
    label.textContent = input.files[0].name;
    document.getElementById("uploadForm").submit();
  } else {
    label.textContent = "+ ADD WORK";
  }
}
</script>
</body>
</html>
