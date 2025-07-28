<?php
include 'log1.php';
session_start();

$currentPage = basename($_SERVER['PHP_SELF']);

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");

if (!isset($_SESSION['userinfo_ID'])) {
    header("Location: login.php");
    exit();
}
// Fetch user data from database using email (assumes session contains 'Email')
$studentId = $_SESSION['userinfo_ID'];
$userQuery = "SELECT FIRSTNAME, MIDDLENAME, LASTNAME, PROFILE_PIC FROM userinfo WHERE userinfo_ID = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $studentId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();

$fullName = $userData 
    ? trim($userData['FIRSTNAME'] . ' ' . $userData['MIDDLENAME'] . ' ' . $userData['LASTNAME']) 
    : "Student";

$profilePic = !empty($userData['PROFILE_PIC']) ? $userData['PROFILE_PIC'] : "default.png";

$studentId = $_SESSION['userinfo_ID'];
$dueDates = [];


// Fetch only assignments assigned to this student
$query = "
    SELECT a.due_date 
    FROM assigned a
    JOIN assignment_students s ON a.ass_id = s.assigned_id
    WHERE s.userinfo_ID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dueDates[] = date('Y-m-d', strtotime($row['due_date']));
}

// history
$completedQuery = $conn->prepare("
    SELECT a.project_name, a.due_date
    FROM assigned a
    JOIN assignment_students s ON a.ass_id = s.assigned_id
    WHERE s.userinfo_ID = ? AND s.status = 'completed'
    ORDER BY a.due_date DESC
    LIMIT 1
");
$completedQuery->bind_param("i", $studentId);
$completedQuery->execute();
$completedResult = $completedQuery->get_result();
$completedProject = $completedResult->fetch_assoc();

//ring
$closestQuery = $conn->prepare("
    SELECT a.project_name, a.due_date
    FROM assigned a
    JOIN assignment_students s ON a.ass_id = s.assigned_id
    WHERE s.userinfo_ID = ? AND a.due_date >= CURDATE()
    ORDER BY a.due_date ASC
    LIMIT 1
");
$closestQuery->bind_param("i", $studentId);
$closestQuery->execute();
$closestResult = $closestQuery->get_result();
$closestProject = $closestResult->fetch_assoc();

//overdue
$overdueQuery = $conn->prepare("
    SELECT a.project_name, a.due_date
    FROM assigned a
    JOIN assignment_students s ON a.ass_id = s.assigned_id
    WHERE s.userinfo_ID = ? AND s.status != 'completed' AND a.due_date < CURDATE()
    ORDER BY a.due_date ASC
    LIMIT 1
");
$overdueQuery->bind_param("i", $studentId);
$overdueQuery->execute();
$overdueResult = $overdueQuery->get_result();
$overdueProject = $overdueResult->fetch_assoc();

// Calculate student-specific progress
$progressQuery = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(             -- turn NULL into 0
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0
        ) AS completed
    FROM assignment_students
    WHERE userinfo_ID = ?
");
$progressQuery->bind_param("i", $studentId);
$progressQuery->execute();
$progressResult = $progressQuery->get_result();
$progressData = $progressResult->fetch_assoc();

$totalTasks = $progressData['total'];
$completedTasks = $progressData['completed'];

$progressPercentage = ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DreamBoard - Student Dashboard</title>
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="dashboard.css" />
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
  <div class="greeting">
  <div class="greeting-inside">
    <div class="greeting-text">
      <h1>Hi, <?php echo htmlspecialchars($fullName); ?>!</h1>
      <p>"You've got this! Let's tackle one task at a time!"</p>
  </div>
     <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="User Image">
</div>
</div>


  <div class="row2">
    <div class="box4">

            <div class="box">
                <i class='bx bx-history'></i>
                <div class="box-inside">
                <div class="title">Last Completed</div>
                <div class="details">
                  <?php if ($completedProject): ?>
                    <?php echo htmlspecialchars($completedProject['project_name']); ?>
                  <?php else: ?> 
                    No completed project yet.
                  <?php endif; ?>
                </div>
                <div class="due">
                  <?php if (!empty($completedProject['due_date'])): ?>
                    Due: <?php echo htmlspecialchars($completedProject['due_date']); ?>
                  <?php endif; ?>
                </div>
                </div>
           </div>

            <div class="box">
              <i class='bx bxs-bell-ring'></i>
              <div class="box-inside">
              <div class="title">Next Due</div>
              <div class="details">
                <?php if ($closestProject): ?>
                  <?php echo htmlspecialchars($closestProject['project_name']); ?>
                <?php else: ?>
                  No upcoming projects.
                <?php endif; ?>
              </div>
              <div class="due">
                <?php if (!empty($closestProject['due_date'])): ?>
                  Due: <?php echo htmlspecialchars($closestProject['due_date']); ?>
                <?php endif; ?>
              </div>
              </div>
            </div>
    
            <div class="box">
            <i class='bx bx-target-lock'></i>
            <div class="box-inside">
            <div class="title">Overdue</div>
            <div class="details">
              <?php if ($overdueProject): ?>
                <?php echo htmlspecialchars($overdueProject['project_name']); ?>
              <?php else: ?>
                No overdue tasks!
              <?php endif; ?>
            </div>
            <div class="due">
              <?php if (!empty($overdueProject['due_date'])): ?>
                Due: <?php echo htmlspecialchars($overdueProject['due_date']); ?>
              <?php endif; ?>
            </div>
            </div>
          </div>
      
          <div class="box">
          <i class='bx bx-bulb'></i>
          <div class="box-inside">
          <div class="title">Fun Fact</div>
          <div class="details">
            CvSU is the largest public university in Cavite,<br>
            with multiple campuses spread across the province,<br>
            making quality education accessible to more students!
          </div>
          </div>
        </div>
         </div>

    <div class="progress">
      <div class="progress-left">
        <h1>Overview</h1>
        <?php
              $progressMessage = "";
              switch ($progressPercentage) {
                  case 0:
                      $progressMessage = "Let's get started! Your journey begins now.";
                      break;
                  case 25:
                      $progressMessage = "You're making progress! Keep pushing forward.";
                      break;
                  case 50:
                      $progressMessage = "Halfway there! You're doing great.";
                      break;
                  case 75:
                      $progressMessage = "So close! Just a bit more effort!";
                      break;
                  case 100:
                      $progressMessage = "Congratulations! You've completed all your tasks!";
                      break;
                  default:
                      $progressMessage = "Keep going, you're doing well!";
              }
              ?>
              <p><span><?php echo $progressPercentage; ?>%</span> done - <?php echo $progressMessage; ?></p>

                <div class="done"><p>COMPLETED <?php echo $completedTasks; ?></p></div>
                <div class="pending"><p>PENDING <?php echo $totalTasks - $completedTasks; ?></p></div>

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
  <!-- ========== TASK LIST ========== -->
  <div class="task">
    <h3>Tasks</h3>
    <div class="task-list">
<?php
// Pull the five nearest-due, not-yet-completed tasks for this student
$taskQuery = "
    SELECT a.ass_id,
           a.project_name
    FROM assigned a
    JOIN assignment_students s ON s.assigned_id = a.ass_id
    WHERE s.userinfo_ID = ?
      AND s.status <> 'completed'
    ORDER BY a.due_date ASC
    LIMIT 5
";
$taskStmt = $conn->prepare($taskQuery);
$taskStmt->bind_param("i", $studentId);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();

if ($taskResult->num_rows > 0) {
    while ($task = $taskResult->fetch_assoc()) {
        // Change content.php to whatever page should show the task
        echo '<a class="task-item" href="content.php?ass_id=' .
              urlencode($task['ass_id']) . '">';
        echo htmlspecialchars($task['project_name']);
        echo '</a>';
    }
} else {
    echo '<p>No tasks assigned yet.</p>';
}
?>
    </div><!-- /.task-list -->
  </div><!-- /.task -->
  
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
</div><!-- /.row3 -->


    
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
setProgress(<?php echo $progressPercentage; ?>);
</script>

</body>
</html>
