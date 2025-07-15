<?php
require_once('../../TCPDF-main/tcpdf.php');

// ✅ HEADER ONLY
class MYPDF extends TCPDF
{
    public function Header()
    {
        $this->Image('NU logo2.jpeg', 10, 10, 25); // Logo
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetXY(0, 10);
        $this->Cell(0, 10, 'PROPOSAL FOR PROJECT/ACTIVITY', 0, 2, 'C');

        $this->SetFont('dejavusans', '', 10);
        $this->SetXY(0, 20);
        $this->Cell(0, 10, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 2, 'C');

        $this->Line(10, 30, 200, 30); // Horizontal line
    }
}
function renderEventSummary($pdf, $event, $pic)
{
    $day_of_week = date('l', strtotime($event['Ev_Date']));
    $start_time = date('h:i A', strtotime($event['Ev_StartTime']));
    $end_time = date('h:i A', strtotime($event['Ev_EndTime']));
    $ref_num = $event['Ev_RefNum'] ?? '-';
    $type_code = $event['Ev_TypeRef'] ?? '-';
    $pax = $event['Ev_Pax'] ?? '-';
    $sdg_usr = $event['Ev_TypeRef'] ?? '-';


    $html = '<h3>Event Summary</h3>';
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">';
    $html .= "<tr><td><strong>Event ID</strong></td><td>{$event['Ev_ID']}</td></tr>";
    $html .= "<tr><td><strong>Reference Number</strong></td><td>{$ref_num}</td></tr>";
    $html .= "<tr><td><strong>Event Type</strong></td><td>{$type_code}</td></tr>";
    $html .= "<tr><td><strong>Date of Submission</strong></td><td>" . date('d F Y') . "</td></tr>";
    $html .= "<tr><td><strong>Club</strong></td><td>{$event['Club_Name']}</td></tr>";
    $html .= "<tr><td><strong>Event Name</strong></td><td>{$event['Ev_Name']}</td></tr>";
    $html .= "<tr><td><strong>Event Nature</strong></td><td>{$event['Ev_ProjectNature']}</td></tr>";
    $html .= "<tr><td><strong>Objectives</strong></td><td>{$event['Ev_Objectives']}</td></tr>";
    $html .= "<tr><td><strong>Date</strong></td><td>{$event['Ev_Date']}</td></tr>";
    $html .= "<tr><td><strong>Day</strong></td><td>{$day_of_week}</td></tr>";
    $html .= "<tr><td><strong>Time</strong></td><td>{$start_time} to {$end_time}</td></tr>";
    $html .= "<tr><td><strong>Venue</strong></td><td>{$event['Ev_VenueID']}</td></tr>";
    $html .= "<tr><td><strong>Estimated Pax</strong></td><td>{$pax}</td></tr>";
    $html .= "<tr><td><strong>Person In Charge</strong></td><td>{$pic['PIC_Name']}</td></tr>";
    $html .= "<tr><td><strong>Contact No.</strong></td><td>{$pic['PIC_PhnNum']}</td></tr>";
    $html .= "<tr><td><strong>ID Number</strong></td><td>{$pic['PIC_ID']}</td></tr>";
    $html .= "<tr><td><strong>SDG / USR No</strong></td><td>{$sdg_usr}</td></tr>";
    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderCoverPage($pdf, $event, $student = null, $club_logo = null)
{
    $pdf->SetFont('dejavusans', '', 12);

    if (!empty($club_logo) && file_exists($club_logo)) {
        $pdf->Image($club_logo, 85, 20, 40); // Club logo centered
    } else {
        $pdf->SetXY(85, 30);
        $pdf->Cell(40, 10, 'No Logo', 0, 1, 'C');
    }

    $pdf->Ln(50);

    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'CO-CURRICULUM PROJECT', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(0, 10, strtoupper($event['Club_Name']), 0, 1, 'C');

    $pdf->Ln(5);
    $pdf->SetFont('dejavusans', '', 13);
    $pdf->MultiCell(0, 10, $event['Ev_Name'], 0, 'C');
    $pdf->Ln(15);

    // Student Info
    $proposerName = $student['Stu_Name'] ?? '-';
    $proposerID = $student['Stu_ID'] ?? '-';
    $department = $student['Stu_Department'] ?? '-';
    $date = date('d F Y', strtotime($event['Ev_Date']));

    $pdf->SetFont('dejavusans', '', 12);
    $pdf->Cell(0, 8, "Proposed by: $proposerName", 0, 1, 'C');
    $pdf->Cell(0, 8, "Proposer ID: $proposerID", 0, 1, 'C');
    $pdf->Cell(0, 8, "Department: $department", 0, 1, 'C');
    $pdf->Cell(0, 8, "Date of Event: $date", 0, 1, 'C');
}
function renderEventOverview($pdf, $event)
{

    $pdf->SetFont('dejavusans', '', 11);

    $intro = nl2br($event['Ev_Intro'] ?? '-');
    $objectives = nl2br($event['Ev_Objectives'] ?? '-');
    $details = nl2br($event['Ev_Details'] ?? '-');

    $html = '<h2 style="text-align:center;">1.0 Event Overview</h2><br>';

    $html .= '<strong>1.1 Introduction:</strong><br>';
    $html .= "<p style='text-align:justify;'>$intro</p><br>";

    $html .= '<strong>1.2 Objectives:</strong><br>';
    $html .= "<p style='text-align:justify;'>$objectives</p><br>";

    $html .= '<strong>1.3 Purpose of Event:</strong><br>';
    $html .= "<p style='text-align:justify;'>$details</p>";

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderEventFlow($pdf, $eventflow_result)
{

    $pdf->SetFont('dejavusans', '', 11);

    $html = '<h2>2.0 Event Flow</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;">';
    $html .= '<tr>
        <th>Date</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Activity</th>
        <th>Remarks</th>
        <th>Hours</th>
    </tr>';

    while ($row = $eventflow_result->fetch_assoc()) {
        $html .= "<tr>
            <td>{$row['Date']}</td>
            <td>{$row['Start_Time']}</td>
            <td>{$row['End_Time']}</td>
            <td>{$row['Activity']}</td>
            <td>{$row['Remarks']}</td>
            <td>{$row['Hours']}</td>
        </tr>";
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderCommitteeDetails($pdf, $committee_result, &$cocu_pdfs)
{

    $pdf->SetFont('dejavusans', '', 11);

    $html = '<h2>3.0 Committee Members</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;">';
    $html .= '<tr>
        <th>ID</th>
        <th>Name</th>
        <th>Position</th>
        <th>Department</th>
        <th>Phone</th>
        <th>Job Scope</th>
        <th>COCU Claimer</th>
    </tr>';

    while ($committee = $committee_result->fetch_assoc()) {
        $cocu = $committee['Com_COCUClaimers'] == '1' ? 'Yes' : 'No';

        $html .= "<tr>
            <td>{$committee['Com_ID']}</td>
            <td>{$committee['Com_Name']}</td>
            <td>{$committee['Com_Position']}</td>
            <td>{$committee['Com_Department']}</td>
            <td>{$committee['Com_PhnNum']}</td>
            <td>{$committee['Com_JobScope']}</td>
            <td>{$cocu}</td>
        </tr>";

        if ($cocu === 'Yes' && !empty($committee['student_statement']) && file_exists($committee['student_statement'])) {
            $cocu_pdfs[] = $committee['student_statement'];
        }
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderLogistics($pdf, $event)
{

    $pdf->SetFont('dejavusans', '', 11);

    $html = '<h2>4.0 Event Logistics</h2>';
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">';

    $html .= "<tr><td style='width: 40%;'><strong>6.1 Proposed Date</strong></td><td>{$event['Ev_Date']}</td></tr>";
    $html .= "<tr><td><strong>6.2 Alternative Date</strong></td><td>{$event['Ev_AlternativeDate']}</td></tr>";
    $html .= "<tr><td><strong>6.3 Proposed Venue</strong></td><td>{$event['Ev_VenueID']}</td></tr>";
    $html .= "<tr><td><strong>6.4 Alternative Venue</strong></td><td>{$event['Ev_AltVenueID']}</td></tr>";

    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}
function renderBudgetSection($pdf, $budget_result, $summary)
{
    $pdf->SetFont('dejavusans', '', 11);

    $html = '<h2>5.0 Budget</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">';
    $html .= '<thead>
        <tr style="background-color: #f2f2f2;">
            <th><strong>Description</strong></th>
            <th><strong>Amount (RM)</strong></th>
            <th><strong>Type</strong></th>
            <th><strong>Remarks</strong></th>
        </tr>
    </thead><tbody>';

    while ($row = $budget_result->fetch_assoc()) {
        $html .= "<tr>
            <td>{$row['Bud_Desc']}</td>
            <td style='text-align: right;'>" . number_format($row['Bud_Amount'], 2) . "</td>
            <td>{$row['Bud_Type']}</td>
            <td>{$row['Bud_Remarks']}</td>
        </tr>";
    }

    $html .= '</tbody></table><br><br>';

    // Summary Section
    $totalIncome = $summary['Total_Income'] ?? '0.00';
    $totalExpense = $summary['Total_Expense'] ?? '0.00';
    $surplus = $summary['Surplus_Deficit'] ?? '0.00';
    $preparedBy = $summary['Prepared_By'] ?? '-';

    $html .= '<h4>Summary</h4>';
    $html .= '<table cellpadding="5" cellspacing="0" style="width:60%;">';
    $html .= "<tr><td><strong>Total Income:</strong></td><td>RM " . number_format($totalIncome, 2) . "</td></tr>";
    $html .= "<tr><td><strong>Total Expenses:</strong></td><td>RM " . number_format($totalExpense, 2) . "</td></tr>";
    $html .= "<tr><td><strong>Surplus / Deficit:</strong></td><td>RM " . number_format($surplus, 2) . "</td></tr>";
    $html .= "<tr><td><strong>Prepared By:</strong></td><td>{$preparedBy}</td></tr>";
    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderEventPoster($pdf, $poster_path)
{
    if (!empty($poster_path) && file_exists($poster_path)) {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'Event Poster', 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->Image($poster_path, 30, 40, 150);
    } else {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 10, 'Event Poster Not Found', 0, 1, 'C');
    }
}
