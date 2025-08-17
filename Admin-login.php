<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
include 'log1.php';


// Check DB connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $Email = trim($_POST['Email']);
    $password = trim($_POST['password']);

    // === Handle Login ===
    if (isset($_POST['login'])) {
        $query = "SELECT adminin.*, admininfo.admininfoID FROM adminin LEFT JOIN admininfo ON adminin.Email = admininfo.Email WHERE adminin.Email=?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $Email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // Check if password matches the one in the database
            if ($password === $row['password']) {
                $_SESSION['fname'] = $row['fname'];
                $_SESSION['lname'] = $row['lname'];
                $_SESSION['Email'] = $row['Email']; // ← this makes profile page work
                $_SESSION['admininfoID'] = $row['admininfoID']; // ← this enables tracking who created the project
                $_SESSION['admin_name'] = $row['INSTRUCTOR']; 
                logTransaction('admin', $row['Email'], 'LOGIN', 'Admin successfully logged in.');
                header('Location: Admin-dashboard.php');
                exit();
            } else {
                echo "<script>alert('Incorrect password.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('No user found with this email.'); window.history.back();</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-compatibel" content="IE-edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title> DreamBoard </title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="Admin-login.css">
</head>
 <style>
        .notification-badge {
            background: red;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 12px;
            margin-left: 6px;
        }
        .mark-read-btn {
            margin-left: 15px;
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .mark-read-btn:disabled {
            background: grey;
            cursor: not-allowed;
        }
    </style>
<body>

<header>
<header>
    <div class="navbar">
      <img src="logo.png" alt="Logo" />
      <p>DreamBoard</p>
    </div>
  </header>
</header>

<div class="container">
  <!--signin-->

  <div class="signin-signup">
            
  <form method="POST" action="Admin-login.php" class="sign-in-form">
    <h2 class="title">LOG IN</h2>

    <div class= "input-field">
      <i class='bx bx-user-circle'></i>
      <input type="text" name="Email" placeholder="Email" required>
    </div> 

   <div class= "input-field">
     <i class='bx bxs-lock'></i>
     <input type="password" name="password" placeholder="password" required>
   </div> 
                
  <button type="submit" name="login" class="btn">lOGIN</button>

               
    </form>
               
               <!--signup-->
    <form method="POST" action="Admin-login.php" class="sign-up-form">
      <h2 class="title">SIGN UP</h2>

      <div class= "input-field">
        <i class='bx bx-user-circle'></i>
        <input type="text" name="Email" placeholder="Email" required>
      </div> 

      <div class= "input-field">
        <i class='bx bxs-lock' ></i>
        <input type="password" name="password" placeholder="password" required>
      </div> 

      <div class= "input-field">
        <i class='bx bxs-lock'></i>
        <input type="password" name="Conpassword" placeholder="Confirm password" required>
      </div> 

      <button type="submit" class="btn" name="submit">SIGN UP</button>

      <p class="social-text"> or sign in using: </p>

    <div class="social-media">
      <a href="https://www.facebook.com/" class="social-icon">
        <i class='bx bxl-facebook-circle' ></i>
      </a>

    <a href="https://www.instagram.com/accounts/login/?hl=en" class="social-icon">
      <i class='bx bxl-instagram-alt' ></i>
    </a>

    <a href="https://accounts.google.com.ph/" class="social-icon">
      <i class='bx bxl-google' ></i>
    </a>

    </div>
                 
                 
    </form>

  </div>

  <div class="panels-container">

   <div class="panel left-panel">

     <div class="content">
     <center><img src="DreamBoard (1).png" height="150px" width="350px"></center>
       <h3> one of us?</h3>
       <p>Welcome back, traveler!  Come and see what new recipes are waiting for you!saddle up and 
          feast your way through a world of mouthwatering aromas tantalizing tastes, and delightful 
          recipes around the world await.
      </p>
      <button class="btn" id="sign-in-btn"> log in</button>
    </div>
    </div>

    <div class="panel right-panel">

      <div class="content">
        <center><img src="DreamBoard (1).png" height="150px" width="350px"></center>
        <h3> Hi Admin!</h3>
        <p>Projects are moving, teams are collaborating, and your leadership sets the pace.
Jump back in, review progress, assign tasks, and keep everything on track — because when you're here, productivity thrives</p>
      </div>
                
            </div>
   


  </div>


</div>
<script src="app.js"></script>
</body>

</html>