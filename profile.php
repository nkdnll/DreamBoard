<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Make sure the email is stored in session
if (!isset($_SESSION['userinfo_ID'])) {
    echo "No user is logged in.";
    exit();
}

$email = $_SESSION['Email'];

// Fetch user data by ID
$query = "SELECT * FROM userinfo WHERE userinfo_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userinfo_ID']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}
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
        <link rel="stylesheet" href="profile.css" />
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
            <a href="profile.php"><i class="fas fa-user"></i> User</a>
        </li>
        <li>
            <a href="#"><i class='bx bxs-bell'></i> Notification</a>
        </li>
          <li>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        </li>
          <li>
            <a href="Projects.php"><i class="fas fa-folder-open"></i>Class Works</a>
        </li>
          <li>
            <a href="calendar (1).php"><i class="fas fa-calendar-alt"></i> Calendar</a>
        </li>
          <li>
            <a href="forms.php"><i class="fas fa-clipboard-list"></i> Forms</a>
        </li>
          <li>
            <a href="about.php"><i class="fas fa-users"></i> About Us</a>
        </li>
        </ul>
        <a href="login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
      
            
    <div class="main-content">
      <div class="profile-box">
        <h2>Profile</h2>
        <div class="content">

        <div class="profile-row-box">
        <div class="profile-row"><span class="profile-label">FIRST NAME:</span> <?php echo htmlspecialchars($user['FIRSTNAME']); ?></div>
        <div class="profile-row"><span class="profile-label">MIDDLE NAME:</span> <?php echo htmlspecialchars($user['MIDDLENAME']); ?></div>
        <div class="profile-row"><span class="profile-label">LAST NAME:</span> <?php echo htmlspecialchars($user['LASTNAME']); ?></div>
        <div class="profile-row"><span class="profile-label">SUFFIX:</span> <?php echo htmlspecialchars($user['SUFFIX']); ?></div>
        <div class="profile-row"><span class="profile-label">SEX:</span> <?php echo htmlspecialchars($user['SEX']); ?></div>
        <div class="profile-row"><span class="profile-label">BIRTHDAY:</span> <?php echo htmlspecialchars($user['BIRTHDAY']); ?></div>
        <div class="profile-row"><span class="profile-label">CITIZENSHIP:</span> <?php echo htmlspecialchars($user['CITIZENSHIP']); ?></div>
        <div class="profile-row"><span class="profile-label">EMAIL:</span> <?php echo htmlspecialchars($user['EMAIL']); ?></div>
        <div class="profile-row"><span class="profile-label">CURRENT SCHOOL:</span> <?php echo htmlspecialchars($user['CURRENT_SCHOOL']); ?></div>
      
        </div>
        
        <div class="profilepic">
        <?php
        $profilePic = !empty($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'change profile.png';
        ?>
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="User Profile Picture" class="profile-image" />

                  <!-- Upload Form -->
        <form action="uploads-user.php" method="POST" enctype="multipart/form-data">
            <!-- Hidden file input -->
            <input type="file" name="profile_pic" id="profile_pic" accept="image/*" required hidden>

            <!-- Custom file input label -->
            <label for="profile_pic" class="custom-file-button">Choose Picture</label>

            <button type="submit">Upload</button>
        </form> 
        </div>

        </div>
    </div>
</div>  
  </div>
</body>
</html>