<?php
require_once('TCPDF-main/tcpdf.php');
include('dbconfig.php');

$event_id = $_GET['id'];

$event_query = "
    SELECT 
        e.*, 
        s.Stu_Name, 
        c.Club_Name
    FROM 
        events e 
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID 
    LEFT JOIN club c ON e.Club_ID = c.Club_ID 
   
    WHERE e.Ev_ID = '$event_id'";

$event_result = $conn->query($event_query);
$event = $event_result->fetch_assoc();

$pic_query = "SELECT * FROM personincharge WHERE Ev_ID = '$event_id'";
$pic_result = $conn->query($pic_query);
$pic = $pic_result->fetch_assoc();

$committee_query = "SELECT * FROM committee WHERE Ev_ID = '$event_id'";
$committee_result = $conn->query($committee_query);

$budget_query = "SELECT * FROM budget WHERE Ev_ID = '$event_id'";
$budget_result = $conn->query($budget_query);

$eventflow_query = "SELECT Flow_Time, Flow_Description FROM eventflow WHERE Ev_ID = '$event_id'";
$eventflow_result = $conn->query($eventflow_query);

$meeting_query = "SELECT * FROM meeting WHERE Ev_ID = '$event_id'";
$meeting_result = $conn->query($meeting_query);

$pdf = new TCPDF();
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);


$html = '<h2 style="text-align:center;">Event Proposal Details</h2>';
$html .= '<h3>Event Details</h3>';
$html .= '<table cellspacing="3" cellpadding="4">';
$html .= '<tr><td><strong>Event ID:</strong></td><td>' . $event['Ev_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Date Submission:</strong></td><td>' . date('Y-m-d') . '</td></tr>';
$html .= '<tr><td><strong>Student Name:</strong></td><td>' . $event['Stu_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Club Name:</strong></td><td>' . $event['Club_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Name:</strong></td><td>' . $event['Ev_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Nature:</strong></td><td>' . $event['Ev_ProjectNature'] . '</td></tr>';
$html .= '<tr><td><strong>Event Introduction:</strong></td><td>' . $event['Ev_Intro'] . '</td></tr>';
$html .= '<tr><td><strong>Event Details:</strong></td><td>' . $event['Ev_Details'] . '</td></tr>';
$html .= '<tr><td><strong>Event Objectives:</strong></td><td>' . $event['Ev_Objectives'] . '</td></tr>';
$html .= '<tr><td><strong>Event Date:</strong></td><td>' . $event['Ev_Date'] . '</td></tr>';
$html .= '<tr><td><strong>Start Time:</strong></td><td>' . $event['Ev_StartTime'] . '</td></tr>';
$html .= '<tr><td><strong>End Time:</strong></td><td>' . $event['Ev_EndTime'] . '</td></tr>';
$html .= '<tr><td><strong>Participants:</strong></td><td>' . $event['Ev_Pax'] . '</td></tr>';
$html .= '<tr><td><strong>Venue:</strong></td><td>' . $event['Ev_Venue'] . '</td></tr>';
$html .= '</table><br>';
$html .= '<h3>Person in Charge</h3>';
$html .= '<table cellspacing="3" cellpadding="4">';
$html .= '<tr><td><strong>Name:</strong></td><td>' . $pic['PIC_Name'] . '</td></tr>';
$html .= '<tr><td><strong>ID:</strong></td><td>' . $pic['PIC_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Phone:</strong></td><td>' . $pic['PIC_PhnNum'] . '</td></tr>';
$html .= '</table><br>';


$html .= '<h3>Event Flow</h3>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr><th>Time</th><th>Flow Description</th></tr>';
while ($flow = $eventflow_result->fetch_assoc()) {
    $html .= '<tr><td>' . $flow['Flow_Time'] . '</td><td>' . $flow['Flow_Description'] . '</td></tr>';
}
$html .= '</table><br>';

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
$html .= '<h3>Committee Members</h3>';
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

$pdf->writeHTML($html, true, false, true, false, '');

if (!empty($event['Ev_Poster'])) {
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 10, 'Event Poster', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->Image($event['Ev_Poster'], 40, 50, 120, 150, '', '', '', true, 150);
}


$pdf->Output('Event_Proposal_' . $event_id . '.pdf', 'D');
?>