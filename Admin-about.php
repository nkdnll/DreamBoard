<?php
session_start();
require 'db.php'; // Connect to DB

$currentPage = basename($_SERVER['PHP_SELF']);
$classesPages = [
  'Admin-project.php',
  'team_proj.php',
  'Admin-teamproj.php',
  'Admin-create.php',
  'Admin-Createproj.php'
];

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>DreamBoard Profile</title>
        <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="Admin-about.css" />
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
        <div class="profile-box">
        <div class="about-why">
            <h1>About us</h1>
            <p>At Dream Board, we turn your biggest goals into reality with smart, efficient, and collaborative project management tools. Our platform helps teams plan, track, and execute projects seamlessly—ensuring deadlines are met and success is achieved. 
                Combining the aspirational vibe of a vision board with powerful organization, Dream Board makes project management effortless, stylish, and goal-driven. Let’s build your dreams—together!</p>
        </div>

        <div class="about-why">
            <h1>Why DreamBoard</h1>
            <p>Using this project management software streamlines collaboration, improves task tracking, and enhances team productivity. It offers intuitive tools for planning, scheduling, and resource management, ensuring deadlines are met and goals are achieved. With real-time updates, clear communication channels, and customizable workflows, 
                this software helps teams stay aligned and efficiently manage projects from start to finish.</p>
        </div>

        <div class="team">
            <h2> Meet our Team </h2>

            <div class="members">
                <img src="benetua.png">
                <img src="cortez.png">
                <img src="sapangila.png">
                <img src="sunga.png">
                <img src="lerio.png">
                <img src="valeza.png">
            </div>


        </div>

        </div>

  </div>

</div> 

</body>
</html>