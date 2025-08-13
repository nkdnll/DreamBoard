<?php
session_start();
include 'unread_count.php';
$conn = mysqli_connect("localhost", "root", "", "projectmanagement");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['Email'])) {
    echo "No user is logged in.";
    exit();
}

$email = $_SESSION['Email'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_pic"])) {
    $file = $_FILES["profile_pic"];
    $targetDir = "uploads/"; // Make sure this folder exists and is writable
    $fileName = basename($file["name"]);
    $targetFile = $targetDir . uniqid() . "_" . $fileName;

    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];

    if (!in_array($imageFileType, $allowedTypes)) {
        echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
        exit();
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Update user record with new profile pic path
        $query = "UPDATE admininfo SET PROFILE_PIC = ? WHERE EMAIL = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $targetFile, $email);
        if ($stmt->execute()) {
            header("Location: Admin.profile.php"); // Reload the profile page
            exit();
        } else {
            echo "Failed to update profile picture in database.";
        }
    } else {
        echo "Failed to upload file.";
    }
} else {
    echo "No file uploaded.";
}
?>
