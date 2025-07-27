<?php
session_start();
include 'log1.php';
$proj_id = isset($_GET['proj_id']) ? (int)$_GET['proj_id'] : null;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectmanagement";

$currentPage = basename($_SERVER['PHP_SELF']);
$classesPages = [
  'Admin-project.php',
  'team_proj.php',
  'Admin-teamproj.php',
  'Admin-create.php',
  'Admin-Createproj.php'
];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$assignedUsernames = [];

if ($proj_id) {
    $stmt = $conn->prepare("
    SELECT CONCAT(TRIM(u.FIRSTNAME), ' ', TRIM(u.MIDDLENAME), ' ', TRIM(u.LASTNAME)) AS full_name
    FROM project_members pm
    JOIN userinfo u ON pm.userinfo_id = u.userinfo_ID
    WHERE pm.proj_id = ?
");
$stmt->bind_param("i", $proj_id);
$stmt->execute();
$result = $stmt->get_result();
$assignedUsernames = [];

while ($row = $result->fetch_assoc()) {
    $assignedUsernames[] = $row['full_name'];
}
$stmt->close();

}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $project_name = $conn->real_escape_string(trim($_POST['project_name'] ?? ''));
    $instructions = $conn->real_escape_string(trim($_POST['instructions'] ?? ''));
    $assigned_students = $conn->real_escape_string(trim($_POST['assigned_students'] ?? ''));
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
    $due_date = !empty($_POST['due_date']) ? $conn->real_escape_string($_POST['due_date']) : null;

    $stmt = $conn->prepare("INSERT INTO assigned (proj_id, project_name, instructions, assigned_students, points, due_date) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("isssds", $proj_id, $project_name, $instructions, $assigned_students, $points, $due_date);

    if ($stmt->execute()) {
        $assigned_id = $stmt->insert_id;

        if (!empty($assigned_students)) {
    $studentList = array_map('trim', explode(',', $assigned_students));

    // 1. Prepare query to get userinfo_ID from userinfo table
    $idLookupStmt = $conn->prepare("
        SELECT userinfo_ID 
        FROM userinfo 
        WHERE CONCAT(TRIM(FIRSTNAME), ' ', TRIM(MIDDLENAME), ' ', TRIM(LASTNAME)) = ?
           OR CONCAT(TRIM(FIRSTNAME), ' ', TRIM(LASTNAME)) = ?
    ");

    // 2. Prepare query to insert into assignment_students
    $insertStudentStmt = $conn->prepare("
        INSERT INTO assignment_students (assigned_id, username, userinfo_ID, status)
        VALUES (?, ?, ?, 'Not Started')
    ");

    // 3. Loop through each selected student name
   foreach ($studentList as $studentUsername) {
    // 1. Look up userinfo_ID
    $idLookupStmt->bind_param("ss", $studentUsername, $studentUsername);
    $idLookupStmt->execute();
    $idLookupStmt->bind_result($uiID);
    $idLookupStmt->fetch();
    $idLookupStmt->free_result();  // fix: clear result buffer
    $idLookupStmt->reset();        // fix: reset statement for next bind

    // 2. Insert student into assignment_students
    $insertStudentStmt->bind_param("isi", $assigned_id, $studentUsername, $uiID);
    $insertStudentStmt->execute();
}


    // 4. Close both statements
    $idLookupStmt->close();
    $insertStudentStmt->close();
}


        // Insert uploaded files (same as before)
        if (!empty($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $fileCount = count($_FILES['attachments']['name']);

            $attach_stmt = $conn->prepare("INSERT INTO attachments (assigned_id, file_name, file_path, file_type, is_url) VALUES (?, ?, ?, ?, 0)");
            if (!$attach_stmt) {
                die("Prepare failed for attachments: (" . $conn->errno . ") " . $conn->error);
            }

            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = basename($_FILES['attachments']['name'][$i]);
                $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                $fileType = $_FILES['attachments']['type'][$i];

                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $targetFilePath = "uploads/" . uniqid() . "-" . $fileName; // ✅ make sure "uploads/" is included


                if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
                    $attach_stmt->bind_param("isss", $assigned_id, $fileName, $targetFilePath, $fileType);
                    $attach_stmt->execute();
                } else {
                    echo "Error uploading file: " . htmlspecialchars($fileName) . "<br>";
                }
            }

            $attach_stmt->close();
        }

        // Insert URL attachments sent as arrays in POST (e.g., attachment_urls[], attachment_types[])
        if (!empty($_POST['attachment_urls']) && !empty($_POST['attachment_types'])) {
            $attachment_urls = $_POST['attachment_urls'];
            $attachment_types = $_POST['attachment_types'];

            // Prepare insert statement for URLs, set file_path = url, file_name = null or url, is_url=1
            $url_stmt = $conn->prepare("INSERT INTO attachments (assigned_id, file_name, file_path, file_type, is_url) VALUES (?, ?, ?, ?, 1)");
            if (!$url_stmt) {
                die("Prepare failed for URL attachments: (" . $conn->errno . ") " . $conn->error);
            }

            foreach ($attachment_urls as $index => $url) {
                $url = trim($url);
                $type = isset($attachment_types[$index]) ? trim($attachment_types[$index]) : 'url';

                if (!empty($url)) {
                    // Here file_name can be null or the URL itself (or you can parse filename from URL if you want)
                    $fileName = null;

                    $url_stmt->bind_param("isss", $assigned_id, $fileName, $url, $type);
                    $url_stmt->execute();
                }
            }

            $url_stmt->close();
        }
        if (isset($_SESSION['Email'])) {
          $adminEmail = $_SESSION['Email'];
          $assignedToList = implode(', ', $studentList); // already built from input

          $description = "Assigned task '{$project_name}' (Project ID: $proj_id) to students: $assignedToList.";
          logTransaction('admin', $adminEmail, 'ASSIGN_TASK', $description);
}


        header("Location: team_proj.php?proj_id=" . $proj_id);
        exit();

    } else {
        echo "Error inserting project: " . $stmt->error;
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Create Project - DreamBoard</title>
    <link rel="stylesheet" href="Admin-Createproj.css" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet" />         
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<header>
    <div class="navbar">
        <img src="logo.png" width="100px" height="50px" alt="Logo" />
        <p>DreamBoard</p>
    </div>
</header>

<body>
  <div class="container">
      <div class="sidebar">
  <ul>
    <li class="user">
      <a href="Admin.profile.php" class="<?= ($currentPage == 'Admin.profile.php') ? 'active' : '' ?>">
        <i class="fas fa-user"></i> Admin
      </a>
    </li>
    <li>
      <a href="#" class="<?= ($currentPage == '#') ? 'active' : '' ?>">
        <i class='bx bxs-bell'></i> Notification
      </a>
    </li>
    <li>
      <a href="Admin-Dashboard.php" class="<?= ($currentPage == 'Admin-Dashboard.php') ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="Admin-project.php" class="<?= in_array($currentPage, $classesPages) ? 'active' : '' ?>">
        <i class="fas fa-folder-open"></i> Classes
      </a>
    </li>
    <li>
      <a href="Admin-calendar.php" class="<?= ($currentPage == 'Admin-calendar.php') ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i> Calendar
      </a>
    </li>
    <li>
      <a href="Admin-forms.php" class="<?= ($currentPage == 'Admin-forms.php') ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> Forms
      </a>
    </li>
    <li>
      <a href="Admin-about.php" class="<?= ($currentPage == 'Admin-about.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> About Us
      </a>
    </li>
  </ul>
  <a href="Admin-login.php" class="logout <?= ($currentPage == 'Admin-login.php') ? 'active' : '' ?>">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</div>



      <div class="main-content">
        <form action="Admin-Createproj.php?proj_id=<?php echo $proj_id; ?>" method="POST" class="create-class" enctype="multipart/form-data" >

        
<label for="project_name">Create Class Work</label>
        <div class="create">

            

            <div class="left">

            <textarea id="project_name" name="project_name" rows="5" placeholder="Title" required></textarea>

            <div id="quill-wrapper">
              <div id="toolbar-container">
                <button class="ql-bold"></button>
                <button class="ql-italic"></button>
                <button class="ql-underline"></button>
                <button class="ql-list" value="ordered"></button>
                <button class="ql-list" value="bullet"></button>
                <button class="ql-link"></button>
                <button class="ql-image"></button>
              </div>
              <div id="editor-container" class="quill-editor"></div>
            </div>

              <div class="attach-bar">
                <h4>Attach</h4>
                <div class="attach-options">
                  <div class="option" id="driveBtn" title="Attach from Google Drive"><img src="google.png" alt="Drive" /></div>
                  <div class="option" id="youtubeBtn" title="Attach YouTube Video"><img src="youtube.png" alt="YouTube" /></div>
                  <div class="option" id="createBtn" title="Create Document"><img src="https://img.icons8.com/ios-filled/50/plus-math.png" alt="Create" /></div>
                  <div class="option" id="uploadBtn" title="Upload Files"><img src="https://img.icons8.com/ios-filled/50/upload.png" alt="Upload" /></div>
                  <div class="option" id="linkBtn" title="Attach Link"><img src="https://img.icons8.com/ios-filled/50/link.png" alt="Link" /></div>
                </div>
                <!-- Hidden file input for uploads -->
                <input type="file" id="fileUploadInput" name="attachments[]" multiple style="display:none;" />
                <!-- Area to show attached files -->
                <div id="attachedFiles"></div>
              </div>

              </div>

                <div class="right">
                <div class="right-panel">
                  <div class="label">Assign to</div>
                  <div class="assign-dropdown">
                    <div class="select-btn" id="studentSelect">
                      <span class="btn-text">SELECT STUDENTS</span>
                      <span class="arrow-dwn"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <ul class="list-items" id="studentList">
                      <li class="item" id="selectAll">
                        <span class="checkbox"><i class="fas fa-check check-icon"></i></span>
                        <span class="item-text">ALL</span>
                      </li>
                      <?php foreach ($assignedUsernames as $username): ?>
                        <li class="item">
                          <span class="checkbox"><i class="fas fa-check check-icon"></i></span>
                          <span class="item-text"><?= htmlspecialchars($username) ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>

      <p><strong>Points</strong></p>
      <div class="points-box">
        <input type="number" name="points" max="100" min="0" placeholder="_" style="width: 50px;" />
        /100
      </div>

      <p><strong>Due</strong></p>
      <div class="due-date-container">
        <input type="date" id="due" name="due_date" class="date-picker" placeholder="No due date" />
      </div>
    </div>

    <div class="buttons">
      <button type="submit">ASSIGN</button>
    </div>

    </div>

    <!-- Hidden inputs to capture Quill content and assigned students -->
    <input type="hidden" name="instructions" id="hiddenInstructions" />
    <input type="hidden" name="assigned_students" id="assignedStudents" />

    <div id="hiddenAttachmentInputs"></div>


    </div>

    
  </form>
</div>
  
  </div>
  <!-- Include Quill JS -->
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

 <script>
  // Initialize Quill editor
  const quill = new Quill('#editor-container', {
    theme: 'snow',
    placeholder: 'Instructions',
    modules: {
      toolbar: '#toolbar-container'
    }
  });

  // Student dropdown logic
  const selectBtn = document.getElementById("studentSelect");
  const items = document.querySelectorAll(".item");
  const btnText = document.querySelector(".btn-text");
  const selectAllItem = document.getElementById("selectAll");

  selectBtn.addEventListener("click", () => {
    selectBtn.classList.toggle("open");
  });

  items.forEach(item => {
    item.addEventListener("click", () => {
      if (item.id === "selectAll") {
        const allChecked = selectAllItem.classList.contains("checked");
        items.forEach(i => {
          if (i.id !== "selectAll") {
            i.classList.toggle("checked", !allChecked);
          }
        });
        selectAllItem.classList.toggle("checked", !allChecked);
      } else {
        item.classList.toggle("checked");

        // Update selectAll checkbox based on individual items
        const individualItems = Array.from(items).filter(i => i.id !== "selectAll");
        const allSelected = individualItems.every(i => i.classList.contains("checked"));
        selectAllItem.classList.toggle("checked", allSelected);
      }

      updateSelectedText();
    });
  });

  function updateSelectedText() {
    const individualItems = Array.from(items).filter(i => i.id !== "selectAll");
    const checkedItems = individualItems.filter(i => i.classList.contains("checked"));

    if (checkedItems.length === individualItems.length && checkedItems.length > 0) {
      btnText.innerText = "ALL";
    } else if (checkedItems.length > 0) {
      btnText.innerText = `${checkedItems.length} Selected`;
    } else {
      btnText.innerText = "Select students";
    }
  }

  // On form submit: update hidden inputs with Quill content and selected students
  const form = document.querySelector(".create-class");
  form.onsubmit = function () {
    // Save Quill HTML content
    document.getElementById('hiddenInstructions').value = quill.root.innerHTML;

    // Gather selected students
    const selectedStudents = Array.from(document.querySelectorAll(".item.checked .item-text"))
      .map(span => span.textContent)
      .filter(name => name !== "ALL");

    document.getElementById('assignedStudents').value = selectedStudents.join(",");
  };

  // Attachment handling
  const attachFilesInput = document.getElementById('fileUploadInput');
  const attachedFilesContainer = document.getElementById('attachedFiles');
  const hiddenAttachmentInputs = document.getElementById('hiddenAttachmentInputs');

  document.getElementById('uploadBtn').addEventListener('click', () => {
    attachFilesInput.click();
  });

  attachFilesInput.addEventListener('change', (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
      // Add visual attachment item
      const div = document.createElement('div');
      div.className = 'attachment-item';
      div.textContent = `📎 ${file.name}`;
      attachedFilesContainer.appendChild(div);

      // Optionally add file info to hidden inputs for form submit
      addHiddenAttachment(file.name, 'file');
    });
  });

  // Generic function to add hidden attachment inputs to form
  function addHiddenAttachment(url, type) {
    const urlInput = document.createElement('input');
    urlInput.type = 'hidden';
    urlInput.name = 'attachment_urls[]';
    urlInput.value = url;

    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'attachment_types[]';
    typeInput.value = type;

    hiddenAttachmentInputs.appendChild(urlInput);
    hiddenAttachmentInputs.appendChild(typeInput);
  }

  // Add link attachment
  document.getElementById('linkBtn').addEventListener('click', () => {
    const url = prompt("Enter a link URL:");
    if (url) {
      const div = document.createElement('div');
      div.className = 'attachment-item';
      div.innerHTML = `<a href="${url}" target="_blank">🔗 ${url}</a>`;
      attachedFilesContainer.appendChild(div);

      addHiddenAttachment(url, 'link');
    }
  });

  // Add YouTube video attachment
  document.getElementById('youtubeBtn').addEventListener('click', () => {
    const url = prompt("Enter a YouTube video URL:");
    if (url) {
      const div = document.createElement('div');
      div.className = 'attachment-item';
      div.innerHTML = `<a href="${url}" target="_blank">▶️ YouTube Video</a>`;
      attachedFilesContainer.appendChild(div);

      addHiddenAttachment(url, 'youtube');
    }
  });

  // Add Google Drive attachment
  document.getElementById('driveBtn').addEventListener('click', () => {
    const url = prompt("Enter a Google Drive file URL:");
    if (url) {
      const div = document.createElement('div');
      div.className = 'attachment-item';
      div.innerHTML = `<a href="${url}" target="_blank">📁 Google Drive File</a>`;
      attachedFilesContainer.appendChild(div);

      addHiddenAttachment(url, 'drive');
    }
  });

  // Create document button alert
  document.getElementById('createBtn').addEventListener('click', () => {
    alert('Create document feature is not implemented yet.');
  });
</script>

</body>
</html>
