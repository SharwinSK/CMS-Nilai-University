<?php
require_once('TCPDF-main/tcpdf.php');
require_once('fpdi/src/autoload.php');
include('dbconfig.php');
session_start();

use setasign\Fpdi\Tcpdf\Fpdi;

$user_type = $_SESSION['user_type'];
$where_clause = '';

switch ($user_type) {
    case 'student':
        $student_id = $_SESSION['Stu_ID'];
        $where_clause = "AND e.Stu_ID = '$student_id'";
        break;
    case 'coordinator':
        $where_clause = '';
        break;
    default:
        die("Unauthorized access");
}

$report_id = $_GET['id'];
$query = "
    SELECT e.*, ep.*, s.Stu_Name, s.Stu_ID, c.Club_Name, bs.statement,
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
    die("Invalid Report ID.");
}

$event_id = $report['Ev_ID'];
$eventflow_result = $conn->query("SELECT * FROM eventflows WHERE Rep_ID = '$report_id'");
$committee_result = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$event_id' AND Com_COCUClaimers = 1");

$individual_query = "
    SELECT ir.*, c.Com_Name, c.Com_Position 
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE ir.Rep_ID = '$report_id' AND c.Ev_ID = '$event_id'
";
$individual_result = $conn->query($individual_query);

$photos = json_decode($report['rep_photo'], true);
$statement_file = $report['statement'];

class MYPDF extends FPDI
{
    public function Header()
    {
        $this->SetFont('dejavusans', 'B', 12);
        $this->Image('NU logo2.jpeg', 10, 10, 20);
        $this->SetXY(35, 12);
        $this->Cell(120, 8, 'POST EVENT REPORT', 0, 0, 'C');
        $this->SetFont('dejavusans', '', 10);
        $this->SetXY(160, 12);
        $this->Cell(40, 8, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 0, 'R');
        $this->Ln(12);
        $this->Cell(0, 0, '', 'T', 1, 'C');
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('dejavusans', '', 9);
        $this->Cell(0, 10, 'RP/UCC/PER/2025 | Page ' . $this->getAliasNumPage(), 0, 0, 'R');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(10, 30, 10);

// Page 1: Cover
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 12);
$pdf->Ln(50);
$pdf->MultiCell(0, 10, "Event ID: " . $report['Ev_ID'], 0, 'L');
$pdf->MultiCell(0, 10, "Student Name: " . $report['Stu_Name'], 0, 'L');
$pdf->MultiCell(0, 10, "ID Number: " . $report['Stu_ID'], 0, 'L');
$pdf->MultiCell(0, 10, "Event Name: " . $report['Ev_Name'], 0, 'L');
$pdf->MultiCell(0, 10, "Date of Event: " . $report['Ev_Date'], 0, 'L');
$pdf->MultiCell(0, 10, "Date of Submission: " . date('Y-m-d'), 0, 'L');
$pdf->MultiCell(0, 10, "PIC Name: " . ($report['PIC_Name'] ?? 'N/A'), 0, 'L');
$pdf->MultiCell(0, 10, "PIC ID: " . ($report['PIC_ID'] ?? 'N/A'), 0, 'L');
$pdf->MultiCell(0, 10, "PIC Phone: " . ($report['PIC_PhnNum'] ?? 'N/A'), 0, 'L');
$pdf->MultiCell(0, 10, "Reference Number: " . $report['Ev_RefNum'], 0, 'L');
$pdf->MultiCell(0, 10, "Event Type: " . $report['Ev_TypeRef'], 0, 'L');

// Page 2: Event Summary
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 11);
$pdf->Write(0, "Event Summary", '', 0, 'C', true, 0, false, false, 0);
$pdf->Ln(5);
$pdf->MultiCell(0, 10, "Event Name: " . $report['Ev_Name'], 0, 'L');
$pdf->MultiCell(0, 10, "Event Nature: " . $report['Ev_ProjectNature'], 0, 'L');
$pdf->MultiCell(0, 10, "Objectives: " . $report['Ev_Objectives'], 0, 'L');
$pdf->MultiCell(0, 10, "Details: " . $report['Ev_Details'], 0, 'L');
$pdf->MultiCell(0, 10, "Challenges: " . $report['Rep_ChallengesDifficulties'], 0, 'L');
$pdf->MultiCell(0, 10, "Recommendation: " . $report['Rep_recomendation'], 0, 'L');  // 👈 new line
$pdf->MultiCell(0, 10, "Conclusion: " . $report['Rep_Conclusion'], 0, 'L');

// Page 3: Event Flow
if ($eventflow_result && $eventflow_result->num_rows > 0) {
    $pdf->AddPage();
    $pdf->Write(0, "Event Flow", '', 0, 'C', true, 0, false, false, 0);
    $pdf->Ln(5);

    $pdf->SetFont('dejavusans', '', 11);
    $html = '<table border="1" cellpadding="5"><thead><tr><th style="width: 30%;">Time</th><th>Description</th></tr></thead><tbody>';

    while ($flow = $eventflow_result->fetch_assoc()) {
        $html .= "<tr><td>{$flow['EvFlow_Time']}</td><td>{$flow['EvFlow_Description']}</td></tr>";
    }

    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}


// Page 4: Event Photos
if (!empty($photos)) {
    $pdf->AddPage();
    $pdf->Write(0, "Event Photos", '', 0, 'C', true, 0, false, false, 0);
    foreach ($photos as $photo) {
        if (file_exists($photo)) {
            $pdf->Image($photo, '', '', 120, 80, '', '', '', true);
            $pdf->Ln(90);
        }
    }
}

// Page 5: Committee Members claiming COCU
$pdf->AddPage();
$html = '<h3>Committee Members (COCU)</h3><table border="1" cellpadding="4"><tr><th>ID</th><th>Name</th><th>Position</th><th>Department</th><th>Phone</th><th>Job Scope</th></tr>';
$committee_result->data_seek(0);
while ($com = $committee_result->fetch_assoc()) {
    $html .= "<tr><td>{$com['Com_ID']}</td><td>{$com['Com_Name']}</td><td>{$com['Com_Position']}</td><td>{$com['Com_Department']}</td><td>{$com['Com_PhnNum']}</td><td>{$com['Com_JobScope']}</td></tr>";
}
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');


// Page 6: Budget + Receipts (using FPDI if available)
if (!empty($statement_file) && file_exists($statement_file)) {
    $pageCount = $pdf->setSourceFile($statement_file);
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $pdf->AddPage();
        $pdf->useTemplate($templateId);
    }
}

// Page 7: Individual Reports PDF (if exist)
if ($individual_result->num_rows > 0) {
    while ($ind = $individual_result->fetch_assoc()) {
        $ind_file = $ind['IR_File'] ?? ''; // Make sure column is IR_File

        // ✅ Build full file path
        $full_path = "uploads/individual_reports/" . $ind_file;

        if (!empty($ind_file) && file_exists($full_path)) {
            $pageCount = $pdf->setSourceFile($full_path);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl = $pdf->importPage($pageNo);
                $pdf->AddPage();
                $pdf->useTemplate($tpl);
            }
        } else {
            error_log("Missing or invalid file: " . $full_path);
        }
    }
}



$pdf->Output("PostEventReport_{$report_id}.pdf", 'D');
?>