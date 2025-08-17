<?php
session_start();
require 'db.php';
include 'log1.php';
include 'unread_count.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proj_id'])) {
    $proj_id = intval($_POST['proj_id']);

    // Step 1: Get project name for logging
    $getNameQuery = "SELECT project_name FROM projects WHERE proj_id = ?";
    $nameStmt = $conn->prepare($getNameQuery);
    $nameStmt->bind_param("i", $proj_id);
    $nameStmt->execute();
    $result = $nameStmt->get_result();
    $project = $result->fetch_assoc();
    $projectName = $project['project_name'] ?? 'Unknown';

    // Step 2: Delete the project
    $delete_query = "DELETE FROM projects WHERE proj_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $proj_id);

    if ($stmt->execute()) {
        // Step 3: Log the deletion
        if (isset($_SESSION['Email'])) {
            logTransaction('admin', $_SESSION['Email'], 'DELETE_PROJECT', "Deleted project '$projectName' (ID: $proj_id)");
        }

        header("Location: Admin-project.php?status=deleted");
    } else {
        echo "Error deleting project.";
    }

    $stmt->close();
    $nameStmt->close();
    $conn->close();
} else {
    header("Location: Admin-project.php");
    exit();
}
?>
