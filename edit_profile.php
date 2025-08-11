<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['userinfo_ID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userinfo_ID'];

// Fetch current user info
$stmt = $conn->prepare("SELECT * FROM userinfo WHERE userinfo_ID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="edit_profile.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <img src="logo.png" alt="Logo"> <p>Dreamboard</p>
        </nav>
    </header>

    <div class="main-container">
        <form method="POST" action="update_profile.php">
            <div class="form-row">
            
                <div class="profile-box">
                    <h2 class="box-header"> Edit Profile</h2>
                    <div class="box-line"></div>
                    
                    <div class="form-column">
                        <div class="form-group">
                            <input type="text" name="FIRSTNAME" value="<?= htmlspecialchars($user['FIRSTNAME']) ?>" required>
                            <label>FIRST NAME:</label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="MIDDLENAME" value="<?= htmlspecialchars($user['MIDDLENAME']) ?>" required>
                            <label>MIDDLE NAME:</label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="LASTNAME" value="<?= htmlspecialchars($user['LASTNAME']) ?>" required>
                            <label>LAST NAME:</label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="SUFFIX" value="<?= htmlspecialchars($user['SUFFIX']) ?>">
                            <label>SUFFIX:</label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="CITIZENSHIP" value="<?= htmlspecialchars($user['CITIZENSHIP']) ?>" required>
                            <label>CITIZENSHIP:</label>
                        </div>
                    </div>
                </div>
            
                <div class="profile-box">
                    <h2 class="box-header" style="visibility: hidden;">Profile</h2> 
                    <div class="box-line" style="visibility: hidden;"></div> 

                    <div class="form-column">
                        <div class="form-group">
                            <select name="SEX" required>
                                <option value="Male" <?= $user['SEX'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $user['SEX'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Prefer not to say" <?= $user['SEX'] == 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                            </select>
                            <label>SEX:</label>
                        </div>
                        <div class="form-group">
                            <div class="date-input-container">
                                <input type="date" name="BIRTHDAY" value="<?= htmlspecialchars($user['BIRTHDAY']) ?>" required>
                                
                            </div>
                            <label>BIRTHDAY:</label>
                        </div>
                        <div class="form-group">
                            <div class="email-display"><?= htmlspecialchars($user['EMAIL']) ?></div>
                            <label>EMAIL:</label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="CURRENT_SCHOOL" value="<?= htmlspecialchars($user['CURRENT_SCHOOL']) ?>" required>
                            <label>CURRENT SCHOOL:</label>
                        </div>
                    </div>

                    <button type="submit" name="update" class="done-btn">DONE</button>
                </div>
            
            </div>
        </form>
    </div>
    
</body>
</html>