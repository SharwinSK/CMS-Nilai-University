<?php
require_once('TCPDF-main/tcpdf.php');
require_once('fpdi/src/autoload.php');
use setasign\Fpdi\Tcpdf\Fpdi;

include('dbconfig.php');
session_start();

if (!isset($_SESSION['user_type']) || !isset($_GET['event_id'])) {
    die("Unauthorized access.");
}

$event_id = $_GET['event_id'];
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
        die("Invalid user type.");
}

// Fetch event main info
$stmt = $conn->prepare("
    SELECT e.*, s.Stu_Name, s.Stu_Email, s.Stu_ID, c.Club_Name, ep.Rep_ChallengesDifficulties, ep.rep_photo,
           ep.Rep_Conclusion, es.Status_Name
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN eventstatus es ON e.Status_ID = es.Status_ID
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE e.Ev_ID = ? $where_clause
");
$stmt->bind_param("s", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event)
    die("Event not found or unauthorized.");

// Other related data
$eventflow_result = $conn->query("SELECT * FROM eventminutes WHERE Ev_ID = '$event_id'");
$committee_result = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$event_id'");
$budget_summary = $conn->query("SELECT * FROM budgetsummary WHERE Ev_ID = '$event_id'")->fetch_assoc();
$income_result = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$event_id' AND Bud_Type = 'Income'");
$expense_result = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$event_id' AND Bud_Type = 'Expense'");
$pic = $conn->query("SELECT * FROM personincharge WHERE Ev_ID = '$event_id'")->fetch_assoc();
$individual_result = $conn->query("
    SELECT ir.IR_File, c.Com_Name, c.Com_ID, c.Com_Position
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE c.Ev_ID = '$event_id'
");

$student_email = $event['Stu_Email'] ?? '';

class MYPDF extends TCPDF
{
    public $customHeader = true;

    public function Header()
    {
        if (!$this->customHeader)
            return;

        $this->SetFont('times', '', 10);

        if ($this->PageNo() == 1) {
            // ==== Page 1: Cover Layout ====
            $this->Cell(0, 10, 'CO-CU Project', 0, 0, 'L');
            $this->Cell(0, 10, 'Appendix A     NU/SOP/SHSS/001/F01(rev.1)', 0, 1, 'R');
            $this->Ln(3);

            $this->Image('NU logo.png', 15, 30, 35); // logo

            global $event, $student_email;
            $this->SetY(30);
            $this->SetX(60);
            $this->SetFont('times', '', 12);

            $info = [
                "Proposer’s Name" => $event['Stu_Name'],
                "ID Number" => $event['Stu_ID'],
                "Event" => $event['Ev_Name'],
                "Date" => date("d/m/Y", strtotime($event['Ev_Date'])),
                "Organiser" => $event['Club_Name'],
                "Email" => $student_email,
                "Event ID" => $event['Ev_ID'],
                "Ref. No" => $event['Ev_RefNum'],
                "SDG/USR/CSR No" => $event['Ev_TypeRef']
            ];

            foreach ($info as $label => $value) {
                $this->Cell(50, 8, "$label :", 0, 0, 'L');
                $this->SetFont('times', 'B', 12);
                $this->Cell(0, 8, $value, 0, 1, 'L');
                $this->SetFont('times', '', 12);
            }

            $this->Ln(5);
            $this->SetFont('times', '', 10);
            $this->Cell(0, 0, '', 'T', 1, 'C');
        } else {
            // ==== Other Pages: Just header line ====
            $this->SetY(10);
            $this->Cell(0, 0, '', 'T', 1, 'C');
        }
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 9);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . '    RP/UCC/PER/2025', 0, 0, 'R');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(20, 45, 20); // Top margin leaves space for the custom header
$pdf->SetAutoPageBreak(true, 20); // Optional but safe
$pdf->SetFont('times', '', 12);

// Start Cover Page (Page 1)
$pdf->AddPage();
$pdf->Ln(90); // Push content below the header section layout


// === PAGE 2: Poster + Event Name ===
$pdf->customHeader = false; // Disable header for this page
$pdf->AddPage(); // Start new page

// Poster
if (!empty($event['Ev_Poster']) && file_exists($event['Ev_Poster'])) {
    $posterWidth = 140;  // you can adjust width if needed
    $pdf->Image($event['Ev_Poster'], '', '', $posterWidth);
    $pdf->Ln(10);
} else {
    $pdf->SetFont('times', 'I', 12);
    $pdf->Cell(0, 10, 'Event Poster Not Available', 0, 1, 'C');
    $pdf->Ln(10);
}

// Event name below poster
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, $event['Ev_Name'], 0, 1, 'C');

$pdf->customHeader = true; // Re-enable header for next pages

// === PAGE 3: Event Summary (Proposal) ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, 'Event Summary', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('times', '', 12);

$html = '
<style>
    table { border-collapse: collapse; width: 100%; }
    td { border: 1px solid #000; padding: 6px; }
    td.label { font-weight: bold; width: 35%; }
</style>
<table>
    <tr><td class="label">Date of Submission</td><td>' . date('d F Y') . '</td></tr>
    <tr><td class="label">Club / Society / Projects</td><td>' . $event['Club_Name'] . '</td></tr>
    <tr><td class="label">Name of Project</td><td>' . $event['Ev_Name'] . '</td></tr>
    <tr><td class="label">Nature of Project</td><td>' . $event['Ev_ProjectNature'] . '</td></tr>
    <tr><td class="label">Objectives</td><td>' . $event['Ev_Objectives'] . '</td></tr>
    <tr><td class="label">Date</td><td>' . $event['Ev_Date'] . '</td></tr>
    <tr><td class="label">Day</td><td>' . date('l', strtotime($event['Ev_Date'])) . '</td></tr>
    <tr><td class="label">Time</td><td>' . date('h:i A', strtotime($event['Ev_StartTime'])) . ' to ' . date('h:i A', strtotime($event['Ev_EndTime'])) . '</td></tr>
    <tr><td class="label">Venue</td><td>' . $event['Ev_Venue'] . '</td></tr>
    <tr><td class="label">Estimated Pax</td><td>' . $event['Ev_Pax'] . ' people</td></tr>
    <tr><td class="label">Person In Charge</td><td>' . $pic['PIC_Name'] . '</td></tr>
    <tr><td class="label">Contact No.</td><td>' . $pic['PIC_PhnNum'] . '</td></tr>
    <tr><td class="label">ID Number</td><td>' . $pic['PIC_ID'] . '</td></tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// === PAGE 4: Event Overview ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, 'Event Overview', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 10, 'Introduction:', 0, 1);
$pdf->SetFont('times', '', 12);
$intro = nl2br($event['Ev_Intro'] ?? '-');
$pdf->writeHTML('<div style="text-align: justify;">' . $intro . '</div>', true, false, true, false, '');
$pdf->Ln(5);

$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 10, 'Objectives:', 0, 1);
$pdf->SetFont('times', '', 12);
$objectives = nl2br($event['Ev_Objectives'] ?? '-');
$pdf->writeHTML('<div style="text-align: justify;">' . $objectives . '</div>', true, false, true, false, '');
$pdf->Ln(5);

$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 10, 'Details:', 0, 1);
$pdf->SetFont('times', '', 12);
$details = nl2br($event['Ev_Details'] ?? '-');
$pdf->writeHTML('<div style="text-align: justify;">' . $details . '</div>', true, false, true, false, '');

// === PAGE 5: Event Flow ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, 'Event Flow', 0, 1, 'C');
$pdf->Ln(3);

// Table styling
$html = '
<style>
    table { border-collapse: collapse; width: 100%; font-family: times; font-size: 12pt; }
    th, td { border: 1px solid #000; padding: 6px; }
    th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
    td.center { text-align: center; }
</style>
<table>
    <tr>
        <th style="width: 12%;">Date</th>
        <th style="width: 12%;">Start</th>
        <th style="width: 12%;">End</th>
        <th style="width: 10%;">Hours</th>
        <th style="width: 27%;">Activity</th>
        <th style="width: 27%;">Remarks</th>
    </tr>';

$eventflow_result->data_seek(0);
while ($row = $eventflow_result->fetch_assoc()) {
    $html .= '<tr>';
    $html .= '<td class="center">' . date('d/m/Y', strtotime($row['Date'])) . '</td>';
    $html .= '<td class="center">' . $row['Start_Time'] . '</td>';
    $html .= '<td class="center">' . $row['End_Time'] . '</td>';
    $html .= '<td class="center">' . $row['Hours'] . '</td>';
    $html .= '<td>' . $row['Activity'] . '</td>';
    $html .= '<td>' . $row['Remarks'] . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// === PAGE 6: Committee Members ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, 'Committee Members', 0, 1, 'C');
$pdf->Ln(3);

$html = '
<style>
    table { border-collapse: collapse; width: 100%; font-family: times; font-size: 12pt; }
    th, td { border: 1px solid #000; padding: 6px; }
    th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
    td.center { text-align: center; }
</style>
<table>
    <tr>
        <th style="width: 10%;">ID</th>
        <th style="width: 18%;">Name</th>
        <th style="width: 15%;">Position</th>
        <th style="width: 15%;">Department</th>
        <th style="width: 10%;">Phone</th>
        <th style="width: 22%;">Job Scope</th>
        <th style="width: 10%;">COCU</th>
    </tr>';

$committee_result->data_seek(0);
while ($committee = $committee_result->fetch_assoc()) {
    $cocu = $committee['Com_COCUClaimers'] == '1' ? 'Yes' : 'No';
    $html .= '<tr>';
    $html .= '<td class="center">' . $committee['Com_ID'] . '</td>';
    $html .= '<td>' . $committee['Com_Name'] . '</td>';
    $html .= '<td>' . $committee['Com_Position'] . '</td>';
    $html .= '<td>' . $committee['Com_Department'] . '</td>';
    $html .= '<td class="center">' . $committee['Com_PhnNum'] . '</td>';
    $html .= '<td>' . $committee['Com_JobScope'] . '</td>';
    $html .= '<td class="center">' . $cocu . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// === PAGE 7: Budget Summary ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, 'Event Budget Summary', 0, 1, 'C');
$pdf->Ln(3);

// Build the table
$html = '
<style>
    table { border-collapse: collapse; width: 100%; font-family: times; font-size: 12pt; }
    th, td { border: 1px solid #000; padding: 6px; }
    th { background-color: #f0f0f0; text-align: center; }
    td.right { text-align: right; }
</style>
<table>
    <tr><th colspan="2">Income</th></tr>
    <tr><th>Description</th><th>Amount (RM)</th></tr>';

$total_income = 0;
$income_result->data_seek(0);
while ($row = $income_result->fetch_assoc()) {
    $amount = number_format($row['Bud_Amount'], 2);
    $html .= "<tr><td>{$row['Bud_Desc']}</td><td class='right'>{$amount}</td></tr>";
    $total_income += $row['Bud_Amount'];
}
$html .= "<tr><td><strong>Total Income</strong></td><td class='right'><strong>RM " . number_format($total_income, 2) . "</strong></td></tr>";

$html .= '
<tr><th colspan="2">Expenses</th></tr>
<tr><th>Description</th><th>Amount (RM)</th></tr>';

$total_expense = 0;
$expense_result->data_seek(0);
while ($row = $expense_result->fetch_assoc()) {
    $amount = number_format($row['Bud_Amount'], 2);
    $html .= "<tr><td>{$row['Bud_Desc']}</td><td class='right'>{$amount}</td></tr>";
    $total_expense += $row['Bud_Amount'];
}
$html .= "<tr><td><strong>Total Expenses</strong></td><td class='right'><strong>RM " . number_format($total_expense, 2) . "</strong></td></tr>";

// Surplus/Deficit inside table
$surplus = $budget_summary['Surplus_Deficit'] ?? ($total_income - $total_expense);
$html .= "<tr><td><strong>Surplus / Deficit</strong></td><td class='right'><strong>RM " . number_format($surplus, 2) . "</strong></td></tr>";
$html .= "</table><br><br>";

// Prepared By
$prepared_by = $budget_summary['Prepared_By'] ?? '-';
$html .= "<strong>Prepared By:</strong> {$prepared_by}";

$pdf->writeHTML($html, true, false, true, false, '');

// === PAGE 8: Post Event Section Divider ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 90, 'Post Event Report Section', 0, 1, 'C');

// === PAGE 9: Post-Event Summary ===
$pdf->AddPage();
$pdf->SetFont('times', 'B', 16);
$pdf->Cell(0, 10, 'Event Summary', 0, 1, 'C');
$pdf->Ln(5);

function writeField($pdf, $label, $content)
{
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 8, $label, 0, 1);
    $pdf->SetFont('times', '', 12);
    $pdf->writeHTML('<div style="text-align: justify;">' . nl2br($content) . '</div>', true, false, true, false, '');
    $pdf->Ln(3);
}

writeField($pdf, 'Event Name:', $event['Ev_Name'] ?? '-');
writeField($pdf, 'Event Nature:', $event['Ev_ProjectNature'] ?? '-');
writeField($pdf, 'Objectives:', $event['Ev_Objectives'] ?? '-');
writeField($pdf, 'Details:', $event['Ev_Details'] ?? '-');
writeField($pdf, 'Challenges:', $event['Rep_ChallengesDifficulties'] ?? '-');
writeField($pdf, 'Conclusion:', $event['Rep_Conclusion'] ?? '-');


// === PAGE 10+: Event Photos ===
$photos = json_decode($event['rep_photo'] ?? '[]', true);

if (!empty($photos)) {
    $pdf->AddPage();
    $pdf->SetFont('times', 'B', 16);
    $pdf->Cell(0, 10, 'Event Photos', 0, 1, 'C');
    $pdf->Ln(5);

    $photoWidth = 80;
    $photoHeight = 60;
    $marginX = 15;
    $spacingX = 10;
    $spacingY = 10;

    $x = $marginX;
    $y = $pdf->GetY();
    $count = 0;
    $figure = 1;

    foreach ($photos as $photoPath) {
        if (file_exists($photoPath)) {
            // Add photo
            $pdf->Image($photoPath, $x, $y, $photoWidth, $photoHeight);
            // Add caption
            $pdf->SetXY($x, $y + $photoHeight + 3);
            $pdf->SetFont('times', '', 11);
            $pdf->MultiCell($photoWidth, 8, 'Figure ' . $figure, 0, 'C');
            $figure++;
            $count++;

            // Move to next position
            if ($count % 2 == 0) {
                $x = $marginX;
                $y += $photoHeight + 15;
            } else {
                $x += $photoWidth + $spacingX;
            }

            // If beyond page height, add new page
            if ($y + $photoHeight > 240) {
                $pdf->AddPage();
                $pdf->SetFont('times', 'B', 16);
                $pdf->Cell(0, 10, 'Event Photos (continued)', 0, 1, 'C');
                $x = $marginX;
                $y = $pdf->GetY() + 5;
            }
        }
    }
}

// === Export TCPDF pages into a temp PDF first ===
$temp_main_pdf = tempnam(sys_get_temp_dir(), 'mainpdf_') . '.pdf';
$pdf->Output($temp_main_pdf, 'F');

// === Create final FPDI document ===
$finalPdf = new Fpdi();
$finalPdf->setPrintHeader(false);
$finalPdf->setPrintFooter(false);

// Merge TCPDF-generated pages
$mainPages = $finalPdf->setSourceFile($temp_main_pdf);
for ($i = 1; $i <= $mainPages; $i++) {
    $tpl = $finalPdf->importPage($i);
    $size = $finalPdf->getTemplateSize($tpl);
    $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $finalPdf->useTemplate($tpl);
}

// 1️⃣ Statement file (bank summary)
$statement_file = $budget_summary['statement'] ?? '';
if (!empty($statement_file) && file_exists($statement_file)) {
    $pages = $finalPdf->setSourceFile($statement_file);
    for ($i = 1; $i <= $pages; $i++) {
        $tpl = $finalPdf->importPage($i);
        $size = $finalPdf->getTemplateSize($tpl);
        $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $finalPdf->useTemplate($tpl);
    }
}

// 2️⃣ COCU PDFs from committee
$cocu_pdfs = [];
$committee_result->data_seek(0);
while ($committee = $committee_result->fetch_assoc()) {
    if ($committee['Com_COCUClaimers'] == '1' && file_exists($committee['student_statement'])) {
        $cocu_pdfs[] = $committee['student_statement'];
    }
}
foreach ($cocu_pdfs as $file) {
    $pages = $finalPdf->setSourceFile($file);
    for ($i = 1; $i <= $pages; $i++) {
        $tpl = $finalPdf->importPage($i);
        $size = $finalPdf->getTemplateSize($tpl);
        $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $finalPdf->useTemplate($tpl);
    }
}

// 3️⃣ Individual reports
$individual_pdfs = [];
$individual_result->data_seek(0);
while ($ind = $individual_result->fetch_assoc()) {
    if (!empty($ind['IR_File']) && file_exists('uploads/individual_reports/' . $ind['IR_File'])) {
        $individual_pdfs[] = 'uploads/individual_reports/' . $ind['IR_File'];
    }
}
foreach ($individual_pdfs as $file) {
    $pages = $finalPdf->setSourceFile($file);
    for ($i = 1; $i <= $pages; $i++) {
        $tpl = $finalPdf->importPage($i);
        $size = $finalPdf->getTemplateSize($tpl);
        $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $finalPdf->useTemplate($tpl);
    }
}

// === Final Output ===
$event_id_safe = str_replace("/", "_", $event['Ev_ID']);
$finalPdf->Output("Event_$event_id_safe.pdf", "D");
