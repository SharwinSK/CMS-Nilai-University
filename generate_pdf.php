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



$eventflow_query = "SELECT * FROM eventflow WHERE Ev_ID = '$event_id'";
$eventflow_result = $conn->query($eventflow_query);



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




$html = '<h3>Event Details</h3>';
$html .= '<table cellspacing="3" cellpadding="4">';
$html .= '<tr><td><strong>Event ID:</strong></td><td>' . $event['Ev_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Date Submission:</strong></td><td>' . date('Y-m-d') . '</td></tr>';
$html .= '<tr><td><strong>Student Name:</strong></td><td>' . $event['Stu_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Club Name:</strong></td><td>' . $event['Club_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Name:</strong></td><td>' . $event['Ev_Name'] . '</td></tr>';
$html .= '<tr><td><strong>Event Date:</strong></td><td>' . $event['Ev_Date'] . '</td></tr>';
$html .= '<tr><td><strong>Venue:</strong></td><td>' . $event['Ev_Venue'] . '</td></tr>';
$html .= '</table><br>';

$html .= '<h3>Person in Charge</h3>';
$html .= '<table cellspacing="3" cellpadding="4">';
$html .= '<tr><td><strong>Name:</strong></td><td>' . $pic['PIC_Name'] . '</td></tr>';
$html .= '<tr><td><strong>ID:</strong></td><td>' . $pic['PIC_ID'] . '</td></tr>';
$html .= '<tr><td><strong>Phone:</strong></td><td>' . $pic['PIC_PhnNum'] . '</td></tr>';
$html .= '</table>';

$pdf->writeHTML($html);



$pdf->AddPage();
$html = '<h3>Event Flow / Minutes of Meeting</h3>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Activity</th>
            <th>Remarks / Meeting Minutes</th>
            <th>Hours</th>
          </tr>';

// Fetch data from the unified `Eventflow` table
while ($row = $eventflow_result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . htmlspecialchars($row['Date']) . '</td>
                <td>' . htmlspecialchars($row['Start_Time']) . '</td>
                <td>' . htmlspecialchars($row['End_Time']) . '</td>
                <td>' . nl2br(htmlspecialchars($row['Activity'])) . '</td>
                <td>' . nl2br(htmlspecialchars($row['Remarks'])) . '</td>
                <td>' . htmlspecialchars($row['Hours']) . '</td>
              </tr>';
}

$html .= '</table><br>';
$pdf->writeHTML($html);
$html = '';



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