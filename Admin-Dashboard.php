<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
include 'log1.php';

if (!isset($_SESSION['Email'])) {
    header("Location: Admin-login.php");
    exit();
}

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

// Fetch due dates for calendar
$dueDates = [];
$calendarQuery = "SELECT due_date FROM assigned";
$calendarResult = mysqli_query($conn, $calendarQuery);
while ($row = mysqli_fetch_assoc($calendarResult)) {
    $dueDates[] = date('Y-m-d', strtotime($row['due_date']));
}

// Fetch submitted works
$submissions = [];
$submissionQuery = "
    SELECT 
        p.project_name, 
        u.FIRSTNAME, u.LASTNAME, 
        s.file_name, s.uploaded_at,
        s.assigned_id,
        astu.grade
    FROM student_submissions s
    JOIN assignment_students astu ON astu.assigned_id = s.assigned_id AND astu.userinfo_id = s.userinfo_id
    JOIN assigned a ON a.ass_id = s.assigned_id
    JOIN projects p ON p.proj_id = a.proj_id
    JOIN userinfo u ON u.userinfo_ID = s.userinfo_id
    WHERE p.admininfoID = '$adminID'
      AND astu.grade IS NULL
    ORDER BY s.uploaded_at DESC
";

$submissionResult = mysqli_query($conn, $submissionQuery);
while ($row = mysqli_fetch_assoc($submissionResult)) {
    $submissions[] = $row;
}

// Latest assignment
$latestAssignment = null;
$latestAssignmentQuery = "
    SELECT 
        a.project_name AS assigned_name,
        a.due_date,
        p.project_name AS base_project
    FROM assigned a
    JOIN projects p ON a.proj_id = p.proj_id
    WHERE p.admininfoID = '$adminID'
    ORDER BY a.created_at DESC
    LIMIT 1
";
$latestAssignmentResult = mysqli_query($conn, $latestAssignmentQuery);
if ($latestAssignmentResult && mysqli_num_rows($latestAssignmentResult) > 0) {
    $latestAssignment = mysqli_fetch_assoc($latestAssignmentResult);
}

// Upcoming deadlines
$upcomingDeadlines = [];
$upcomingQuery = "
    SELECT project_name, due_date 
    FROM assigned 
    WHERE due_date >= CURDATE()
    ORDER BY due_date ASC 
    LIMIT 5
";
$upcomingResult = mysqli_query($conn, $upcomingQuery);
while ($row = mysqli_fetch_assoc($upcomingResult)) {
    $upcomingDeadlines[] = $row;
}

// Activity Feed
$activityFeed = [];
$commentQuery = "
    SELECT comment_text AS title, ass_id AS ref_id, created_at
    FROM comments
    WHERE user_type = 'student'
    ORDER BY created_at DESC
    LIMIT 1
";
$commentResult = mysqli_query($conn, $commentQuery);
while ($row = mysqli_fetch_assoc($commentResult)) {
    $activityFeed[] = [
        'title' => $row['title'],
        'extra' => "on assignment #{$row['ref_id']}",
        'time' => $row['created_at']
    ];
}

// Grading Queue
$gradingQueue = [];
$gradingQuery = "
    SELECT 
        p.project_name AS project_title,
        u.FIRSTNAME, u.LASTNAME,
        a.due_date,
        COUNT(s.id) AS pending_files
    FROM student_submissions s
    JOIN assignment_students astu ON astu.assigned_id = s.assigned_id AND astu.userinfo_id = s.userinfo_id
    JOIN assigned a ON a.ass_id = s.assigned_id
    JOIN projects p ON p.proj_id = a.proj_id
    JOIN userinfo u ON u.userinfo_ID = s.userinfo_id
    WHERE astu.grade IS NULL
      AND p.admininfoID = '$adminID'
    GROUP BY s.assigned_id, s.userinfo_id
    ORDER BY a.due_date ASC
    LIMIT 5
";
$gradingResult = mysqli_query($conn, $gradingQuery);
while ($row = mysqli_fetch_assoc($gradingResult)) {
    $gradingQueue[] = [
        'project_title' => $row['project_title'],
        'student_name' => $row['FIRSTNAME'] . ' ' . $row['LASTNAME'],
        'pending_files' => $row['pending_files'],
        'due_date' => $row['due_date']
    ];
}

// Completed and Pending Student Counts
$completedStudentsCount = 0;
$completedQuery = "
    SELECT COUNT(*) AS total_completed
    FROM assignment_students astu
    JOIN assigned a ON a.ass_id = astu.assigned_id
    JOIN projects p ON p.proj_id = a.proj_id
    WHERE astu.status = 'Completed' AND p.admininfoID = '$adminID'
";
$completedResult = mysqli_query($conn, $completedQuery);
if ($completedResult && mysqli_num_rows($completedResult) > 0) {
    $row = mysqli_fetch_assoc($completedResult);
    $completedStudentsCount = $row['total_completed'];
}

$pendingStudentsCount = 0;
$pendingQuery = "
    SELECT COUNT(*) AS total_pending
FROM assignment_students astu
JOIN assigned a ON a.ass_id = astu.assigned_id
JOIN projects p ON p.proj_id = a.proj_id
WHERE astu.status != 'Completed'
  AND p.admininfoID = '$adminID'
";
$pendingResult = mysqli_query($conn, $pendingQuery);
if ($pendingResult && mysqli_num_rows($pendingResult) > 0) {
    $row = mysqli_fetch_assoc($pendingResult);
    $pendingStudentsCount = $row['total_pending'];
}

$totalStudents = $completedStudentsCount + $pendingStudentsCount;
$completionPercent = $totalStudents > 0 ? round(($completedStudentsCount / $totalStudents) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>DreamBoard - Admin Dashboard</title>
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="Admin-Dashboard.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
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
      <li class="user"><a href="Admin.profile.php"><i class="fas fa-user"></i> Admin</a></li>
      <li><a href="Admin-Dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
      <li><a href="Admin-project.php"><i class="fas fa-folder-open"></i> Project</a></li>
      <li><a href="Admin-calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
      <li><a href="Admin-forms.php"><i class="fas fa-clipboard-list"></i> Forms</a></li>
      <li><a href="Admin-about.php"><i class="fas fa-users"></i> About Us</a></li>
    </ul>
    <a href="Admin-login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>


<div class="main-content">
  <div class="greeting">
    <h1>Hi! <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h1>
    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'default.png'); ?>" alt="Profile Picture">
  </div>

  <div class="row2">
    <div class="box4">
      <div class="box">
  <i class='bx bx-upload'></i>
  <div class="box-inside">
    <div class="title">Latest Assignment</div>

    <div class="details">
      <?php if ($latestAssignment): ?>
        <?php echo htmlspecialchars($latestAssignment['assigned_name']); ?><br>
        Base: <?php echo htmlspecialchars($latestAssignment['base_project']); ?>
      <?php else: ?>
        No project yet.
      <?php endif; ?>
    </div>

    <div class="due">
      <?php if (!empty($latestAssignment['due_date'])): ?>
        Due: <?php echo date('M d, Y', strtotime($latestAssignment['due_date'])); ?>
      <?php endif; ?>
    </div>
  </div>
</div>


      <div class="box">
  <i class='bx bx-calendar'></i>
  <div class="box-inside">
    <div class="title">Upcoming Deadlines</div>
    <div class="details" style="font-size: 14px;">
      <?php if (count($upcomingDeadlines) > 0): ?>
        <ul style="padding-left: 10px; margin: 0;">
          <?php foreach ($upcomingDeadlines as $deadline): ?>
            <?php
              $dueDate = new DateTime($deadline['due_date']);
              $today = new DateTime();
              $interval = $today->diff($dueDate);
              $daysLeft = (int)$interval->format('%r%a');
              $countdown = $daysLeft === 0 ? "Today" : 
                          ($daysLeft === 1 ? "in 1 day" : "in {$daysLeft} days");
            ?>
            <li style="margin-bottom: 4px;">
              <strong><?php echo htmlspecialchars($deadline['project_name']); ?></strong><br>
              <small><?php echo date("M d", strtotime($deadline['due_date'])); ?> – <em><?php echo $countdown; ?></em></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <small>No upcoming deadlines.</small>
      <?php endif; ?>
    </div>
    <!-- No "due" div here as this box lists multiple due dates -->
  </div>
</div>

<div class="box">
  <i class='bx bx-comment-detail'></i>
  <div class="box-inside">
    <div class="title">Student Comments</div>
    <div class="details" style="font-size: 14px;">
      <?php if (count($activityFeed) > 0): ?>
        <ul style="padding-left: 10px; margin: 0;">
          <?php foreach ($activityFeed as $item): ?>
            <li style="margin-bottom: 6px;">
              <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
              <small><?php echo htmlspecialchars($item['extra']); ?></small><br>
              <em><small><?php echo date("M d, H:i", strtotime($item['time'])); ?></small></em>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <small>No recent student comments.</small>
      <?php endif; ?>
    </div>
    <!-- No "due" section since it's not date-based like a deadline -->
  </div>
</div>

                <div class="box">
  <i class='bx bx-task'></i>
  <div class="box-inside">
    <div class="title">Pending Reviews</div>
    <div class="details" style="font-size: 14px;">
      <?php if (count($gradingQueue) > 0): ?>
        <ul style="padding-left: 10px; margin: 0;">
          <?php foreach ($gradingQueue as $item): ?>
            <?php
              $isOverdue = (strtotime($item['due_date']) < strtotime(date("Y-m-d")));
              $dueLabel = $isOverdue ? "<span style='color:red'>(Overdue)</span>" : "";
            ?>
            <li style="margin-bottom: 6px;">
              <strong><?php echo htmlspecialchars($item['project_title']); ?></strong><br>
              <?php echo htmlspecialchars($item['student_name']); ?> – 
              <?php echo htmlspecialchars($item['pending_files']); ?> file(s) pending<br>
              <small>Due: <?php echo date("M d", strtotime($item['due_date'])); ?> <?php echo $dueLabel; ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <small>No pending reviews.</small>
      <?php endif; ?>
    </div>
    <!-- No separate 'due' div since due dates are inline per item -->
  </div>
</div>

    </div>

    <div class="progress">
      <div class="progress-left">
        <h1>Overview</h1>
        <p>
          <span><?php echo $completionPercent; ?>%</span> done - 
          <?php
            if ($completionPercent == 0) {
                echo "No progress yet!";
            } elseif ($completionPercent > 0 && $completionPercent <= 25) {
                echo "Oh no, better start working!";
            } elseif ($completionPercent <= 75) {
                echo "Keep up the good work!";
            } else {
                echo "Almost there!";
            }

            if ($completionPercent == 100) {
                echo " All students completed!";
            }
          ?>
        </p>
                <div class="done">
          <p>COMPLETED <?php echo $completedStudentsCount; ?></p>

        </div>
          <div class="pending">
            <p>PENDING <?php echo $pendingStudentsCount; ?></p>
 
          </div>
      </div>
      <div class="progress-right">
        <div class="circular-progress">
          <svg class="progress-ring" width="100%" height="100%" viewBox="0 0 200 200">
            <circle class="progress-ring-bg" cx="100" cy="100" r="90" />
            <circle class="progress-ring-fill" cx="100" cy="100" r="90" />
          </svg>
          <div class="progress-value">75%</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row3">
    <div class="task">
      <h3>Submitted Works</h3>
      <div class="task-list">
        <?php if (count($submissions) > 0): ?>
          <?php foreach ($submissions as $sub): ?>
            <a href="admin-teamproj.php?ass_id=<?php echo urlencode($sub['assigned_id']); ?>" class="task-item">
              <strong><?php echo htmlspecialchars($sub['project_name']); ?></strong>
              <p>Submitted by <?php echo htmlspecialchars($sub['FIRSTNAME'] . ' ' . $sub['LASTNAME']); ?></p>
              <p>Date: <?php echo date("F j, Y", strtotime($sub['uploaded_at'])); ?></p>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No student submissions yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="calendar">
      <div class="head">
        <p class="current-date"></p>
        <div class="icons">
          <span id="prev" class="material-symbols-rounded">chevron_left</span>
          <span id="next" class="material-symbols-rounded">chevron_right</span>
        </div>
      </div>
      <div class="calendar1">
        <ul class="weeks">
          <li>Sun</li><li>Mon</li><li>Tue</li><li>Wed</li><li>Thu</li><li>Fri</li><li>Sat</li>
        </ul>
        <ul class="days"></ul>
      </div>
    </div>
  </div>
</div>
</div>
<script>
const dueDates = <?php echo json_encode($dueDates); ?>;

const daysTag = document.querySelector(".days"),
currentDate = document.querySelector(".current-date"),
prevNextIcon = document.querySelectorAll(".icons span");

let date = new Date(),
currYear = date.getFullYear(),
currMonth = date.getMonth();

const months = ["January", "February", "March", "April", "May", "June", "July",
              "August", "September", "October", "November", "December"];

const renderCalendar = () => {
    let firstDayofMonth = new Date(currYear, currMonth, 1).getDay(),
        lastDateofMonth = new Date(currYear, currMonth + 1, 0).getDate(),
        lastDayofMonth = new Date(currYear, currMonth, lastDateofMonth).getDay(),
        lastDateofLastMonth = new Date(currYear, currMonth, 0).getDate();

    let liTag = "";

    for (let i = firstDayofMonth; i > 0; i--) {
        liTag += `<li class="inactive">${lastDateofLastMonth - i + 1}</li>`;
    }

    for (let i = 1; i <= lastDateofMonth; i++) {
        let fullDate = `${currYear}-${String(currMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        let isToday = i === date.getDate() && currMonth === new Date().getMonth() 
                      && currYear === new Date().getFullYear() ? "active" : "";
        let isDue = dueDates.includes(fullDate) ? "due-date" : "";
        liTag += `<li class="${isToday} ${isDue}">${i}</li>`;
    }

    for (let i = lastDayofMonth; i < 6; i++) {
        liTag += `<li class="inactive">${i - lastDayofMonth + 1}</li>`;
    }

    currentDate.innerText = `${months[currMonth]} ${currYear}`;
    daysTag.innerHTML = liTag;
};

renderCalendar();

prevNextIcon.forEach(icon => {
    icon.addEventListener("click", () => {
        currMonth = icon.id === "prev" ? currMonth - 1 : currMonth + 1;

        if (currMonth < 0 || currMonth > 11) {
            date = new Date(currYear, currMonth, new Date().getDate());
            currYear = date.getFullYear();
            currMonth = date.getMonth();
        } else {
            date = new Date();
        }

        renderCalendar();
    });
});

function setProgress(percent) {
    const radius = 90;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percent / 100) * circumference;

    document.querySelector('.progress-ring-fill').style.strokeDashoffset = offset;
    document.querySelector('.progress-value').textContent = percent + '%';
}

setProgress(<?php echo $completionPercent; ?>);
</script>

</body>
</html>
