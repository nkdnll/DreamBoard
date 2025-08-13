<?php
$conn = new mysqli("localhost", "root", "", "projectmanagement");
if ($conn->connect_error) {
    die(json_encode([]));
}

// You can adjust LIMIT and ORDER as needed
$sql = "
    SELECT c.comment_text AS message, c.created_at
    FROM comments c
    ORDER BY c.created_at DESC
    LIMIT 10
";
$result = $conn->query($sql);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

header('Content-Type: application/json');
echo json_encode($notifications);
