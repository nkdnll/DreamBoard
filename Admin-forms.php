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
        <link rel="stylesheet" href="Admin-forms.css" />
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
          <h2>Forms</h2>
          <div class="form-list">
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_spreadsheet_x32.png" alt="Google Sheets Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/spreadsheets" target="_blank">Google Sheets</a>
            </div>
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_document_x32.png" alt="Google Docs Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/document" target="_blank">Google Docs</a>
            </div>
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_form_x32.png" alt="Google Forms Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://forms.google.com" target="_blank">Google Forms</a>
            </div>
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_presentation_x32.png" alt="Google Slides Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/presentation" target="_blank">Google Slides</a>
            </div>
            <div class="form-item">
              <img src="https://upload.wikimedia.org/wikipedia/commons/d/da/Google_Drive_logo.png" alt="Google Drive Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://drive.google.com" target="_blank">Google Drive</a>
            </div>
          </div>
        </div>
      </div></div>    
      <script>
        const currentLocation = window.location.pathname;
        const menuItems = document.querySelectorAll('.sidebar li a');
    
        menuItems.forEach(item => {
            if (item.href === window.location.href) {
                item.classList.add('active');
            }
        });
    </script>
  </div>
</body>
</html>