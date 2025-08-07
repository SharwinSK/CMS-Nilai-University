<?php
require_once('../../TCPDF-main/tcpdf.php');

// ✅ ENHANCED HEADER WITH BETTER STYLING
class MYPDF extends TCPDF
{
    public function Header()
    {
        // Logo
        $this->Image('NU logo2.jpeg', 15, 10, 20);

        // Header text with Times New Roman
        $this->SetFont('times', 'B', 16);
        $this->SetXY(0, 12);
        $this->Cell(0, 8, 'PROPOSAL FOR PROJECT/ACTIVITY', 0, 1, 'C');

        $this->SetFont('times', '', 10);
        $this->SetXY(0, 22);
        $this->Cell(0, 6, 'NU/SOP/SHSS/001/F01 (rev. 1)', 0, 1, 'C');

        // Enhanced line styling
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.5);
        $this->Line(15, 32, 195, 32);

        // Add some spacing after header
        $this->Ln(10);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

function renderEventSummary($pdf, $event, $pic)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'EVENT SUMMARY', 0, 1, 'C');
    $pdf->Ln(5);

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
        }
        .summary td {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }
        .summary .label {
            background-color: #f8f8f8;
            font-weight: bold;
            width: 35%;
        }
        .summary .content {
            text-align: justify;
            width: 65%;
        }
    </style>
    
    <table class="summary">
        <tr><td class="label">Event ID</td><td class="content">' . htmlspecialchars($event['Ev_ID']) . '</td></tr>
        <tr><td class="label">Reference Number</td><td class="content">' . htmlspecialchars($ref_num) . '</td></tr>
        <tr><td class="label">Event Type</td><td class="content">' . htmlspecialchars($type_code) . '</td></tr>
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
}

function renderCoverPage($pdf, $event, $student = null, $club_logo = null)
{
    $pdf->SetTextColor(0, 0, 0);

    // Club logo with better positioning
    if (!empty($club_logo) && file_exists($club_logo)) {
        $pdf->Image($club_logo, 85, 40, 40);
    } else {
        $pdf->SetFont('times', 'I', 10);
        $pdf->SetXY(85, 50);
        $pdf->Cell(40, 8, '[Club Logo]', 1, 1, 'C');
    }

    $pdf->Ln(60);

    // Title styling
    $pdf->SetFont('times', 'B', 18);
    $pdf->Cell(0, 12, 'CO-CURRICULUM PROJECT PROPOSAL', 0, 1, 'C');
    $pdf->Ln(8);

    // Club name
    $pdf->SetFont('times', 'B', 16);
    $pdf->Cell(0, 10, strtoupper($event['Club_Name']), 0, 1, 'C');
    $pdf->Ln(8);

    // Event name with better formatting
    $pdf->SetFont('times', 'B', 14);
    $pdf->MultiCell(0, 8, '"' . $event['Ev_Name'] . '"', 0, 'C');
    $pdf->Ln(15);

    // Student information section
    $proposerName = $student['Stu_Name'] ?? '-';
    $proposerID = $student['Stu_ID'] ?? '-';
    $program = $student['Stu_Program'] ?? '-';
    $school = $student['Stu_School'] ?? '-';
    $date = date('d F Y', strtotime($event['Ev_Date']));

    $pdf->SetFont('times', '', 12);
    $info_html = '
    <div style="text-align: center; font-family: times; font-size: 12pt; line-height: 1.6;">
        <p><strong>Proposed by:</strong> ' . htmlspecialchars($proposerName) . '</p>
        <p><strong>Student ID:</strong> ' . htmlspecialchars($proposerID) . '</p>
        <p><strong>Program:</strong> ' . htmlspecialchars($program) . '</p>
        <p><strong>School:</strong> ' . htmlspecialchars($school) . '</p>
        <p><strong>Event Date:</strong> ' . $date . '</p>
        <p><strong>Submission Date:</strong> ' . date('d F Y') . '</p>
    </div>';

    $pdf->writeHTML($info_html, true, false, true, false, '');
}

function renderEventOverview($pdf, $event)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '1.0 EVENT OVERVIEW', 0, 1, 'C');
    $pdf->Ln(5);

    $intro = $event['Ev_Intro'] ?? 'No introduction provided.';
    $objectives = $event['Ev_Objectives'] ?? 'No objectives specified.';
    $details = $event['Ev_Details'] ?? 'No details provided.';

    $html = '
    <style>
        body { font-family: "times"; font-size: 12pt; line-height: 1.6; }
        .section-title { font-size: 12pt; font-weight: bold; margin-bottom: 8px; margin-top: 12px; }
        .content { text-align: justify; margin-bottom: 15px; text-indent: 20px; }
    </style>
    
    <div class="section-title">1.1 Introduction</div>
    <div class="content">' . nl2br(htmlspecialchars($intro)) . '</div>
    
    <div class="section-title">1.2 Objectives</div>
    <div class="content">' . nl2br(htmlspecialchars($objectives)) . '</div>
    
    <div class="section-title">1.3 Purpose and Details</div>
    <div class="content">' . nl2br(htmlspecialchars($details)) . '</div>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderEventFlow($pdf, $eventflow_result)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '2.0 EVENT SCHEDULE AND FLOW', 0, 1, 'C');
    $pdf->Ln(5);

    $html = '
    <style>
        table.flow {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
        }
        .flow th {
            background-color: #e8e8e8;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .flow td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top;
        }
        .flow .date-col { width: 12%; text-align: center; }
        .flow .time-col { width: 12%; text-align: center; }
        .flow .activity-col { width: 35%; text-align: justify; }
        .flow .remarks-col { width: 20%; text-align: justify; }
        .flow .hours-col { width: 9%; text-align: center; }
    </style>
    
    <table class="flow">
        <thead>
            <tr>
                <th class="date-col">Date</th>
                <th class="time-col">Start Time</th>
                <th class="time-col">End Time</th>
                <th class="activity-col">Activity</th>
                <th class="remarks-col">Remarks</th>
                <th class="hours-col">Hours</th>
            </tr>
        </thead>
        <tbody>';

    $total_hours = 0;
    while ($row = $eventflow_result->fetch_assoc()) {
        $date_formatted = date('d/m/Y', strtotime($row['Date']));
        $start_time = date('H:i', strtotime($row['Start_Time']));
        $end_time = date('H:i', strtotime($row['End_Time']));
        $total_hours += $row['Hours'];

        $html .= '
            <tr>
                <td class="date-col">' . $date_formatted . '</td>
                <td class="time-col">' . $start_time . '</td>
                <td class="time-col">' . $end_time . '</td>
                <td class="activity-col">' . htmlspecialchars($row['Activity']) . '</td>
                <td class="remarks-col">' . htmlspecialchars($row['Remarks']) . '</td>
                <td class="hours-col">' . $row['Hours'] . '</td>
            </tr>';
    }

    $html .= '
            <tr style="background-color: #f0f0f0;">
                <td colspan="5" style="text-align: right; font-weight: bold;">Total Hours:</td>
                <td class="hours-col" style="font-weight: bold;">' . $total_hours . '</td>
            </tr>
        </tbody>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderCommitteeDetails($pdf, $committee_result, &$cocu_pdfs)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '3.0 ORGANIZING COMMITTEE', 0, 1, 'C');
    $pdf->Ln(5);

    $html = '
    <style>
        table.committee {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 10pt;
        }
        .committee th {
            background-color: #e8e8e8;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .committee td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top;
        }
        .committee .id-col { width: 12%; text-align: center; }
        .committee .name-col { width: 18%; }
        .committee .position-col { width: 15%; }
        .committee .dept-col { width: 12%; }
        .committee .phone-col { width: 12%; text-align: center; }
        .committee .scope-col { width: 23%; text-align: justify; }
        .committee .cocu-col { width: 8%; text-align: center; }
    </style>
    
    <table class="committee">
        <thead>
            <tr>
                <th class="id-col">Student ID</th>
                <th class="name-col">Name</th>
                <th class="position-col">Position</th>
                <th class="dept-col">Department</th>
                <th class="phone-col">Phone</th>
                <th class="scope-col">Job Scope</th>
                <th class="cocu-col">COCU</th>
            </tr>
        </thead>
        <tbody>';

    while ($committee = $committee_result->fetch_assoc()) {
        $cocu = $committee['Com_COCUClaimers'] == 'yes' ? 'Yes' : 'No';

        $html .= '
            <tr>
                <td class="id-col">' . htmlspecialchars($committee['Com_ID']) . '</td>
                <td class="name-col">' . htmlspecialchars($committee['Com_Name']) . '</td>
                <td class="position-col">' . htmlspecialchars($committee['Com_Position']) . '</td>
                <td class="dept-col">' . htmlspecialchars($committee['Com_Department']) . '</td>
                <td class="phone-col">' . htmlspecialchars($committee['Com_PhnNum']) . '</td>
                <td class="scope-col">' . htmlspecialchars($committee['Com_JobScope']) . '</td>
                <td class="cocu-col">' . $cocu . '</td>
            </tr>';

        // Collect COCU PDFs
        if ($cocu === 'Yes' && !empty($committee['student_statement']) && file_exists($committee['student_statement'])) {
            $cocu_pdfs[] = $committee['student_statement'];
        }
    }

    $html .= '
        </tbody>
    </table>
    
    <div style="margin-top: 15px; font-size: 10pt; font-style: italic;">
        <strong>Note:</strong> COCU refers to Co-Curriculum points claimers. Supporting documents are attached as appendices.
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderLogistics($pdf, $event)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '4.0 EVENT LOGISTICS', 0, 1, 'C');
    $pdf->Ln(5);

    $proposed_date = date('d F Y', strtotime($event['Ev_Date']));
    $alt_date = $event['Ev_AlternativeDate'] ? date('d F Y', strtotime($event['Ev_AlternativeDate'])) : 'Not specified';

    $html = '
    <style>
        table.logistics {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 12pt;
        }
        .logistics td {
            border: 1px solid #333;
            padding: 10px;
            vertical-align: top;
        }
        .logistics .label {
            background-color: #f8f8f8;
            font-weight: bold;
            width: 30%;
        }
        .logistics .content {
            width: 70%;
        }
    </style>
    
    <table class="logistics">
        <tr>
            <td class="label">4.1 Proposed Date</td>
            <td class="content">' . $proposed_date . '</td>
        </tr>
        <tr>
            <td class="label">4.2 Alternative Date</td>
            <td class="content">' . $alt_date . '</td>
        </tr>
        <tr>
            <td class="label">4.3 Proposed Venue</td>
            <td class="content">' . htmlspecialchars($event['Ev_VenueID']) . '</td>
        </tr>
        <tr>
            <td class="label">4.4 Alternative Venue</td>
            <td class="content">' . htmlspecialchars($event['Ev_AltVenueID']) . '</td>
        </tr>
        <tr>
            <td class="label">4.5 Expected Participants</td>
            <td class="content">' . number_format($event['Ev_Pax']) . ' people</td>
        </tr>
        <tr>
            <td class="label">4.6 Duration</td>
            <td class="content">' . date('H:i', strtotime($event['Ev_StartTime'])) . ' - ' . date('H:i', strtotime($event['Ev_EndTime'])) . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderBudgetSection($pdf, $budget_result, $summary)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '5.0 BUDGET BREAKDOWN', 0, 1, 'C');
    $pdf->Ln(5);

    $html = '
    <style>
        table.budget {
            border-collapse: collapse;
            width: 100%;
            font-family: "times";
            font-size: 11pt;
        }
        .budget th {
            background-color: #e8e8e8;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .budget td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top;
        }
        .budget .desc-col { width: 40%; text-align: justify; }
        .budget .amount-col { width: 15%; text-align: right; }
        .budget .type-col { width: 15%; text-align: center; }
        .budget .remarks-col { width: 30%; text-align: justify; }
        .income-row { background-color: #e8f5e8; }
        .expense-row { background-color: #ffe8e8; }
    </style>
    
    <table class="budget">
        <thead>
            <tr>
                <th class="desc-col">Description</th>
                <th class="amount-col">Amount (RM)</th>
                <th class="type-col">Type</th>
                <th class="remarks-col">Remarks</th>
            </tr>
        </thead>
        <tbody>';

    while ($row = $budget_result->fetch_assoc()) {
        $row_class = ($row['Bud_Type'] == 'Income') ? 'income-row' : 'expense-row';
        $html .= '
            <tr class="' . $row_class . '">
                <td class="desc-col">' . htmlspecialchars($row['Bud_Desc']) . '</td>
                <td class="amount-col">' . number_format($row['Bud_Amount'], 2) . '</td>
                <td class="type-col">' . htmlspecialchars($row['Bud_Type']) . '</td>
                <td class="remarks-col">' . htmlspecialchars($row['Bud_Remarks']) . '</td>
            </tr>';
    }

    $html .= '
        </tbody>
    </table>';

    // Summary section
    $totalIncome = $summary['Total_Income'] ?? 0.00;
    $totalExpense = $summary['Total_Expense'] ?? 0.00;
    $surplus = $summary['Surplus_Deficit'] ?? 0.00;
    $preparedBy = $summary['Prepared_By'] ?? 'Not specified';

    $surplus_color = $surplus >= 0 ? '#006600' : '#cc0000';
    $surplus_label = $surplus >= 0 ? 'Surplus' : 'Deficit';

    $html .= '
    <div style="margin-top: 20px;">
        <table style="width: 60%; border-collapse: collapse; font-family: times; font-size: 12pt;">
            <tr>
                <td style="border: 1px solid #333; padding: 8px; background-color: #f8f8f8; font-weight: bold;">Total Income:</td>
                <td style="border: 1px solid #333; padding: 8px; text-align: right; color: #006600;">RM ' . number_format($totalIncome, 2) . '</td>
            </tr>
            <tr>
                <td style="border: 1px solid #333; padding: 8px; background-color: #f8f8f8; font-weight: bold;">Total Expenses:</td>
                <td style="border: 1px solid #333; padding: 8px; text-align: right; color: #cc0000;">RM ' . number_format($totalExpense, 2) . '</td>
            </tr>
            <tr>
                <td style="border: 1px solid #333; padding: 8px; background-color: #f0f0f0; font-weight: bold;">' . $surplus_label . ':</td>
                <td style="border: 1px solid #333; padding: 8px; text-align: right; font-weight: bold; color: ' . $surplus_color . ';">RM ' . number_format(abs($surplus), 2) . '</td>
            </tr>
            <tr>
                <td style="border: 1px solid #333; padding: 8px; background-color: #f8f8f8; font-weight: bold;">Prepared By:</td>
                <td style="border: 1px solid #333; padding: 8px;">' . htmlspecialchars($preparedBy) . '</td>
            </tr>
        </table>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

function renderEventPoster($pdf, $poster_path)
{
    $pdf->SetFont('times', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, '6.0 EVENT POSTER', 0, 1, 'C');
    $pdf->Ln(10);

    if (!empty($poster_path) && file_exists($poster_path)) {
        // Center the poster image
        $pdf->Image($poster_path, 25, 60, 160, 0, '', '', '', true, 300);
    } else {
        $pdf->SetFont('times', 'I', 12);
        $pdf->SetXY(0, 80);
        $pdf->Cell(0, 20, 'Event Poster Not Available', 1, 1, 'C');
        $pdf->SetXY(0, 100);
        $pdf->Cell(0, 10, 'Please attach the poster separately', 0, 1, 'C');
    }
}