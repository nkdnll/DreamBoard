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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_SESSION['userinfo_ID'];

    $FIRSTNAME = $_POST['FIRSTNAME'];
    $MIDDLENAME = $_POST['MIDDLENAME'];
    $LASTNAME = $_POST['LASTNAME'];
    $SUFFIX = $_POST['SUFFIX'];
    $CITIZENSHIP = $_POST['CITIZENSHIP'];
    $SEX = $_POST['SEX'];
    $BIRTHDAY = $_POST['BIRTHDAY'];
    $CURRENT_SCHOOL = $_POST['CURRENT_SCHOOL'];

    $stmt = $conn->prepare("UPDATE userinfo SET 
      FIRSTNAME = ?, MIDDLENAME = ?, LASTNAME = ?, SUFFIX = ?, 
      CITIZENSHIP = ?, SEX = ?, BIRTHDAY = ?, CURRENT_SCHOOL = ?
      WHERE userinfo_ID = ?");

    $stmt->bind_param("ssssssssi", $FIRSTNAME, $MIDDLENAME, $LASTNAME, $SUFFIX, $CITIZENSHIP, $SEX, $BIRTHDAY, $CURRENT_SCHOOL, $id);

    if ($stmt->execute()) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
}
?>
