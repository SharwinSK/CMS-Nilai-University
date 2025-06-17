<?php
require_once('TCPDF-main/tcpdf.php');
require_once('fpdi/src/autoload.php');

use setasign\Fpdi\Tcpdf\Fpdi;
include('dbconfig.php');
session_start();

$user_type = $_SESSION['user_type'];
$where_clause = '';
switch ($user_type) {
    case 'student':
        $where_clause = "AND e.Stu_ID = '{$_SESSION['Stu_ID']}'";
        break;
    case 'advisor':
        $where_clause = "AND e.Club_ID = '{$_SESSION['Club_ID']}'";
        break;
    case 'coordinator':
        $where_clause = '';
        break;
    default:
        die("Unauthorized access");
}

$event_id = $_GET['id'];

$event_query = "SELECT e.*, s.Stu_Name, c.Club_Name FROM events e
LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
LEFT JOIN club c ON e.Club_ID = c.Club_ID
WHERE e.Ev_ID = '$event_id' $where_clause";

$event_result = $conn->query($event_query);
if ($event_result->num_rows == 0)
    die("No permission.");
$event = $event_result->fetch_assoc();

$pic = $conn->query("SELECT * FROM personincharge WHERE Ev_ID = '$event_id'")->fetch_assoc();
$committee_result = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$event_id'");
$budget_summary = $conn->query("SELECT * FROM budgetsummary WHERE Ev_ID = '$event_id' LIMIT 1")->fetch_assoc();
$eventflow_result = $conn->query("SELECT * FROM eventflow WHERE Ev_ID = '$event_id'");

class MYPDF extends TCPDF
{
    public function Header()
    {
        $this->Image('NU logo2.jpeg', 10, 10, 25);
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetXY(0, 10);
        $this->Cell(0, 10, 'PROPOSAL FOR PROJECT/ACTIVITY', 0, 2, 'C');

        $this->SetFont('dejavusans', '', 10);
        $this->SetXY(0, 20);
        $this->Cell(0, 10, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 2, 'C');

        $this->Line(10, 30, 200, 30); // full horizontal line
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(10, 35, 10);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

if (!empty($event['Ev_Poster']) && file_exists($event['Ev_Poster'])) {
    $pdf->Image($event['Ev_Poster'], 40, 50, 120, 120);
    $pdf->Ln(130);
} else {
    $pdf->Cell(0, 10, 'Event Poster Not Found', 0, 1, 'C');
}

$pdf->AddPage();

// Handle optional values
$event_id = $event['Ev_ID'] ?? '-';
$ref_num = !empty($event['Ev_RefNum']) ? $event['Ev_RefNum'] : '-';
$type_code = !empty($event['Ev_TypeRef']) ? $event['Ev_TypeRef'] : '-';

$day_of_week = date('l', strtotime($event['Ev_Date']));
$start_time = date('h:i A', strtotime($event['Ev_StartTime']));
$end_time = date('h:i A', strtotime($event['Ev_EndTime']));

$html = '<h3>Event Summary</h3>';
$html .= '<table border="1" cellpadding="8" cellspacing="0" style="width: 100%;">';
$html .= "<tr><td style='width: 35%;'><strong>Event ID</strong></td><td>{$event_id}</td></tr>";
$html .= "<tr><td><strong>Reference Number</strong></td><td>{$ref_num}</td></tr>";
$html .= "<tr><td><strong>Event Type</strong></td><td>{$type_code}</td></tr>";
$html .= "<tr><td><strong>Date of Submission</strong></td><td>" . date('d F Y') . "</td></tr>";
$html .= "<tr><td><strong>Club / Society / Projects</strong></td><td>{$event['Club_Name']}</td></tr>";
$html .= "<tr><td><strong>Name of Project</strong></td><td>{$event['Ev_Name']}</td></tr>";
$html .= "<tr><td><strong>Nature of Project</strong></td><td>{$event['Ev_ProjectNature']}</td></tr>";
$html .= "<tr><td><strong>Objectives</strong></td><td>{$event['Ev_Objectives']}</td></tr>";
$html .= "<tr><td><strong>Date</strong></td><td>{$event['Ev_Date']}</td></tr>";
$html .= "<tr><td><strong>Day</strong></td><td>{$day_of_week}</td></tr>";
$html .= "<tr><td><strong>Time</strong></td><td>{$start_time} to {$end_time}</td></tr>";
$html .= "<tr><td><strong>Venue</strong></td><td>{$event['Ev_Venue']}</td></tr>";
$html .= "<tr><td><strong>Estimated Pax</strong></td><td>{$event['Ev_Pax']} people</td></tr>";
$html .= "<tr><td><strong>Person In Charge</strong></td><td>{$pic['PIC_Name']}</td></tr>";
$html .= "<tr><td><strong>Contact No.</strong></td><td>{$pic['PIC_PhnNum']}</td></tr>";
$html .= "<tr><td><strong>ID Number</strong></td><td>{$pic['PIC_ID']}</td></tr>";
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->AddPage();
$html = '<h3>Event Flow</h3><table border="1" cellpadding="4"><tr>
<th>Date</th><th>Start Time</th><th>End Time</th><th>Activity</th><th>Remarks</th><th>Hours</th></tr>';
while ($row = $eventflow_result->fetch_assoc()) {
    $html .= "<tr><td>{$row['Date']}</td><td>{$row['Start_Time']}</td><td>{$row['End_Time']}</td>
    <td>{$row['Activity']}</td><td>{$row['Remarks']}</td><td>{$row['Hours']}</td></tr>";
}
$html .= '</table>';
$pdf->writeHTML($html);

$pdf->AddPage();
$html = '<h3>Committee Members</h3><table border="1" cellpadding="4">
<tr><th>ID</th><th>Name</th><th>Position</th><th>Department</th><th>Phone</th><th>Job Scope</th><th>COCU</th></tr>';
$cocu_pdfs = [];
while ($committee = $committee_result->fetch_assoc()) {
    $cocu = $committee['Com_COCUClaimers'] == '1' ? 'Yes' : 'No';
    $html .= "<tr><td>{$committee['Com_ID']}</td><td>{$committee['Com_Name']}</td>
    <td>{$committee['Com_Position']}</td><td>{$committee['Com_Department']}</td>
    <td>{$committee['Com_PhnNum']}</td><td>{$committee['Com_JobScope']}</td><td>{$cocu}</td></tr>";
    if ($cocu === 'Yes' && file_exists($committee['student_statement'])) {
        $cocu_pdfs[] = $committee['student_statement'];
    }
}
$html .= '</table>';
$pdf->writeHTML($html);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// === BUDGET PAGE START ===
$html = '<h3>Event Proposal Budget</h3>';
$html .= "<p><strong>Name of Event:</strong> {$event['Ev_Name']}<br>";
$html .= "<strong>Date of Event:</strong> {$event['Ev_Date']}<br>";
$html .= "<strong>Organized by:</strong> {$event['Club_Name']}</p>";

// === INCOME TABLE ===
$html .= '<h4>Income</h4>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">';
$html .= '<tr><th style="width: 80%;">Description</th><th style="width: 20%;" align="right">Amount (RM)</th></tr>';

$total_income = 0;
$income_result = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$event_id' AND Bud_Type = 'Income'");
while ($row = $income_result->fetch_assoc()) {
    $amount = number_format($row['Bud_Amount'], 2);
    $html .= "<tr><td>{$row['Bud_Desc']}</td><td align='right'>{$amount}</td></tr>";
    $total_income += $row['Bud_Amount'];
}
$html .= "<tr><td><strong>Total Income</strong></td><td align='right'><strong>RM " . number_format($total_income, 2) . "</strong></td></tr>";
$html .= '</table><br><br>';

// === EXPENSE TABLE ===
$html .= '<h4>Expenses</h4>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">';
$html .= '<tr><th style="width: 80%;">Description</th><th style="width: 20%;" align="right">Amount (RM)</th></tr>';

$total_expense = 0;
$expense_result = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$event_id' AND Bud_Type = 'Expense'");
while ($row = $expense_result->fetch_assoc()) {
    $amount = number_format($row['Bud_Amount'], 2);
    $html .= "<tr><td>{$row['Bud_Desc']}</td><td align='right'>{$amount}</td></tr>";
    $total_expense += $row['Bud_Amount'];
}
$html .= "<tr><td><strong>Total Expenses</strong></td><td align='right'><strong>RM " . number_format($total_expense, 2) . "</strong></td></tr>";
$html .= '</table><br><br>';

// === SUMMARY ===
$surplus = $budget_summary['Surplus_Deficit'] ?? ($total_income - $total_expense);
$prepared_by = $budget_summary['Prepared_By'] ?? '-';

$html .= "<p><strong>Surplus / Deficit:</strong> RM " . number_format($surplus, 2) . "</p>";
$html .= "<p><strong>Prepared By:</strong> {$prepared_by}</p>";

$pdf->writeHTML($html, true, false, true, false, '');


$temp_file = tempnam(sys_get_temp_dir(), 'proposal_') . '.pdf';
$pdf->Output($temp_file, 'F');

$finalPdf = new Fpdi();
$pageCount = $finalPdf->setSourceFile($temp_file);
for ($i = 1; $i <= $pageCount; $i++) {
    $tplId = $finalPdf->importPage($i);
    $size = $finalPdf->getTemplateSize($tplId);
    $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $finalPdf->useTemplate($tplId);
}

foreach ($cocu_pdfs as $cocuFile) {
    if (file_exists($cocuFile)) {
        $cocuPageCount = $finalPdf->setSourceFile($cocuFile);
        for ($i = 1; $i <= $cocuPageCount; $i++) {
            $tplId = $finalPdf->importPage($i);
            $size = $finalPdf->getTemplateSize($tplId);
            $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $finalPdf->useTemplate($tplId);
        }
    }
}

$event_id_safe = str_replace("/", "_", $event_id);
$finalPdf->Output("Event_{$event_id_safe}.pdf", 'D');
?>