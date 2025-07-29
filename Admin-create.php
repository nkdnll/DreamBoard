<?php
session_start();
require 'db.php'; // Connect to DB
include 'log1.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$classesPages = [
  'Admin-project.php',
  'team_proj.php',
  'Admin-teamproj.php',
  'Admin-create.php',
  'Admin-Createproj.php'
];

// Function to generate unique join code
function generateJoinCode($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['admininfoID'])) {
        die("Error: Admin not logged in.");
    }

    $projectName = trim($_POST['project_name']);
    $teamName = trim($_POST['team_name']);
    $teamDescription = trim($_POST['team_description']);
    $adminId = $_SESSION['admininfoID'];
    $joinCode = generateJoinCode();

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO projects (project_name, team_name, team_description, join_code, admininfoID) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $projectName, $teamName, $teamDescription, $joinCode, $adminId);

    if ($stmt->execute()) {
        // Log creation
        if (isset($_SESSION['Email'])) {
            $logDescription = "Created project '$projectName' for team '$teamName'";
            logTransaction('admin', $_SESSION['Email'], 'CREATE_PROJECT', $logDescription);
        }
        header("Location: Admin-project.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About Us - DreamBoard</title>
  <link href="Admin-create.css" rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
  />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
  <header>
    <div class="navbar">
      <img src="logo.png" width="100" height="50" alt="DreamBoard Logo" />
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



    <form action="Admin-create.php" method="POST" class="project-form">

    <div class="content">

      <label for="project_name">Create class</label>

      <textarea
        id="project_name"
        name="project_name"
        rows="5"
        placeholder="Class Name"
        required
      ></textarea>

      <textarea
        id="team_name"
        name="team_name"
        rows="5"
        placeholder="Subject"
        required
      ></textarea>

      <textarea
        id="team_description"
        name="team_description"
        rows="5"
        placeholder="Class Description"
      ></textarea>

      <div class="buttons">
        <button type="reset" class="cancel">
          <a href="Admin-project.php" style="color: inherit; text-decoration: none;">Cancel</a>
        </button>
        <button type="submit">Create</button>
      </div>

      </div>

    </form>

  </div>

  <script src="class.js"></script>
  <script>
    function addUsername() {
      const wrapper = document.getElementById("usenamesWrapper");
      const input = document.createElement("input");
      input.type = "text";
      input.name = "usernames[]";
      input.placeholder = "Enter another student username";
      wrapper.appendChild(document.createElement("br"));
      wrapper.appendChild(input);
    }
  </script>
</body>
</html>
