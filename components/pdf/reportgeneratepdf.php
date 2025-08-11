<?php
require_once('../../TCPDF-main/tcpdf.php');
require_once('../../fpdi/src/autoload.php');
include('../../db/dbconfig.php'); // Adjust path if needed
session_start();

use setasign\Fpdi\Tcpdf\Fpdi;

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
if (empty($report_id))
    die("Missing Report ID.");

$query = "
    SELECT e.*, ep.*, s.Stu_Name, s.Stu_ID, s.Stu_Email, c.Club_Name, c.Club_Logo, bs.statement,
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
if (!$report)
    die("Invalid Report ID or Access Denied.");

$event_id = $report['Ev_ID'];
$eventflow_result = $conn->query("SELECT * FROM eventflows WHERE Rep_ID = '$report_id'");
$meeting_result = $conn->query("SELECT * FROM posteventmeeting WHERE Rep_ID = '$report_id'");
$total_meeting = $meeting_result->num_rows;

$committee_result = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$event_id' AND Com_COCUClaimers = 1");
$individual_query = "SELECT ir.*, c.Com_Name, c.Com_Position FROM individualreport ir JOIN committee c ON ir.Com_ID = c.Com_ID WHERE ir.Rep_ID = '$report_id' AND c.Ev_ID = '$event_id'";
$individual_result = $conn->query($individual_query);
$photos = json_decode($report['rep_photo'], true);
$statement_file = $report['statement'];

$attendance = [];
foreach ($committee_result as $row) {
    $com_id = $row['Com_ID'];
    $name = $row['Com_Name'];
    $position = $row['Com_Position'];
    $attended = $conn->query("SELECT COUNT(*) AS total FROM committeeattendance WHERE Rep_ID = '$report_id' AND Com_ID = '$com_id' AND Attendance_Status = 'Present'")->fetch_assoc()['total'] ?? 0;
    $attendance[] = [
        'name' => $name,
        'position' => $position,
        'attended' => $attended,
        'total' => $total_meeting
    ];
}

class MYPDF extends FPDI
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('dejavusans', '', 9);
        $this->Cell(0, 10, 'RP/UCC/PER/2025 | Page ' . $this->getAliasNumPage(), 0, 0, 'R');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(10, 20, 10);
$pdf->AddPage();
$pdf->Image('NU logo2.jpeg', 10, 10, 25);
if (!empty($report['Club_Logo']) && file_exists($report['Club_Logo']))
    $pdf->Image($report['Club_Logo'], 170, 10, 25);
$pdf->Ln(40);
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 12, 'Post Event Report', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('dejavusans', '', 11);

$table = [
    'Proposer Name' => $report['Stu_Name'],
    'Proposer ID' => $report['Stu_ID'],
    'Email' => $report['Stu_Email'],
    'Event Name' => $report['Ev_Name'],
    'Event Date' => $report['Ev_Date'],
    'Organiser' => $report['Club_Name'],
    'Ref. No' => $report['Ev_RefNum'],
    'SDG/USR No' => $report['Ev_TypeRef']
];

foreach ($table as $label => $value) {
    $pdf->Cell(50, 8, $label, 0, 0);
    $pdf->Cell(5, 8, ':', 0, 0);
    $pdf->MultiCell(0, 8, $value, 0, 'L');
}

$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 11);
$pdf->SetFillColor(230, 240, 255);
$pdf->MultiCell(0, 8, "1. Objectives", 0, 'L', true);
$pdf->Ln(2);
$pdf->MultiCell(0, 8, $report['Ev_Objectives']);

if ($eventflow_result->num_rows) {
    $pdf->Ln(4);
    $pdf->SetFillColor(230, 240, 255);
    $pdf->MultiCell(0, 8, "2. Event Flow", 0, 'L', true);
    foreach ($eventflow_result as $row) {
        $pdf->MultiCell(0, 7, "- {$row['EvFlow_Time']} : {$row['EvFlow_Description']}");
    }
}

if ($meeting_result->num_rows) {
    $pdf->Ln(4);
    $pdf->SetFillColor(230, 240, 255);
    $pdf->MultiCell(0, 8, "3. Post-Event Meetings", 0, 'L', true);
    foreach ($meeting_result as $m) {
        $pdf->MultiCell(0, 6, "- {$m['Meeting_Date']} ({$m['Start_Time']} - {$m['End_Time']}) @ {$m['Meeting_Location']}\n  {$m['Meeting_Description']}");
    }
}

$pdf->AddPage();
$pdf->SetFillColor(230, 240, 255);
$pdf->MultiCell(0, 8, "4. Group Challenges / Difficulties", 0, 'L', true);
$pdf->Ln(2);
$pdf->MultiCell(0, 8, $report['Rep_ChallengesDifficulties']);

$pdf->Ln(4);
$pdf->SetFillColor(230, 240, 255);
$pdf->MultiCell(0, 8, "5. Recommendations", 0, 'L', true);
$pdf->Ln(2);
$pdf->MultiCell(0, 8, $report['Rep_recomendation']);

$pdf->Ln(4);
$pdf->SetFillColor(230, 240, 255);
$pdf->MultiCell(0, 8, "6. Conclusion", 0, 'L', true);
$pdf->Ln(2);
$pdf->MultiCell(0, 8, $report['Rep_Conclusion']);

$pdf->Ln(8);
$pdf->Cell(40, 8, "Submitted by:", 0, 0);
$pdf->Cell(0, 8, $report['Stu_Name'], 0, 1);
$pdf->Cell(40, 8, "Date:", 0, 0);
$pdf->Cell(0, 8, date('d M Y'), 0, 1);

$pdf->AddPage();
$pdf->SetFont('dejavusans', 'B', 13);
$pdf->Cell(0, 10, 'COCU Claimer Attendance', 0, 1, 'C');
$pdf->SetFont('dejavusans', '', 11);
$html = '<table border="1" cellpadding="6"><thead><tr><th>Name</th><th>Position</th><th>Attendance</th></tr></thead><tbody>';
foreach ($attendance as $att) {
    $html .= "<tr><td>{$att['name']}</td><td>{$att['position']}</td><td>{$att['attended']} / {$att['total']}</td></tr>";
}
$html .= '</tbody></table>';
$pdf->writeHTML($html, true, false, true, false, '');

if (!empty($photos)) {
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 13);
    $pdf->Cell(0, 10, 'Event Photos', 0, 1, 'C');
    $x = 15;
    $y = $pdf->GetY();
    $count = 0;
    foreach ($photos as $img) {
        if (file_exists($img)) {
            $pdf->Image($img, $x, $y, 60, 40);
            $x += 65;
            $count++;
            if ($count % 3 === 0) {
                $x = 15;
                $y += 50;
            }
        }
    }
}

if (!empty($statement_file) && file_exists($statement_file)) {
    $pageCount = $pdf->setSourceFile($statement_file);
    for ($i = 1; $i <= $pageCount; $i++) {
        $tpl = $pdf->importPage($i);
        $pdf->AddPage();
        $pdf->useTemplate($tpl);
    }
}

if ($individual_result->num_rows > 0) {
    while ($ind = $individual_result->fetch_assoc()) {
        $file = "uploads/individual_reports/" . $ind['IR_File'];
        if (!empty($ind['IR_File']) && file_exists($file)) {
            $pages = $pdf->setSourceFile($file);
            for ($p = 1; $p <= $pages; $p++) {
                $tpl = $pdf->importPage($p);
                $pdf->AddPage();
                $pdf->useTemplate($tpl);
            }
        }
    }
}

$pdf->Output("PostEventReport_{$report_id}.pdf", 'D');
