<?php
// ✅ Set timezone to your local region (Philippines)
date_default_timezone_set('Asia/Manila');

//include 'log1.php';
session_start();

//$studentId = $_SESSION['userinfo_ID'] ?? null;
//if (!$studentId) {
//    die("Access denied. Please log in.");
//}

$connection = new mysqli("localhost", "root", "", "projectmanagement");
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$monthName = date('F', $firstDayOfMonth);
$daysInMonth = date('t', $firstDayOfMonth);
$startDayOfWeek = date('w', $firstDayOfMonth);

// ✅ Fetch due dates for this student
$assignments = [];

$query = "
    SELECT a.project_name, a.due_date 
    FROM assigned a
    JOIN assignment_students s ON a.ass_id = s.assigned_id
    WHERE s.userinfo_ID = ? AND MONTH(a.due_date) = ? AND YEAR(a.due_date) = ?
";
$stmt = $connection->prepare($query);
$stmt->bind_param("iii", $studentId, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $day = (int)date('j', strtotime($row['due_date']));
    $assignments[$day][] = $row['project_name'];
}
$stmt->close();
?>
<?php
$todayMonth = date('n');
$todayYear = date('Y');
$todayDay = date('j');
$todayFull = date('Y-m-d');

if ($month == $todayMonth && $year == $todayYear) {
    // Show today's date and day
    $headerDate = date('F j, Y');
    $headerDay  = date('l');
    $showDay = true;
} else {
    // Show only the viewed month and year
    $headerDate = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $showDay = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DreamBoard Calendar</title>
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
/*header*/
header {
    position: fixed;
    height: 10%;
    width: 100%;
    top: 0;
    left: 0;
    right: 0;
    background: #291C0E;
    transition: 0.6s;
    box-shadow: 0rem 0.5rem rgba(163, 136, 136, 0.1);
    z-index: 100000;
    display: flex;
    align-items: center;
    padding: 0 20px;
  }

  .navbar {
    display: flex;
    align-items: center;
    width: 100%;
  }

  .navbar img {
    width: 100px;
    height: 50px;
    object-fit: contain;
    margin-right: 15px;
    margin-left: 80px;
  }

  .navbar p {
    font-size: 25px;
    color: rgb(238, 238, 238);
    font-weight: bold;
    margin: 0;
  }
/*end of header*/

body {
  margin: 0;
  padding: 0;
  background-color: #F7F2F2;
  height: 100vh;
  overflow: hidden;

}

/*sidebar*/

  .container {
   display: flex;
  height: 100vh;
  padding-top: 10vh; /* corrected from 5vh — your header is 10% */
  box-sizing: border-box;
  overflow: hidden;
  }
  .sidebar {
    width: 210px;
  background-color: #75483D;
  color: #f0e8d5;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 20px;
  }
  
  .sidebar ul {
    padding: 0;
    padding-top: 40px;
    list-style: none;
  }
  .sidebar li {
    margin: 20px 0;
  }

  .sidebar li a {
    display: block;
    align-items: center;
    width: 100%;   
    color: #e0d4c8;
    text-decoration: none;
    font-size: 21px;
    padding: 10px 15px;
    border-radius: 0 20px 20px 0;
    transition: all 0.3s ease;
    box-sizing: border-box;  
  }
  
  .sidebar li a i {
    margin-right: 10px;
    font-size: 18px;
  }
  
  .sidebar li a:hover,
  .sidebar li a.active {
    width: 250px;                 /* Extends the link width */
  margin-left: -20px;           /* Moves it slightly left to look centered */
  padding-left: 35px;           /* Indent text inward */
  background-color: #b99a84;
  color: #4e3b34;
  font-weight: bold;
  border-radius: 0 20px 20px 0;
  }
  
  .logout {
    margin-top: auto;
  align-self: flex-end;
  font-size: 18px;
  color: #f0e8d5;
  text-decoration: none;
  display: flex;
  align-items: center;
  padding: 10px 15px;
  border-radius: 0 20px 20px 0;
  transition: all 0.3s ease;
  width: 100%;
  box-sizing: border-box;
  margin-bottom: 30px;
  }
  
  .logout i {
    margin-right: 8px;
  }
  
  .logout:hover {
    color: #4e3b34;
    font-weight: bold;
    margin-left: -20px;
    padding-left: 35px;
    border-radius: 0 20px 20px 0;
  }
  


/*END OF SIDE BAR*/

.main-content {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  overflow-y: auto;
  background-color: #F7F2F2;
  padding: 10px 20px 20px 20px; /* top right bottom left */
  box-sizing: border-box;
  height: calc(100vh - 10vh); /* remaining height after fixed header */
  flex: 1;
  position: relative;
  margin-top: 3vh;
}
.calendar-outer-container{
  margin-bottom:30px;
}
  .calendar-container {
    background-color: #f4f4f4;
    padding: 20px;
    border-radius: 10px;
    max-width:95%;
  }
  .calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 20px;
  }
  .calendar-header {
    font-size: 2.2rem;
    font-weight: 600;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    padding: 1.5rem 2rem;
    color: #222;
    letter-spacing: 1px;
  }
  .calendar-header .nav {
    background: none;
    color: #6e473b;
    border: 1.5px solid #e0d4c8;
    padding: 0.7rem 1.5rem;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 500;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    cursor: pointer;
    outline: none;
    text-decoration: none;
    margin: 0 1rem;
  }
  .calendar-header .nav:hover {
    background: #6e473b;
    color: #fff;
    border-color: #6e473b;
    box-shadow: 0 2px 8px rgba(110,71,59,0.08);
  }
  .header-date-group {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .header-date {
    font-size: 2.2rem;
    font-weight: 600;
    color: #222;
    letter-spacing: 1px;
  }
  .header-day {
    font-size: 1.1rem;
    color: #2ecc40; /* Modern green */
    margin-top: 0.2em;
    font-weight: 500;
    letter-spacing: 0.5px;
  }
  

  .day, .date {
    background-color: #fff;
    border: 1px solid #ccc;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 16px;
    border-radius: 8px;
    padding: 5px;
  }

  .day {
    background-color: #dedede;
    font-weight: bold;
    height: 60px;
    justify-content: center;
  }

  .due-task {
    font-size: 12px;
    background-color: #ffe4c4;
    padding: 3px 5px;
    margin-top: 5px;
    border-radius: 4px;
    color: #5a3e36;
    text-align: center;
    width: 100%;
  }

  a.nav {
    text-decoration: none;
    background-color: #6e473b;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    font-size: 18px;
  }
.date.today {
  border: 2px solid green;
  background-color: green
  box-shadow: 0 0 8px rgba(192, 57, 43, 0.3);
}

.header-date-group {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.header-date {
  font-size: 2.2rem;
  font-weight: 600;
  color: #222;
  letter-spacing: 1px;
}

.header-day {
  font-size: 1.1rem;
  color: #2ecc40; /* Modern green */
  margin-top: 0.2em;
  font-weight: 500;
  letter-spacing: 0.5px;
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
      <li><a href="profile.php"><i class="fas fa-user"></i> User</a></li>
      <li>
            <a href="#"><i class='bx bxs-bell'></i> Notification</a>
        </li>
      <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
      <li><a href="Projects.php"><i class="fas fa-folder-open"></i>Class Works</a></li>
      <li><a href="calendar (1).php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
      <li><a href="forms.php"><i class="fas fa-clipboard-list"></i> Forms</a></li>
      <li><a href="about.php"><i class="fas fa-users"></i> About Us</a></li>
    </ul>
    <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main-content">
    <div class="calendar-outer-container">
      <div class="calendar-header">
        <a class="nav" href="?month=<?php echo $month - 1; ?>&year=<?php echo $year; ?>">&lt; Prev</a>
        <div class="header-date-group">
          <span class="header-date"><?php echo $headerDate; ?></span>
          <?php if ($showDay): ?>
            <span class="header-day"><?php echo $headerDay; ?></span>
          <?php endif; ?>
        </div>
        <a class="nav" href="?month=<?php echo $month + 1; ?>&year=<?php echo $year; ?>">Next &gt;</a>
      </div>
      <div class="calendar">
        <?php
        $weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($weekDays as $day) {
            echo "<div class='day'>$day</div>";
        }

        for ($i = 0; $i < $startDayOfWeek; $i++) {
            echo "<div class='date'></div>";
        }

        $today = date('Y-m-d');

      for ($d = 1; $d <= $daysInMonth; $d++) {
          $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
          $isTodayClass = ($currentDate === $today) ? ' today' : '';

          echo "<div class='date$isTodayClass'><strong>$d</strong>";

          if (isset($assignments[$d])) {
              foreach ($assignments[$d] as $projectName) {
                  echo "<div class='due-task'>📌 " . htmlspecialchars($projectName) . "</div>";
              }
          }

          echo "</div>";
      }
        ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
