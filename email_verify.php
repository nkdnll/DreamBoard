<?php
session_start();
include 'log1.php';
include 'config.php';
// Set error reporting to all for debugging, but prevent display on production
ini_set('display_errors', 1); // Set to 0 in production
ini_set('display_startup_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Ensure the connection is established from config.php
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ensure PHPMailer classes are loaded
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// It's good practice to explicitly require PHPMailer files if not relying solely on Composer's autoload
// Composer's autoload should be sufficient if vendor/autoload.php is properly set up.
// If not using Composer for these, or if Composer doesn't pick them up, explicitly include:
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';


// These variables are set but not used in the OTP request/verify logic below
// They might be leftovers from login.php or intended for other parts of the script
// Consider if they are truly needed here, or if they should be scoped locally where used.
$otp_str = str_shuffle("012345");
$otp = substr($otp_str, 0, 0); // This will always result in an empty string for $otp
$status = 'pending'; // This 'pending' status is usually set during registration in login.php

$act_str = rand (10000, 10000000);
$activation_code_gen = str_shuffle ("abcdefghi".$act_str); // Renamed to avoid conflict with $activation_code from GET


$message = ''; // Used for displaying HTML messages in the traditional form submission path
$email = ''; // Initialized for displaying in the form's email input
$activation_code = isset($_GET['code']) ? $_GET['code'] : ''; // For account activation via link, if applicable

// Handle OTP request/resend
if (isset($_POST['request_otp'])) {
    header('Content-Type: application/json'); // IMPORTANT: Tell the browser to expect JSON

    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if email exists in userin table
    $check_email_query = "SELECT * FROM userin WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $check_email_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // The activation_code from the row is not used in the OTP sending process,
        // it's typically for direct account activation via a link.
        // $activation_code_from_db = $row['activation_code']; 

        // Generate a new OTP
        $otp_to_send = substr(str_shuffle("0123456789"), 0, 5); // Use a distinct variable name

        // Update OTP in the database
        $update_otp_query = "UPDATE userin SET otp = ? WHERE Email = ?";
        $stmt_update = mysqli_prepare($conn, $update_otp_query);
        mysqli_stmt_bind_param($stmt_update, "ss", $otp_to_send, $email);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

        try {
            $mail = new PHPMailer(true);
            $mail->IsSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = '465';
            $mail->SMTPAuth = true;
            $mail->Username = 'dongustavo638@gmail.com'; // Your Gmail address
            $mail->Password = 'khbttdcuxinbcihi'; // Your Gmail App Password
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
    file_put_contents('PHPMailer_debug.log', date('Y-m-d H:i:s') . " [$level] $str\n", FILE_APPEND);
};
        
            $mail->setFrom('dongustavo638@gmail.com', 'DreamBoard Support Test');
            $mail->addAddress('your_recipient_email@example.com'); // Your test recipient
            $mail->isHTML(true);
            $mail->Subject = 'PHPMailer Test';
            $mail->Body    = 'This is a test email from PHPMailer setup.';
        
            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            echo " Debug Info: " . $e->getMessage();
        }
    mysqli_stmt_close($stmt); // Close statement for check_email_query
    exit(); // IMPORTANT: Stop script execution after sending JSON response
}
}

// Handle OTP verification (this block typically handles the form submission for OTP verification)
if (isset($_POST['verify_otp'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $entered_otp = mysqli_real_escape_string($conn, $_POST['otp']);

    // Fetch stored OTP and activation status
    $verify_query = "SELECT otp, status FROM userin WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stored_otp = $row['otp'];
        $current_status = $row['status'];

        if ($entered_otp === $stored_otp) {
            // OTP matches, activate the account if not already active
            if ($current_status !== 'active') {
                $update_status_query = "UPDATE userin SET status = 'active' WHERE Email = ?";
                $stmt_update = mysqli_prepare($conn, $update_status_query);
                mysqli_stmt_bind_param($stmt_update, "s", $email);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
                $message = "<div class='message success'>Email verified successfully! Your account is now active.</div>";
                logTransaction('user', $email, 'Email Verification', "Email $email verified and account activated.");
            } else {
                $message = "<div class='message info'>Email already verified.</div>";
            }
            // Redirect to login page after successful verification
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000); // Redirect after 2 seconds
                  </script>";
        } else {
            $message = "<div class='message error'>Invalid OTP. Please try again.</div>";
            logTransaction('user', $email, 'Email Verification Failed', "Invalid OTP entered for $email.");
        }
    } else {
        $message = "<div class='message error'>Email not found.</div>";
    }
    mysqli_stmt_close($stmt); // Close statement for verify_query
}

mysqli_close($conn); // Close the database connection at the end of the script, after all processing.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DreamBoard - Email Verification</title>
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
        <div class="header">
            <img src="logo.png" alt="DreamBoard Logo">
            <h1>DreamBoard</h1>
        </div>
        <h2>Email Verification</h2>

        <?php echo $message; // Display messages here for non-AJAX form submissions ?>

        <form method="POST" action="email_verify.php" id="otpForm">
            <div class="input-field">
                <i class='bx bx-envelope'></i>
                <input type="email" name="email" id="emailInput" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="input-field">
                <i class='bx bx-key'></i>
                <input type="text" name="otp" id="otpInput" placeholder="Enter OTP" required>
            </div>

            <button type="submit" name="verify_otp" class="btn">Verify OTP</button>
            <button type="button" name="request_otp" class="btn" id="requestOtpBtn">Resend OTP</button>
        </form>

        <div class="links">
            <p>Do you already have an account? <a href="login.php">Log In</a></p>
        </div>
    </div>

    <script>
        // Client-side validation and dynamic button text
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('emailInput');
            const requestOtpBtn = document.getElementById('requestOtpBtn');
            // const otpInput = document.getElementById('otpInput'); // Not directly used in this event listener
            // const otpForm = document.getElementById('otpForm'); // Not directly used in this event listener

            // Add event listener for the "Resend OTP" button
            requestOtpBtn.addEventListener('click', async function() {
                const email = emailInput.value.trim();
                if (!email) {
                    showMessageBox("Please enter your email address to request an OTP.", "error");
                    return;
                }

                // Temporarily disable the button and show loading text
                requestOtpBtn.disabled = true;
                const originalText = requestOtpBtn.textContent;
                requestOtpBtn.textContent = 'Sending...';

                const formData = new FormData();
                formData.append('email', email);
                formData.append('request_otp', '1'); // Indicate OTP request

                try {
                    const response = await fetch('email_verify.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Always log the raw response text for debugging
                    const rawResponse = await response.text();
                    console.log('Raw Server Response:', rawResponse);

                    // Attempt to parse as JSON
                    const result = JSON.parse(rawResponse); // Use JSON.parse with rawResponse

                    if (result.success) {
                        showMessageBox(result.message, "success");
                    } else {
                        showMessageBox(result.message, "error");
                    }
                } catch (error) {
                    console.error('Error requesting OTP or parsing JSON:', error);
                    // Show a generic error if parsing fails or other network error
                    showMessageBox('An error occurred while requesting OTP. Please try again. (Check console for details)', "error");
                } finally {
                    // Re-enable the button and restore text after a short delay
                    setTimeout(() => {
                        requestOtpBtn.disabled = false;
                        requestOtpBtn.textContent = originalText;
                    }, 2000); // 2-second delay before re-enabling
                }
            });

            // This function is already defined in content.php, but included here for self-containment
            function showMessageBox(message, type) {
                const messageBox = document.getElementById('messageBox');
                const messageText = document.getElementById('messageText');
                messageText.textContent = message;
                messageBox.className = 'message-box ' + type;
                messageBox.style.display = 'block';
            }

            function hideMessageBox() {
                document.getElementById('messageBox').style.display = 'none';
            }
        });
    </script>
    <div id="messageBox" class="message-box" style="display: none;">
        <p id="messageText"></p>
        <button onclick="hideMessageBox()">OK</button>
    </div>
</body>
</html>