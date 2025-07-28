<?php
$currentPage = basename($_SERVER['PHP_SELF']);
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
        <link rel="stylesheet" href="forms.css" />
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
        <div class="profile-box">
          <h2>Forms</h2>
          <div class="form-list">
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_spreadsheet_x32.png" alt="Google Sheets Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/spreadsheets/d/1zngIsnhwwSwSBeqsb6BAgjFIHUPCF93y5gpXsTkoG_U/edit?gid=0#gid=0" target="_blank">Google Sheets</a>
            </div>
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_document_x32.png" alt="Google Docs Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/document/d/1FTQ_XJXKDHRpKi5_7djXeBBo0AUk92KrxQQZqlMhe-o/edit?tab=t.0" target="_blank">Google Docs</a>
            </div>
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_form_x32.png" alt="Google Forms Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/forms/d/1yZRO70S3gtkHcOTg4Uo3kLJ7mF_8AKGfGpLGmyxo3cE/edit" target="_blank">Google Forms</a>
            </div>
            <div class="form-item">
              <img src="https://ssl.gstatic.com/docs/doclist/images/mediatype/icon_1_presentation_x32.png" alt="Google Slides Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://docs.google.com/presentation/d/1cibZvbI0m4WLZRJT2sgEOofk5u5bC8Pd-K7eG_Tw93A/edit?usp=sharing" target="_blank">Google Slides</a>
            </div>
            <div class="form-item">
              <img src="https://upload.wikimedia.org/wikipedia/commons/d/da/Google_Drive_logo.png" alt="Google Drive Icon" style="width:24px; height:24px; vertical-align:middle; margin-right:8px;">
              <a href="https://drive.google.com" target="_blank">Google Drive</a>
            </div>
          </div>
        </div>
      </div>
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
</div> 
</body>
</html>