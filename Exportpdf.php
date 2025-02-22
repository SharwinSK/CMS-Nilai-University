<?php
require_once('TCPDF-main/tcpdf.php');
include('dbconfig.php');
session_start();

if (!isset($_SESSION['user_type']) || !isset($_GET['event_id'])) {
    die("Unauthorized access.");
}

$user_type = $_SESSION['user_type'];
$event_id = $_GET['event_id'];


$where_clause = '';
switch ($user_type) {
    case 'student':
        $student_id = $_SESSION['Stu_ID'];
        $where_clause = "AND e.Stu_ID = '$student_id'";
        break;
    case 'advisor':
        $club_id = $_SESSION['Club_ID'];
        $where_clause = "AND e.Club_ID = '$club_id'";
        break;
    case 'coordinator':

        $where_clause = ''; 
        break;
    default:
        die("Invalid user type.");
}

$event_query = "
    SELECT 
        e.Ev_ID, e.Ev_Name, e.Ev_ProjectNature, e.Ev_Objectives, e.Ev_Intro, e.Ev_Details, e.Ev_Pax, 
        v.Venue_Name AS Ev_Venue, e.Ev_Date, e.Ev_StartTime, e.Ev_EndTime, e.Ev_Poster,
        ep.Rep_ChallengesDifficulties, ep.Rep_Photo, ep.Rep_Receipt, ep.Rep_Conclusion, ep.Rep_RefNum,
        c.Club_Name, s.Stu_Name
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN venue v ON e.Ev_Venue = v.Venue_ID
    WHERE e.Ev_ID = '$event_id' $where_clause
";

$event_result = $conn->query($event_query);
if ($event_result->num_rows == 0) {
    die("No event found or unauthorized access.");
}
$event = $event_result->fetch_assoc();

$budget_query = "
    SELECT Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks
    FROM budget
    WHERE Ev_ID = '$event_id'
";
$budget_result = $conn->query($budget_query);

$flow_query = "
    SELECT Flow_Time, Flow_Description
    FROM eventflow
    WHERE Rep_ID = (
        SELECT Rep_ID FROM eventpostmortem WHERE Ev_ID = '$event_id'
    )
";
$flow_result = $conn->query($flow_query);

$meeting_query = "
    SELECT Meeting_Date, Meeting_StartTime, Meeting_EndTime, Meeting_Location, Meeting_Discussion
    FROM meeting
    WHERE Rep_ID = (
        SELECT Rep_ID FROM eventpostmortem WHERE Ev_ID = '$event_id'
    )
";
$meeting_result = $conn->query($meeting_query);

$individual_query = "
    SELECT c.Com_Name, c.Com_ID, c.Com_Position, ir.IRS_Duties, ir.IRS_Attendance, 
    ir.IRS_Experience, ir.IRS_Challenges, ir.IRS_Benefits
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE c.Ev_ID = '$event_id'
";
$individual_result = $conn->query($individual_query);

class PDF extends TCPDF
{

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Cocurricular Management System', 0, 0, 'C');
    }
}
$pdf = new PDF();

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Cocurricular Management System');
$pdf->SetTitle('Event Report');
$pdf->SetSubject('Event Details');

$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Event Report', 0, 1, 'C');



$pdf->SetFont('helvetica', '', 12);

$html = '
<h3>Event Details</h3>
<p><b>Event ID:</b> ' . $event['Ev_ID'] . '</p>
<p><b>Event Name:</b> ' . $event['Ev_Name'] . '</p>
<p><b>Reference Number:</b> ' . $event['Rep_RefNum'] . '</p>
<p><b>Club Name:</b> ' . $event['Club_Name'] . '</p>
<p><b>Student Name:</b> ' . $event['Stu_Name'] . '</p>
<p><b>Project Nature:</b> ' . $event['Ev_ProjectNature'] . '</p>
<p><b>Objectives:</b> ' . $event['Ev_Objectives'] . '</p>
<p><b>Introduction:</b> ' . $event['Ev_Intro'] . '</p>
<p><b>Details:</b> ' . $event['Ev_Details'] . '</p>
<p><b>Estimated Participants:</b> ' . $event['Ev_Pax'] . '</p>
<p><b>Venue:</b> ' . $event['Ev_Venue'] . '</p>
<p><b>Date:</b> ' . $event['Ev_Date'] . '</p>
<p><b>Start Time:</b> ' . $event['Ev_StartTime'] . '</p>
<p><b>End Time:</b> ' . $event['Ev_EndTime'] . '</p>
';
$pdf->writeHTML($html);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Ln(10);
$pdf->Cell(0, 10, 'Budget Details', 0, 1, 'L');
$html = '<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Description</th>
            <th>Amount (RM)</th>
            <th>Type</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>';
while ($budget = $budget_result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $budget['Bud_Desc'] . '</td>
        <td>' . number_format($budget['Bud_Amount'], 2) . '</td>
        <td>' . $budget['Bud_Type'] . '</td>
        <td>' . $budget['Bud_Remarks'] . '</td>
    </tr>';
}
$html .= '</tbody></table>';
$pdf->writeHTML($html);

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Event Flow', 0, 1, 'L');
$html = '<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Time</th><th>Description</th>
        </tr>
    </thead>
    <tbody>';
while ($flow = $flow_result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $flow['Flow_Time'] . '</td>
        <td>' . $flow['Flow_Description'] . '</td>
    </tr>';
}
$html .= '</tbody></table>';
$pdf->writeHTML($html);

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Meeting Details', 0, 1, 'L');
$html = '<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Date</th><th>Start Time</th><th>End Time
            </th><th>Location</th><th>Discussion</th>
        </tr>
    </thead>
    <tbody>';
while ($meeting = $meeting_result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $meeting['Meeting_Date'] . '</td>
        <td>' . $meeting['Meeting_StartTime'] . '</td>
        <td>' . $meeting['Meeting_EndTime'] . '</td>
        <td>' . $meeting['Meeting_Location'] . '</td>
        <td>' . $meeting['Meeting_Discussion'] . '</td>
    </tr>';
}
$html .= '</tbody></table>';
$pdf->writeHTML($html);
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Individual Reports', 0, 1, 'L');

if ($individual_result->num_rows > 0) {
    $html = '<table border="1" cellpadding="5">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th>Committee Name</th>
                <th>Committee ID</th>
                <th>Position</th>
                <th>Duties</th>
                <th>Attendance</th>
                <th>Experience</th>
                <th>Challenges</th>
                <th>Benefits</th>
            </tr>
        </thead>
        <tbody>';
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
    $html .= '</tbody></table>';
    $pdf->writeHTML($html);
} else {
    $pdf->Cell(0, 10, 'No individual reports available 
    for this event.', 0, 1, 'L');
}

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Challenges and Conclusion', 0, 1, 'L');
$html = '<p><b>Challenges:</b> ' . $event['Rep_ChallengesDifficulties'] . '</p>';
$html .= '<p><b>Conclusion:</b> ' . $event['Rep_Conclusion'] . '</p>';
$pdf->writeHTML($html);

if (!empty($event['Ev_Poster']) && file_exists($event['Ev_Poster'])) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Event Poster', 0, 1, 'L');
    $pdf->Ln(10);
    $pdf->Image($event['Ev_Poster'], '', '', 100, 75, '', '', '', true, 300);
}

if (!empty($event['Rep_Photo'])) {
    $photo_paths = json_decode($event['Rep_Photo'], true);
    if (!empty($photo_paths)) {
        foreach ($photo_paths as $photo_path) {
            if (file_exists($photo_path)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Event Photo', 0, 1, 'L');
                $pdf->Ln(10);
                $pdf->Image($photo_path, '', '', 100, 75, '', '', '', true, 300);
            }
        }
    }
}

if (!empty($event['Rep_Receipt'])) {
    $receipt_paths = json_decode($event['Rep_Receipt'], true);
    if (!empty($receipt_paths)) {
        foreach ($receipt_paths as $receipt_path) {
            if (file_exists($receipt_path)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Expense Receipt', 0, 1, 'L');
                $pdf->Ln(10);
                $pdf->Image($receipt_path, '', '', 100, 75, '', '', '', true, 300);
            }
        }
    }
}


$pdf->Output('event_report_' . $event['Ev_ID'] . '.pdf', 'D');
?>