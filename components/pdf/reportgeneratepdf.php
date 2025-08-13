<?php
require_once('../../TCPDF-main/tcpdf.php');
require_once('../../fpdi/src/autoload.php');
include('../../db/dbconfig.php');
session_start();

use setasign\Fpdi\Tcpdf\Fpdi;

// Enhanced PDF class with proper header/footer
class POSTMYPDF extends TCPDF
{
    public function Header()
    {
        // Add Nilai University Logo on the left
        $logo_path = '../../assets/img/NU logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 25, 0, '', '', '', false, 300, '', false, false, 0);
        }

        // Get page dimensions for proper centering
        $page_width = $this->getPageWidth();
        $left_margin = $this->getMargins()['left'];
        $right_margin = $this->getMargins()['right'];
        $content_width = $page_width - $left_margin - $right_margin;

        // Header text with proper centering
        $this->SetFont('times', 'B', 16);

        // Calculate Y position for first line
        $y_pos = 12;
        $this->SetXY($left_margin, $y_pos);
        $this->Cell($content_width, 8, 'POST EVENT REPORT', 0, 0, 'C');

        // Second line with smaller font and proper form reference
        $this->SetFont('times', '', 11);
        $y_pos += 10;
        $this->SetXY($left_margin, $y_pos);
        $this->Cell($content_width, 6, 'NU/SOP/SHSS/001/F03 (rev.1)', 0, 0, 'C');

        // Enhanced line styling - full width line
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.5);
        $line_y = $y_pos + 8;
        $this->Line($left_margin, $line_y, $page_width - $right_margin, $line_y);

        // Set proper spacing after header
        $this->SetY($line_y + 5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Authorization check
$user_type = $_SESSION['user_type'] ?? '';
$where_clause = '';

switch ($user_type) {
    case 'student':
        $student_id = $_SESSION['Stu_ID'];
        $where_clause = "AND e.Stu_ID = '$student_id'";
        break;
    case 'coordinator':
    case 'admin':
        $where_clause = "";
        break;
    default:
        die("Unauthorized access");
}

$report_id = $_GET['id'] ?? '';
if (empty($report_id)) {
    die("Missing Report ID.");
}

// Fetch all required data
$query = "
    SELECT e.*, ep.*, s.Stu_Name, s.Stu_ID, s.Stu_Email, s.Stu_Program, s.Stu_School,
           c.Club_Name, c.Club_Logo, bs.statement,
           pic.PIC_Name, pic.PIC_ID, pic.PIC_PhnNum
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN budgetsummary bs ON e.Ev_ID = bs.Ev_ID
    LEFT JOIN personincharge pic ON e.Ev_ID = pic.Ev_ID
    WHERE ep.Rep_ID = '$report_id' $where_clause
";

$result = $conn->query($query);
$report = $result->fetch_assoc();
if (!$report) {
    die("Invalid Report ID or Access Denied.");
}

$event_id = $report['Ev_ID'];

// Fetch additional data
$eventflow_result = $conn->query("SELECT * FROM eventflows WHERE Rep_ID = '$report_id' ORDER BY EvFlow_Time");
$meeting_result = $conn->query("SELECT * FROM posteventmeeting WHERE Rep_ID = '$report_id' ORDER BY Meeting_Date, Start_Time");
$total_meeting = $meeting_result->num_rows;

$committee_result = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$event_id' AND Com_COCUClaimers = 'yes'");
$individual_query = "SELECT ir.*, c.Com_Name, c.Com_Position FROM individualreport ir JOIN committee c ON ir.Com_ID = c.Com_ID WHERE ir.Rep_ID = '$report_id' AND c.Ev_ID = '$event_id'";
$individual_result = $conn->query($individual_query);

// Parse photos - Fix the photo path issue
$photos = json_decode($report['rep_photo'], true) ?? [];
// Ensure proper photo paths
$valid_photos = [];
foreach ($photos as $photo) {
    // Check different possible photo paths
    $possible_paths = [
        $photo,
        'uploads/photos/' . basename($photo),
        '../../uploads/photos/' . basename($photo),
        'uploads/event_photos/' . basename($photo),
        '../../uploads/event_photos/' . basename($photo)
    ];

    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $valid_photos[] = $path;
            break;
        }
    }
}

// Calculate attendance for COCU claimers
$attendance = [];
$committee_result->data_seek(0);
while ($row = $committee_result->fetch_assoc()) {
    $com_id = $row['Com_ID'];
    $name = $row['Com_Name'];
    $position = $row['Com_Position'];
    $attended_query = "SELECT COUNT(*) AS total FROM committeeattendance 
                      WHERE Rep_ID = '$report_id' AND Com_ID = '$com_id' AND Attendance_Status = 'Present'";
    $attended = $conn->query($attended_query)->fetch_assoc()['total'] ?? 0;
    $percentage = $total_meeting > 0 ? round(($attended / $total_meeting) * 100, 1) : 0;

    $attendance[] = [
        'name' => $name,
        'position' => $position,
        'attended' => $attended,
        'total' => $total_meeting,
        'percentage' => $percentage
    ];
}

// Create PDF
$pdf = new POSTMYPDF();
$pdf->SetMargins(20, 40, 20);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->SetDefaultMonospacedFont('times');

// Set document information
$pdf->SetCreator('Nilai University - Event Management System');
$pdf->SetAuthor($report['Stu_Name'] ?? 'Student');
$pdf->SetTitle('Post Event Report: ' . $report['Ev_Name']);
$pdf->SetSubject('Post Event Report Document');

// === PAGE 1: COVER PAGE WITH CLUB LOGO ===
$pdf->AddPage();

$pdf->SetTextColor(0, 0, 0);

// Club logo with better positioning
$club_logo = $report['Club_Logo'];
if (!empty($club_logo) && file_exists($club_logo)) {
    $pdf->Image($club_logo, 85, 50, 40);
} else {
    $pdf->SetFont('times', '', 12);
    $pdf->SetXY(85, 55);
    $pdf->Cell(40, 8, '[Club Logo]', 1, 1, 'C');
}

$pdf->Ln(55);

// Title styling
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 10, 'POST EVENT REPORT', 0, 1, 'C');
$pdf->Ln(6);

// Club name
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 8, strtoupper($report['Club_Name']), 0, 1, 'C');
$pdf->Ln(6);

// Event name
$pdf->SetFont('times', 'B', 14);
$pdf->MultiCell(0, 6, '"' . $report['Ev_Name'] . '"', 0, 'C');
$pdf->Ln(12);

// Student information section
$proposerName = $report['Stu_Name'] ?? '-';
$proposerID = $report['Stu_ID'] ?? '-';
$program = $report['Stu_Program'] ?? '-';
$school = $report['Stu_School'] ?? '-';
$date = date('d F Y', strtotime($report['Ev_Date']));

$pdf->SetFont('times', '', 12);
$info_html = '
<div style="text-align: center; font-family: times; font-size: 12pt; line-height: 1.3;">
    <p><span style="font-weight: bold;">Submitted by:</span> ' . htmlspecialchars($proposerName) . '</p>
    <p><span style="font-weight: bold;">Student ID:</span> ' . htmlspecialchars($proposerID) . '</p>
    <p><span style="font-weight: bold;">Program:</span> ' . htmlspecialchars($program) . '</p>
    <p><span style="font-weight: bold;">School:</span> ' . htmlspecialchars($school) . '</p>
    <p><span style="font-weight: bold;">Event Date:</span> ' . $date . '</p>
    <p><span style="font-weight: bold;">Report Date:</span> ' . date('d F Y') . '</p>
</div>';

$pdf->writeHTML($info_html, true, false, true, false, '');

// === PAGE 2: OBJECTIVES & EVENT FLOW ===
$pdf->AddPage();

// 1.0 OBJECTIVES
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 12, '1.0 OBJECTIVES', 0, 1, 'L');
$pdf->Ln(3);

$objectives = $report['Ev_Objectives'] ?? 'No objectives specified.';
$objectives_html = '
<style>
    body { font-family: "times"; font-size: 12pt; line-height: 1.5; }
    p { text-align: justify; margin-bottom: 8px; }
</style>
<p>' . nl2br(htmlspecialchars($objectives)) . '</p>';

$pdf->writeHTML($objectives_html, true, false, true, false, '');

// 2.0 EVENT FLOW
$pdf->Ln(10);
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 12, '2.0 EVENT FLOW', 0, 1, 'L');
$pdf->Ln(5);

if ($eventflow_result->num_rows > 0) {
    $flow_html = '
    <style>
        table.flow {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
            line-height: 1.4;
        }
        .flow th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }
        .flow td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        .flow .time-col { width: 20%; text-align: center; }
        .flow .desc-col { width: 80%; text-align: justify; }
    </style>
    
    <table class="flow">
        <thead>
            <tr>
                <th class="time-col">Time</th>
                <th class="desc-col">Description</th>
            </tr>
        </thead>
        <tbody>';

    $eventflow_result->data_seek(0);
    while ($row = $eventflow_result->fetch_assoc()) {
        $time = date('h:i A', strtotime($row['EvFlow_Time']));
        $flow_html .= '
            <tr>
                <td class="time-col">' . $time . '</td>
                <td class="desc-col">' . htmlspecialchars($row['EvFlow_Description']) . '</td>
            </tr>';
    }

    $flow_html .= '
        </tbody>
    </table>';
} else {
    $flow_html = '
    <style>
        body { font-family: "times"; font-size: 12pt; line-height: 1.5; }
        p { text-align: justify; }
    </style>
    <p>No event flow information available.</p>';
}

$pdf->writeHTML($flow_html, true, false, true, false, '');

// === PAGE 3: POST EVENT MEETINGS ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 12, '3.0 POST EVENT MEETINGS', 0, 1, 'L');
$pdf->Ln(5);

if ($meeting_result->num_rows > 0) {
    $meeting_html = '
    <style>
        table.meeting {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
            line-height: 1.4;
        }
        .meeting th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }
        .meeting td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        .meeting .date-col { width: 15%; text-align: center; }
        .meeting .time-col { width: 20%; text-align: center; }
        .meeting .location-col { width: 25%; text-align: left; }
        .meeting .desc-col { width: 40%; text-align: justify; }
    </style>
    
    <table class="meeting">
        <thead>
            <tr>
                <th class="date-col">Date</th>
                <th class="time-col">Time</th>
                <th class="location-col">Location</th>
                <th class="desc-col">Description</th>
            </tr>
        </thead>
        <tbody>';

    $meeting_result->data_seek(0);
    while ($row = $meeting_result->fetch_assoc()) {
        $date = date('d/m/Y', strtotime($row['Meeting_Date']));
        $start_time = date('h:i A', strtotime($row['Start_Time']));
        $end_time = date('h:i A', strtotime($row['End_Time']));
        $time_range = $start_time . ' - ' . $end_time;

        $meeting_html .= '
            <tr>
                <td class="date-col">' . $date . '</td>
                <td class="time-col">' . $time_range . '</td>
                <td class="location-col">' . htmlspecialchars($row['Meeting_Location']) . '</td>
                <td class="desc-col">' . htmlspecialchars($row['Meeting_Description']) . '</td>
            </tr>';
    }

    $meeting_html .= '
        </tbody>
    </table>';
} else {
    $meeting_html = '
    <style>
        body { font-family: "times"; font-size: 12pt; line-height: 1.5; }
        p { text-align: justify; }
    </style>
    <p>No post event meetings were conducted.</p>';
}

$pdf->writeHTML($meeting_html, true, false, true, false, '');

// === PAGE 4: CHALLENGES, RECOMMENDATIONS, CONCLUSION ===
$pdf->AddPage();

// 4.0 Challenges and Difficulties
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 12, '4.0 GROUP CHALLENGES / DIFFICULTIES', 0, 1, 'L');
$pdf->Ln(3);

$challenges = $report['Rep_ChallengesDifficulties'] ?? 'No challenges reported.';
$challenges_html = '
<style>
    body { font-family: "times"; font-size: 12pt; line-height: 1.5; }
    p { text-align: justify; text-indent: 20px; margin-bottom: 10px; }
</style>
<p>' . nl2br(htmlspecialchars($challenges)) . '</p>';

$pdf->writeHTML($challenges_html, true, false, true, false, '');

// 4.1 Recommendations
$pdf->Ln(10);
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 12, '4.1 RECOMMENDATIONS', 0, 1, 'L');
$pdf->Ln(3);

$recommendations = $report['Rep_recomendation'] ?? 'No recommendations provided.';
$recommendations_html = '
<style>
    body { font-family: "times"; font-size: 12pt; line-height: 1.5; }
    p { text-align: justify; text-indent: 20px; margin-bottom: 10px; }
</style>
<p>' . nl2br(htmlspecialchars($recommendations)) . '</p>';

$pdf->writeHTML($recommendations_html, true, false, true, false, '');

// 4.2 Conclusion
$pdf->Ln(10);
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 12, '4.2 CONCLUSION', 0, 1, 'L');
$pdf->Ln(3);

$conclusion = $report['Rep_Conclusion'] ?? 'No conclusion provided.';
$conclusion_html = '
<style>
    body { font-family: "times"; font-size: 12pt; line-height: 1.5; }
    p { text-align: justify; text-indent: 20px; margin-bottom: 10px; }
</style>
<p>' . nl2br(htmlspecialchars($conclusion)) . '</p>';

$pdf->writeHTML($conclusion_html, true, false, true, false, '');

// Submitted by section - NO SIGNATURE LINE, JUST NAME
$pdf->Ln(15);
$pdf->SetFont('times', '', 12);
$pdf->Cell(0, 8, 'Submitted by: ' . htmlspecialchars($report['Stu_Name']), 0, 1, 'L');
$pdf->Cell(0, 8, 'Date: ' . date('d F Y'), 0, 1, 'L');

// === EVENT PHOTOS ===
if (!empty($valid_photos)) {
    $pdf->AddPage();
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 12, '5.0 EVENT PHOTOS', 0, 1, 'C');
    $pdf->Ln(10);

    $x = 30;  // Starting X position
    $y = $pdf->GetY();
    $photo_width = 75;
    $photo_height = 60;
    $spacing = 10;
    $photos_per_row = 2;
    $photo_count = 0;

    foreach ($valid_photos as $img_path) {
        // Calculate position
        $col = $photo_count % $photos_per_row;
        $row = floor($photo_count / $photos_per_row);

        $photo_x = $x + ($col * ($photo_width + $spacing));
        $photo_y = $y + ($row * ($photo_height + $spacing + 5));

        // Check if we need a new page
        if ($photo_y + $photo_height > $pdf->getPageHeight() - 30) {
            $pdf->AddPage();
            $photo_y = $pdf->GetY() + 10;
            $y = $photo_y;
            $row = 0;
            $photo_y = $y + ($row * ($photo_height + $spacing + 5));
        }

        try {
            $pdf->Image($img_path, $photo_x, $photo_y, $photo_width, $photo_height, '', '', '', true, 300, '', false, false, 1);
            $photo_count++;
        } catch (Exception $e) {
            error_log("Error adding photo: " . $img_path . " - " . $e->getMessage());
        }
    }
}

// === COCU CLAIMER ATTENDANCE ===
if (!empty($attendance)) {
    $pdf->AddPage();
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 12, '6.0 COCU CLAIMER ATTENDANCE', 0, 1, 'C');
    $pdf->Ln(8);

    $attendance_html = '
    <style>
        table.attendance {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 12pt;
            line-height: 1.5;
        }
        .attendance th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .attendance td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .attendance .name-col { width: 35%; text-align: left; }
        .attendance .position-col { width: 30%; text-align: center; }
        .attendance .attendance-col { width: 20%; text-align: center; }
        .attendance .percentage-col { width: 15%; text-align: center; }
    </style>
    
    <table class="attendance">
        <thead>
            <tr>
                <th class="name-col">Name</th>
                <th class="position-col">Position</th>
                <th class="attendance-col">Attendance</th>
                <th class="percentage-col">Percentage</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($attendance as $att) {
        $attendance_html .= '
            <tr>
                <td class="name-col">' . htmlspecialchars($att['name']) . '</td>
                <td class="position-col">' . htmlspecialchars($att['position']) . '</td>
                <td class="attendance-col">' . $att['attended'] . ' / ' . $att['total'] . '</td>
                <td class="percentage-col">' . $att['percentage'] . '%</td>
            </tr>';
    }

    $attendance_html .= '
        </tbody>
    </table>';

    $pdf->writeHTML($attendance_html, true, false, true, false, '');
}

// === SAVE PDF TO TEMPORARY FILE ===
$temp_file = tempnam(sys_get_temp_dir(), 'postevent_report_') . '.pdf';
$pdf->Output($temp_file, 'F');

// === MERGE WITH APPENDICES (7.0 APPENDIX) ===
try {
    $finalPdf = new Fpdi();

    // Set PDF metadata
    $finalPdf->SetCreator('Nilai University - Event Management System');
    $finalPdf->SetAuthor($report['Stu_Name'] ?? 'Student');
    $finalPdf->SetTitle('Complete Post Event Report: ' . $report['Ev_Name']);
    $finalPdf->SetSubject('Post Event Report with Appendices');

    // Import main PDF pages
    $pageCount = $finalPdf->setSourceFile($temp_file);
    for ($i = 1; $i <= $pageCount; $i++) {
        $tplId = $finalPdf->importPage($i);
        $size = $finalPdf->getTemplateSize($tplId);
        $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $finalPdf->useTemplate($tplId);
    }

    // Add appendix section
    $appendix_added = false;

    // Add budget summary statement if exists
    $statement_file = $report['statement'];
    if (!empty($statement_file) && file_exists($statement_file)) {
        if (!$appendix_added) {
            // Add appendix title page
            $finalPdf->AddPage();
            $finalPdf->SetFont('times', 'B', 16);
            $finalPdf->Cell(0, 30, '', 0, 1);
            $finalPdf->Cell(0, 15, '7.0 APPENDIX', 0, 1, 'C');
            $finalPdf->SetLineWidth(0.5);
            $finalPdf->Line(50, $finalPdf->GetY() + 5, 160, $finalPdf->GetY() + 5);
            $finalPdf->Ln(20);
            $finalPdf->SetFont('times', '', 12);
            $finalPdf->Cell(0, 8, 'This section contains:', 0, 1, 'L');
            $finalPdf->Cell(0, 6, '• Budget Summary Statement', 0, 1, 'L');
            $finalPdf->Cell(0, 6, '• Individual Committee Reports', 0, 1, 'L');
            $appendix_added = true;
        }

        // Import statement pages
        try {
            $statementPages = $finalPdf->setSourceFile($statement_file);
            for ($i = 1; $i <= $statementPages; $i++) {
                $tpl = $finalPdf->importPage($i);
                $finalPdf->AddPage();
                $finalPdf->useTemplate($tpl);
            }
        } catch (Exception $e) {
            error_log("Error adding budget statement: " . $e->getMessage());
        }
    }

    // Add individual reports - Fix the file path issue
    if ($individual_result->num_rows > 0) {
        if (!$appendix_added) {
            // Add appendix title page
            $finalPdf->AddPage();
            $finalPdf->SetFont('times', 'B', 16);
            $finalPdf->Cell(0, 30, '', 0, 1);
            $finalPdf->Cell(0, 15, '7.0 APPENDIX', 0, 1, 'C');
            $finalPdf->SetLineWidth(0.5);
            $finalPdf->Line(50, $finalPdf->GetY() + 5, 160, $finalPdf->GetY() + 5);
            $finalPdf->Ln(20);
            $finalPdf->SetFont('times', '', 12);
            $finalPdf->Cell(0, 8, 'This section contains:', 0, 1, 'L');
            $finalPdf->Cell(0, 6, '• Individual Committee Reports', 0, 1, 'L');
            $appendix_added = true;
        }

        $individual_result->data_seek(0);
        while ($ind = $individual_result->fetch_assoc()) {
            // Try multiple possible paths for individual reports
            $possible_report_paths = [
                $ind['IR_File'],
                'uploads/individual_reports/' . $ind['IR_File'],
                '../../uploads/individualeports/' . $ind['IR_File'],
                'uploads/individual_reports/' . basename($ind['IR_File']),
                '../../uploads/individualreports/' . basename($ind['IR_File'])
            ];

            $report_found = false;
            foreach ($possible_report_paths as $file_path) {
                if (!empty($file_path) && file_exists($file_path)) {
                    try {
                        $pages = $finalPdf->setSourceFile($file_path);
                        for ($p = 1; $p <= $pages; $p++) {
                            $tpl = $finalPdf->importPage($p);
                            $finalPdf->AddPage();
                            $finalPdf->useTemplate($tpl);
                        }
                        $report_found = true;
                        break;
                    } catch (Exception $e) {
                        error_log("Error adding individual report: " . $file_path . " - " . $e->getMessage());
                    }
                }
            }

            if (!$report_found) {
                error_log("Individual report not found for: " . $ind['Com_Name'] . " - " . $ind['IR_File']);
            }
        }
    }

    // If no appendices were added, add a note
    if (!$appendix_added) {
        $finalPdf->AddPage();
        $finalPdf->SetFont('times', 'B', 16);
        $finalPdf->Cell(0, 30, '', 0, 1);
        $finalPdf->Cell(0, 15, '7.0 APPENDIX', 0, 1, 'C');
        $finalPdf->SetLineWidth(0.5);
        $finalPdf->Line(50, $finalPdf->GetY() + 5, 160, $finalPdf->GetY() + 5);
        $finalPdf->Ln(20);
        $finalPdf->SetFont('times', '', 12);
        $finalPdf->Cell(0, 10, 'No additional documents attached.', 0, 1, 'C');
    }

    // Generate safe filename
    $event_id_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $report['Ev_ID']);
    $event_name_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', substr($report['Ev_Name'], 0, 30));
    $filename = "PostEvent_Report_{$event_id_safe}_{$event_name_safe}.pdf";

    // Output the final merged PDF
    $finalPdf->Output($filename, 'D');

    // Clean up temporary file
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }

} catch (Exception $e) {
    // Clean up on error
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }

    error_log("Post Event PDF Error: " . $e->getMessage());
    die("Error generating post event report: " . $e->getMessage());
}

// Close database connection
$conn->close();
?>