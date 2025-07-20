<?php
require 'db.php'; // Connect to DB

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

        $stmt = $conn->prepare("INSERT INTO projects (project_name, team_name, team_description, usernames) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $projectName, $teamName, $teamDescription, $usernames);

        if ($stmt->execute()) {
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
    <meta charset="UTF-8">
    <title>About Us - DreamBoard</title>
    <link href= "Admin-create.css" rel = "stylesheet">         
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
</head>
<header>
        <div class="navbar">
              <img src="logo.png" width="100px" height="50px">
              
              <p>DreamBoard</p>
        </div>
      </header>  
            
<body>
  <div class="container">
      <div class="sidebar">
        <ul>
          <li class="user">
            <a href="Admin.profile.php"><i class="fas fa-user"></i> Admin</a>
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
    </div><form action="Admin-create.php" method="POST" class="project-form">

    <label for = "class" >Create class</label>
  
  <textarea id="project_name" name="project_name" rows="5" placeholder="Project Name" required></textarea>

  <textarea id="team_name" name="team_name" rows="5" placeholder="Team Name" required></textarea>

  
  <textarea id="team_description" name="team_description" rows="5" placeholder="Team Description"></textarea>

  
  <textarea id="usernames" name="usernames" rows="5" placeholder="Usernames (one per line or comma-separated)" required></textarea>

  <div class="buttons">
    <button type="reset" class="cancel" >
        <a href="Admin-project.php">Cancel</a>
    </button>
    <button type="submit">Create</button>
  </div>
</form>
  
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


            
        </aside>
    </div>
</body>
</html>