<?php
require_once('TCPDF-main/tcpdf.php');
include('dbconfig.php');

$report_id = $_GET['id'];

$report_query = "
    SELECT 
        e.*, 
        ep.*, 
        s.Stu_Name, 
        c.Club_Name
    FROM 
        events e 
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID 
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID 
    LEFT JOIN club c ON e.Club_ID = c.Club_ID 
    WHERE ep.Rep_ID = '$report_id'";

$report_result = $conn->query($report_query);
$report = $report_result->fetch_assoc();

if (!$report) {
    die("Invalid Report ID.");
}

$eventflow_query = "SELECT Flow_Time, Flow_Description FROM eventflow WHERE Ev_ID = '$report_id'";
$eventflow_result = $conn->query($eventflow_query);

$meeting_query = "SELECT * FROM meeting WHERE Ev_ID = '$report_id'";
$meeting_result = $conn->query($meeting_query);

$committee_query = "SELECT * FROM committee WHERE Ev_ID = '$report_id'";
$committee_result = $conn->query($committee_query);

$budget_query = "SELECT * FROM budget WHERE Ev_ID = '$report_id'";
$budget_result = $conn->query($budget_query);

$individual_query = "
    SELECT ir.Rep_ID, ir.Com_ID, ir.IRS_Duties, ir.IRS_Attendance, ir.IRS_Experience, 
           ir.IRS_Challenges, ir.IRS_Benefits, 
           c.Com_Name, c.Com_Position
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE ir.Rep_ID = '$report_id' AND c.Ev_ID = (SELECT Ev_ID FROM eventpostmortem WHERE Rep_ID = '$report_id')";
$individual_result = $conn->query($individual_query);


$photos = json_decode($report['Rep_Photo'], true);
$receipts = json_decode($report['Rep_Receipt'], true);


class MYPDF extends TCPDF
{

    public function Header()
    {
        $this->SetFont('dejavusans', 'B', 12);
        $this->Cell(0, 10, 'PROPOSAL FOR PROJECT/ACTIVITY', 0, 1, 'C');

        //  Logo 
        $this->Image('NU logo2.jpeg', 5, 5, 20);

        //  "Co-Cu Project" Box
        $this->SetXY(60, 15);
        $this->SetFont('dejavusans', '', 10);
        $this->Cell(40, 8, 'Co-Cu Project', 1, 0, 'C');

        //  Reference Number 
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetXY(160, 15); // Adjust position
        $this->Cell(40, 8, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 0, 'R');

        //  Line Below Header
        $this->Ln(15);
        $this->Cell(0, 0, '', 'T', 1, 'C');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);


$html = '<h2 style="text-align:center;">Postmortem Report</h2>';
$html .= '<h3>Event Details</h3>';
$html .= '<table cellspacing="3" cellpadding="4">';
$html .= '<tr><td><strong>Event ID:</strong></td><td>' . $report['Ev_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Report ID:</strong></td><td>' . $report['Rep_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Date Submission:</strong></td><td>' . date('d-m-Y') . '</td></tr>';
$html .= '<tr><td><strong>Student Name:</strong></td><td>' . $report['Stu_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Club Name:</strong></td><td>' . $report['Club_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Name:</strong></td><td>' . $report['Ev_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Nature:</strong></td><td>' . $report['Ev_ProjectNature'] . '</td></tr>';
$html .= '<tr><td><strong>Event Introduction:</strong></td><td>' . $report['Ev_Intro'] . '</td></tr>';
$html .= '<tr><td><strong>Event Details:</strong></td><td>' . $report['Ev_Details'] . '</td></tr>';
$html .= '<tr><td><strong>Event Objectives:</strong></td><td>' . $report['Ev_Objectives'] . '</td></tr>';
$html .= '<tr><td><strong>Event Date:</strong></td><td>' . $report['Ev_Date'] . '</td></tr>';
$html .= '<tr><td><strong>Start Time:</strong></td><td>' . $report['Ev_StartTime'] . '</td></tr>';
$html .= '<tr><td><strong>End Time:</strong></td><td>' . $report['Ev_EndTime'] . '</td></tr>';
$html .= '<tr><td><strong>Participants:</strong></td><td>' . $report['Ev_Pax'] . '</td></tr>';
$html .= '<tr><td><strong>Venue:</strong></td><td>' . $report['Ev_Venue'] . '</td></tr>';
$html .= '<tr><td><strong>Challenges:</strong></td><td>' . $report['Rep_ChallengesDifficulties'] . '</td></tr>';
$html .= '<tr><td><strong>Conclusion:</strong></td><td>' . $report['Rep_Conclusion'] . '</td></tr>';
$html .= '</table><br>';

//Event Flow 
$pdf->AddPage();
$html = '<h3>Event Flow</h3>';
$html .= '<table border="1" cellpadding="4"><tr><th>Time</th><th>Description</th></tr>';
while ($row = $eventflow_result->fetch_assoc()) {
    $html .= '<tr><td>' . $row['Flow_Time'] . '</td><td>' . $row['Flow_Description'] . '</td></tr>';
}
$html .= '</table>';

// Minutes of Meeting
$html .= '<h3>Minutes of Meeting</h3>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr><th>Date</th><th>Start Time</th><th>End Time</th><th>Location</th><th>Discussion</th></tr>';
while ($meeting = $meeting_result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $meeting['Meeting_Date'] . '</td>
                <td>' . $meeting['Meeting_StartTime'] . '</td>
                <td>' . $meeting['Meeting_EndTime'] . '</td>
                <td>' . $meeting['Meeting_Location'] . '</td>
                <td>' . $meeting['Meeting_Discussion'] . '</td>
              </tr>';
}
$html .= '</table><br>';
$pdf->writeHTML($html);
$html = '';

//Commitee Members
$pdf->AddPage();
$html = '<h3>Committee Members</h3>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr>
            <th>ID</th>
            <th>Name</th>
            <th>Position</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Job Scope</th>
            <th>COCU Claimers</th>
          </tr>';
while ($committee = $committee_result->fetch_assoc()) {
    $cocu = $committee['Com_COCUClaimers'] == '1' ? 'Yes' : 'No';
    $html .= '<tr>
                <td>' . $committee['Com_ID'] . '</td>
                <td>' . $committee['Com_Name'] . '</td>
                <td>' . $committee['Com_Position'] . '</td>
                <td>' . $committee['Com_Department'] . '</td>
                <td>' . $committee['Com_PhnNum'] . '</td>
                <td>' . $committee['Com_JobScope'] . '</td>
                <td>' . $cocu . '</td>
              </tr>';
}
$html .= '</table><br>';

//Budget
$html .= '<h3>Budget</h3>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr>
            <th>Description</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Remarks</th>
          </tr>';
while ($budget = $budget_result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $budget['Bud_Desc'] . '</td>
                <td>' . $budget['Bud_Amount'] . '</td>
                <td>' . $budget['Bud_Type'] . '</td>
                <td>' . $budget['Bud_Remarks'] . '</td>
              </tr>';
}
$html .= '</table><br>';
$pdf->writeHTML($html);
$html = '';

//Individual Report 
$html .= '<h3>Individual Reports</h3>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr><th>Name</th><th>ID</th><th>Position</th><th>Duties</th><th>Attendance
</th><th>Experience</th><th>Challenges</th><th>Benefits</th></tr>';
while ($individual = $individual_result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $individual['Com_Name'] . '</td>
                <td>' . $individual['Com_ID'] . '</td>
                <td>' . $individual['Com_Position'] . '</td>
                <td>' . $individual['IRS_Duties'] . '</td>
                <td>' . $individual['IRS_Attendance'] . '</td>
                <td>' . $individual['IRS_Experience'] . '</td>
                <td>' . $individual['IRS_Challenges'] . '</td>
                <td>' . $individual['IRS_Benefits'] . '</td>
              </tr>';
}
$html .= '</table><br>';

//Event Photo
$pdf->writeHTML($html, true, false, true, false, '');

if (!empty($photos)) {
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 10, 'Event Photos', 0, 1, 'C');
    foreach ($photos as $photo) {
        $pdf->Image($photo, 50, '', 100, 80, '', '', '', true);
        $pdf->Ln(90);
    }
}

//Event expenses Receipt
if (!empty($receipts)) {
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 10, 'Expense Receipts', 0, 1, 'C');
    foreach ($receipts as $receipt) {
        $pdf->Image($receipt, 50, '', 100, 80, '', '', '', true);
        $pdf->Ln(90);
    }
}

$pdf->Output('Postmortem_Report_' . $report_id . '.pdf', 'D');
?>