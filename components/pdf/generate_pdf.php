<?php
require_once('../../TCPDF-main/tcpdf.php');
require_once('../../fpdi/src/autoload.php');

use setasign\Fpdi\Tcpdf\Fpdi;
include('../../db/dbconfig.php');
include('pdf_functions.php'); // Your updated functions file
session_start();

// Authorization check
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

// === FETCH DATA WITH BETTER ERROR HANDLING ===
try {
    // Main event data
    $event_query = "SELECT e.*, s.Stu_Name, s.Stu_Program, s.Stu_School, c.Club_Name, c.Club_Logo 
                   FROM events e
                   LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
                   LEFT JOIN club c ON e.Club_ID = c.Club_ID
                   WHERE e.Ev_ID = ? $where_clause";

    $stmt = $conn->prepare($event_query);
    $stmt->bind_param("s", $event_id);
    $stmt->execute();
    $event_result = $stmt->get_result();

    if ($event_result->num_rows == 0) {
        die("Event not found or no permission to access.");
    }

    $event = $event_result->fetch_assoc();

    // Person in charge
    $pic_query = "SELECT * FROM personincharge WHERE Ev_ID = ?";
    $pic_stmt = $conn->prepare($pic_query);
    $pic_stmt->bind_param("s", $event_id);
    $pic_stmt->execute();
    $pic = $pic_stmt->get_result()->fetch_assoc();

    // Committee members
    $committee_query = "SELECT * FROM committee WHERE Ev_ID = ? ORDER BY Com_Position, Com_Name";
    $committee_stmt = $conn->prepare($committee_query);
    $committee_stmt->bind_param("s", $event_id);
    $committee_stmt->execute();
    $committee_result = $committee_stmt->get_result();

    // Budget summary
    $budget_summary_query = "SELECT * FROM budgetsummary WHERE Ev_ID = ? LIMIT 1";
    $budget_summary_stmt = $conn->prepare($budget_summary_query);
    $budget_summary_stmt->bind_param("s", $event_id);
    $budget_summary_stmt->execute();
    $budget_summary = $budget_summary_stmt->get_result()->fetch_assoc();

    // Event flow/minutes
    $eventflow_query = "SELECT * FROM eventminutes WHERE Ev_ID = ? ORDER BY Date, Start_Time";
    $eventflow_stmt = $conn->prepare($eventflow_query);
    $eventflow_stmt->bind_param("s", $event_id);
    $eventflow_stmt->execute();
    $eventflow_result = $eventflow_stmt->get_result();

    // Student details
    $student_query = "SELECT * FROM student WHERE Stu_ID = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("s", $event['Stu_ID']);
    $student_stmt->execute();
    $student = $student_stmt->get_result()->fetch_assoc();

    // Venue names
    $venue_query = "SELECT Venue_Name FROM venue WHERE Venue_ID = ?";

    // Main venue
    $venue_stmt = $conn->prepare($venue_query);
    $venue_stmt->bind_param("i", $event['Ev_VenueID']);
    $venue_stmt->execute();
    $venue_result = $venue_stmt->get_result()->fetch_assoc();
    $venue_name = $venue_result['Venue_Name'] ?? 'Not specified';

    // Alternative venue
    $alt_venue_name = 'Not specified';
    if (!empty($event['Ev_AltVenueID'])) {
        $alt_venue_stmt = $conn->prepare($venue_query);
        $alt_venue_stmt->bind_param("i", $event['Ev_AltVenueID']);
        $alt_venue_stmt->execute();
        $alt_venue_result = $alt_venue_stmt->get_result()->fetch_assoc();
        $alt_venue_name = $alt_venue_result['Venue_Name'] ?? 'Not specified';
    }

    // Fetch advisor name from advisor table
    $advisor_query = "SELECT Adv_Name FROM advisor WHERE Club_ID = ?";
    $advisor_stmt = $conn->prepare($advisor_query);
    $advisor_stmt->bind_param("i", $event['Club_ID']);
    $advisor_stmt->execute();
    $advisor_result = $advisor_stmt->get_result()->fetch_assoc();
    $event['Advisor_Name'] = $advisor_result['Adv_Name'] ?? 'Advisor Name';

    // Fetch coordinator name from coordinator table
    $coordinator_query = "SELECT Coor_Name FROM coordinator LIMIT 1";
    $coordinator_stmt = $conn->prepare($coordinator_query);
    $coordinator_stmt->execute();
    $coordinator_result = $coordinator_stmt->get_result()->fetch_assoc();
    $event['Coordinator_Name'] = $coordinator_result['Coor_Name'] ?? 'Coordinator Name';

    // Budget details
    $budget_query = "SELECT * FROM budget WHERE Ev_ID = ? ORDER BY Bud_Type DESC, Bud_Amount DESC";
    $budget_stmt = $conn->prepare($budget_query);
    $budget_stmt->bind_param("s", $event_id);
    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();

    // Update venue fields in event array for easier access
    $event['Ev_VenueID'] = $venue_name;
    $event['Ev_AltVenueID'] = $alt_venue_name;

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// === GENERATE PDF WITH ENHANCED STYLING AND NEW STRUCTURE ===
try {
    $pdf = new MYPDF();

    // Set default font to Times New Roman with proper sizing
    $pdf->SetDefaultMonospacedFont('times');
    $pdf->SetFont('times', '', 12);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // UPDATED: Better margins for professional look - reduced from 25 to 20
    $pdf->SetMargins(20, 40, 20); // Left, Top, Right - reduced all margins
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(15);

    // Set document information
    $pdf->SetCreator('Nilai University CMS');
    $pdf->SetAuthor($student['Stu_Name'] ?? 'Student');
    $pdf->SetTitle('Event Proposal: ' . $event['Ev_Name']);
    $pdf->SetSubject('Event Proposal Document');
    $pdf->SetKeywords('Event, Proposal, Nilai University, ' . $event['Club_Name']);

    // Page 1: Event Summary (with signatures)
    $pdf->AddPage();
    renderEventSummary($pdf, $event, $pic);

    // Page 2: Cover Page
    $pdf->AddPage();
    renderCoverPage($pdf, $event, $student, $event['Club_Logo'] ?? null);

    // Page 3: 1.0 Introduction & 2.0 Objectives (combined on one page)
    $pdf->AddPage();
    renderIntroductionAndObjectives($pdf, $event);

    // Page 4: 3.0 Purpose of Event
    $pdf->AddPage();
    renderPurposeOfEvent($pdf, $event);

    // Page 5: 4.0 Event Details
    $pdf->AddPage();
    renderEventDetails($pdf, $eventflow_result);

    // Page 6: 5.0 Committee Details
    $pdf->AddPage();
    $cocu_pdfs = [];
    renderCommitteeDetails($pdf, $committee_result, $cocu_pdfs);

    // Page 7: 6.0 Event
    $pdf->AddPage();
    renderEvent($pdf, $event);

    // Page 8: 7.0 Event Poster
    $pdf->AddPage();
    renderEventPoster($pdf, $event['Ev_Poster'] ?? null);

    // Page 9: 8.0 Budget
    $pdf->AddPage();
    $pdf->setBudgetPage(true); // Set budget page flag BEFORE adding the page
    renderBudgetSection($pdf, $budget_result, $budget_summary);

} catch (Exception $e) {
    die("PDF generation error: " . $e->getMessage());
}

// === SAVE TCPDF OUTPUT TO TEMPORARY FILE ===
$temp_file = tempnam(sys_get_temp_dir(), 'redesigned_proposal_') . '.pdf';

try {
    $pdf->Output($temp_file, 'F');

    if (!file_exists($temp_file) || filesize($temp_file) == 0) {
        throw new Exception("Failed to generate PDF file");
    }

} catch (Exception $e) {
    die("Error saving PDF: " . $e->getMessage());
}

// === MERGE WITH APPENDICES USING FPDI ===
include('merge_appendices.php');

try {
    mergeAppendices($temp_file, $event, $budget_summary, $cocu_pdfs);

    // Clean up temporary file
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }

} catch (Exception $e) {
    // Clean up on error
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    die("Error merging appendices: " . $e->getMessage());
}

// Close database connections
$conn->close();
?>