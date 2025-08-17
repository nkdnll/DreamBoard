<?php
session_start();
require 'db.php'; // connection file mo

// Check kung logged in
if (!isset($_SESSION['Email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];

// Check kung may POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $firstname   = mysqli_real_escape_string($conn, $_POST['FirstName']);
    $lastname    = mysqli_real_escape_string($conn, $_POST['LastName']);
    $gender      = mysqli_real_escape_string($conn, $_POST['Gender']);
    $instructor  = mysqli_real_escape_string($conn, $_POST['Instructor']);
    $contact     = mysqli_real_escape_string($conn, $_POST['Contact']);
    $address     = mysqli_real_escape_string($conn, $_POST['Address']);
    $birthdate   = mysqli_real_escape_string($conn, $_POST['Birthdate']);

    // Update query
    $sql = "UPDATE admininfo 
            SET FirstName = '$firstname',
                LastName = '$lastname',
                Gender = '$gender',
                Instructor = '$instructor',
                Contact = '$contact',
                Address = '$address',
                Birthdate = '$birthdate'
            WHERE Email = '$email'";

    if (mysqli_query($conn, $sql)) {
        // Success → balik sa profile page
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: admin_profile.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
} else {
    // Kung walang POST, balik sa edit profile
    header("Location: admin_edit_profile.php");
    exit();
}
?>
