<?php
require_once('libs/tcpdf/tcpdf.php');
session_start();

// Database connection
$connection = new mysqli("localhost", "root", "", "projectmanagement");
if ($connection->connect_error) die("Connection failed: " . $connection->connect_error);

// Get assignment ID
$ass_id = isset($_GET['ass_id']) ? (int)$_GET['ass_id'] : 0;
if (!$ass_id) die("Invalid assignment.");

// Get project name
$stmt = $connection->prepare("SELECT project_name FROM assigned WHERE ass_id = ?");
$stmt->bind_param("i", $ass_id);
$stmt->execute();
$stmt->bind_result($project_name);
$stmt->fetch();
$stmt->close();

// Get admin name
$adminName = 'Unknown Admin';
if (isset($_SESSION['admininfoID'])) {
    $adminID = $_SESSION['admininfoID'];
    $stmt = $connection->prepare("SELECT INSTRUCTOR FROM admininfo WHERE admininfoID = ?");
    $stmt->bind_param("i", $adminID);
    $stmt->execute();
    $stmt->bind_result($instructor);
    if ($stmt->fetch()) {
        $adminName = $instructor;
    }
    $stmt->close();
}

// Get assigned students
$students = [];
$stmt = $connection->prepare("
    SELECT a.userinfo_ID, u.FIRSTNAME, u.MIDDLENAME, u.LASTNAME, a.status, a.grade
    FROM assignment_students a
    JOIN userinfo u ON a.userinfo_ID = u.userinfo_ID
    WHERE a.assigned_id = ?
");
$stmt->bind_param("i", $ass_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $fullName = trim($row['FIRSTNAME'] . ' ' . ($row['MIDDLENAME'] ? $row['MIDDLENAME'] . ' ' : '') . $row['LASTNAME']);
    $students[$row['userinfo_ID']] = [
        'fullname' => $fullName,
        'status' => $row['status'],
        'grade' => $row['grade'],
        'submissions' => [],
        'timestamps' => [],
        'comments' => []
    ];
}
$stmt->close();

// Submissions
$stmt = $connection->prepare("SELECT userinfo_id, file_name, uploaded_at FROM student_submissions WHERE assigned_id = ?");
$stmt->bind_param("i", $ass_id);
$stmt->execute();
$res = $stmt->get_result();
while ($file = $res->fetch_assoc()) {
    $uid = $file['userinfo_id'];
    $students[$uid]['submissions'][] = $file['file_name'];
    $students[$uid]['timestamps'][] = date("M d, Y h:i A", strtotime($file['uploaded_at']));
}
$stmt->close();

// Comments
foreach ($students as $uid => &$s) {
    $stmt = $connection->prepare("
        SELECT c.comment_text, c.user_type, c.created_at,
               u.FIRSTNAME AS sf, u.MIDDLENAME AS sm, u.LASTNAME AS sl,
               a.INSTRUCTOR AS an
        FROM comments c
        LEFT JOIN userinfo u ON c.user_type='student' AND c.userinfo_id=u.userinfo_ID
        LEFT JOIN admininfo a ON c.user_type='admin' AND c.userinfo_id=a.admininfoID
        WHERE c.ass_id=? AND c.recipient_id=?
        ORDER BY c.created_at ASC
    ");
    $stmt->bind_param("ii", $ass_id, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($c = $res->fetch_assoc()) {
        $uname = ($c['user_type'] === 'student')
            ? trim($c['sf'] . ' ' . ($c['sm'] ? $c['sm'] . ' ' : '') . $c['sl'])
            : trim($c['an']);
        $s['comments'][] = $uname . ': ' . $c['comment_text'] . ' [' . $c['created_at'] . ']';
    }
    $stmt->close();
}
unset($s);

// Count totals
$totalCount = count($students);
$completedCount = count(array_filter($students, fn($s) => count($s['submissions']) > 0));

// Extend TCPDF for footer
class CustomPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Init PDF
$pdf = new CustomPDF();
$pdf->SetCreator('DreamBoard');
$pdf->SetTitle($project_name . ' - Assignment Report');
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();


// Header with logo and system name
$logoPath = 'vz.jpg';
$systemName = 'DreamBoard';
$logoWidth = 20;
$spacing = 5;
$pdf->SetFont('helvetica', 'B', 16);
$textWidth = $pdf->GetStringWidth($systemName);
$totalWidth = $logoWidth + $spacing + $textWidth;
$startX = ($pdf->GetPageWidth() - $totalWidth) / 2;
$pdf->Image($logoPath, $startX, 11, $logoWidth, $logoWidth);
$pdf->SetXY($startX + $logoWidth + $spacing, 14);
$pdf->Cell($textWidth, 13, $systemName, 0, 0, 'L');
$pdf->Ln(20);

// Report title
$pdf->SetFont('helvetica', '', 11);
$pdf->Write(0, "$project_name - Assignment Report", '', 0, 'L', true);
$pdf->Ln(2);
$pdf->Write(0, "Prepared by: $adminName", '', 0, 'L', true);
$pdf->Write(0, "Total Students: $totalCount | Completed: $completedCount", '', 0, 'L', true);
$pdf->Ln(4);

// Table setup
$colWidths = [35, 25, 15, 35, 35, 45];
$headers = ['Name', 'Status', 'Grade', 'Submissions', 'Submitted At', 'Comments'];
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetFont('', 'B');
foreach ($headers as $i => $header) {
    $pdf->Cell($colWidths[$i], 10, $header, 1, 0, 'C', true);
}
$pdf->Ln();

// Table data
$rowCount = 0;
foreach ($students as $s) {
    $rowCount++;
    $hasSubmission = !empty($s['submissions']);
    $bgColor = !$hasSubmission ? [255, 230, 230] : ($rowCount % 2 === 0 ? [249, 249, 249] : [255, 255, 255]);
    $textColor = !$hasSubmission ? [160, 0, 0] : [0, 0, 0];

    $pdf->SetFillColor(...$bgColor);
    $pdf->SetTextColor(...$textColor);
    $pdf->SetFont('', '');

    $row = [
        $s['fullname'],
        $s['status'],
        $s['grade'] ?? 'N/A',
        implode(", ", $s['submissions']),
        implode(", ", $s['timestamps']),
        implode("; ", $s['comments'])
    ];

    // Determine max row height
    $maxHeight = 10;
    foreach ($row as $i => $text) {
        $nbLines = $pdf->getNumLines($text, $colWidths[$i]);
        $cellHeight = $nbLines * 5;
        if ($cellHeight > $maxHeight) $maxHeight = $cellHeight;
    }

    // Draw cells
    foreach ($row as $i => $text) {
        $pdf->MultiCell($colWidths[$i], $maxHeight, $text, 1, 'L', true, 0, '', '', true, 0, false, true, $maxHeight, 'M');
    }
    $pdf->Ln($maxHeight);
}

$pdf->Output($project_name . '_assignment_report.pdf', 'I');
