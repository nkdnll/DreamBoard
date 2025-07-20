<?php
session_start();
require 'db.php'; // Connect to DB
include 'log1.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $projectName = $_POST['project_name'];
    $teamName = $_POST['team_name'];
    $teamDescription = $_POST['team_description'];
    $usernamesInput = $_POST['usernames'];

    // Normalize input: comma- or newline-separated
    $enteredUsernames = preg_split('/[\s]*[\r\n,]+[\s]*/', trim($usernamesInput));
    $enteredUsernames = array_filter($enteredUsernames); // remove blanks
    $enteredUsernames = array_map('strtolower', $enteredUsernames); // make lowercase for comparison

    // Fetch all users and build "usernames"
    $usersQuery = "SELECT FIRSTNAME, MIDDLENAME, LASTNAME FROM userinfo";
    $result = $conn->query($usersQuery);

    $validUsernames = [];
    while ($row = $result->fetch_assoc()) {
        $fullname = strtolower(trim($row['FIRSTNAME'] . ' ' . $row['MIDDLENAME'] . ' ' . $row['LASTNAME']));
        $fullname = preg_replace('/\s+/', ' ', $fullname); // Normalize spaces
        $validUsernames[] = $fullname;
    }

    // Compare entered usernames to valid full names
    $missingUsernames = [];
    foreach ($enteredUsernames as $entered) {
        if (!in_array($entered, $validUsernames)) {
            $missingUsernames[] = $entered;
        }
    }

    if (!empty($missingUsernames)) {
        echo "<p style='color:red;'>These users were not found: " . implode(', ', $missingUsernames) . "</p>";
    } else {
        // Store usernames as-is
        $usernames = implode(',', $enteredUsernames);

         $adminId = $_SESSION['admininfoID'];

         if (!isset($_SESSION['admininfoID'])) {
    // redirect to login or show error
    die("Error: Admin not logged in.");
}

        $stmt = $conn->prepare("INSERT INTO projects (project_name, team_name, team_description, usernames, admininfoID) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $projectName, $teamName, $teamDescription, $usernames, $adminId);

        if ($stmt->execute()) {
    // Log the creation
    if (isset($_SESSION['Email'])) {
        $logDescription = "Created project '$projectName' for team '$teamName'";
        logTransaction('admin', $_SESSION['Email'], 'CREATE_PROJECT', $logDescription);
    }

    header("Location: Admin-project.php");
    exit();
        } else {
            echo "Error: " . $stmt->error;
        }

    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About Us - DreamBoard</title>
  <link href="Admin-create.css" rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
  />
</head>

<body>
  <header>
    <div class="navbar">
      <img src="logo.png" width="100" height="50" alt="DreamBoard Logo" />
      <p>DreamBoard</p>
    </div>
  </header>


  <div class="container">

    <div class="sidebar">
      <ul>
        <li class="user">
          <a href="Admin.profile.php"><i class="fas fa-user"></i> User</a>
        </li>
        <li>
          <a href="Admin-Dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        </li>
        <li>
          <a href="Admin-project.php"><i class="fas fa-folder-open"></i> Project</a>
        </li>
        <li>
          <a href="Admin-calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a>
        </li>
        <li>
          <a href="Admin-forms.php"><i class="fas fa-clipboard-list"></i> Forms</a>
        </li>
        <li>
          <a href="Admin-about.php"><i class="fas fa-users"></i> About Us</a>
        </li>
      </ul>
      <a href="Admin-login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <form action="Admin-create.php" method="POST" class="project-form">

    <div class="content">

      <label for="project_name">Create class</label>

      <textarea
        id="project_name"
        name="project_name"
        rows="5"
        placeholder="Project Name"
        required
      ></textarea>

      <textarea
        id="team_name"
        name="team_name"
        rows="5"
        placeholder="Team Name"
        required
      ></textarea>

      <textarea
        id="team_description"
        name="team_description"
        rows="5"
        placeholder="Team Description"
      ></textarea>

      <div class="buttons">
        <button type="reset" class="cancel">
          <a href="Admin-project.php" style="color: inherit; text-decoration: none;">Cancel</a>
        </button>
        <button type="submit">Create</button>
      </div>

      </div>

    </form>

  </div>

  <script src="class.js"></script>
  <script>
    function addUsername() {
      const wrapper = document.getElementById("usenamesWrapper");
      const input = document.createElement("input");
      input.type = "text";
      input.name = "usernames[]";
      input.placeholder = "Enter another student username";
      wrapper.appendChild(document.createElement("br"));
      wrapper.appendChild(input);
    }
  </script>
</body>
</html>
