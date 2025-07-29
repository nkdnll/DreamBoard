<?php
session_start();         // ✅ Must come early!
ob_start();              // 🛠 Prevent premature output (so header() works)

// Redirect if email not in session
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$userID = $_SESSION['UserID'];

// Get email from database
$query = $conn->prepare("SELECT Email FROM userin WHERE UserID = ?");
$query->bind_param("i", $userID);
$query->execute();
$query->bind_result($email);
$query->fetch();
$query->close();

$message = "";

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit'])) {
    $FIRSTNAME = trim($_POST['FIRSTNAME']);
    $MIDDLENAME = trim($_POST['MIDDLENAME']);
    $LASTNAME = trim($_POST['LASTNAME']);
    $CITIZENSHIP = trim($_POST['CITIZENSHIP']);
    $SUFFIX = trim($_POST['SUFFIX']);
    $SEX = trim($_POST['SEX']);
    $BIRTHDAY = trim($_POST['BIRTHDAY']);
    $CURRENT_SCHOOL = trim($_POST['CURRENT_SCHOOL']);

    // Prevent duplicate entry
    $check = $conn->prepare("SELECT userinfo_ID FROM userinfo WHERE EMAIL = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "You already have a profile.";
    } else {
        $stmt = $conn->prepare("INSERT INTO userinfo (FIRSTNAME, MIDDLENAME, LASTNAME, CITIZENSHIP, SUFFIX, SEX, BIRTHDAY, EMAIL, CURRENT_SCHOOL)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $FIRSTNAME, $MIDDLENAME, $LASTNAME, $CITIZENSHIP, $SUFFIX, $SEX, $BIRTHDAY, $email, $CURRENT_SCHOOL);

        if ($stmt->execute()) {
            $_SESSION['userinfo_ID'] = $conn->insert_id;
            $_SESSION['fname'] = $FIRSTNAME;
            $_SESSION['lname'] = $LASTNAME;

            header("Location: profile.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DreamBoard Profile</title>
  <style>
   * {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: Arial, sans-serif;
  background-color: #F7F2F2;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

header {
  position: fixed;
  height: 10%;
  width: 100%;
  top: 0;
  left: 0;
  background: #291C0E;
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
  width: 40px;
  height: 40px;
  object-fit: contain;
  margin-right: 15px;
}

.navbar p {
  font-size: 25px;
  color: rgb(238, 238, 238);
  font-weight: bold;
  margin: 0;
}

/* Main Container */
.main-container {
  max-width: 1200px;
  width: 90%;
  margin: auto;
  margin-top: 200px; /* to offset fixed header */
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Layout Row */
.form-row {
  display: flex;
  justify-content: center;
  gap: 40px;
  flex-wrap: wrap;
  width: 100%;
}

/* Profile Box Styling */
.profile-box {
  background-color: white;
  padding: 40px 30px;
  border-radius: 15px;
  flex: 1;
  min-width: 350px;
  max-width: 700px;
  display: flex;
  flex-direction: column;
}

.box-header {
  color: black;
  font-size: 26px;
  font-weight: bold;
  margin-bottom: 10px;
  text-align: left;
}

.box-line {
  height: 2px;
  background-color: black;
  width: 100%;
  margin-bottom: 25px;
}

/* Form Columns */
.form-column {
  display: flex;
  flex-direction: column;
  gap: 30px;
  align-items: center;
}

.form-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

/* Input Styling */
input {
  height: 50px;
  background-color: #D9D9D9;
  border: none;
  border-radius: 8px;
  padding: 0 15px;
  font-size: 18px;
  width: 100%;
  max-width: 400px;
  text-align: center;
  color: black;
}

input::placeholder {
  color: black;
}

/* Label Styling */
label {
  margin-top: 10px;
  font-size: 16px;
  font-weight: bold;
  color: black;
  text-transform: uppercase;
  text-align: center;
}

/* Button Styling */
.done-btn {
  margin-top: 40px;
  width: 150px;
  padding: 14px;
  background-color: #5A3824;
  border: none;
  font-weight: bold;
  border-radius: 8px;
  cursor: pointer;
  color: white;
  font-size: 16px;
  transition: background-color 0.3s ease;
  align-self: flex-end;
}

.done-btn:hover {
  background-color: #8c6a4b;
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

  <form method="POST" action="userinfo.php">
  <div class="main-container">
    <div class="form-row">
      
      <div class="profile-box">
        <div class="box-header">Profile</div>
        <div class="box-line"></div>
        <div class="form-column">

          <div class="form-group">
            <input type="text" name="FIRSTNAME" required/>
            <label>FIRST NAME:</label>
          </div>

          <div class="form-group">
            <input type="text" name="MIDDLENAME" required/>
            <label>MIDDLE NAME:</label>
          </div>

          <div class="form-group">
            <input type="text"name="LASTNAME" required/>
            <label>LAST NAME:</label>
          </div>

          <div class="form-group">
            <input type="text" name="SUFFIX"/>
            <label>SUFFIX:</label>
          </div>

          <div class="form-group">
            <input type="text" name="CITIZENSHIP" required />
            <label>CITIZENSHIP:</label>
          </div>

        </div>
      </div>

    
      <div class="profile-box">
        <div class="box-header">&nbsp</div>
        <div class="box-line"></div>
        <div class="form-column">

         <div class="form-group">
            <input type="text" name="SEX" required/>
            <label>SEX:</label>
            </div >

          <div class="form-group">
            <input type="date" name="BIRTHDAY" required/>
            <label>BIRTHDAY:</label>
          </div>
          
          <div class="form-group">
            <input type="hidden" name="EMAIL" value="<?= htmlspecialchars($email) ?>">
            <p style="font-size: 18px; font-weight: bold;"><?= htmlspecialchars($email) ?></p>
            <label>EMAIL:</label>
          </div>

          <div class="form-group">
            <input type="text" name="CURRENT_SCHOOL" required/>
            <label>CURRENT SCHOOL:</label>
          </div>

          <button type="submit" class="done-btn" name="submit">DONE</button>

        </div>
      </div>
    </div>
  </div>
  </form>
</body>
</html>