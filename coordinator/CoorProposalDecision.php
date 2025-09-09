<?php
session_start();
include('../db/dbconfig.php');
require_once '../model/sendMailTemplates.php';


if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$ev_id = $_GET['id'] ?? '';
if (empty($ev_id)) {
    die("Invalid event ID.");
}

// 1. Get proposal details (join student, club, budgetsummary, venue)
$stmt = $conn->prepare("
    SELECT 
        e.*, 
        s.Stu_Name, 
        s.Stu_Email,
        a.Adv_Email,
        c.Club_Name, 
        bs.Total_Income, 
        bs.Total_Expense, 
        bs.Surplus_Deficit, 
        bs.Prepared_By,
        v1.Venue_Name AS MainVenue,
        v2.Venue_Name AS AltVenue
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN advisor a ON e.Club_ID = a.Club_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN budgetsummary bs ON e.Ev_ID = bs.Ev_ID
    LEFT JOIN venue v1 ON e.Ev_VenueID = v1.Venue_ID
    LEFT JOIN venue v2 ON e.Ev_AltVenueID = v2.Venue_ID
    WHERE e.Ev_ID = ?
");

$stmt->bind_param("s", $ev_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();

if (!$details) {
    die("Proposal not found.");
}

// 2. Person In Charge
$pic = [];
$stmt = $conn->prepare("SELECT * FROM personincharge WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$pic = $stmt->get_result()->fetch_assoc();

// 3. Event Flow / Minutes
$flows = [];
$stmt = $conn->prepare("SELECT * FROM eventminutes WHERE Ev_ID = ? ORDER BY Date, Start_Time");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$flows = $stmt->get_result();

// 4. Committee Members
$committees = [];
$stmt = $conn->prepare("SELECT * FROM committee WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$committees = $stmt->get_result();

// 5. Budget Breakdown
$budget = [];
$stmt = $conn->prepare("SELECT * FROM budget WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$budget = $stmt->get_result();

// Handle Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'approve') {
        $event_type = $_POST['event_type'] ?? '';
        if (empty($event_type)) {
            die("Event type is required.");
        }

        // Step 1: Get current year
        $year = date('Y');
        $year_suffix = substr($year, -2); // '25' for 2025

        // Step 2: Get next number from eventtyperef
        $stmt = $conn->prepare("SELECT Last_Number FROM eventtyperef WHERE Type_Code = ? AND Year = ?");
        $stmt->bind_param("ss", $event_type, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $next_type_number = ($row['Last_Number'] ?? 0) + 1;

        // Step 3: Insert or update eventtyperef
        if ($row) {
            $stmt = $conn->prepare("UPDATE eventtyperef SET Last_Number = ? WHERE Type_Code = ? AND Year = ?");
            $stmt->bind_param("iss", $next_type_number, $event_type, $year);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO eventtyperef (Type_Code, Year, Last_Number) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $event_type, $year, $next_type_number);
            $stmt->execute();
        }

        // Step 4: Generate Ev_RefNum
        $query = "SELECT Ev_RefNum FROM events 
                  WHERE Ev_RefNum LIKE '%/$year_suffix' 
                  ORDER BY Ev_RefNum DESC LIMIT 1";
        $result = $conn->query($query);
        $row = $result->fetch_assoc();

        if ($row && preg_match('/^(\d{2})\/\d{2}$/', $row['Ev_RefNum'], $matches)) {
            $last_num = (int) $matches[1];
            $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $new_num = '01';
        }

        $Ev_RefNum = $new_num . '/' . $year_suffix;
        $Ev_TypeRef = $event_type . ' ' . str_pad($next_type_number, 2, '0', STR_PAD_LEFT) . '/' . $year_suffix;

        // Step 5: Update events
        $stmt = $conn->prepare("UPDATE events 
            SET Ev_TypeCode = ?, Ev_TypeRef = ?, Ev_RefNum = ?, Status_ID = 5 
            WHERE Ev_ID = ?");
        $stmt->bind_param("ssss", $event_type, $Ev_TypeRef, $Ev_RefNum, $ev_id);
        $stmt->execute();
        // 💌 Send approval email to student + advisor
        // ✅ Send proposal approval email
        $eventName = $details['Ev_Name'];
        $studentEmail = $details['Stu_Email'];
        $advisorEmail = $details['Adv_Email'];

        coordinatorApproved($eventName, $studentEmail, $advisorEmail);

        // Optional: redirect or return JSON
        echo json_encode(['status' => 'success']);
        exit();
    }

    // Handle Rejection
    if ($action === 'reject') {
        $rejected_sections = $_POST['rejected_sections'] ?? '';
        $comments = trim($_POST['comments'] ?? '');

        if (empty($rejected_sections) && empty($comments)) {
            die("Rejection must have at least one reason.");
        }

        // Insert into eventcomment table
        $stmt = $conn->prepare("INSERT INTO eventcomment (Ev_ID, Status_ID, Reviewer_Comment, Updated_By)
            VALUES (?, 4, ?, 'Coordinator')");
        $stmt->bind_param("ss", $ev_id, $comments);
        $stmt->execute();

        // Update event status
        $stmt = $conn->prepare("UPDATE events SET Status_ID = 4 WHERE Ev_ID = ?");
        $stmt->bind_param("s", $ev_id);
        $stmt->execute();

        // 💌 Send rejection email to student + advisor

        $eventName = $details['Ev_Name'];
        $studentEmail = $details['Stu_Email'];
        $advisorEmail = $details['Adv_Email'];

        coordinatorRejected($eventName, $studentEmail, $advisorEmail);


        echo json_encode(['status' => 'rejected']);
        exit();
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Review Proposal</title>
    <link href="../assets/css/coordinator/coorproposal.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <button class="back-btn" onclick="goBack()">🏠</button>
            <h1>Event Proposal Review</h1>
            <button class="export-btn" onclick="exportPDF()">📄 Export PDF</button>
        </div>

        <div class="content">
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <button class="bulk-btn bulk-approve" onclick="approveAll()">
                    ✅ Approve All
                </button>
                <button class="bulk-btn bulk-reject" onclick="rejectAll()">
                    ❌ Reject All
                </button>
                <button class="bulk-btn bulk-clear" onclick="clearAll()">
                    🔄 Clear All
                </button>
            </div>

            <!-- Poster Section -->
            <div class="poster-section">
                <img src="<?= str_replace('../../', '../', $details['Ev_Poster']) ?>" alt="Event Poster" class="poster"
                    onclick="openPosterModal()" />
                <p><small>Click poster to view full size</small></p>
            </div>

            <!-- Event Details Section -->
            <div class="section">
                <div class="checkbox-container">
                    <div class="section-title">📋 Event Details Verification</div>
                    <div class="section-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox approve-checkbox"
                                data-section="Event Details" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label approve-label">✓ Approve</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox reject-checkbox" data-section="Event Details"
                                onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label reject-label">✗ Reject</label>
                        </div>
                    </div>
                </div>
                <h2>📋 Event Details</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Event ID</div>
                        <div class="detail-value"><?= $details['Ev_ID'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Student Name</div>
                        <div class="detail-value"><?= $details['Stu_Name'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Student ID</div>
                        <div class="detail-value"><?= $details['Stu_ID'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Position</div>
                        <div class="detail-value"><?= $details['Proposal_Position'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Club Name</div>
                        <div class="detail-value"><?= $details['Club_Name'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Name</div>
                        <div class="detail-value"><?= $details['Ev_Name'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Nature</div>
                        <div class="detail-value"><?= $details['Ev_ProjectNature'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Category</div>
                        <div class="detail-value"><?= $details['Ev_Category'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Objectives</div>
                        <div class="detail-value">
                            <?= nl2br($details['Ev_Objectives']) ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Introduction</div>
                        <div class="detail-value">
                            <?= nl2br($details['Ev_Intro']) ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Purpose of Event</div>
                        <div class="detail-value">
                            <?= nl2br($details['Ev_Details']) ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Participants</div>
                        <div class="detail-value">
                            <?= $details['Ev_Pax'] ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Venue</div>
                        <div class="detail-value"><?= $details['MainVenue'] ?? 'Not specified' ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Event Date</div>
                        <div class="detail-value"><?= date("F d, Y", strtotime($details['Ev_Date'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Start Time</div>
                        <div class="detail-value"><?= date("g:i A", strtotime($details['Ev_StartTime'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">End Time</div>
                        <div class="detail-value"><?= date("g:i A", strtotime($details['Ev_EndTime'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Person in Charge Section -->
            <div class="section">
                <div class="checkbox-container">
                    <div class="section-title">👤 Person in Charge Verification</div>
                    <div class="section-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox approve-checkbox"
                                data-section="Person in Charge" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label approve-label">✓ Approve</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox reject-checkbox"
                                data-section="Person in Charge" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label reject-label">✗ Reject</label>
                        </div>
                    </div>
                </div>
                <h2>👤 Person in Charge</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value"><?= $pic['PIC_Name'] ?? '-' ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">ID</div>
                        <div class="detail-value"><?= $pic['PIC_ID'] ?? '-' ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value"><?= $pic['PIC_PhnNum'] ?? '-' ?></div>
                    </div>
                </div>
            </div>

            <!-- Event Flow/Minutes Section -->
            <div class="section">
                <div class="checkbox-container">
                    <div class="section-title">📝 Event Flow/Minutes Verification</div>
                    <div class="section-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox approve-checkbox" data-section="Event Flow"
                                onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label approve-label">✓ Approve</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox reject-checkbox" data-section="Event Flow"
                                onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label reject-label">✗ Reject</label>
                        </div>
                    </div>
                </div>
                <h2>📝 Event Flow/Minutes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Hours</th>
                            <th>Activity</th>
                            <th>Remarks/Meeting Minutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $flows->fetch_assoc()): ?>
                            <tr>
                                <td><?= date("F d, Y", strtotime($row['Date'])) ?></td>
                                <td><?= date("g:i A", strtotime($row['Start_Time'])) ?></td>
                                <td><?= date("g:i A", strtotime($row['End_Time'])) ?></td>
                                <td><?= $row['Hours'] ?></td>
                                <td><?= htmlspecialchars($row['Activity']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['Remarks'])) ?></td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

            <!-- Committee Members Section -->
            <div class="section">
                <div class="checkbox-container">
                    <div class="section-title">👥 Committee Members Verification</div>
                    <div class="section-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox approve-checkbox"
                                data-section="Committee Members" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label approve-label">✓ Approve</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox reject-checkbox"
                                data-section="Committee Members" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label reject-label">✗ Reject</label>
                        </div>
                    </div>
                </div>
                <h2>👥 Committee Members</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Job Scope</th>
                            <th>Cocu Claimers</th>
                            <th>Registered</th>
                            <th>Cocu Statement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $committees->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Com_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Com_Position']) ?></td>
                                <td><?= htmlspecialchars($row['Com_Email']) ?></td>
                                <td><?= htmlspecialchars($row['Com_Department']) ?></td>
                                <td><?= htmlspecialchars($row['Com_PhnNum']) ?></td>
                                <td><?= htmlspecialchars($row['Com_JobScope']) ?></td>
                                <td><?= $row['Com_COCUClaimers'] === 'yes' ? 'Yes' : 'No' ?></td>
                                <td>
                                    <span
                                        class="registration-status <?= $row['Com_Register'] === 'Yes' ? 'registered' : 'not-registered' ?>">
                                        <?= $row['Com_Register'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $path = $row['student_statement'] ?? '';
                                    $fixedPath = str_replace('../../', '../', $path);
                                    ?>
                                    <?php if (!empty($row['student_statement'])): ?>
                                        <a href="<?= $fixedPath ?>" class="view-btn" target="_blank">View</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

            <!-- Budget Section -->
            <div class="section">
                <div class="checkbox-container">
                    <div class="section-title">💰 Budget Verification</div>
                    <div class="section-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox approve-checkbox" data-section="Budget"
                                onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label approve-label">✓ Approve</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox reject-checkbox" data-section="Budget"
                                onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label reject-label">✗ Reject</label>
                        </div>
                    </div>
                </div>
                <h2>💰 Budget</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount (RM)</th>
                            <th>Income/Expenses</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $budget->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Bud_Desc']) ?></td>
                                <td>RM <?= number_format($row['Bud_Amount'], 2) ?></td>
                                <td><?= $row['Bud_Type'] ?></td>
                                <td><?= htmlspecialchars($row['Bud_Remarks']) ?></td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>

                <div class="budget-summary">
                    <div class="budget-row">
                        <span>Total Income:</span>
                        <span><?= number_format($details['Total_Income'], 2) ?></span>
                    </div>
                    <div class="budget-row">
                        <span>Total Expenses:</span>
                        <span><?= number_format($details['Total_Expense'], 2) ?></span>
                    </div>
                    <div class="budget-row total">
                        <span>Surplus/Deficit:</span>
                        <span><?= number_format($details['Surplus_Deficit'], 2) ?></span>
                    </div>
                    <div class="budget-row">
                        <span>Prepared by:</span>
                        <span><?= $details['Prepared_By'] ?? '-' ?></span>
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="section">
                <div class="checkbox-container">
                    <div class="section-title">
                        📌 Additional Information Verification
                    </div>
                    <div class="section-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox approve-checkbox"
                                data-section="Additional Information" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label approve-label">✓ Approve</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" class="section-checkbox reject-checkbox"
                                data-section="Additional Information" onchange="updateCheckboxes(this)" />
                            <label class="checkbox-label reject-label">✗ Reject</label>
                        </div>
                    </div>
                </div>
                <h2>📌 Additional Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Alternative Venue</div>
                        <div class="detail-value">
                            <?= $details['AltVenue'] ?? 'Not specified' ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Alternative Date</div>
                        <div class="detail-value">
                            <?= $details['Ev_AlternativeDate'] ? date("F d, Y", strtotime($details['Ev_AlternativeDate'])) : '-' ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Additional Document</div>
                        <?php
                        $addPath = $details['Ev_AdditionalInfo'] ?? '';
                        $fixedAddPath = str_replace('../../', '../', $addPath);
                        ?>
                        <div class="detail-value">
                            <a href="<?= $fixedAddPath ?>" style="color: var(--primary-color)" target="_blank">📄 View
                                File</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Section -->
            <div class="action-section">
                <h2 style="color: var(--primary-color); text-align: center">
                    📋 Review Action
                </h2>

                <!-- Show rejected sections summary if any -->
                <div id="rejectedItemsSummary" class="reject-summary" style="display: none">
                    <h4>⚠️ Sections Requiring Attention:</h4>
                    <ul id="rejectedItemsList"></ul>
                </div>

                <label for="eventType" style="font-weight: bold; color: var(--text-dark)">Select Event Type (Required
                    for Approval Only):</label>
                <select id="eventType" class="dropdown">
                    <option value="">Please select event type</option>
                    <option value="USR">USR (University Social Responsibility)</option>
                    <option value="SDG">SDG (Sustainable Development Goals)</option>
                    <option value="CSR">CSR (Corporate Social Responsibility)</option>
                </select>

                <div class="button-group">
                    <button id="approveBtn" class="approve-btn" onclick="approveEvent()" disabled>
                        ✅ Approve
                    </button>
                    <button id="rejectBtn" class="reject-btn" onclick="rejectEvent()" disabled>
                        ❌ Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Poster Modal -->
    <div id="posterModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePosterModal()">&times;</span>
            <img src="https://via.placeholder.com/600x800/0abab5/ffffff?text=Event+Poster+Full+Size"
                alt="Event Poster Full Size" />
        </div>
    </div>

    <!-- Reject Feedback Modal -->
    <div id="rejectModal" class="modal">
        <div class="reject-modal-content">
            <span class="close" onclick="closeRejectModal()">&times;</span>
            <h3 style="color: var(--primary-color); text-align: center">
                Rejection Feedback
            </h3>

            <div class="reject-summary">
                <h4>Rejected Sections:</h4>
                <div id="rejectedItemsDisplay"></div>
            </div>

            <p><strong>Additional Feedback (Optional):</strong></p>
            <textarea id="feedbackText" class="feedback-textarea"
                placeholder="Enter any additional feedback here..."></textarea>
            <button class="submit-feedback" onclick="submitFeedback()">
                Submit Rejection
            </button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSuccessModal()">&times;</span>
            <div class="success-message">
                <h3>✅ Event Approved Successfully!</h3>
                <p>
                    Thank you for approving the event. The organizers have been
                    notified.
                </p>
            </div>
        </div>
    </div>
    <!-- Loading Screen for Approval -->
    <div id="approvalLoadingModal" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-message">Processing Approval...</div>
            <div class="loading-submessage">Please wait while we send notifications</div>
        </div>
    </div>

    <!-- Success Screen for Approval -->
    <div id="approvalSuccessModal" class="loading-overlay">
        <div class="loading-content">
            <div class="success-icon">✅</div>
            <div class="loading-message">Event Approved Successfully!</div>
            <div class="loading-submessage">Redirecting to dashboard...</div>
        </div>
    </div>

    <!-- Loading Screen for Rejection -->
    <div id="rejectionLoadingModal" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-message">Processing Rejection...</div>
            <div class="loading-submessage">Please wait while we send notifications</div>
        </div>
    </div>

    <!-- Success Screen for Rejection -->
    <div id="rejectionSuccessModal" class="loading-overlay">
        <div class="loading-content">
            <div class="reject-icon">📋</div>
            <div class="loading-message">Rejection Submitted Successfully!</div>
            <div class="loading-submessage">Redirecting to dashboard...</div>
        </div>
    </div>
    <script>
        // Track rejected sections
        let rejectedSections = [];

        // Poster Modal Functions
        function openPosterModal() {
            document.getElementById("posterModal").style.display = "flex";
        }

        function closePosterModal() {
            document.getElementById("posterModal").style.display = "none";
        }

        // Fixed updateCheckboxes - only affects current section, not all sections
        function updateCheckboxes(currentCheckbox) {
            const section = currentCheckbox.dataset.section;
            const isApprove =
                currentCheckbox.classList.contains("approve-checkbox");

            if (currentCheckbox.checked) {
                // Find the opposite checkbox for the SAME section only
                const oppositeClass = isApprove
                    ? "reject-checkbox"
                    : "approve-checkbox";
                const oppositeCheckbox = document.querySelector(
                    `[data-section="${section}"].${oppositeClass}`
                );

                if (oppositeCheckbox) {
                    oppositeCheckbox.checked = false;
                }

                // Update rejected sections list
                if (!isApprove) {
                    if (!rejectedSections.includes(section)) {
                        rejectedSections.push(section);
                    }
                } else {
                    rejectedSections = rejectedSections.filter((s) => s !== section);
                }
            } else {
                // If unchecking, remove from rejected sections if it was there
                if (!isApprove) {
                    rejectedSections = rejectedSections.filter((s) => s !== section);
                }
            }

            updateApprovalStatus();
            updateRejectedSectionsDisplay();
        }

        // Bulk action functions
        function approveAll() {
            const allApproveCheckboxes =
                document.querySelectorAll(".approve-checkbox");
            const allRejectCheckboxes =
                document.querySelectorAll(".reject-checkbox");

            // Clear all reject checkboxes first
            allRejectCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });

            // Check all approve checkboxes
            allApproveCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });

            // Clear rejected sections
            rejectedSections = [];

            updateApprovalStatus();
            updateRejectedSectionsDisplay();
        }

        function rejectAll() {
            const allApproveCheckboxes =
                document.querySelectorAll(".approve-checkbox");
            const allRejectCheckboxes =
                document.querySelectorAll(".reject-checkbox");

            // Clear all approve checkboxes first
            allApproveCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });

            // Check all reject checkboxes and collect sections
            rejectedSections = [];
            allRejectCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
                const section = checkbox.dataset.section;
                if (!rejectedSections.includes(section)) {
                    rejectedSections.push(section);
                }
            });

            updateApprovalStatus();
            updateRejectedSectionsDisplay();
        }

        function clearAll() {
            const allCheckboxes = document.querySelectorAll(".section-checkbox");

            // Uncheck all checkboxes
            allCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });

            // Clear rejected sections
            rejectedSections = [];

            updateApprovalStatus();
            updateRejectedSectionsDisplay();
        }

        // Update approval status based on checkboxes
        // Update approval status based on checkboxes
        function updateApprovalStatus() {
            const allApproveCheckboxes =
                document.querySelectorAll(".approve-checkbox");
            const approveBtn = document.getElementById("approveBtn");
            const rejectBtn = document.getElementById("rejectBtn");
            const eventTypeDropdown = document.getElementById("eventType"); // Add this line

            // Get unique sections
            const sections = [
                ...new Set(
                    Array.from(allApproveCheckboxes).map((cb) => cb.dataset.section)
                ),
            ];
            let reviewedSections = 0;

            sections.forEach((section) => {
                const approveCheckbox = document.querySelector(
                    `.approve-checkbox[data-section="${section}"]`
                );
                const rejectCheckbox = document.querySelector(
                    `.reject-checkbox[data-section="${section}"]`
                );

                if (approveCheckbox.checked || rejectCheckbox.checked) {
                    reviewedSections++;
                }
            });

            const allSectionsReviewed = reviewedSections === sections.length;
            const hasRejectedSections = rejectedSections.length > 0;

            // Enable/disable event type dropdown based on rejected sections
            if (hasRejectedSections) {
                eventTypeDropdown.disabled = true;
                eventTypeDropdown.style.background = "#f5f5f5";
                eventTypeDropdown.style.color = "#999";
                eventTypeDropdown.style.cursor = "not-allowed";
            } else {
                eventTypeDropdown.disabled = false;
                eventTypeDropdown.style.background = "white";
                eventTypeDropdown.style.color = "black";
                eventTypeDropdown.style.cursor = "pointer";
            }

            // Enable/disable buttons
            if (allSectionsReviewed) {
                if (hasRejectedSections) {
                    // Enable reject button, disable approve button
                    rejectBtn.disabled = false;
                    rejectBtn.style.background = "#dc3545";
                    rejectBtn.style.cursor = "pointer";

                    approveBtn.disabled = true;
                    approveBtn.style.background = "#cccccc";
                    approveBtn.style.cursor = "not-allowed";
                } else {
                    // Enable approve button, disable reject button
                    approveBtn.disabled = false;
                    approveBtn.style.background = "#28a745";
                    approveBtn.style.cursor = "pointer";

                    rejectBtn.disabled = true;
                    rejectBtn.style.background = "#cccccc";
                    rejectBtn.style.cursor = "not-allowed";
                }
            } else {
                // Disable both buttons
                approveBtn.disabled = true;
                approveBtn.style.background = "#cccccc";
                approveBtn.style.cursor = "not-allowed";

                rejectBtn.disabled = true;
                rejectBtn.style.background = "#cccccc";
                rejectBtn.style.cursor = "not-allowed";
            }
        }

        // Update rejected sections display
        function updateRejectedSectionsDisplay() {
            const summaryDiv = document.getElementById("rejectedItemsSummary");
            const listDiv = document.getElementById("rejectedItemsList");

            if (rejectedSections.length > 0) {
                summaryDiv.style.display = "block";
                listDiv.innerHTML = rejectedSections
                    .map((section) => `<li>${section}</li>`)
                    .join("");
            } else {
                summaryDiv.style.display = "none";
            }
        }

        // Reject Modal Functions
        function rejectEvent() {
            if (rejectedSections.length === 0) {
                alert("Please mark sections for rejection first!");
                return;
            }

            // Display rejected sections in modal
            const rejectedItemsDisplay = document.getElementById(
                "rejectedItemsDisplay"
            );
            rejectedItemsDisplay.innerHTML = rejectedSections
                .map((section) => `<p>• ${section}</p>`)
                .join("");

            document.getElementById("rejectModal").style.display = "flex";
        }

        function closeRejectModal() {
            document.getElementById("rejectModal").style.display = "none";
        }

        // Updated Submit Feedback Function
        function submitFeedback() {
            const feedback = document.getElementById("feedbackText").value.trim();
            const rejectedList = rejectedSections.join(', ');

            // Hide the reject modal first
            document.getElementById("rejectModal").style.display = "none";

            // Show loading screen
            document.getElementById("rejectionLoadingModal").style.display = "flex";

            fetch(window.location.href, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "reject",
                    rejected_sections: rejectedList,
                    comments: feedback
                })
            })
                .then(res => res.json())
                .then(data => {
                    // Hide loading screen
                    document.getElementById("rejectionLoadingModal").style.display = "none";

                    if (data.status === 'rejected') {
                        // Show success screen
                        document.getElementById("rejectionSuccessModal").style.display = "flex";

                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = 'CoordinatorDashboard.php';
                        }, 3000);
                    } else {
                        alert("Error occurred. Please try again.");
                    }
                })
                .catch(error => {
                    // Hide loading screen on error
                    document.getElementById("rejectionLoadingModal").style.display = "none";
                    alert("Network error. Please try again.");
                    console.error('Error:', error);
                });
        }
        // Approve Event Function
        // Updated Approve Event Function
        function approveEvent() {
            const eventType = document.getElementById("eventType").value;
            if (!eventType) {
                alert("Please select an event type first!");
                return;
            }

            // Show loading screen
            document.getElementById("approvalLoadingModal").style.display = "flex";

            fetch(window.location.href, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "approve",
                    event_type: eventType
                })
            })
                .then(res => res.json())
                .then(data => {
                    // Hide loading screen
                    document.getElementById("approvalLoadingModal").style.display = "none";

                    if (data.status === 'success') {
                        // Show success screen
                        document.getElementById("approvalSuccessModal").style.display = "flex";

                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = 'CoordinatorDashboard.php';
                        }, 3000);
                    } else {
                        alert("Error occurred. Please try again.");
                    }
                })
                .catch(error => {
                    // Hide loading screen on error
                    document.getElementById("approvalLoadingModal").style.display = "none";
                    alert("Network error. Please try again.");
                    console.error('Error:', error);
                });
        }


        function closeSuccessModal() {
            document.getElementById("successModal").style.display = "none";
        }

        // Utility Functions
        function goBack() {
            // Here you would typically navigate back to the previous page
            if (
                confirm(
                    "Are you sure you want to go back? Any unsaved changes will be lost."
                )
            ) {
                window.history.back();
            }
        }
        function exportPDF() {
            const evID = "<?= $ev_id ?>";
            window.open(`../components/pdf/generate_pdf.php?id=${evID}`, '_blank');
        }


        // Close modals when clicking outside
        window.onclick = function (event) {
            const modals = document.querySelectorAll(".modal");
            modals.forEach((modal) => {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        };
    </script>
</body>

</html>