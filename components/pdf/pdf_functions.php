<?php
require_once('../../TCPDF-main/tcpdf.php');

// ✅ ENHANCED HEADER WITH PROPER CENTERING AND NILAI UNIVERSITY LOGO
class MYPDF extends TCPDF
{
    private $isBudgetPage = false;

    public function setBudgetPage($isBudget = false)
    {
        $this->isBudgetPage = $isBudget;
    }

    public function Header()
    {
        // Add Nilai University Logo on the left
        $logo_path = '../../assets/img/NU logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 25, 0, '', '', '', false, 300, '', false, false, 0);
        }

        // Get page dimensions for proper centering
        $page_width = $this->getPageWidth();
        $left_margin = $this->getMargins()['left'];
        $right_margin = $this->getMargins()['right'];
        $content_width = $page_width - $left_margin - $right_margin;

        // Header text with proper centering
        $this->SetFont('times', 'B', 16);

        // Calculate Y position for first line
        $y_pos = 12;
        $this->SetXY($left_margin, $y_pos);
        $this->Cell($content_width, 8, 'PROPOSAL FOR PROJECT/ACTIVITY', 0, 0, 'C');

        // Second line with smaller font and proper form reference
        $this->SetFont('times', '', 11);
        $y_pos += 10;
        $this->SetXY($left_margin, $y_pos);

        // Different header number for budget page
        if ($this->isBudgetPage) {
            $this->Cell($content_width, 6, 'NU/SOP/SHSS/001/F02 (rev.1)', 0, 0, 'C');
        } else {
            $this->Cell($content_width, 6, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 0, 'C');
        }

        // Enhanced line styling - full width line
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.5);
        $line_y = $y_pos + 8;
        $this->Line($left_margin, $line_y, $page_width - $right_margin, $line_y);

        // Set proper spacing after header
        $this->SetY($line_y + 5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Rest of your functions remain the same...
function renderEventSummary($pdf, $event, $pic)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'EVENT SUMMARY', 0, 1, 'C');
    $pdf->Ln(8);

    // Prepare data
    $day_of_week = date('l', strtotime($event['Ev_Date']));
    $start_time = date('h:i A', strtotime($event['Ev_StartTime']));
    $end_time = date('h:i A', strtotime($event['Ev_EndTime']));
    $ref_num = $event['Ev_RefNum'] ?? '-';
    $type_code = $event['Ev_TypeRef'] ?? '-';
    $pax = $event['Ev_Pax'] ?? '-';
    $sdg_usr = $event['Ev_TypeRef'] ?? '-';

    $html = '
    <style>
        table.summary {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 12pt;
            line-height: 1.5;
        }
        .summary td {
            border: 1px solid #333;
            padding: 12px 10px;
            vertical-align: top;
        }
        .summary .label {
            background-color: #f8f8f8;
            font-weight: normal;
            width: 35%;
            text-align: left;
        }
        .summary .content {
            text-align: justify;
            width: 65%;
            font-weight: normal;
        }
    </style>
    
    <table class="summary">
        <tr><td class="label">Event ID</td><td class="content">' . htmlspecialchars($event['Ev_ID']) . '</td></tr>
        <tr><td class="label">Reference Number</td><td class="content">' . htmlspecialchars($ref_num) . '</td></tr>
        <tr><td class="label">Date of Submission</td><td class="content">' . date('d F Y') . '</td></tr>
        <tr><td class="label">Club</td><td class="content">' . htmlspecialchars($event['Club_Name']) . '</td></tr>
        <tr><td class="label">Event Name</td><td class="content">' . htmlspecialchars($event['Ev_Name']) . '</td></tr>
        <tr><td class="label">Event Nature</td><td class="content">' . htmlspecialchars($event['Ev_ProjectNature']) . '</td></tr>
        <tr><td class="label">Objectives</td><td class="content">' . nl2br(htmlspecialchars($event['Ev_Objectives'])) . '</td></tr>
        <tr><td class="label">Date</td><td class="content">' . date('d F Y', strtotime($event['Ev_Date'])) . '</td></tr>
        <tr><td class="label">Day</td><td class="content">' . $day_of_week . '</td></tr>
        <tr><td class="label">Time</td><td class="content">' . $start_time . ' to ' . $end_time . '</td></tr>
        <tr><td class="label">Venue</td><td class="content">' . htmlspecialchars($event['Ev_VenueID']) . '</td></tr>
        <tr><td class="label">Estimated Participants</td><td class="content">' . number_format($pax) . ' people</td></tr>
        <tr><td class="label">Person In Charge</td><td class="content">' . htmlspecialchars($pic['PIC_Name']) . '</td></tr>
        <tr><td class="label">Contact Number</td><td class="content">' . htmlspecialchars($pic['PIC_PhnNum']) . '</td></tr>
        <tr><td class="label">ID Number</td><td class="content">' . htmlspecialchars($pic['PIC_ID']) . '</td></tr>
        <tr><td class="label">SDG / USR No</td><td class="content">' . htmlspecialchars($sdg_usr) . '</td></tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Add signature section with names
    $pdf->Ln(15);

    // Get names for signature section
    $student_name = $event['Stu_Name'] ?? 'Student Name';
    $advisor_name = $event['Advisor_Name'] ?? 'Advisor Name';
    $coordinator_name = $event['Coordinator_Name'] ?? 'Coordinator Name';

    $signature_html = '
    <style>
        table.signatures {
            width: 100%;
            font-family: "times";
            font-size: 11pt;
            line-height: 1.5;
        }
        .signatures td {
            padding: 8px;
            vertical-align: top;
            text-align: center;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            height: 30px;
            margin-bottom: 5px;
        }
        .sig-name {
            font-weight: normal;
            margin-bottom: 3px;
        }
        .sig-title {
            font-weight: bold;
        }
    </style>
    
    <table class="signatures">
        <tr>
            <td width="33%">
                <div class="sig-line"></div>
                <div class="sig-name">' . htmlspecialchars($student_name) . '</div>
                <div class="sig-title">Proposed & Submitted by</div>
            </td>
            <td width="33%">
                <div class="sig-line"></div>
                <div class="sig-name">' . htmlspecialchars($advisor_name) . '</div>
                <div class="sig-title">Moderated by Faculty/<br/>Club/Society Advisor</div>
            </td>
            <td width="34%">
                <div class="sig-line"></div>
                <div class="sig-name">' . htmlspecialchars($coordinator_name) . '</div>
                <div class="sig-title">U Course Coordinator</div>
            </td>
        </tr>
    </table>';

    // --- Computer-generated note (non-intrusive, won't affect previous HTML) ---
    $needed = 12; // min space we need (mm-ish)
    $y = $pdf->GetY();
    $page_height = $pdf->getPageHeight();
    $bottom = $pdf->getBreakMargin();
    if (($page_height - $y - $bottom) < $needed) {
        $pdf->AddPage(); // avoid pushing the signature off the page
    }

    $pdf->Ln(4);
    $pdf->SetFont('times', 'I', 10);
    $pdf->SetTextColor(85, 85, 85);
    $pdf->Cell(0, 6, 'This document is computer-generated; no physical signature is required.', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0); // restore for next sections
    $pdf->writeHTML($signature_html, true, false, true, false, '');

}

function renderCoverPage($pdf, $event, $student = null, $club_logo = null)
{
    $pdf->SetTextColor(0, 0, 0);

    // Club logo with better positioning
    if (!empty($club_logo) && file_exists($club_logo)) {
        $pdf->Image($club_logo, 85, 50, 40);
    } else {
        $pdf->SetFont('times', '', 12);
        $pdf->SetXY(85, 55);
        $pdf->Cell(40, 8, '[Club Logo]', 1, 1, 'C');
    }

    $pdf->Ln(55);

    // Title styling - Only headings are bold
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 10, 'CO-CURRICULUM PROJECT PROPOSAL', 0, 1, 'C');
    $pdf->Ln(6);

    // Club name - heading so bold
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 8, strtoupper($event['Club_Name']), 0, 1, 'C');
    $pdf->Ln(6);

    // Event name - heading so bold
    $pdf->SetFont('times', 'B', 14);
    $pdf->MultiCell(0, 6, '"' . $event['Ev_Name'] . '"', 0, 'C');
    $pdf->Ln(12);

    // Student information section - regular text
    $proposerName = $student['Stu_Name'] ?? '-';
    $proposerID = $student['Stu_ID'] ?? '-';
    $program = $student['Stu_Program'] ?? '-';
    $school = $student['Stu_School'] ?? '-';
    $date = date('d F Y', strtotime($event['Ev_Date']));

    $pdf->SetFont('times', '', 12);
    $info_html = '
    <div style="text-align: center; font-family: times; font-size: 12pt; line-height: 1.3;">
        <p><span style="font-weight: bold;">Proposed by:</span> ' . htmlspecialchars($proposerName) . '</p>
        <p><span style="font-weight: bold;">Student ID:</span> ' . htmlspecialchars($proposerID) . '</p>
        <p><span style="font-weight: bold;">Program:</span> ' . htmlspecialchars($program) . '</p>
        <p><span style="font-weight: bold;">School:</span> ' . htmlspecialchars($school) . '</p>
        <p><span style="font-weight: bold;">Event Date:</span> ' . $date . '</p>
        <p><span style="font-weight: bold;">Submission Date:</span> ' . date('d F Y') . '</p>
    </div>';

    $pdf->writeHTML($info_html, true, false, true, false, '');
}

// FIXED: Combined Introduction and Objectives with proper justified text
function renderIntroductionAndObjectives($pdf, $event)
{
    // 1.0 Introduction
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '1.0 INTRODUCTION', 0, 1, 'L');
    $pdf->Ln(2);

    $intro = $event['Ev_Intro'] ?? 'No introduction provided.';

    // Process introduction text to ensure proper formatting
    $intro = trim($intro);
    if (!empty($intro)) {
        // Split into paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $intro);
        $formatted_intro = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                // Remove excessive spaces and line breaks
                $paragraph = preg_replace('/\s+/', ' ', $paragraph);
                $formatted_intro .= '<p style="text-align: justify; text-indent: 20px; margin-bottom: 10px;">' . htmlspecialchars($paragraph) . '</p>';
            }
        }
    } else {
        $formatted_intro = '<p style="text-align: justify; text-indent: 20px;">No introduction provided.</p>';
    }

    $intro_html = '
    <style>
        body { 
            font-family: "times"; 
            font-size: 12pt; 
            line-height: 1.4; 
        }
        p {
            text-align: justify;
            text-indent: 20px;
            margin-bottom: 10px;
            font-weight: normal;
        }
    </style>
    
    ' . $formatted_intro;

    $pdf->writeHTML($intro_html, true, false, true, false, '');

    $pdf->Ln(6);

    // 2.0 Objectives
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 12, '2.0 OBJECTIVES', 0, 1, 'L');
    $pdf->Ln(2);

    $objectives = $event['Ev_Objectives'] ?? 'No objectives specified.';

    // Process objectives text
    $objectives = trim($objectives);
    if (!empty($objectives)) {
        // Check if it's a bulleted list or paragraph format
        if (strpos($objectives, '-') !== false || strpos($objectives, '•') !== false) {
            // Format as bullet points
            $lines = explode("\n", $objectives);
            $formatted_objectives = '';

            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Clean up bullet formatting
                    $line = preg_replace('/^[-•]\s*/', '', $line);
                    $line = preg_replace('/\s+/', ' ', $line);
                    $formatted_objectives .= '<p style="text-align: justify; margin-left: 20px; margin-bottom: 8px;">• ' . htmlspecialchars($line) . '</p>';
                }
            }
        } else {
            // Format as paragraphs
            $paragraphs = preg_split('/\n\s*\n/', $objectives);
            $formatted_objectives = '';

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph)) {
                    $paragraph = preg_replace('/\s+/', ' ', $paragraph);
                    $formatted_objectives .= '<p style="text-align: justify; text-indent: 20px; margin-bottom: 10px;">' . htmlspecialchars($paragraph) . '</p>';
                }
            }
        }
    } else {
        $formatted_objectives = '<p style="text-align: justify; text-indent: 20px;">No objectives specified.</p>';
    }

    $objectives_html = '
    <style>
        body { 
            font-family: "times"; 
            font-size: 12pt; 
            line-height: 1.4; 
        }
        p {
            font-weight: normal;
        }
    </style>
    
    ' . $formatted_objectives;

    $pdf->writeHTML($objectives_html, true, false, true, false, '');
}

// FIXED: Purpose of Event with justified text
function renderPurposeOfEvent($pdf, $event)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '3.0 PURPOSE OF EVENT', 0, 1, 'L');
    $pdf->Ln(2);

    $details = $event['Ev_Details'] ?? 'No details provided.';

    // Process details text to ensure proper formatting
    $details = trim($details);
    if (!empty($details)) {
        $paragraphs = preg_split('/\n\s*\n/', $details);
        $formatted_details = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                $paragraph = preg_replace('/\s+/', ' ', $paragraph);
                $formatted_details .= '<p style="text-align: justify; text-indent: 20px; margin-bottom: 10px;">' . htmlspecialchars($paragraph) . '</p>';
            }
        }
    } else {
        $formatted_details = '<p style="text-align: justify; text-indent: 20px;">No details provided.</p>';
    }

    $html = '
    <style>
        body { 
            font-family: "times"; 
            font-size: 12pt; 
            line-height: 1.5; 
        }
        p {
            text-align: justify;
            text-indent: 20px;
            margin-bottom: 10px;
            font-weight: normal;
        }
    </style>
    
    ' . $formatted_details;

    $pdf->writeHTML($html, true, false, true, false, '');
}

// FIXED: Event Details table - removed spacing issue
function renderEventDetails($pdf, $eventflow_result)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '4.0 EVENT DETAILS', 0, 1, 'L');
    $pdf->Ln(2); // Reduced spacing

    $html = '
    <style>
        .container {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        table.flow {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            table-layout: fixed;
        }
        .flow th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }
        .flow td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
            font-weight: normal;
        }
        .flow .date-col { 
            width: 18%; 
            text-align: center;
        }
        .flow .time-start-col, .flow .time-end-col { 
            width: 15%; 
            text-align: center;
        }
        .flow .activity-col { 
            width: 32%; 
            text-align: left; 
            padding-left: 6px;
        }
        .flow .remarks-col { 
            width: 15%; 
            text-align: left; 
            padding-left: 6px;
        }
        .flow .hours-col { 
            width: 10%; 
            text-align: center;
        }
        .total-row {
            background-color: #e8e8e8;
            font-weight: bold;
        }
    </style>
    
    <div class="container">
        <table class="flow">
            <thead>
                <tr>
                    <th class="date-col">Date</th>
                    <th class="time-start-col">Start Time</th>
                    <th class="time-end-col">End Time</th>
                    <th class="activity-col">Activity</th>
                    <th class="remarks-col">Remarks</th>
                    <th class="hours-col">Hours</th>
                </tr>
            </thead>
            <tbody>';

    $total_hours = 0;
    while ($row = $eventflow_result->fetch_assoc()) {
        $date_formatted = date('d/m/Y', strtotime($row['Date']));
        $start_time = date('h:i A', strtotime($row['Start_Time']));
        $end_time = date('h:i A', strtotime($row['End_Time']));
        $total_hours += $row['Hours'];

        $html .= '
            <tr>
                <td class="date-col">' . $date_formatted . '</td>
                <td class="time-start-col">' . $start_time . '</td>
                <td class="time-end-col">' . $end_time . '</td>
                <td class="activity-col">' . htmlspecialchars($row['Activity']) . '</td>
                <td class="remarks-col">' . htmlspecialchars($row['Remarks']) . '</td>
                <td class="hours-col">' . $row['Hours'] . '</td>
            </tr>';
    }

    $html .= '
            <tr class="total-row">
                <td colspan="5" style="text-align: right; padding-right: 8px;">Total Hours:</td>
                <td class="hours-col">' . $total_hours . '</td>
            </tr>
        </tbody>
    </table>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

// Committee Details (unchanged but optimized spacing)
function renderCommitteeDetails($pdf, $committee_result, &$cocu_pdfs)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '5.0 COMMITTEE DETAILS', 0, 1, 'L');
    $pdf->Ln(2);

    // Store committee data for position table
    $committee_data = [];
    $committee_result->data_seek(0); // Reset pointer

    $html = '
    <style>
        table.committee {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .committee th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
        }
        .committee td {
            border: 1px solid #000;
            padding: 6px 4px;
            vertical-align: top;
            font-weight: normal;
        }
        .committee .id-col { width: 18%; text-align: center; }
        .committee .name-col { width: 25%; }
        .committee .dept-col { width: 20%; }
        .committee .phone-col { width: 18%; text-align: center; }
        .committee .cocu-col { width: 19%; text-align: center; }
    </style>
    
    <table class="committee">
        <thead>
            <tr>
                <th class="id-col">Student ID</th>
                <th class="name-col">Name</th>
                <th class="dept-col">Department</th>
                <th class="phone-col">Phone</th>
                <th class="cocu-col">Claimer</th>
            </tr>
        </thead>
        <tbody>';

    while ($committee = $committee_result->fetch_assoc()) {
        $committee_data[] = $committee; // Store for position table
        $cocu = $committee['Com_COCUClaimers'] == 'yes' ? 'Yes' : 'No';

        $html .= '
            <tr>
                <td class="id-col">' . htmlspecialchars($committee['Com_ID']) . '</td>
                <td class="name-col">' . htmlspecialchars($committee['Com_Name']) . '</td>
                <td class="dept-col">' . htmlspecialchars($committee['Com_Department']) . '</td>
                <td class="phone-col">' . htmlspecialchars($committee['Com_PhnNum']) . '</td>
                <td class="cocu-col">' . $cocu . '</td>
            </tr>';

        // Collect COCU PDFs
        if ($cocu === 'Yes' && !empty($committee['student_statement']) && file_exists($committee['student_statement'])) {
            $cocu_pdfs[] = $committee['student_statement'];
        }
    }

    $html .= '
        </tbody>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Add 5.1 Position and Job Scope section
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 10, '5.1 POSITION AND JOB SCOPE', 0, 1, 'L');
    $pdf->Ln(2);

    $position_html = '
    <style>
        table.position {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
            line-height: 1.4;
        }
        .position th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }
        .position td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            font-weight: normal;
        }
        .position .name-col { width: 25%; }
        .position .position-col { width: 25%; }
        .position .scope-col { width: 50%; text-align: justify; }
    </style>
    
    <table class="position">
        <thead>
            <tr>
                <th class="name-col">Name</th>
                <th class="position-col">Position</th>
                <th class="scope-col">Job Scope</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($committee_data as $committee) {
        $position_html .= '
            <tr>
                <td class="name-col">' . htmlspecialchars($committee['Com_Name']) . '</td>
                <td class="position-col">' . htmlspecialchars($committee['Com_Position']) . '</td>
                <td class="scope-col">' . htmlspecialchars($committee['Com_JobScope']) . '</td>
            </tr>';
    }

    $position_html .= '
        </tbody>
    </table>';

    $pdf->writeHTML($position_html, true, false, true, false, '');
}

// Event section (unchanged)
function renderEvent($pdf, $event)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '6.0 EVENT', 0, 1, 'L');
    $pdf->Ln(2);

    $proposed_date = date('d F Y', strtotime($event['Ev_Date']));
    $alt_date = $event['Ev_AlternativeDate'] ? date('d F Y', strtotime($event['Ev_AlternativeDate'])) : 'Not specified';

    $html = '
    <style>
        table.logistics {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 12pt;
            line-height: 1.5;
        }
        .logistics td {
            border: 1px solid #333;
            padding: 12px 10px;
            vertical-align: top;
        }
        .logistics .label {
            background-color: #f8f8f8;
            font-weight: normal;
            width: 30%;
        }
        .logistics .content {
            width: 70%;
            font-weight: normal;
        }
    </style>
    
    <table class="logistics">
        <tr>
            <td class="label">6.1 Proposed Date</td>
            <td class="content">' . $proposed_date . '</td>
        </tr>
        <tr>
            <td class="label">6.2 Alternative Date</td>
            <td class="content">' . $alt_date . '</td>
        </tr>
        <tr>
            <td class="label">6.3 Proposed Venue</td>
            <td class="content">' . htmlspecialchars($event['Ev_VenueID']) . '</td>
        </tr>
        <tr>
            <td class="label">6.4 Alternative Venue</td>
            <td class="content">' . htmlspecialchars($event['Ev_AltVenueID']) . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

// Event Poster (unchanged)
function renderEventPoster($pdf, $poster_path)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '7.0 EVENT POSTER', 0, 1, 'L');
    $pdf->Ln(10);

    if (!empty($poster_path) && file_exists($poster_path)) {
        // Calculate available space and scale poster accordingly
        $current_y = $pdf->GetY();
        $page_height = $pdf->getPageHeight();
        $margin_bottom = $pdf->getBreakMargin();
        $available_height = $page_height - $current_y - $margin_bottom - 20; // 20 for safety margin

        // Center the poster image on the same page with proper scaling
        $max_width = 160; // Maximum width for poster
        $max_height = min($available_height, 200); // Use available space or max 200

        $pdf->Image($poster_path, 25, $current_y, $max_width, $max_height, '', '', '', true, 300, '', false, false, 1);
    } else {
        $pdf->SetFont('times', '', 12);
        $current_y = $pdf->GetY();
        $pdf->SetXY(25, $current_y);
        $pdf->Cell(160, 20, 'Event Poster Not Available', 1, 1, 'C');
        $pdf->SetXY(25, $current_y + 20);
        $pdf->Cell(160, 10, 'Please attach the poster separately', 0, 1, 'C');
    }
}

// FIXED: Budget section with reduced spacing
function renderBudgetSection($pdf, $budget_result, $summary)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 12, '8.0 BUDGET', 0, 1, 'L');
    $pdf->Ln(3); // Reduced spacing from 8 to 3

    // Separate income and expenditure data
    $income_items = [];
    $expenditure_items = [];
    $total_income = 0;
    $total_expenditure = 0;

    // Reset the result pointer to read data again
    $budget_result->data_seek(0);

    while ($row = $budget_result->fetch_assoc()) {
        if (strtolower($row['Bud_Type']) == 'income') {
            $income_items[] = $row;
            $total_income += $row['Bud_Amount'];
        } else {
            $expenditure_items[] = $row;
            $total_expenditure += $row['Bud_Amount'];
        }
    }

    // Shared table style with reduced margins
    $table_style = '
    <style>
        table.budget-table {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 12pt;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .budget-table th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }
        .budget-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            font-weight: normal;
        }
        .budget-table .desc-col { width: 60%; text-align: left; }
        .budget-table .amount-col { width: 20%; text-align: center; }
        .budget-table .remarks-col { width: 20%; text-align: left; }
        .total-row { 
            background-color: #e0e0e0; 
            font-weight: bold; 
        }
        .empty-row { height: 22px; }
    </style>';

    // 8.1 Income Section
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 8, '8.1 Income', 0, 1, 'L'); // Reduced height from 10 to 8
    $pdf->Ln(1); // Reduced spacing from 3 to 1

    $income_html = $table_style . '
    <table class="budget-table">
        <thead>
            <tr>
                <th class="desc-col">Description</th>
                <th class="amount-col">Amount (RM)</th>
                <th class="remarks-col">Remarks</th>
            </tr>
        </thead>
        <tbody>';

    // Add income items
    $income_rows_filled = 0;
    foreach ($income_items as $item) {
        $income_html .= '
            <tr>
                <td class="desc-col">' . htmlspecialchars($item['Bud_Desc']) . '</td>
                <td class="amount-col">RM ' . number_format($item['Bud_Amount'], 2) . '</td>
                <td class="remarks-col">' . htmlspecialchars($item['Bud_Remarks']) . '</td>
            </tr>';
        $income_rows_filled++;
    }

    // Add empty rows to make 4 total rows for income
    for ($i = $income_rows_filled; $i < 4; $i++) {
        $income_html .= '
            <tr class="empty-row">
                <td class="desc-col">&nbsp;</td>
                <td class="amount-col">&nbsp;</td>
                <td class="remarks-col">&nbsp;</td>
            </tr>';
    }

    // Total Income row
    $income_html .= '
            <tr class="total-row">
                <td class="desc-col">Total Income</td>
                <td class="amount-col">RM ' . number_format($total_income, 2) . '</td>
                <td class="remarks-col">&nbsp;</td>
            </tr>
        </tbody>
    </table>';

    $pdf->writeHTML($income_html, true, false, true, false, '');

    // 8.2 Expenditure Section
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 8, '8.2 Expenditure', 0, 1, 'L'); // Reduced height
    $pdf->Ln(1); // Reduced spacing

    $expenditure_html = $table_style . '
    <table class="budget-table">
        <thead>
            <tr>
                <th class="desc-col">Description</th>
                <th class="amount-col">Amount (RM)</th>
                <th class="remarks-col">Remarks</th>
            </tr>
        </thead>
        <tbody>';

    // Add expenditure items
    $expenditure_rows_filled = 0;
    foreach ($expenditure_items as $item) {
        $expenditure_html .= '
            <tr>
                <td class="desc-col">' . htmlspecialchars($item['Bud_Desc']) . '</td>
                <td class="amount-col">RM ' . number_format($item['Bud_Amount'], 2) . '</td>
                <td class="remarks-col">' . htmlspecialchars($item['Bud_Remarks']) . '</td>
            </tr>';
        $expenditure_rows_filled++;
    }

    // Add empty rows to make 6 total rows for expenditure
    for ($i = $expenditure_rows_filled; $i < 6; $i++) {
        $expenditure_html .= '
            <tr class="empty-row">
                <td class="desc-col">&nbsp;</td>
                <td class="amount-col">&nbsp;</td>
                <td class="remarks-col">&nbsp;</td>
            </tr>';
    }

    // Total Expenditure row
    $expenditure_html .= '
            <tr class="total-row">
                <td class="desc-col">Total Expenditure</td>
                <td class="amount-col">RM ' . number_format($total_expenditure, 2) . '</td>
                <td class="remarks-col">&nbsp;</td>
            </tr>
        </tbody>
    </table>';

    $pdf->writeHTML($expenditure_html, true, false, true, false, '');

    // Add separate summary table with reduced spacing
    $pdf->Ln(5); // Reduced from 10 to 5
    $surplus_deficit = $total_income - $total_expenditure;

    $summary_html = '
    <style>
        table.summary-table {
            border-collapse: collapse;
            width: 70%;
            font-family: "times";
            font-size: 12pt;
            line-height: 1.4;
            margin: 0 auto;
        }
        .summary-table th {
            background-color: #d3d3d3;
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-weight: bold;
        }
        .summary-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            font-weight: normal;
        }
        .summary-table .label-col { width: 60%; text-align: left; }
        .summary-table .amount-col { width: 40%; text-align: center; }
    </style>
    
    <table class="summary-table">
        <thead>
            <tr>
                <th colspan="2" style="text-align: center;">Budget Summary</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-col">Total Income</td>
                <td class="amount-col">RM ' . number_format($total_income, 2) . '</td>
            </tr>
            <tr>
                <td class="label-col">Total Expenditure</td>
                <td class="amount-col">RM ' . number_format($total_expenditure, 2) . '</td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td class="label-col"><strong>Surplus/Deficit</strong></td>
                <td class="amount-col"><strong>RM ' . number_format($surplus_deficit, 2) . '</strong></td>
            </tr>
        </tbody>
    </table>';

    $pdf->writeHTML($summary_html, true, false, true, false, '');

    // Add "Prepared by" section with reduced spacing
    $pdf->Ln(5); // Reduced from 10 to 5
    $preparedBy = $summary['Prepared_By'] ?? 'Not specified';

    $pdf->SetFont('times', '', 12);
    $preparedBy_html = '
    <div style="font-family: times; font-size: 12pt; line-height: 1.5;">
        <p><strong>Prepared by:</strong> ' . htmlspecialchars($preparedBy) . '</p>
    </div>';

    $pdf->writeHTML($preparedBy_html, true, false, true, false, '');

    // Reset budget page flag
    $pdf->setBudgetPage(false);
}

?>