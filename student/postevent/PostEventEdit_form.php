<?php
session_start();
include('../../db/dbconfig.php');

$mode = $_GET['mode'] ?? '';
$rep_id = $_GET['rep_id'] ?? '';

if (!in_array($mode, ['edit', 'modify']) || empty($rep_id)) {
    die("Invalid access.");
}

// Get status from eventpostmortem
$stmt = $conn->prepare("SELECT Ev_ID, Status_ID FROM eventpostmortem WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Post-event report not found.");
}

$row = $result->fetch_assoc();
$ev_id = $row['Ev_ID'];
$status_id = (int) $row['Status_ID'];

// Access control
if (($mode === 'edit' && $status_id !== 6) || ($mode === 'modify' && $status_id !== 7)) {
    die("Not allowed to $mode at this status.");
}

$proposerName = '';
$eventName = '';
$clubName = '';
$objectives = '';

$stmt = $conn->prepare("
    SELECT s.Stu_Name, e.Ev_Name, c.Club_Name, e.Ev_Objectives
    FROM events e
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    WHERE e.Ev_ID = ?
");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$stmt->bind_result($proposerName, $eventName, $clubName, $objectives);
$stmt->fetch();
$stmt->close();

$eventFlows = [];

$stmt = $conn->prepare("SELECT EvFlow_Time, EvFlow_Description FROM eventflows WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $eventFlows[] = $row;
}
$stmt->close();

$meetings = [];
$attendance = [];

// 1. Fetch meetings
$stmt = $conn->prepare("SELECT * FROM posteventmeeting WHERE Rep_ID = ? ORDER BY Meeting_ID");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$meetingResult = $stmt->get_result();

while ($row = $meetingResult->fetch_assoc()) {
    $meetings[] = $row;
}
$stmt->close();

// 2. Fetch attendance
$stmt = $conn->prepare("SELECT * FROM committeeattendance WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$attendanceResult = $stmt->get_result();

while ($row = $attendanceResult->fetch_assoc()) {
    $attendance[$row['Com_ID']][$row['Meeting_ID']] = $row['Attendance_Status'];
}
$stmt->close();

$photoFilenames = [];

// Fetch photo JSON string
$stmt = $conn->prepare("SELECT rep_photo FROM eventpostmortem WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$stmt->bind_result($photoJSON);
$stmt->fetch();
$stmt->close();

// Decode JSON into PHP array
$photoFilenames = json_decode($photoJSON, true);
$photoFilenames = is_array($photoFilenames) ? $photoFilenames : [];

$budgetStatementFile = null;

$stmt = $conn->prepare("SELECT statement FROM budgetsummary WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$stmt->bind_result($budgetStatementFile);
$stmt->fetch();
$stmt->close();

$challenges = '';
$recommendation = '';
$conclusion = '';

// Fetch 3 columns from eventpostmortem
$stmt = $conn->prepare("
    SELECT Rep_ChallengesDifficulties, Rep_recomendation, Rep_Conclusion 
    FROM eventpostmortem 
    WHERE Rep_ID = ?
");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$stmt->bind_result($challenges, $recommendation, $conclusion);
$stmt->fetch();
$stmt->close();

$individualReports = [];

$stmt = $conn->prepare("SELECT Com_ID, IR_File FROM individualreport WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $individualReports[$row['Com_ID']] = $row['IR_File'];
}

$stmt->close();

$committeeMembers = [];

$stmt = $conn->prepare("SELECT Com_ID, Com_Name, Com_Position, Com_COCUClaimers FROM committee WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $committeeMembers[] = $row;
}

$stmt->close();

$attendanceData = [];

$stmt = $conn->prepare("SELECT Meeting_ID, Com_ID, Attendance_Status FROM committeeattendance WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $com_id = $row['Com_ID'];
    $meeting_id = $row['Meeting_ID'];
    $status = $row['Attendance_Status'];
    $attendanceData[$com_id][$meeting_id] = $status;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Post Event Form</title>
    <link href="posteventedit.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Post Event Report</h1>
        </div>

        <div class="form-container">
            <form id="postEventForm" method="POST" enctype="multipart/form-data" action="PosteventUpdate.php">
                <input type="hidden" name="rep_id" value="<?= htmlspecialchars($rep_id) ?>">
                <input type="hidden" name="ev_id" value="<?= htmlspecialchars($ev_id) ?>">
                <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">

                <!-- Section 1: Event Information -->
                <div class="section">
                    <h2 class="section-title">1. Event Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="proposerName">Proposer Name <span class="required">*</span></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($proposerName) ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="eventName">Event Name <span class="required">*</span></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eventName) ?>"
                                readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clubName">Club Name <span class="required">*</span></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($clubName) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="eventObjectives">Event Objectives</label>
                        <textarea class="form-control" readonly><?= htmlspecialchars($objectives) ?></textarea>
                    </div>
                </div>

                <!-- Section 2: Event Flow -->
                <div class="section">
                    <h2 class="section-title">2. Event Flow (Event Day)</h2>
                    <div class="table-container">
                        <table id="eventFlowTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="eventFlowTableBody">
                                <?php if (!empty($eventFlows)): ?>
                                    <?php foreach ($eventFlows as $index => $flow): ?>
                                        <tr>
                                            <td>
                                                <input type="time" name="event_flows[<?= $index ?>][time]" class="form-control"
                                                    value="<?= htmlspecialchars($flow['EvFlow_Time']) ?>" required />
                                            </td>
                                            <td>
                                                <input type="text" name="event_flows[<?= $index ?>][description]"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($flow['EvFlow_Description']) ?>" required />
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm removeFlowRow">🗑</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td><input type="time" name="event_flows[0][time]" class="form-control" required />
                                        </td>
                                        <td><input type="text" name="event_flows[0][description]" class="form-control"
                                                required /></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm removeFlowRow">🗑</button>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addEventFlowRow()">
                        Add Row
                    </button>
                </div>

                <!-- Section 3: Meeting -->
                <div class="section">
                    <h2 class="section-title">3. Meeting</h2>
                    <div class="table-container">
                        <table id="meetingTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Location</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($meetings)): ?>
                                    <?php foreach ($meetings as $index => $meet): ?>
                                        <tr data-meeting-id="<?= $meet['Meeting_ID'] ?>">
                                            <td><input type="date" name="meetingDate[]"
                                                    value="<?= htmlspecialchars($meet['Meeting_Date']) ?>" required
                                                    onchange="updateAttendanceTable()"></td>
                                            <td><input type="time" name="meetingStartTime[]"
                                                    value="<?= htmlspecialchars($meet['Start_Time']) ?>" required></td>
                                            <td><input type="time" name="meetingEndTime[]"
                                                    value="<?= htmlspecialchars($meet['End_Time']) ?>" required></td>
                                            <td><input type="text" name="meetingLocation[]"
                                                    value="<?= htmlspecialchars($meet['Meeting_Location']) ?>"
                                                    pattern="[A-Za-z0-9\s]+"
                                                    title="Only alphabetic characters and numbers allowed" required></td>
                                            <td>
                                                <button type="button" class="btn btn-success" onclick="showAddModal(this)"
                                                    data-description="<?= htmlspecialchars($meet['Meeting_Description']) ?>">Add</button>
                                                <button type="button" class="btn btn-secondary" onclick="showViewModal(this)"
                                                    data-description="<?= htmlspecialchars($meet['Meeting_Description']) ?>">View</button>
                                                <input type="hidden" name="meeting_descriptions[]"
                                                    value="<?= htmlspecialchars($meet['Meeting_Description']) ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger"
                                                    onclick="removeMeetingRow(this)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr data-meeting-id="new-1">
                                        <td><input type="date" name="meetingDate[]" required
                                                onchange="updateAttendanceTable()"></td>
                                        <td><input type="time" name="meetingStartTime[]" required></td>
                                        <td><input type="time" name="meetingEndTime[]" required></td>
                                        <td><input type="text" name="meetingLocation[]" pattern="[A-Za-z0-9\s]+"
                                                title="Only alphabetic characters and numbers allowed" required></td>
                                        <td>
                                            <button type="button" class="btn btn-success" onclick="showAddModal(this)"
                                                data-description="">Add</button>
                                            <button type="button" class="btn btn-secondary" onclick="showViewModal(this)"
                                                data-description="">View</button>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger"
                                                onclick="removeMeetingRow(this)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addMeetingRow()">
                        Add Meeting
                    </button>
                </div>

                <!-- Section 4: Upload -->
                <div class="section">
                    <h2 class="section-title">4. Upload</h2>
                    <div class="form-group">
                        <label for="eventPhotos">Upload Event Photos (Maximum 10 photos, JPG/PNG only)</label>
                        <input type="file" id="eventPhotos" name="eventPhotos[]" accept=".jpg,.jpeg,.png" multiple />

                        <!-- Display existing photos -->
                        <div class="photo-preview" id="photoPreview">
                            <?php if (!empty($photoFilenames)): ?>
                                <?php foreach ($photoFilenames as $index => $photo): ?>
                                    <div class="photo-item existing-photo" data-filename="<?= htmlspecialchars($photo) ?>">
                                        <img src="../../uploads/photos/<?= htmlspecialchars($photo) ?>" alt="Event Photo"
                                            onclick="openModal(this)">
                                        <button type="button" class="remove-btn" onclick="removeExistingPhoto(this)"
                                            data-index="<?= $index ?>">&times;</button>
                                        <input type="hidden" name="existing_photos[]" value="<?= htmlspecialchars($photo) ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="budgetStatement">Upload Budget Statement & Receipt (PDF only, max 5MB)</label>
                        <input type="file" id="budgetStatement" name="budgetStatement" accept=".pdf" />

                        <!-- Display existing budget statement -->
                        <?php if (!empty($budgetStatementFile)): ?>
                            <div class="file-preview show" id="budgetPreview">
                                <span class="file-name">Current file: <?= basename($budgetStatementFile) ?></span>
                                <a href="../../uploads/statements/<?= basename($budgetStatementFile) ?>" target="_blank"
                                    class="btn btn-secondary btn-sm">View Current</a>
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="removeExistingBudget()">Remove</button>
                                <input type="hidden" name="existing_budget"
                                    value="<?= htmlspecialchars($budgetStatementFile) ?>" id="existingBudgetInput">
                            </div>
                        <?php else: ?>
                            <div class="file-preview" id="budgetPreview">
                                <span class="file-name"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section 5: Challenges and Recommendations -->
                <div class="section">
                    <h2 class="section-title">5. Challenges and Recommendations</h2>

                    <div class="form-group">
                        <label for="challenges">Challenges and Difficulties</label>
                        <textarea id="challenges" name="challenges"><?= htmlspecialchars($challenges) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="recommendations">Recommendations</label>
                        <textarea id="recommendations"
                            name="recommendations"><?= htmlspecialchars($recommendation) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="conclusion">Conclusion</label>
                        <textarea id="conclusion" name="conclusion"><?= htmlspecialchars($conclusion) ?></textarea>
                    </div>
                </div>

                <!-- Section 6: COCU Claimer Attendance -->
                <div class="section">
                    <h2 class="section-title">6. COCU Claimer Attendance</h2>
                    <div class="table-container">
                        <table class="table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>COCU Claimer Name</th>
                                    <th id="attendanceHeader">Meeting Dates</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                <?php foreach ($committeeMembers as $member): ?>
                                    <?php if ($member['Com_COCUClaimers'] === 'yes'): ?>
                                        <tr data-com-id="<?= $member['Com_ID'] ?>">
                                            <td><strong><?= htmlspecialchars($member['Com_Name']) ?></strong></td>
                                            <td class="attendance-cell">
                                                <?php if (!empty($meetings)): ?>
                                                    <?php foreach ($meetings as $meeting): ?>
                                                        <?php
                                                        $meetingID = $meeting['Meeting_ID'];
                                                        $status = $attendanceData[$member['Com_ID']][$meetingID] ?? '';
                                                        ?>
                                                        <div class="attendance-row" data-meeting-id="<?= $meetingID ?>"
                                                            style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 5px;">
                                                            <span style="font-weight: bold; min-width: 80px;">
                                                                <?= htmlspecialchars(date('M d', strtotime($meeting['Meeting_Date']))) ?>:
                                                            </span>
                                                            <label style="display: flex; align-items: center; gap: 5px; margin: 0;">
                                                                <input type="radio"
                                                                    name="attendance[<?= $member['Com_ID'] ?>][<?= $meetingID ?>]"
                                                                    value="present" <?= ($status === 'present') ? 'checked' : '' ?>
                                                                    style="width: auto;">
                                                                <span style="color: #27ae60;">Present</span>
                                                            </label>
                                                            <label style="display: flex; align-items: center; gap: 5px; margin: 0;">
                                                                <input type="radio"
                                                                    name="attendance[<?= $member['Com_ID'] ?>][<?= $meetingID ?>]"
                                                                    value="absent" <?= ($status === 'absent') ? 'checked' : '' ?>
                                                                    style="width: auto;">
                                                                <span style="color: #e74c3c;">Absent</span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span style="color: #666; font-style: italic;">No meetings scheduled</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="color: #666; font-size: 14px; margin-top: 10px;">
                        <em>Note: Attendance records will be automatically updated when you add or remove meetings in
                            Section 3.</em>
                    </p>
                </div>

                <!-- Section 7: Individual Report -->
                <div class="section">
                    <h2 class="section-title">7. Individual Report (COCU Claimers)</h2>
                    <div class="table-container">
                        <table class="table" id="individualReportTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Position</th>
                                    <th>Upload Report (PDF)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($committeeMembers as $member): ?>
                                    <?php if ($member['Com_COCUClaimers'] === 'yes'): ?>
                                        <?php
                                        $comId = $member['Com_ID'];
                                        $file = $individualReports[$comId] ?? '';
                                        ?>
                                        <tr>
                                            <td><input type="text" name="committeeName[]"
                                                    value="<?= htmlspecialchars($member['Com_Name']) ?>" readonly /></td>
                                            <td><input type="text" name="committeeId[]" value="<?= htmlspecialchars($comId) ?>"
                                                    readonly /></td>
                                            <td><input type="text" name="position[]"
                                                    value="<?= htmlspecialchars($member['Com_Position']) ?>" readonly /></td>
                                            <td>
                                                <input type="file" name="individualReport[<?= $comId ?>]" accept=".pdf" />
                                                <?php if (!empty($file)): ?>
                                                    <div class="file-preview show">
                                                        <span class="file-name">Current: <?= basename($file) ?></span>
                                                        <a href="../../uploads/individualreports/<?= basename($file) ?>"
                                                            target="_blank" class="btn btn-secondary btn-sm">View</a>

                                                        <button type="button" class="btn btn-danger btn-sm"
                                                            onclick="removeIndividualReport(this, '<?= $comId ?>')">Remove</button>
                                                        <input type="hidden" name="existing_individual_report[<?= $comId ?>]"
                                                            value="<?= htmlspecialchars($file) ?>" class="existing-report-input">
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Delete button removed -->
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="navigation-buttons">
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="goBack()">
                            Back
                        </button>
                    </div>
                    <div style="display: flex; gap: 10px">
                        <button type="button" class="btn btn-secondary" onclick="previewForm()">
                            Preview
                        </button>
                        <button type="submit" class="btn btn-success">Submit Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for image preview -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage" />
    </div>

    <!-- Add Description Modal -->
    <div id="addDescriptionModal" class="modal">
        <div class="modal-dialog">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Description</h3>
            <textarea id="addDescriptionTextarea" style="width: 100%; height: 150px"
                placeholder="Enter meeting description..."></textarea>
            <div style="margin-top: 15px; text-align: right">
                <button class="btn btn-success" onclick="saveMeetingDescription()">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- View Description Modal -->
    <div id="viewDescriptionModal" class="modal">
        <div class="modal-dialog">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <h3>Meeting Description</h3>
            <div id="viewDescriptionText" style="
                white-space: pre-wrap;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 5px;
                color: #333;
                margin-top: 10px;
            "></div>
        </div>
    </div>

    <script>
        // Global variables to track meetings and attendance
        let meetingCounter = <?= count($meetings) ?>;
        let removedPhotos = [];
        let existingAttendance = <?= json_encode($attendanceData) ?>;
        let committeeMembers = <?= json_encode($committeeMembers) ?>;

        // Event Flow Table Functions
        function addEventFlowRow() {
            const tableBody = document.querySelector("#eventFlowTable tbody");
            const rowCount = tableBody.rows.length; // count rows to get next index
            const row = document.createElement("tr");

            row.innerHTML = `
        <td><input type="time" name="event_flows[${rowCount}][time]" class="form-control" required></td>
        <td><input type="text" name="event_flows[${rowCount}][description]" class="form-control" required></td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm removeFlowRow">🗑</button>
        </td>
    `;

            tableBody.appendChild(row);
        }


        // Event delegation for remove flow row buttons
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('removeFlowRow')) {
                const row = e.target.closest("tr");
                row.remove();
            }
        });

        // Meeting Functions
        function addMeetingRow() {
            meetingCounter++;
            const tableBody = document.querySelector("#meetingTable tbody");
            const row = document.createElement("tr");
            const newMeetingId = `new-${meetingCounter}`;

            row.setAttribute('data-meeting-id', newMeetingId);
            row.innerHTML = `
                <td><input type="date" name="meetingDate[]" required onchange="updateAttendanceTable()"></td>
                <td><input type="time" name="meetingStartTime[]" required></td>
                <td><input type="time" name="meetingEndTime[]" required></td>
                <td><input type="text" name="meetingLocation[]" pattern="[A-Za-z0-9\\s]+" title="Only alphabetic characters and numbers allowed" required></td>
                <td>
                    <button type="button" class="btn btn-success" onclick="showAddModal(this)" data-description="">Add</button>
                    <button type="button" class="btn btn-secondary" onclick="showViewModal(this)" data-description="">View</button>
                </td>
                <td>
                    <button type="button" class="btn btn-danger" onclick="removeMeetingRow(this)">Delete</button>
                </td>
            `;

            tableBody.appendChild(row);
            updateAttendanceTable();
        }

        function removeMeetingRow(button) {
            const row = button.closest("tr");
            const meetingId = row.getAttribute('data-meeting-id');

            // Remove attendance records for this meeting
            const attendanceRows = document.querySelectorAll(`[data-meeting-id="${meetingId}"]`);
            attendanceRows.forEach(attendanceRow => {
                if (attendanceRow.classList.contains('attendance-row')) {
                    attendanceRow.remove();
                }
            });

            row.remove();
            updateAttendanceTable();
        }

        // Photo Upload Functions
        document.getElementById("eventPhotos").addEventListener("change", function (e) {
            const files = e.target.files;
            const preview = document.getElementById("photoPreview");
            const existingPhotos = preview.querySelectorAll('.existing-photo').length;

            if (existingPhotos + files.length > 10) {
                alert(`Maximum 10 photos allowed. You currently have ${existingPhotos} existing photos.`);
                e.target.value = "";
                return;
            }

            // Remove any previously added new photos
            const newPhotos = preview.querySelectorAll('.new-photo');
            newPhotos.forEach(photo => photo.remove());

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const photoItem = document.createElement("div");
                    photoItem.className = "photo-item new-photo";
                    photoItem.innerHTML = `
                        <img src="${e.target.result}" alt="Event Photo" onclick="openModal(this)">
                        <button type="button" class="remove-btn" onclick="removeNewPhoto(this, ${i})">&times;</button>
                    `;
                    preview.appendChild(photoItem);
                };

                reader.readAsDataURL(file);
            }
        });

        function removeExistingPhoto(button) {
            const photoItem = button.closest(".photo-item");
            const filename = photoItem.getAttribute('data-filename');

            // Add to removed photos array
            removedPhotos.push(filename);

            // Create hidden input to track removed photos
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'removed_photos[]';
            hiddenInput.value = filename;
            document.getElementById('postEventForm').appendChild(hiddenInput);

            photoItem.remove();
        }

        function removeNewPhoto(button, index) {
            const photoItem = button.closest(".photo-item");
            photoItem.remove();

            // Reset file input to remove the selected file
            document.getElementById("eventPhotos").value = "";
        }

        function openModal(img) {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            modal.style.display = "block";
            modalImg.src = img.src;
        }

        function closeModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Budget Statement Functions
        function removeExistingBudget() {
            const budgetPreview = document.getElementById("budgetPreview");
            const existingBudgetInput = document.getElementById("existingBudgetInput");

            if (existingBudgetInput) {
                existingBudgetInput.remove();
            }

            // Create hidden input to track removed budget
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'remove_existing_budget';
            hiddenInput.value = '1';
            document.getElementById('postEventForm').appendChild(hiddenInput);

            budgetPreview.innerHTML = '<span class="file-name"></span>';
            budgetPreview.classList.remove('show');
        }

        // PDF Upload Functions
        document.getElementById("budgetStatement").addEventListener("change", function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById("budgetPreview");

            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert("File size must be less than 5MB");
                    e.target.value = "";
                    return;
                }

                preview.innerHTML = `<span class="file-name">${file.name}</span>`;
                preview.classList.add("show");
            }
        });

        // COCU Claimer Attendance Functions
        function updateAttendanceTable() {
            const meetingRows = document.querySelectorAll('#meetingTable tbody tr');
            const attendanceTableBody = document.getElementById('attendanceTableBody');

            // Collect all meeting data
            const meetings = [];
            meetingRows.forEach((row, index) => {
                const dateInput = row.querySelector('input[name="meetingDate[]"]');
                const meetingId = row.getAttribute('data-meeting-id');

                if (dateInput && dateInput.value && meetingId) {
                    meetings.push({
                        id: meetingId,
                        date: dateInput.value,
                        index: index
                    });
                }
            });

            // Update each COCU claimer row
            const claimerRows = attendanceTableBody.querySelectorAll('tr[data-com-id]');
            claimerRows.forEach(row => {
                const comId = row.getAttribute('data-com-id');
                const attendanceCell = row.querySelector('.attendance-cell');

                if (meetings.length === 0) {
                    attendanceCell.innerHTML = '<span style="color: #666; font-style: italic;">No meetings scheduled</span>';
                } else {
                    let attendanceHtml = '';

                    meetings.forEach(meeting => {
                        const formattedDate = new Date(meeting.date).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                        });

                        // Check if attendance exists for this meeting
                        let existingStatus = '';
                        if (existingAttendance[comId] && existingAttendance[comId][meeting.id]) {
                            existingStatus = existingAttendance[comId][meeting.id];
                        } else {
                            // For new meetings, preserve any currently selected radio buttons
                            const currentRadio = document.querySelector(`input[name="attendance[${comId}][${meeting.id}]"]:checked`);
                            if (currentRadio) {
                                existingStatus = currentRadio.value;
                            }
                        }

                        attendanceHtml += `
                            <div class="attendance-row" data-meeting-id="${meeting.id}"
                                style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 5px;">
                                <span style="font-weight: bold; min-width: 80px;">${formattedDate}:</span>
                                <label style="display: flex; align-items: center; gap: 5px; margin: 0;">
                                    <input type="radio" name="attendance[${comId}][${meeting.id}]" value="present" 
                                        ${existingStatus === 'present' ? 'checked' : ''} style="width: auto;">
                                    <span style="color: #27ae60;">Present</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; margin: 0;">
                                    <input type="radio" name="attendance[${comId}][${meeting.id}]" value="absent" 
                                        ${existingStatus === 'absent' ? 'checked' : ''} style="width: auto;">
                                    <span style="color: #e74c3c;">Absent</span>
                                </label>
                            </div>
                        `;
                    });

                    attendanceCell.innerHTML = attendanceHtml;
                }
            });
        }

        // Individual Report Functions
        function removeIndividualReport(button, comId) {
            const row = button.closest('td');
            const existingReportInput = row.querySelector('.existing-report-input');

            if (existingReportInput) {
                // Create hidden input to track removed report
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'removed_individual_reports[]';
                hiddenInput.value = comId;
                document.getElementById('postEventForm').appendChild(hiddenInput);

                // Remove the preview
                const filePreview = row.querySelector('.file-preview');
                filePreview.remove();
            }
        }

        function removeIndividualReportRow(button) {
            // Function removed - no longer needed
        }

        function viewIndividualReport(button) {
            const row = button.closest("tr");
            const fileInput = row.querySelector('input[type="file"]');
            const file = fileInput.files[0];

            if (file) {
                const url = URL.createObjectURL(file);
                window.open(url, "_blank");
            } else {
                alert("No file uploaded for this committee member.");
            }
        }

        // Navigation Functions
        function goBack() {
            if (confirm("Are you sure you want to go back? Unsaved changes will be lost.")) {
                window.history.back();
            }
        }

        function previewForm() {
            alert("Preview functionality - This would show a formatted preview of all entered data.");
        }

        // Meeting Description Modal Functions
        let currentAddButton = null;

        function showAddModal(button) {
            currentAddButton = button;
            const existingDesc = button.getAttribute("data-description") || "";
            document.getElementById("addDescriptionTextarea").value = existingDesc;
            document.getElementById("addDescriptionModal").style.display = "block";
        }

        function saveMeetingDescription() {
            if (currentAddButton) {
                const newDesc = document.getElementById("addDescriptionTextarea").value;
                const parentCell = currentAddButton.parentNode;

                // Update buttons
                parentCell.querySelectorAll("button").forEach((btn) => {
                    btn.setAttribute("data-description", newDesc);
                });

                // Update or insert hidden input
                let hiddenInput = parentCell.querySelector('input[name="meeting_descriptions[]"]');
                if (!hiddenInput) {
                    hiddenInput = document.createElement("input");
                    hiddenInput.type = "hidden";
                    hiddenInput.name = "meeting_descriptions[]";
                    parentCell.appendChild(hiddenInput);
                }

                hiddenInput.value = newDesc;

                closeAddModal();
                alert("Meeting description saved successfully!");
            }
        }

        function closeAddModal() {
            document.getElementById("addDescriptionModal").style.display = "none";
        }

        function showViewModal(button) {
            const desc = button.getAttribute("data-description") || "No description provided.";
            document.getElementById("viewDescriptionText").textContent = desc;
            document.getElementById("viewDescriptionModal").style.display = "block";
        }

        function closeViewModal() {
            document.getElementById("viewDescriptionModal").style.display = "none";
        }

        // Form Submission
        document.getElementById("postEventForm").addEventListener("submit", function (e) {
            e.preventDefault();

            if (confirm("Submit Post Event Report? Please review all information before submitting.")) {
                // Here you would normally submit the form
                // For now, just show success message
                alert("Form submitted successfully! In a real application, this would be processed by the server.");

                // Uncomment the next line to actually submit the form
                this.submit();
            }
        });

        // Input validation for alphabetic only fields
        document.addEventListener("input", function (e) {
            if (e.target.pattern === "[A-Za-z\\s]+") {
                e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
            }
            if (e.target.pattern === "[A-Za-z0-9\\s]+") {
                e.target.value = e.target.value.replace(/[^A-Za-z0-9\s]/g, "");
            }
        });

        // Add event listeners for individual report file inputs
        document.addEventListener("change", function (e) {
            if (e.target.matches('input[name^="individualReport"]')) {
                const file = e.target.files[0];
                const row = e.target.closest('td');
                let preview = row.querySelector(".file-preview");

                if (file) {
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'file-preview show';
                        preview.innerHTML = '<span class="file-name"></span>';
                        e.target.parentNode.appendChild(preview);
                    }

                    preview.querySelector(".file-name").textContent = file.name;
                    preview.classList.add("show");
                }
            }
        });

        // Initialize attendance table on page load
        document.addEventListener('DOMContentLoaded', function () {
            updateAttendanceTable();
        });

        // Close modals when clicking outside
        window.addEventListener("click", function (e) {
            const imageModal = document.getElementById("imageModal");
            const addModal = document.getElementById("addDescriptionModal");
            const viewModal = document.getElementById("viewDescriptionModal");

            if (e.target === imageModal) {
                closeModal();
            }
            if (e.target === addModal) {
                closeAddModal();
            }
            if (e.target === viewModal) {
                closeViewModal();
            }
        });
    </script>
</body>

</html>