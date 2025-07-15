<?php
require_once('../../TCPDF-main/tcpdf.php');
require_once('../../fpdi/src/autoload.php');

use setasign\Fpdi\Tcpdf\Fpdi;
include('../../db/dbconfig.php'); // Adjust path if needed
include('pdf_functions.php');
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
    case 'admin':
        $where_clause = "";
        break;
    default:
        die("Unauthorized access");
}

$event_id = $_GET['id'];

// === FETCH DATA ===
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
$eventflow_result = $conn->query("SELECT * FROM eventminutes WHERE Ev_ID = '$event_id'");
$student = $conn->query("SELECT * FROM student WHERE Stu_ID = '{$event['Stu_ID']}'")->fetch_assoc();
$club_query = $conn->query("SELECT Club_Logo FROM club WHERE Club_ID = '{$event['Club_ID']}'");
$club_logo = $club_query->fetch_assoc()['Club_Logo'] ?? null;
$venue_name = $conn->query("SELECT Venue_Name FROM venue WHERE Venue_ID = '{$event['Ev_VenueID']}'")->fetch_assoc()['Venue_Name'] ?? '-';
$alt_venue_name = $conn->query("SELECT Venue_Name FROM venue WHERE Venue_ID = '{$event['Ev_AltVenueID']}'")->fetch_assoc()['Venue_Name'] ?? '-';
$budget_result = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$event_id'");

$event['Ev_VenueID'] = $venue_name;
$event['Ev_AltVenueID'] = $alt_venue_name;

//  GENERATE PDF CONTENT 
$pdf = new MYPDF();
$pdf->SetMargins(10, 35, 10);

$pdf->AddPage(); // Page 1
renderEventSummary($pdf, $event, $pic);

$pdf->AddPage();
renderCoverPage($pdf, $event, $student, $club_logo);

$pdf->AddPage(); // Page 3
renderEventOverview($pdf, $event);

$pdf->AddPage(); // Page 4
renderEventFlow($pdf, $eventflow_result);

$pdf->AddPage(); // Page 5
$cocu_pdfs = [];
renderCommitteeDetails($pdf, $committee_result, $cocu_pdfs);

$pdf->AddPage(); // Page 6
renderLogistics($pdf, $event);

$pdf->AddPage(); // Page 7
renderEventPoster($pdf, $event['Ev_Poster']);

$pdf->AddPage(); // Page 8
renderBudgetSection($pdf, $budget_result, $budget_summary);


// === SAVE TCPDF OUTPUT TO TEMP FILE ===
$temp_file = tempnam(sys_get_temp_dir(), 'proposal_') . '.pdf';
$pdf->Output($temp_file, 'F');

// === FINAL MERGE USING FPDI ===
include('merge_appendices.php');
mergeAppendices($temp_file, $event, $budget_summary, $cocu_pdfs);
?>