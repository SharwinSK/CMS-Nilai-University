<?php
session_start();
include('../../db/dbconfig.php'); // adjust path if needed

$mode = $_GET['mode'] ?? '';
$ev_id = $_GET['id'] ?? '';

// Validate mode
if (!in_array($mode, ['edit', 'modify'])) {
    die("Invalid mode specified.");
}

// Fetch current Status_ID
$stmt = $conn->prepare("SELECT Status_ID FROM events WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Event not found.");
}

$row = $result->fetch_assoc();
$status_id = (int) $row['Status_ID'];

// Mode-based validation
if ($mode === 'edit' && !in_array($status_id, [1, 3])) {
    die("You are not allowed to edit this proposal at its current status.");
}

if ($mode === 'modify' && !in_array($status_id, [2, 4])) {
    die("You are not allowed to modify this proposal at its current status.");
}


//fetch data
// 1. Fetch main event info
$event_sql = "
SELECT e.*, s.Stu_Name, s.Stu_ID, c.Club_Name, v.Venue_Name 
FROM events e
JOIN student s ON e.Stu_ID = s.Stu_ID
JOIN club c ON e.Club_ID = c.Club_ID
LEFT JOIN venue v ON e.Ev_VenueID = v.Venue_ID
WHERE e.Ev_ID = ?
";

$stmt = $conn->prepare($event_sql);
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$event_result = $stmt->get_result();
$event = $event_result->fetch_assoc();

$event_name = $event['Ev_Name'];
$event_objectives = $event['Ev_Objectives'];
$student_id = $event['Stu_ID'];
$student_name = $event['Stu_Name'];
$club_id = $event['Club_ID'];
$venue_id = $event['Ev_VenueID'];
$alt_venue = $event['Ev_AltVenueID'];
$event_date = $event['Ev_Date'];
$event_time_start = $event['Ev_StartTime'];
$event_time_end = $event['Ev_EndTime'];
$poster_path = $event['Ev_Poster'];
$additional_info_path = $event['Ev_AdditionalInfo'];
$alt_date = $event['Ev_AlternativeDate'];
$event_nature = $event['Ev_ProjectNature'];
$event_intro = $event['Ev_Intro'];
$event_purpose = $event['Ev_Details'];
$estimated_participant = $event['Ev_Pax'];
// 2. Fetch all clubs for dropdown
$clubs = [];
$club_query = "SELECT Club_ID, Club_Name FROM club ORDER BY Club_Name ASC";
$result_club = $conn->query($club_query);
while ($row = $result_club->fetch_assoc()) {
    $clubs[] = $row;
}

// 3. Fetch all venues for dropdown
$venues = [];
$venue_query = "SELECT Venue_ID, Venue_Name FROM venue ORDER BY Venue_Name ASC";
$result_venue = $conn->query($venue_query);
while ($row = $result_venue->fetch_assoc()) {
    $venues[] = $row;
}

// 4. Fetch PIC info
$pic_query = "SELECT * FROM personincharge WHERE Ev_ID = ?";
$stmt = $conn->prepare($pic_query);
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$pic_result = $stmt->get_result();
$pic = $pic_result->fetch_assoc();

$pic_name = $pic['PIC_Name'] ?? '';
$pic_phone = $pic['PIC_PhnNum'] ?? '';
$pic_id = $pic['PIC_ID'] ?? '';

//event flow data 
$eventFlows = [];
$stmt = $conn->prepare("SELECT Flow_ID, Date, Start_Time, End_Time, Hours, Activity, Remarks FROM eventminutes WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id); // was wrongly $Ev_ID earlier
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $eventFlows[] = $row;
}

$committees = [];
$stmt = $conn->prepare("SELECT * FROM committee WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $committees[] = $row;
}

// Budget rows
$budgetItems = [];
$stmt = $conn->prepare("SELECT Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks FROM budget WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $budgetItems[] = $row;
}

// Budget summary
$stmt = $conn->prepare("SELECT Total_Income, Total_Expense, Surplus_Deficit, Prepared_By, statement FROM budgetsummary WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$summaryResult = $stmt->get_result();
$budgetSummary = $summaryResult->fetch_assoc() ?? [
    'Total_Income' => 0.00,
    'Total_Expense' => 0.00,
    'Surplus_Deficit' => 0.00,
    'Prepared_By' => '',
    'statement' => ''
];

$times = [];
for ($h = 8; $h <= 22; $h++) {
    $value = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00'; // for DB
    $label = $h . '.00am';
    if ($h >= 12) {
        $label = ($h == 12 ? 12 : $h - 12) . '.00pm';
    }
    $times[] = ['value' => $value, 'label' => $label];
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Proposal Form</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../../assets/css/proposal.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <div class="header">
            <button type="button" class="back-btn-header" id="headerBackBtn">
                ← Back
            </button>
            <h1>Proposal Form</h1>
            <p>Nilai University Content Management System</p>
        </div>

        <div class="form-container">
            <form id="proposalForm" method="POST" enctype="multipart/form-data"
                action="proposalUpdate.php?mode=<?= $mode ?>&id=<?= $ev_id ?>">

                <input type="hidden" name="Ev_ID" value="<?= htmlspecialchars($ev_id) ?>">
                <!-- Section 1: Student Information -->
                <div class="section">
                    <h2 class="section-title">Student Information</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="studentName" class="required">Student Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($student_name) ?>"
                                    readonly>
                                <div class="error-message">Please enter student name</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="mb-3">
                                <label class="form-label">Organizing Club</label>
                                <select name="Club_ID" class="form-select">
                                    <?php foreach ($clubs as $club): ?>
                                        <option value="<?= $club['Club_ID'] ?>" <?= ($club_id == $club['Club_ID']) ? 'selected' : '' ?>>
                                            <?= $club['Club_Name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="studentId" class="required">Student ID</label>
                                <input type="text" name="Stu_ID" class="form-control"
                                    value="<?= htmlspecialchars($student_id) ?>" readonly>
                                <div class="error-message">Please enter student ID</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Event Details -->
                <div class="section">
                    <h2 class="section-title">Event Details</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventName" class="required">Event Name</label>
                                <input type="text" name="Ev_Name" class="form-control"
                                    value="<?= htmlspecialchars($event_name) ?>">
                                <div class="error-message">Please enter event name</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventNature" class="required">Event Nature</label>
                                <select id="eventNature" name="Ev_ProjectNature" required>
                                    <option value="">Select Category</option>
                                    <option value="Category A: Games/Sports & Martial Arts" <?= $event_nature == 'Category A: Games/Sports & Martial Arts' ? 'selected' : '' ?>>
                                        Category A: Games/Sports & Martial Arts
                                    </option>
                                    <option value="Category B: Club/Societies/Uniformed Units"
                                        <?= $event_nature == 'Category B: Club/Societies/Uniformed Units' ? 'selected' : '' ?>>
                                        Category B: Club/Societies/Uniformed Units
                                    </option>
                                    <option value="Category C: Community Service" <?= $event_nature == 'Category C: Community Service' ? 'selected' : '' ?>>
                                        Category C: Community Service
                                    </option>
                                </select>

                                <div class="error-message">Please select event nature</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventObjectives" class="required">Event Objectives</label>
                        <input type="text" name="Ev_Objectives" class="form-control"
                            value="<?= htmlspecialchars($event_objectives) ?>">

                        <div class="error-message">Please enter event objectives</div>
                    </div>

                    <div class="form-group">
                        <label for="eventIntroduction" class="required">Introduction Event</label>
                        <textarea name="Ev_Intro" class="form-control"><?= htmlspecialchars($event_intro) ?></textarea>
                        <div class="error-message">Please enter event introduction</div>
                    </div>

                    <div class="form-group">
                        <label for="eventPurpose" class="required">Purpose of Event</label>
                        <textarea name="Ev_Details"
                            class="form-control"><?= htmlspecialchars($event_purpose) ?></textarea>
                        <div class="error-message">Please enter event purpose</div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="estimatedParticipants" class="required">Estimated Participants</label>
                                <input type="number" name="Ev_Pax" class="form-control"
                                    value="<?= htmlspecialchars($estimated_participant) ?>">
                                <div class="error-message">
                                    Please enter estimated participants
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventDate" class="required">Event Date</label>
                                <input type="date" name="Ev_Date" class="form-control"
                                    value="<?= htmlspecialchars($event_date) ?>">
                                <div class="error-message">
                                    Please select event date (minimum 14 days from today)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <select name="Ev_StartTime" class="form-select">
                                        <?php foreach ($times as $time): ?>
                                            <option value="<?= $time['value'] ?>" <?= ($event_time_start == $time['value']) ? 'selected' : '' ?>>
                                                <?= $time['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <select name="Ev_EndTime" class="form-select">
                                        <?php foreach ($times as $time): ?>
                                            <option value="<?= $time['value'] ?>" <?= ($event_time_end == $time['value']) ? 'selected' : '' ?>>
                                                <?= $time['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <div class="mb-3">
                                    <label class="form-label">Venue</label>
                                    <select name="Ev_VenueID" class="form-select">
                                        <?php foreach ($venues as $venue): ?>
                                            <option value="<?= $venue['Venue_ID'] ?>" <?= ($venue_id == $venue['Venue_ID']) ? 'selected' : '' ?>>
                                                <?= $venue['Venue_Name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventPoster" class="required">Event Poster</label>
                        <div class="poster-container">
                            <div class="poster-preview" id="posterPreview" onclick="openPosterModal()">
                                <?php if (!empty($poster_path)): ?>
                                    <img src="<?= htmlspecialchars($poster_path) ?>" alt="Event Poster" id="posterImage">
                                <?php else: ?>
                                    <div class="poster-placeholder">
                                        <p>📷</p>
                                        <p>No poster</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button type="button" class="upload-btn"
                                    onclick="document.getElementById('eventPoster').click()">
                                    📁 Upload Poster
                                </button>
                                <input type="file" id="eventPoster" name="eventPoster" accept=".jpg,.jpeg,.png"
                                    style="display: none" onchange="handlePosterChange(this)" />
                                <div class="file-info" style="margin-top: 8px;">
                                    Maximum file size: 20MB<br>
                                    Accepted formats: JPEG, PNG
                                </div>
                            </div>
                        </div>
                        <div class="error-message">Please upload event poster</div>
                    </div>
                </div>



                <!-- Section 3: Person in Charge -->
                <div class="section">
                    <h2 class="section-title">Person in Charge</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picName" class="required">Name</label>
                                <input type="text" id="picName" name="picName" class="form-control"
                                    value="<?= htmlspecialchars($pic_name) ?>" required />
                                <div class="error-message">
                                    Please enter person in charge name
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picId" class="required">ID</label>
                                <input type="text" id="picid" name="picid" class="form-control"
                                    value="<?= htmlspecialchars($pic_id) ?>" required />
                                <div class="error-message">
                                    Please enter person in charge ID
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picPhone" class="required">Phone Number</label>
                                <input type="text" id="picPhone" name="picPhone" class="form-control"
                                    value="<?= htmlspecialchars($pic_phone) ?>" required />
                                <div class="error-message">Please enter phone number</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Event Flow -->
                <div class="section">
                    <h2 class="section-title">Event Flow (Minutes of Meeting)</h2>
                    <div class="table-container">
                        <table id="eventFlowTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours</th>
                                    <th>Activity Description</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="eventFlowBody">
                                <?php foreach ($eventFlows as $index => $flow):
                                    $rowId = "flow" . $index;
                                    ?>
                                    <tr>
                                        <td><input type="date" name="eventFlowDate[]" value="<?= $flow['Date'] ?>" required>
                                        </td>
                                        <td><input type="time" name="eventFlowStart[]" value="<?= $flow['Start_Time'] ?>"
                                                required></td>
                                        <td><input type="time" name="eventFlowEnd[]" value="<?= $flow['End_Time'] ?>"
                                                required></td>
                                        <td><input type="number" name="eventFlowHours[]" value="<?= $flow['Hours'] ?>"
                                                step="0.5" readonly></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick="addActivityDescription('<?= $rowId ?>')">Add</button>
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    onclick="viewActivityDescription('<?= $rowId ?>')"
                                                    <?= empty($flow['Activity']) ? 'disabled' : '' ?>>View</button>
                                            </div>
                                            <input type="hidden" name="eventFlowActivity[]" id="activity_<?= $rowId ?>"
                                                value="<?= htmlspecialchars($flow['Activity']) ?>">
                                        </td>
                                        <td><input type="text" name="eventFlowRemarks[]"
                                                value="<?= htmlspecialchars($flow['Remarks']) ?>"></td>
                                        <td><button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteRow(this)">Delete</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>

                        <button type="button" class="btn btn-add" id="addEventFlowBtn">
                            + Add New Row
                        </button>
                    </div>
                    <div id="hoursStatus" style="margin-top: 10px; font-weight: bold; color: #2d4f2b">
                        🕒 40 hours remaining to reach minimum requirement
                    </div>
                </div>

                <!-- Section 5: Committee Members -->
                <div class="section">
                    <h2 class="section-title">Committee Members</h2>
                    <div class="file-info">
                        Note: Shall upload PDF files only, maximum file size: 2MB
                    </div>

                    <div class="table-container">
                        <table id="committeeTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Phone</th>
                                    <th>Job Scope</th>
                                    <th>COCU Claimer</th>
                                    <th>Upload COCU Statement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="committeeBody">
                                <?php foreach ($committees as $index => $com):
                                    $rowId = "committee" . $index;
                                    $cocu = strtolower($com['Com_COCUClaimers']);
                                    ?>
                                    <tr>
                                        <td><input type="text" name="committeeId[]"
                                                value="<?= htmlspecialchars($com['Com_ID']) ?>" required></td>
                                        <td><input type="text" name="committeeName[]"
                                                value="<?= htmlspecialchars($com['Com_Name']) ?>" required></td>
                                        <td><input type="text" name="committeePosition[]"
                                                value="<?= htmlspecialchars($com['Com_Position']) ?>" required></td>
                                        <td>
                                            <select name="committeeDepartment[]" required>
                                                <option value="">Select Department</option>
                                                <?php
                                                $departments = ["Foundation in Business", "Foundation in Science", "DCS", "DIA", "DAME", "DIT", "DHM", "DCA", "DBA", "DIN", "BOF", "BAAF", "BBAF", "BSB", "BCSAI", "BITC", "BSE", "BCSDS", "BIT", "BITIECC", "BEM", "BHMBM", "BBAGL", "BBADM", "BBAM", "BBAMT", "BBAIB", "BBAHRM", "BBA", "BSN"];
                                                foreach ($departments as $dep) {
                                                    $selected = ($dep === $com['Com_Department']) ? 'selected' : '';
                                                    echo "<option value='$dep' $selected>$dep</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="committeePhone[]"
                                                value="<?= htmlspecialchars($com['Com_PhnNum']) ?>" required></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick="addJobScope('<?= $rowId ?>')">Add</button>
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    onclick="viewJobScope('<?= $rowId ?>')" <?= empty($com['Com_JobScope']) ? 'disabled' : '' ?>>View</button>
                                            </div>
                                            <input type="hidden" name="committeeJobScope[]" id="jobScope_<?= $rowId ?>"
                                                value="<?= htmlspecialchars($com['Com_JobScope']) ?>">
                                        </td>
                                        <td>
                                            <select name="cocuClaimer[]" onchange="toggleCocuUpload(this, '<?= $rowId ?>')"
                                                required>
                                                <option value="no" <?= $cocu === "no" ? "selected" : "" ?>>No</option>
                                                <option value="yes" <?= $cocu === "yes" ? "selected" : "" ?>>Yes</option>
                                            </select>
                                        </td>
                                        <td>
                                            <?php if (!empty($com['student_statement'])): ?>
                                                <a href="<?= htmlspecialchars($com['student_statement']) ?>" target="_blank"
                                                    class="view-pdf-btn">
                                                    📄 View PDF
                                                </a><br>
                                            <?php endif; ?>
                                            <input type="file" name="cocuStatement[]" id="cocuFile_<?= $rowId ?>"
                                                accept=".pdf" <?= $cocu === "yes" ? "" : "disabled" ?>>
                                        </td>
                                        <td><button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteRow(this)">Delete</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>

                        <button type="button" class="btn btn-add" id="addCommitteeBtn">
                            + Add New Row
                        </button>
                    </div>
                </div>

                <!-- Section 6: Budget -->
                <div class="section">
                    <h2 class="section-title">Budget</h2>

                    <div class="table-container">
                        <table id="budgetTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount (RM)</th>
                                    <th>Type</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="budgetBody">
                                <?php foreach ($budgetItems as $item): ?>
                                    <tr>
                                        <td><input type="text" name="budgetDescription[]"
                                                value="<?= htmlspecialchars($item['Bud_Desc']) ?>" required></td>
                                        <td><input type="number" name="budgetAmount[]" step="0.01" min="0"
                                                value="<?= $item['Bud_Amount'] ?>" required></td>
                                        <td>
                                            <select name="budgetType[]" required>
                                                <option value="">Select</option>
                                                <option value="income" <?= strtolower($item['Bud_Type']) === 'income' ? 'selected' : '' ?>>Income</option>
                                                <option value="expense" <?= strtolower($item['Bud_Type']) === 'expense' ? 'selected' : '' ?>>Expense</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="budgetRemarks[]"
                                                value="<?= htmlspecialchars($item['Bud_Remarks']) ?>"></td>
                                        <td><button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteRow(this)">Delete</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                        <button type="button" class="btn btn-add" id="addBudgetBtn">
                            + Add New Row
                        </button>
                    </div>
                    <div class="budget-summary">
                        <h4>Budget Summary</h4>
                        <div class="budget-row">
                            <span>Total Income:</span>
                            <span id="totalIncome">RM <?= number_format($budgetSummary['Total_Income'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span>Total Expense:</span>
                            <span id="totalExpense">RM <?= number_format($budgetSummary['Total_Expense'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <strong>
                                <span id="surplusDeficitLabel">Surplus/Deficit:</span>
                                <span id="surplusDeficit">RM
                                    <?= number_format($budgetSummary['Surplus_Deficit'], 2) ?></span>
                            </strong>
                        </div>
                        <div class="form-group" style="margin-top: 15px">
                            <label for="preparedBy" class="required">Prepared By:</label>
                            <input type="text" id="preparedBy" name="preparedBy"
                                value="<?= htmlspecialchars($budgetSummary['Prepared_By']) ?>" required />
                            <div class="error-message">Please enter prepared by</div>
                        </div>
                    </div>
                </div>

                <!-- Section 7: Additional Information -->
                <div class="section">
                    <h2 class="section-title">Additional Information</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="alternativeDate" class="required">Alternative Date</label>
                                <input type="date" name="Ev_AlternativeDate" class="form-control"
                                    value="<?= htmlspecialchars($alt_date) ?>">
                                <div class="error-message">
                                    Please select alternative date
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="alternativeVenue" class="required">Alternative Venue</label>
                                <select id="alternativeVenue" name="altVenue" class="form-select" required>
                                    <option value="">-- Select Alternative Venue --</option>
                                    <?php foreach ($venues as $venue): ?>
                                        <option value="<?= $venue['Venue_ID'] ?>" <?= ($alt_venue == $venue['Venue_ID']) ? 'selected' : '' ?>>
                                            <?= $venue['Venue_Name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">
                                    Please select alternative venue
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="additionalDocument">Additional Document (Optional)</label>
                        <div class="document-section">
                            <?php if (!empty($additional_info_path)): ?>
                                <a href="<?= htmlspecialchars($additional_info_path) ?>" target="_blank"
                                    class="view-pdf-btn">
                                    📄 View Current Document
                                </a>
                            <?php else: ?>
                                <span style="color: #666; font-size: 14px;">No document uploaded</span>
                            <?php endif; ?>

                            <button type="button" class="upload-btn"
                                onclick="document.getElementById('additionalDocument').click()">
                                📁 Upload Document
                            </button>
                            <input type="file" id="additionalDocument" name="additionalDocument" style="display: none"
                                onchange="handleDocumentChange(this)" />
                        </div>
                        <div class="file-info" style="margin-top: 8px;">
                            Any file type accepted
                        </div>
                        <div id="documentStatus" style="margin-top: 8px; font-size: 14px; color: #27ae60;"></div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="backBtn">
                        ← Back
                    </button>
                    <div>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            📝 Submit Proposal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Description Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Activity Description</h3>
            <textarea id="activityDescription" placeholder="Enter activity description..."
                style="width: 100%; height: 150px; margin: 15px 0"></textarea>
            <button type="button" class="btn btn-primary" id="saveActivityBtn">
                Save
            </button>
        </div>
    </div>

    <!-- Job Scope Modal -->
    <div id="jobScopeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Job Scope</h3>
            <textarea id="jobScopeDescription" placeholder="Enter job scope..."
                style="width: 100%; height: 150px; margin: 15px 0"></textarea>
            <button type="button" class="btn btn-primary" id="saveJobScopeBtn">
                Save
            </button>
        </div>
    </div>

    <!--poster model-->
    <div id="posterModal" class="modal">
        <div class="modal-content-image">
            <span class="close-image" onclick="closePosterModal()">&times;</span>
            <img id="fullPosterImage" src="" alt="Full Poster View">
        </div>
    </div>

    <script>
        // Initialize form when page loads
        document.addEventListener("DOMContentLoaded", function () {
            setupEventListeners();
            setMinimumDates();
            populateTimeOptions();
            calculateBudget(); // Calculate initial budget if there are existing items
            updateRemainingHours(); // Update hours if there are existing flows
        });

        function populateTimeOptions() {
            const startTimeSelect = document.getElementById("startTime");
            const endTimeSelect = document.getElementById("endTime");

            // Only populate if elements exist and are empty
            if (startTimeSelect && startTimeSelect.options.length <= 1) {
                startTimeSelect.innerHTML = '<option value="">-- Select Start Time --</option>';
                for (let h = 8; h <= 22; h++) {
                    const timeValue = String(h).padStart(2, "0") + ":00";
                    const labelHour = h === 12 ? 12 : h % 12;
                    const ampm = h < 12 ? "AM" : "PM";
                    const displayTime = `${labelHour}:00 ${ampm}`;
                    startTimeSelect.innerHTML += `<option value="${timeValue}">${displayTime}</option>`;
                }
            }

            if (endTimeSelect && endTimeSelect.options.length <= 1) {
                endTimeSelect.innerHTML = '<option value="">-- Select End Time --</option>';
                for (let h = 8; h <= 22; h++) {
                    const timeValue = String(h).padStart(2, "0") + ":00";
                    const labelHour = h === 12 ? 12 : h % 12;
                    const ampm = h < 12 ? "AM" : "PM";
                    const displayTime = `${labelHour}:00 ${ampm}`;
                    endTimeSelect.innerHTML += `<option value="${timeValue}">${displayTime}</option>`;
                }
            }
        }

        function setupEventListeners() {
            // File upload handlers
            const posterUpload = document.getElementById("eventPoster");
            if (posterUpload) {
                posterUpload.addEventListener("change", function () {
                    handlePosterChange(this);
                });
            }

            const additionalDoc = document.getElementById("additionalDocument");
            if (additionalDoc) {
                additionalDoc.addEventListener("change", function () {
                    handleDocumentChange(this);
                });
            }

            // Button handlers - Check if elements exist before adding listeners
            const addEventFlowBtn = document.getElementById("addEventFlowBtn");
            if (addEventFlowBtn) {
                addEventFlowBtn.addEventListener("click", addEventFlowRow);
            }

            const addCommitteeBtn = document.getElementById("addCommitteeBtn");
            if (addCommitteeBtn) {
                addCommitteeBtn.addEventListener("click", addCommitteeRow);
            }

            const addBudgetBtn = document.getElementById("addBudgetBtn");
            if (addBudgetBtn) {
                addBudgetBtn.addEventListener("click", addBudgetRow);
            }

            // Modal handlers
            setupModalHandlers();

            // Form submission
            const backBtn = document.getElementById("backBtn");
            const headerBackBtn = document.getElementById("headerBackBtn");
            const proposalForm = document.getElementById("proposalForm");

            if (backBtn) backBtn.addEventListener("click", handleBack);
            if (headerBackBtn) headerBackBtn.addEventListener("click", handleBack);

            // Budget calculation - Use event delegation
            const budgetBody = document.getElementById("budgetBody");
            if (budgetBody) {
                budgetBody.addEventListener("input", calculateBudget);
                budgetBody.addEventListener("change", calculateBudget);
            }

            // Event flow hours calculation - Use event delegation
            const eventFlowBody = document.getElementById("eventFlowBody");
            if (eventFlowBody) {
                eventFlowBody.addEventListener("input", function (e) {
                    if (e.target.name === "eventFlowStart[]" || e.target.name === "eventFlowEnd[]") {
                        calculateHoursForRow(e.target);
                    }
                });
                eventFlowBody.addEventListener("change", function (e) {
                    if (e.target.name === "eventFlowStart[]" || e.target.name === "eventFlowEnd[]") {
                        calculateHoursForRow(e.target);
                    }
                });
            }
        }

        function calculateHoursForRow(changedInput) {
            const row = changedInput.closest('tr');
            const startInput = row.querySelector('input[name="eventFlowStart[]"]');
            const endInput = row.querySelector('input[name="eventFlowEnd[]"]');
            const hoursInput = row.querySelector('input[name="eventFlowHours[]"]');

            if (startInput.value && endInput.value) {
                const start = new Date(`2000-01-01T${startInput.value}`);
                const end = new Date(`2000-01-01T${endInput.value}`);
                const diff = (end - start) / (1000 * 60 * 60);
                hoursInput.value = diff > 0 ? diff.toFixed(1) : 0;
            } else {
                hoursInput.value = 0;
            }
            updateRemainingHours();
        }

        function setMinimumDates() {
            const today = new Date();
            const minDate = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000);
            const minDateStr = minDate.toISOString().split("T")[0];

            const eventDateEl = document.getElementById("eventDate");
            const altDateEl = document.getElementById("alternativeDate");

            if (eventDateEl) eventDateEl.min = minDateStr;
            if (altDateEl) altDateEl.min = minDateStr;
        }

        function updateRemainingHours() {
            const totalHours = getTotalEventFlowHours();
            const hoursStatus = document.getElementById("hoursStatus");

            if (!hoursStatus) return;

            const remaining = 40 - totalHours;

            if (totalHours >= 40) {
                hoursStatus.textContent = `✅ Minimum requirement met: ${totalHours.toFixed(1)} hours`;
                hoursStatus.style.color = "#27ae60";
            } else {
                hoursStatus.textContent = `🕒 ${remaining.toFixed(1)} hours remaining to reach minimum requirement`;
                hoursStatus.style.color = "#e67e22";
            }
        }

        function handlePosterChange(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file
                const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
                const allowedExtensions = [".jpg", ".jpeg", ".png"];
                const extension = file.name.substring(file.name.lastIndexOf(".")).toLowerCase();

                if (!allowedTypes.includes(file.type) || !allowedExtensions.includes(extension)) {
                    alert("Only JPG and PNG formats are allowed.");
                    input.value = "";
                    return;
                }

                if (file.size > 20 * 1024 * 1024) {
                    alert("Maximum file size is 20MB.");
                    input.value = "";
                    return;
                }

                // Show preview of the NEW uploaded image
                const reader = new FileReader();
                reader.onload = function (e) {
                    const posterPreview = document.getElementById('posterPreview');
                    if (posterPreview) {
                        posterPreview.innerHTML = `<img src="${e.target.result}" alt="New Event Poster" id="posterImage">`;
                    }

                    // Update the modal image source as well
                    const fullPosterImage = document.getElementById('fullPosterImage');
                    if (fullPosterImage) {
                        fullPosterImage.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function openPosterModal() {
            const posterImage = document.getElementById('posterImage');
            if (posterImage) {
                const modal = document.getElementById('posterModal');
                const fullPosterImage = document.getElementById('fullPosterImage');
                if (modal && fullPosterImage) {
                    fullPosterImage.src = posterImage.src;
                    modal.style.display = 'block';
                }
            }
        }

        function closePosterModal() {
            const modal = document.getElementById('posterModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function handleDocumentChange(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const statusDiv = document.getElementById('documentStatus');
                if (statusDiv) {
                    statusDiv.textContent = `✅ ${file.name} selected for upload`;
                }
            }
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const posterModal = document.getElementById('posterModal');
            if (event.target === posterModal) {
                posterModal.style.display = 'none';
            }
        }

        function addEventFlowRow() {
            const tbody = document.getElementById("eventFlowBody");
            if (!tbody) return;

            const row = document.createElement("tr");
            const rowId = "eventFlow_" + Date.now();

            row.innerHTML = `
        <td><input type="date" name="eventFlowDate[]" required></td>
        <td><input type="time" name="eventFlowStart[]" required></td>
        <td><input type="time" name="eventFlowEnd[]" required></td>
        <td><input type="number" name="eventFlowHours[]" class="hours-input" step="0.5" min="0" readonly></td>
        <td>
            <div style="display: flex; gap: 5px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addActivityDescription('${rowId}')">Add</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="viewActivityDescription('${rowId}')" disabled>View</button>
            </div>
            <input type="hidden" name="eventFlowActivity[]" id="activity_${rowId}">
        </td>
        <td><input type="text" name="eventFlowRemarks[]"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button></td>
    `;

            tbody.appendChild(row);
            console.log("Added new event flow row"); // Debug log
            updateRemainingHours();
        }

        function addCommitteeRow() {
            const tbody = document.getElementById("committeeBody");
            if (!tbody) return;

            const row = document.createElement("tr");
            const rowId = "committee_" + Date.now();

            row.innerHTML = `
        <td><input type="text" name="committeeId[]" required></td>
        <td><input type="text" name="committeeName[]" required></td>
        <td><input type="text" name="committeePosition[]" required></td>
        <td>
            <select name="committeeDepartment[]" required>
                <option value="">Select Department</option>
                <option value="Foundation in Business">Foundation in Business</option>
                <option value="Foundation in Science">Foundation in Science</option>
                <option value="DCS">Diploma in Computer Science</option>
                <option value="DIA">Diploma in Accounting</option>
                <option value="DAME">Diploma in Aircraft Maintenance Engineering</option>
                <option value="DIT">Diploma in Information Technology</option>
                <option value="DHM">Diploma in Hotel Management</option>
                <option value="DCA">Diploma in Culinary Arts</option>
                <option value="DBA">Diploma in Business Administration</option>
                <option value="DIN">Diploma in Nursing</option>
                <option value="BOF">Bachelor of Finance</option>
                <option value="BAAF">Bachelor of Arts in Accounting & Finance</option>
                <option value="BBAF">Bachelor of Business Administration in Finance</option>
                <option value="BSB">Bachelor of Science Biotechnology</option>
                <option value="BCSAI">Bachelor of Computer Science Artificial Intelligence</option>
                <option value="BITC">Bachelor of Information Technology Cybersecurity</option>
                <option value="BSE">Bachelor of Software Engineering</option>
                <option value="BCSDS">Bachelor of Computer Science Data Science</option>
                <option value="BIT">Bachelor of Information Technology</option>
                <option value="BITIECC">Bachelor of Information Technology Internet Engineering and Cloud Computing</option>
                <option value="BEM">Bachelor of Event Management</option>
                <option value="BHMBM">Bachelor of Hospitality Management with Business Management</option>
                <option value="BBAGL">Bachelor of Business Administration in Global Logistics</option>
                <option value="BBADM">Bachelor of Business Administration in Digital Marketing</option>
                <option value="BBAM">Bachelor of Business Administration in Marketing</option>
                <option value="BBAMT">Bachelor of Business Administration in Management</option>
                <option value="BBAIB">Bachelor of Business Administration in International Business</option>
                <option value="BBAHRM">Bachelor of Business Administration in Human Resource Management</option>
                <option value="BBA">Bachelor of Business Administration</option>
                <option value="BSN">Bachelor of Science in Nursing</option>
            </select>
        </td>
        <td><input type="tel" name="committeePhone[]" placeholder="012-3456789" required></td>
        <td>
            <div style="display: flex; gap: 5px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addJobScope('${rowId}')">Add</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="viewJobScope('${rowId}')" disabled>View</button>
            </div>
            <input type="hidden" name="committeeJobScope[]" id="jobScope_${rowId}">
        </td>
        <td>
            <select name="cocuClaimer[]" onchange="toggleCocuUpload(this, '${rowId}')" required>
                <option value="no" selected>No</option>
                <option value="yes">Yes</option>
            </select>
        </td>
        <td>
            <input type="file" name="cocuStatement[]" id="cocuFile_${rowId}" accept=".pdf" disabled>
        </td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button></td>
    `;

            tbody.appendChild(row);
            console.log("Added new committee row"); // Debug log
        }

        function addBudgetRow() {
            const tbody = document.getElementById("budgetBody");
            if (!tbody) return;

            const row = document.createElement("tr");

            row.innerHTML = `
        <td><input type="text" name="budgetDescription[]" required></td>
        <td><input type="number" name="budgetAmount[]" step="0.01" min="0" required></td>
        <td>
            <select name="budgetType[]" required>
                <option value="">Select</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
        </td>
        <td><input type="text" name="budgetRemarks[]"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button></td>
    `;

            tbody.appendChild(row);
            console.log("Added new budget row"); // Debug log
        }

        function deleteRow(button) {
            if (confirm("Are you sure you want to delete this row?")) {
                button.closest("tr").remove();
                calculateBudget();
                updateRemainingHours();
            }
        }

        function setupModalHandlers() {
            // Activity Description Modal
            const activityModal = document.getElementById("activityModal");
            const jobScopeModal = document.getElementById("jobScopeModal");

            // Close modals
            document.querySelectorAll(".close").forEach((closeBtn) => {
                closeBtn.addEventListener("click", function () {
                    const modal = this.closest(".modal");
                    if (modal) modal.style.display = "none";
                });
            });

            // Close modal on outside click
            window.addEventListener("click", function (e) {
                if (e.target.classList.contains("modal")) {
                    e.target.style.display = "none";
                }
            });

            // Save activity description
            const saveActivityBtn = document.getElementById("saveActivityBtn");
            if (saveActivityBtn) {
                saveActivityBtn.addEventListener("click", function () {
                    const description = document.getElementById("activityDescription").value;
                    if (description.trim()) {
                        const rowId = activityModal.getAttribute("data-row-id");
                        const hiddenInput = document.getElementById("activity_" + rowId);
                        if (hiddenInput) {
                            hiddenInput.value = description;
                        }

                        // Enable view button
                        const viewBtn = document.querySelector(
                            `button[onclick="viewActivityDescription('${rowId}')"]`
                        );
                        if (viewBtn) viewBtn.disabled = false;

                        activityModal.style.display = "none";
                        document.getElementById("activityDescription").value = "";
                    }
                });
            }

            // Save job scope
            const saveJobScopeBtn = document.getElementById("saveJobScopeBtn");
            if (saveJobScopeBtn) {
                saveJobScopeBtn.addEventListener("click", function () {
                    const description = document.getElementById("jobScopeDescription").value;
                    if (description.trim()) {
                        const rowId = jobScopeModal.getAttribute("data-row-id");
                        const hiddenInput = document.getElementById("jobScope_" + rowId);
                        if (hiddenInput) {
                            hiddenInput.value = description;
                        }

                        // Enable view button
                        const viewBtn = document.querySelector(
                            `button[onclick="viewJobScope('${rowId}')"]`
                        );
                        if (viewBtn) viewBtn.disabled = false;

                        jobScopeModal.style.display = "none";
                        document.getElementById("jobScopeDescription").value = "";
                    }
                });
            }
        }

        function addActivityDescription(rowId) {
            const modal = document.getElementById("activityModal");
            if (!modal) return;

            modal.setAttribute("data-row-id", rowId);
            modal.style.display = "block";

            // Load existing description if any
            const hiddenInput = document.getElementById("activity_" + rowId);
            const textarea = document.getElementById("activityDescription");
            if (hiddenInput && textarea) {
                textarea.value = hiddenInput.value || "";
            }
        }

        function viewActivityDescription(rowId) {
            const hiddenInput = document.getElementById("activity_" + rowId);
            const description = hiddenInput ? hiddenInput.value : "";

            if (description) {
                Swal.fire({
                    title: "Activity Description",
                    text: description,
                    icon: "info",
                    confirmButtonText: "Close",
                });
            } else {
                Swal.fire({
                    title: "No Description",
                    text: "No description has been added for this activity yet.",
                    icon: "warning",
                    confirmButtonText: "OK",
                });
            }
        }

        function addJobScope(rowId) {
            const modal = document.getElementById("jobScopeModal");
            if (!modal) return;

            modal.setAttribute("data-row-id", rowId);
            modal.style.display = "block";

            // Load existing job scope if any
            const hiddenInput = document.getElementById("jobScope_" + rowId);
            const textarea = document.getElementById("jobScopeDescription");
            if (hiddenInput && textarea) {
                textarea.value = hiddenInput.value || "";
            }
        }

        function viewJobScope(rowId) {
            const hiddenInput = document.getElementById("jobScope_" + rowId);
            const jobScope = hiddenInput ? hiddenInput.value : "";

            if (jobScope) {
                Swal.fire({
                    title: "Job Scope Description",
                    text: jobScope,
                    icon: "info",
                    confirmButtonText: "Close",
                });
            } else {
                Swal.fire({
                    title: "No Description",
                    text: "No job scope has been added yet.",
                    icon: "warning",
                    confirmButtonText: "OK",
                });
            }
        }

        function toggleCocuUpload(select, rowId) {
            const fileInput = document.getElementById("cocuFile_" + rowId);
            if (fileInput) {
                fileInput.disabled = select.value !== "yes";
                if (select.value !== "yes") {
                    fileInput.value = "";
                }
            }
        }

        function calculateBudget() {
            const amounts = document.querySelectorAll('input[name="budgetAmount[]"]');
            const types = document.querySelectorAll('select[name="budgetType[]"]');

            let totalIncome = 0;
            let totalExpense = 0;

            amounts.forEach((amountInput, index) => {
                const amount = parseFloat(amountInput.value) || 0;
                const type = types[index] ? types[index].value : "";

                if (type === "income") {
                    totalIncome += amount;
                } else if (type === "expense") {
                    totalExpense += amount;
                }
            });

            const surplusDeficit = totalIncome - totalExpense;

            // Update display elements if they exist
            const totalIncomeEl = document.getElementById("totalIncome");
            const totalExpenseEl = document.getElementById("totalExpense");
            const surplusDeficitEl = document.getElementById("surplusDeficit");

            if (totalIncomeEl) totalIncomeEl.textContent = `RM ${totalIncome.toFixed(2)}`;
            if (totalExpenseEl) totalExpenseEl.textContent = `RM ${totalExpense.toFixed(2)}`;
            if (surplusDeficitEl) {
                surplusDeficitEl.textContent = `RM ${surplusDeficit.toFixed(2)}`;

                // Change color based on surplus/deficit
                if (surplusDeficit > 0) {
                    surplusDeficitEl.style.color = "#27ae60";
                } else if (surplusDeficit < 0) {
                    surplusDeficitEl.style.color = "#e74c3c";
                } else {
                    surplusDeficitEl.style.color = "#333";
                }
            }
        }

        function validateForm() {
            const requiredFields = document.querySelectorAll("input[required], select[required], textarea[required]");
            let isValid = true;

            requiredFields.forEach((field) => {
                const formGroup = field.closest(".form-group");
                if (!field.value.trim()) {
                    if (formGroup) formGroup.classList.add("error");
                    isValid = false;
                } else {
                    if (formGroup) formGroup.classList.remove("error");
                }
            });

            // Event date validation (minimum 14 days from today)
            const eventDate = document.querySelector('input[name="Ev_Date"]');
            if (eventDate) {
                const today = new Date();
                const minDate = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000);

                if (eventDate.value && new Date(eventDate.value) < minDate) {
                    const formGroup = eventDate.closest(".form-group");
                    if (formGroup) formGroup.classList.add("error");
                    isValid = false;
                }
            }

            // Table validations
            const eventFlowRows = document.querySelectorAll("#eventFlowBody tr");
            const committeeRows = document.querySelectorAll("#committeeBody tr");
            const budgetRows = document.querySelectorAll("#budgetBody tr");

            if (eventFlowRows.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire("Missing Data", "Please add at least one event flow entry.", "error");
                } else {
                    alert("Please add at least one event flow entry.");
                }
                isValid = false;
            }

            if (committeeRows.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire("Missing Data", "Please add at least one committee member.", "error");
                } else {
                    alert("Please add at least one committee member.");
                }
                isValid = false;
            }

            if (budgetRows.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire("Missing Data", "Please add at least one budget entry.", "error");
                } else {
                    alert("Please add at least one budget entry.");
                }
                isValid = false;
            }

            return isValid;
        }

        function handleBack() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: "Are you sure?",
                    text: "Any unsaved changes will be lost.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, go back",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.history.back();
                    }
                });
            } else {
                if (confirm("Are you sure? Any unsaved changes will be lost.")) {
                    window.history.back();
                }
            }
        }

        function handleFormSubmit(e) {
            e.preventDefault();

            const totalHours = getTotalEventFlowHours();
            if (totalHours < 40) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: "Minimum 40 Hours Required",
                        text: `You only have ${totalHours.toFixed(1)} hours. Minimum 40 hours needed.`,
                    });
                } else {
                    alert(`Minimum 40 hours required. You only have ${totalHours.toFixed(1)} hours.`);
                }
                return;
            }

            if (!validateForm()) {
                return;
            }

            // SweetAlert confirmation
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: "Submit Proposal?",
                    text: "Once submitted, you can only modify it if rejected.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Submit",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {

                        // Show success message since we can't actually submit
                        Swal.fire({
                            icon: "success",
                            title: "Proposal Submitted!",
                            text: "Your proposal has been submitted successfully.",
                            confirmButtonText: "OK",
                        });
                    }
                });
            } else {
                if (confirm("Submit proposal? Once submitted, you can only modify it if rejected.")) {
                    alert("Proposal submitted successfully!");
                }
            }
        }

        function getTotalEventFlowHours() {
            const hourInputs = document.querySelectorAll('input[name="eventFlowHours[]"]');
            let total = 0;
            hourInputs.forEach((input) => {
                total += parseFloat(input.value) || 0;
            });
            return total;
        }
        document.getElementById("proposalForm").addEventListener("submit", function (e) {
            e.preventDefault(); // Stop normal form submit

            const form = this; // Capture the form reference

            Swal.fire({
                title: "Are you sure?",
                text: "You want to submit this proposal?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, submit it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // Submit if confirmed
                }
            });
        });
    </script>
</body>

</html>