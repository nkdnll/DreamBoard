<?php
session_start();
require 'log1.php';

$conn = mysqli_connect("localhost", "root", "", "projectmanagement");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Siguraduhin na naka-login ang admin
if (!isset($_SESSION['Email'])) {
    header("Location: Admin-login.php");
    exit();
}

$email = $_SESSION['Email'];

// Fetch current admin data
$query = "SELECT * FROM admininfo WHERE EMAIL = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    echo "Admin not found.";
    exit();
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instructor = $_POST['INSTRUCTOR'];
    $office = $_POST['OFFICE'];
    $contact = $_POST['CONTACT'];
    $gender = $_POST['GENDER'];
    $birthday = $_POST['BIRTHDAY'];
    $citizenship = $_POST['CITIZENSHIP'];
    $university = $_POST['UNIVERSITY'];

    $update = "UPDATE admininfo SET INSTRUCTOR=?, OFFICE=?, CONTACT=?, GENDER=?, BIRTHDAY=?, CITIZENSHIP=?, UNIVERSITY=? WHERE EMAIL=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ssssssss", $instructor, $office, $contact, $gender, $birthday, $citizenship, $university, $email);

    if ($stmt->execute()) {
        header("Location: Admin.profile.php?updated=1");
        exit();
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Admin Profile</title>
    <link rel="stylesheet" href="Admin-edit_profile.css"> 
</head>
<body>
<header>
    <div class="navbar">
        <img src="logo.png" alt="Logo" />
        <p>Dreamboard</p>
    </div>
</header>

<div class="main-container">
    <form method="POST">
        <div class="form-row">
            <div class="profile-box">
                <div class="box-header">Edit Profile</div>
                <div class="box-line"></div>
                <div class="form-column">
                    <div class="form-group">
                        <input type="text" name="INSTRUCTOR" value="<?= htmlspecialchars($admin['INSTRUCTOR']) ?>" required>
                        <label>Instructor:</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="OFFICE" value="<?= htmlspecialchars($admin['OFFICE']) ?>">
                        <label>Office:</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="CONTACT" value="<?= htmlspecialchars($admin['CONTACT']) ?>">
                        <label>Contact:</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="CITIZENSHIP" value="<?= htmlspecialchars($admin['CITIZENSHIP']) ?>">
                        <label>Citizenship:</label>
                    </div>
                </div>
            </div>

            <div class="profile-box">
                <div class="form-column">
                    <div class="form-group">
                        <select name="GENDER">
                            <option value="Male" <?= ($admin['GENDER']=="Male")?'selected':'' ?>>Male</option>
                            <option value="Female" <?= ($admin['GENDER']=="Female")?'selected':'' ?>>Female</option>
                            <option value="Prefer not to say" <?= ($admin['GENDER']=="Prefer not to say")?'selected':'' ?>>Prefer not to say</option>
                        </select>
                        <label>Gender:</label>
                    </div>

                    <div class="form-group">
                        <div class="date-input-container">
                            <input type="date" name="BIRTHDAY" value="<?= htmlspecialchars($admin['BIRTHDAY']) ?>">
                        </div>
                        <label>Birthday:</label>
                    </div>

                    <div class="form-group">
                        <input type="text" name="UNIVERSITY" value="<?= htmlspecialchars($admin['UNIVERSITY']) ?>">
                        <label>University:</label>
                    </div>
                    
                    <div class="form-group">
                        <div class="email-display"><?= htmlspecialchars($admin['EMAIL']) ?></div>
                        <label>Email:</label>
                    </div>
                    
                    <button type="submit" class="done-btn">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>
</body>
</html>