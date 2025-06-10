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
        e.Ev_Venue, e.Ev_Date, e.Ev_StartTime, e.Ev_EndTime, e.Ev_Poster, e.Ev_Type, e.Ev_TypeNum, 
        ep.Rep_ChallengesDifficulties, ep.Rep_Photo, ep.Rep_Conclusion, ep.Rep_RefNum,
        c.Club_Name, s.Stu_Name
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
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
    SELECT Date, Start_Time, End_Time, Activity, Remarks
    FROM eventflow
    WHERE Ev_ID = '$event_id'";
$flow_result = $conn->query($flow_query);



$individual_query = "
    SELECT c.Com_Name, c.Com_ID, c.Com_Position, ir.IRS_Duties, ir.IRS_Attendance, 
    ir.IRS_Experience, ir.IRS_Challenges, ir.IRS_Benefits
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE c.Ev_ID = '$event_id'
";
$individual_result = $conn->query($individual_query);

class MYPDF extends TCPDF
{
    public function Header()
    {
        $this->SetFont('dejavusans', 'B', 12);
        $this->Cell(0, 10, 'PROPOSAL FOR PROJECT/ACTIVITY', 0, 1, 'C');
        $this->Image('NU logo2.jpeg', 5, 5, 20);
        $this->SetXY(60, 15);
        $this->SetFont('dejavusans', '', 10);
        $this->Cell(40, 8, 'Co-Cu Project', 1, 0, 'C');
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetXY(160, 15);
        $this->Cell(40, 8, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 0, 'R');
        $this->Ln(15);
        $this->Cell(0, 0, '', 'T', 1, 'C');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// Event Details Section
$html = '<h2 style="text-align:center;">Post Event Report</h2>';
$html .= '<h3>Event Details</h3>';
$html .= '<table cellspacing="3" cellpadding="4">';
$html .= '<tr><td><strong>Event ID:</strong></td><td>' . $event['Ev_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Date Submission:</strong></td><td>' . date('d-m-Y') . '</td></tr>';
$html .= '<tr><td><strong>Student Name:</strong></td><td>' . $event['Stu_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Club Name:</strong></td><td>' . $event['Club_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Name:</strong></td><td>' . $event['Ev_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Nature:</strong></td><td>' . $event['Ev_ProjectNature'] . '</td></tr>';
$html .= '<tr><td><strong>Event Type:</strong></td><td>' . $event['Ev_Type'] . '</td></tr>';
$html .= '<tr><td><strong>Event Type Number:</strong></td><td>' . $event['Ev_TypeNum'] . '</td></tr>';
$html .= '<tr><td><strong>Event Introduction:</strong></td><td>' . $event['Ev_Intro'] . '</td></tr>';
$html .= '<tr><td><strong>Event Details:</strong></td><td>' . $event['Ev_Details'] . '</td></tr>';
$html .= '<tr><td><strong>Event Objectives:</strong></td><td>' . $event['Ev_Objectives'] . '</td></tr>';
$html .= '<tr><td><strong>Event Date:</strong></td><td>' . $event['Ev_Date'] . '</td></tr>';
$html .= '<tr><td><strong>Start Time:</strong></td><td>' . $event['Ev_StartTime'] . '</td></tr>';
$html .= '<tr><td><strong>End Time:</strong></td><td>' . $event['Ev_EndTime'] . '</td></tr>';
$html .= '<tr><td><strong>Participants:</strong></td><td>' . $event['Ev_Pax'] . '</td></tr>';
$html .= '<tr><td><strong>Venue:</strong></td><td>' . $event['Ev_Venue'] . '</td></tr>';
$html .= '<tr><td><strong>Challenges:</strong></td><td>' . $event['Rep_ChallengesDifficulties'] . '</td></tr>';
$html .= '<tr><td><strong>Conclusion:</strong></td><td>' . $event['Rep_Conclusion'] . '</td></tr>';

$html .= '<h3>Challenges and Conclusion</h3>';
$html .= '<table><tr><td><b>Challenges:</b> ' . $event['Rep_ChallengesDifficulties'] . '</td></tr>';
$html .= '<tr><td><b>Conclusion:</b> ' . $event['Rep_Conclusion'] . '</td></tr></table><br>';

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


$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Event Flow', 0, 1, 'L');

$html = '<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Date</th><th>Start Time</th><th>End Time</th><th>Activity</th><th>Remarks</th>
        </tr>
    </thead>
    <tbody>';
while ($flow = $flow_result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $flow['Date'] . '</td>
        <td>' . $flow['Start_Time'] . '</td>
        <td>' . $flow['End_Time'] . '</td>
        <td>' . $flow['Activity'] . '</td>
        <td>' . $flow['Remarks'] . '</td>
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


$event_id_safe = str_replace("/", "_", $event_id);
$pdf->Output("Event_$event_id_safe.pdf", "D");

?>