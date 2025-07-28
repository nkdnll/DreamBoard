  <?php
  session_start();
  include 'log1.php';
  include  'config.php';


  require 'PHPMailer/src/PHPMailer.php';
  require 'PHPMailer/src/SMTP.php';
  require 'PHPMailer/src/Exception.php';

  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\SMTP;
  use PHPMailer\PHPMailer\Exception;

        
                
          
  // Ensure the connection is established from config.php
  if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
  }

  // Check if the PHPMailer class exists before requiring it
  // This prevents errors if it's not needed for every page load
  if (!class_exists('PHPMailer\PHPMailer\PHPMailer')){
      require 'PHPMailer/src/Exception.php';
  }


  if ($_SERVER['REQUEST_METHOD'] == "POST") {
      $Email = trim($_POST['Email']);
      $password = trim($_POST['password']);

      // === Handle Login ===
      if (isset($_POST['login'])) {
          $query = "SELECT u.UserID, u.Email, u.password, u.status, ui.userinfo_ID, ui.firstname, ui.middlename, ui.lastname, ui.PROFILE_PIC
            FROM userin u
            LEFT JOIN userinfo ui ON u.Email = ui.EMAIL
            WHERE u.Email = ?"; // Changed to LEFT JOIN as userinfo might not exist yet for new users
          $stmt = mysqli_prepare($conn, $query);
          mysqli_stmt_bind_param($stmt, "s", $Email);
          mysqli_stmt_execute($stmt);
          $result = mysqli_stmt_get_result($stmt);

          if (mysqli_num_rows($result) == 1) {
              $row = mysqli_fetch_assoc($result);

              if ($password === $row['password']) { // Assuming password is not hashed for now based on original code
                  if ($row['status'] === 'active') {
                      $_SESSION['Email'] = $row['EMAIL'];
                      $_SESSION['userinfo_ID'] = $row['userinfo_ID'];
                      $fullname = trim($row['firstname'] . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? '')); // Handle potential null middlename/lastname
                      $_SESSION['username'] = $fullname;
                      $_SESSION['profile_pic'] = $row['PROFILE_PIC'];
                      logTransaction('user', $email, 'LOGIN', 'User successfully logged in.');
                      header('Location: dashboard.php');
                      exit();
                  } elseif ($row['status'] === 'pending') {
                      echo "<script>alert('Your account is not verified. Please check your email for the OTP.'); window.location.href='email_verify.php?email=" . urlencode($Email) . "';</script>";
                      exit();
                  } else {
                      echo "<script>alert('Your account status is " . htmlspecialchars($row['status']) . ". Please contact support.'); window.history.back();</script>";
                  }
              } else {
                  echo "<script>alert('Incorrect password.'); window.history.back();</script>";
              }
          } else {
              echo "<script>alert('No user found with this email.'); window.history.back();</script>";
          }
          mysqli_stmt_close($stmt);
      }

      // === Handle Registration ===
      elseif (isset($_POST['submit'])) {
          $confirm_password = trim($_POST['Conpassword']);

          if ($password !== $confirm_password) {
              echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
          } else {
              // Modified check_query to also select the status
              $check_query = "SELECT status FROM userin WHERE Email = ?";
              $stmt = mysqli_prepare($conn, $check_query);
              mysqli_stmt_bind_param($stmt, "s", $Email);
              mysqli_stmt_execute($stmt);
              $check_result = mysqli_stmt_get_result($stmt);

              if (mysqli_num_rows($check_result) > 0) {
                  $existing_user = mysqli_fetch_assoc($check_result);
                  if ($existing_user['status'] === 'active') {
                      echo "<script>alert('Email already registered and active. Please use a different one or log in.'); window.history.back();</script>";
                  } elseif ($existing_user['status'] === 'pending') {
                      echo "<script>alert('This email is already registered and pending verification. Please verify your account or use a different email.'); window.location.href='email_verify.php?email=" . urlencode($Email) . "';</script>";
                      exit();
                  } else {
                      // Handle other statuses if necessary, or default to already registered
                      echo "<script>alert('Email already registered with an unknown status. Please contact support.'); window.history.back();</script>";
                  }
            } else {
                // Generate OTP for new registration
                $otp_str = str_shuffle("0123456789");
                $otp = substr($otp_str, 0, 5);
                $status = 'pending'; // Set initial status to pending

                // Insert user with OTP and pending status
                // IMPORTANT: For this to work, ensure your `userin` table has `otp` (VARCHAR(10))
                // and `status` (VARCHAR(50) DEFAULT 'pending') columns.
                // You might need to run SQL like:
                // ALTER TABLE userin ADD COLUMN otp VARCHAR(10) DEFAULT NULL;
                // ALTER TABLE userin ADD COLUMN status VARCHAR(50) DEFAULT 'pending';
                              

                $sql = "INSERT INTO userin (Email, password, otp, status) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssss", $Email, $password, $otp, $status);

                if (mysqli_stmt_execute($stmt)) {
                  $message_body = "
                     <h2>Welcome to DreamBoard!</h2>
                     <p>Thank you for registering with us.</p>
                     <p>Your One-Time Password (OTP) for verification is: <strong>$otp</strong></p>
                     <p>Please enter this code on the verification page to activate your account.</p>
                     ";

                  $mail = new PHPMailer(true); // Enable exceptions
                  try{
                      $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // ✅ Gmail SMTP
                        $mail->Port = 587;              // ✅ TLS port
                        $mail->SMTPAuth = true;
                        $mail->Username = 'dreamboard07@gmail.com'; 
                        $mail->Password = 'dnzmontlkggfwomk'; 
                        $mail->SMTPSecure = 'tls';
                        $mail->From = 'dreamboard07@gmail.com';
                        $mail->FromName = 'Dreamboard';
                        $mail->addAddress($Email);
                        $mail->WordWrap = 50;
                        $mail->isHTML(true);
                        $mail->Subject = 'DreamBoard: Verify Your Email Address';
                        $mail->Body = $message_body;

                      
                        $mail->send();
                        logTransaction('user', $Email, 'Registration', 'New user registered and OTP sent for verification.');
                        echo "<script>alert('Registration successful! Please check your email for a verification code.'); window.location.href='email_verify.php?email=" . urlencode($Email) . "';</script>";
                      } catch (Exception $e) {
                        echo "Mailer Error: " . $mail->ErrorInfo;
                          // If email sending fails, log the error
                          logTransaction('user', $Email, 'Registration Failed', "New user registered but failed to send OTP. Mailer Error: {$mail->ErrorInfo}");
                          echo "<script>alert('Registration successful, but failed to send verification email. Please try resending OTP from the verification page or contact support. Error: {$e->getMessage()}'); window.location.href='email_verify.php?email=" . urlencode($Email) . "';</script>";
                          exit();
                      }
                }
              }
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
      <link rel="stylesheet" href="login.css">
  </head>

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

    <form method="POST" action="login.php" class="sign-in-form">
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

          <p class="account-text"> Dont have an acc? <a href="#" id="sign-up-btn2">Sign Up</a></p>

      </form>

                <!--signup-->
      <form method="POST" action="login.php" class="sign-up-form">
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

      <p class="account-text"> already have an acc? <a href="#" id="sign-in-btn2">Sign In</a></p>

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
          <h3> want to be one of us?</h3>
          <p>Welcome to our vibrant online menu, Hungry for more than just delicious dishes?
            Create your free account today and feast your way through a world of
            mouthwatering aromas   tantalizing tastes, and delightful recipes around the world await.</p>
          <button class="btn" id="sign-up-btn">  Sign Up</button>
        </div>

              </div>


    </div>


  </div>
  <script src="app.js"></script>
  </body>

  </html>
