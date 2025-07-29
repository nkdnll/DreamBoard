<?php
session_start();
include 'log1.php';
include 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$message = '';
$email = $_GET['email'] ?? ''; // Get email from URL or form

// === Handle Resend OTP ===
if (isset($_POST['request_otp'])) {
    header('Content-Type: application/json');
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $stmt = $conn->prepare("SELECT * FROM userin WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

        $update = $conn->prepare("UPDATE userin SET otp = ? WHERE Email = ?");
        $update->bind_param("ss", $otp, $email);
        $update->execute();
        $update->close();

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465;
            $mail->SMTPAuth = true;
            $mail->Username = 'dongustavo638@gmail.com';
            $mail->Password = 'khbttdcuxinbcihi';
            $mail->SMTPSecure = 'ssl';

            $mail->setFrom('dongustavo638@gmail.com', 'DreamBoard Support');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "<p>Your OTP code is: <strong>$otp</strong></p>";

            $mail->send();

            echo json_encode(['success' => true, 'message' => 'OTP has been resent to your email.']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
        exit;
    }
}

// === Handle OTP Verification ===
if (isset($_POST['verify_otp'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $entered_otp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT otp, status FROM userin WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $stored_otp = trim($row['otp']);
        $status = strtolower($row['status']);

        if ($entered_otp === $stored_otp) {
            if ($status !== 'active') {
                $update = $conn->prepare("UPDATE userin SET status = 'active' WHERE Email = ?");
                $update->bind_param("s", $email);
                $update->execute();
                $update->close();

                $message = "<div class='message success'>Email verified! Redirecting to profile form...</div>";
                logTransaction('user', $email, 'Email Verified', 'Account activated');

                echo "<script>
                    setTimeout(() => window.location.href = 'userinfo.php?email=" . urlencode($email) . "', 2000);
                </script>";
            } else {
                $message = "<div class='message info'>Email already verified. Redirecting...</div>";
                echo "<script>
                    setTimeout(() => window.location.href = 'login.php', 2000);
                </script>";
            }
        } else {
            $message = "<div class='message error'>Invalid OTP. Try again.</div>";
        }
    } else {
        $message = "<div class='message error'>Email not found.</div>";
    }

    $stmt->close();
}

$conn->close();
?>

<!-- ========== HTML for OTP Verification ========== -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Email - DreamBoard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            box-sizing: border-box;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }

        .header img {
            height: 50px;
            margin-right: 10px;
            border-radius: 8px;
        }

        .header h1 {
            font-size: 2em;
            color: #4CAF50;
            margin: 0;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        p {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .input-field {
            position: relative;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            background-color: #f0f2f5;
            border-radius: 8px;
            padding: 8px 15px;
        }

        .input-field i {
            color: #777;
            margin-right: 10px;
            font-size: 1.2em;
        }

        .input-field input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 0;
            font-size: 1em;
            outline: none;
            color: #333;
            width: 100%;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }

        .btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.3);
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            text-align: left;
            word-wrap: break-word; /* Ensure long messages break */
        }

        .message.success {
            background-color: #e6ffe6;
            color: #28a745;
            border: 1px solid #28a745;
        }

        .message.error {
            background-color: #ffe6e6;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .message.info {
            background-color: #e0f7fa;
            color: #17a2b8;
            border: 1px solid #17a2b8;
        }

        .links {
            margin-top: 20px;
        }

        .links a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .container {
                margin: 20px;
                padding: 25px;
            }

            .header h1 {
                font-size: 1.8em;
            }

            h2 {
                font-size: 1.5em;
            }

            .btn {
                padding: 10px 20px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
  <div class="container">
    <h2>Email Verification</h2>
    <?= $message ?>

    <form method="POST">
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <label>Enter OTP:</label>
      <input type="text" name="otp" placeholder="12345" required />
      <button type="submit" name="verify_otp">Verify</button>
    </form>

    <form method="POST" id="resend-form">
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <button type="submit" name="request_otp">Resend OTP</button>
    </form>
  </div>

  <script>
  document.getElementById("resend-form").addEventListener("submit", async function(e) {
    e.preventDefault();
    const form = new FormData(this);
    const response = await fetch("", {
      method: "POST",
      body: form
    });
    const result = await response.json();
    alert(result.message);
  });
  </script>
</body>
</html>
