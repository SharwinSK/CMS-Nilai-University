<?php
include('../../db/dbconfig.php');
$Ev_ID = $_GET['event_id'] ?? '';


$Status_ID = 7;
$isDisabled = '';
$eventName = $proposerName = $clubName = $objectives = '';
$eventFlows = [];
$meetings = [];
$committeeMembers = [];
$individualReports = [];

$challenges = '';
$recommendation = '';
$conclusion = '';
$budgetFileName = '';
$uploadedPhotos = [];

// Load from events table
$sql = "SELECT e.Ev_ID, e.Ev_Name, e.Ev_Objectives, s.Stu_Name, c.Club_Name, e.Status_ID 
        FROM events e
        JOIN student s ON e.Stu_ID = s.Stu_ID
        JOIN club c ON e.Club_ID = c.Club_ID
        WHERE e.Ev_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $Ev_ID);
$stmt->execute();
$stmt->bind_result($tmpEvID, $tmpName, $tmpObj, $tmpStu, $tmpClub, $tmpStatus);
if ($stmt->fetch()) {
    $eventName = $tmpName;
    $objectives = $tmpObj;
    $proposerName = $tmpStu;
    $clubName = $tmpClub;
    $Status_ID = $tmpStatus;
}
$stmt->close();

// Fix COCU claimers query
$sql = "SELECT Com_ID, Com_Name, Com_Position FROM committee WHERE Ev_ID = ? AND Com_COCUClaimers = 'yes'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $Ev_ID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $committeeMembers[] = $row;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Post Event Form</title>
    <link href="postevnt.css?v=<?= time() ?>" rel="stylesheet" />


</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Post Event Report</h1>
        </div>

        <div class="form-container">
            <form id="postEventForm" method="POST" enctype="multipart/form-data">
                <!-- Section 1: Event Information -->
                <div class="section">
                    <h2 class="section-title">1. Event Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="proposerName">Proposer Name <span class="required">*</span></label>
                            <input type="text" id="proposerName" name="proposerName"
                                value="<?= htmlspecialchars($proposerName) ?>" readonly disabled />
                        </div>
                        <div class="form-group">
                            <label for="eventName">Event Name <span class="required">*</span></label>
                            <input type="text" id="eventName" name="eventName"
                                value="<?= htmlspecialchars($eventName) ?>" readonly disabled />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clubName">Club Name <span class="required">*</span></label>
                            <input type="text" id="clubName" name="clubName" value="<?= htmlspecialchars($clubName) ?>"
                                readonly disabled />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="eventObjectives">Event Objectives</label>
                        <textarea id="eventObjectives" name="eventObjectives" readonly
                            disabled><?= htmlspecialchars($objectives) ?></textarea>
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
                            <tbody>
                                <?php if (!empty($eventFlows)): ?>
                                    <?php foreach ($eventFlows as $row): ?>
                                        <tr>
                                            <td>
                                                <input type="time" name="eventTime[]"
                                                    value="<?= htmlspecialchars($row['Flow_Time']) ?>" <?= $isDisabled ?>
                                                    required />
                                            </td>
                                            <td>
                                                <!-- REMOVED: pattern="[A-Za-z\s]+" and title="Only alphabetic characters allowed" -->
                                                <input type="text" name="eventDescription[]"
                                                    value="<?= htmlspecialchars($row['Flow_Desc']) ?>" <?= $isDisabled ?>
                                                    required />
                                            </td>
                                            <td>
                                                <?php if (empty($isDisabled)): ?>
                                                    <button type="button" class="btn btn-danger"
                                                        onclick="removeEventFlowRow(this)">🗑️</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Default empty row -->
                                    <tr>
                                        <td><input type="time" name="eventTime[]" required /></td>
                                        <!-- REMOVED: pattern="[A-Za-z\s]+" and title="Only alphabetic characters allowed" -->
                                        <td><input type="text" name="eventDescription[]" placeholder="Enter description"
                                                required /></td>
                                        <td>
                                            <?php if (empty($isDisabled)): ?>
                                                <button type="button" class="btn btn-danger"
                                                    onclick="removeEventFlowRow(this)">Delete</button>
                                            <?php endif; ?>
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

                    <div class="file-info"
                        style="margin-bottom: 15px; background: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                        📝 <strong>Instructions:</strong>
                        <ul style="margin: 5px 0 0 20px; color: #666;">
                            <li>Click "Add" button in Activity Description column to enter detailed activity description
                            </li>
                        </ul>
                    </div>
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
                                    <?php foreach ($meetings as $meeting): ?>
                                        <tr>
                                            <td><input type="date" name="meetingDate[]"
                                                    value="<?= htmlspecialchars($meeting['Meeting_Date']) ?>" <?= $isDisabled ?>>
                                            </td>
                                            <td><input type="time" name="meetingStartTime[]"
                                                    value="<?= htmlspecialchars($meeting['Start_Time']) ?>" <?= $isDisabled ?>>
                                            </td>
                                            <td><input type="time" name="meetingEndTime[]"
                                                    value="<?= htmlspecialchars($meeting['End_Time']) ?>" <?= $isDisabled ?>>
                                            </td>
                                            <td><input type="text" name="meetingLocation[]"
                                                    value="<?= htmlspecialchars($meeting['Location']) ?>"
                                                    pattern="[A-Za-z0-9\s]+" <?= $isDisabled ?>></td>
                                            <td>
                                                <?php if ($isDisabled): ?>
                                                    <button type="button" class="btn btn-secondary"
                                                        onclick="alert('<?= htmlspecialchars($meeting['Description']) ?>')">View</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-success" onclick="showAddModal(this)"
                                                        data-description="<?= htmlspecialchars($meeting['Description']) ?>">Add</button>
                                                    <button type="button" class="btn btn-secondary" onclick="showViewModal(this)"
                                                        data-description="<?= htmlspecialchars($meeting['Description']) ?>">View</button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$isDisabled): ?>
                                                    <button type="button" class="btn btn-danger"
                                                        onclick="removeMeetingRow(this)">Delete</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Default empty row if no existing data -->
                                    <tr>
                                        <td><input type="date" name="meetingDate[]" <?= $isDisabled ?>></td>
                                        <td><input type="time" name="meetingStartTime[]" <?= $isDisabled ?>></td>
                                        <td><input type="time" name="meetingEndTime[]" <?= $isDisabled ?>></td>
                                        <td><input type="text" name="meetingLocation[]" placeholder="location"
                                                pattern="[A-Za-z0-9\s]+" <?= $isDisabled ?>></td>
                                        <td>
                                            <?php if (!$isDisabled): ?>
                                                <button type="button" class="btn btn-success"
                                                    onclick="showAddModal(this)">Add</button>
                                                <button type="button" class="btn btn-secondary"
                                                    onclick="showViewModal(this)">View</button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$isDisabled): ?>
                                                <button type="button" class="btn btn-danger"
                                                    onclick="removeMeetingRow(this)">Delete</button>
                                            <?php endif; ?>
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
                        <input type="file" id="eventPhotos" name="eventPhotos[]" accept=".jpg,.jpeg,.png" multiple
                            <?= $isDisabled ?> />
                        <div class="photo-preview" id="photoPreview">
                            <?php if (!empty($uploadedPhotos)): ?>
                                <?php foreach ($uploadedPhotos as $photo): ?>
                                    <div class="photo-item">
                                        <img src="uploads/event_photos/<?= htmlspecialchars($photo['Photo_File']) ?>"
                                            alt="Event Photo" onclick="openModal(this)">
                                        <?php if (!$isDisabled): ?>
                                            <button class="remove-btn" onclick="removePhoto(this)">&times;</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="budgetStatement">
                            Upload Budget Statement & Receipt (PDF only, max 5MB)
                            <a href="../../assets/file/sampleStatement.xlsx" download="budgetSample.docx"
                                style="margin-left: 10px; color: #ac73ff; text-decoration: none; font-size: 14px;"
                                title="Download Sample Budget Statement">
                                📥 Download Sample
                            </a>
                        </label>
                        <input type="file" id="budgetStatement" name="budgetStatement" accept=".pdf" <?= $isDisabled ?> />
                        <div class="file-preview show" id="budgetPreview">
                            <span class="file-name"><?= htmlspecialchars($budgetFileName) ?></span>
                            <?php if (!empty($budgetFileName)): ?>
                                <a class="btn btn-secondary" target="_blank"
                                    href="uploads/budget_statements/<?= $budgetFileName ?>">View</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>



                <!-- Section 5: Challenges and Recommendations -->
                <div class="section">
                    <h2 class="section-title">5. Challenges and Recommendations</h2>

                    <div class="form-group">
                        <label for="challenges">Challenges and Difficulties</label>
                        <textarea id="challenges" name="challenges"
                            placeholder="Enter the Challenges & Difficulties during the event" <?php echo $isDisabled; ?>><?php echo htmlspecialchars($challenges); ?></textarea>

                    </div>

                    <div class="form-group">
                        <label for="recommendations">Recommendations</label>
                        <textarea id="recommendations" name="recommendations" placeholder="Enter your recommendations"
                            <?php echo $isDisabled; ?>><?= htmlspecialchars($recommendation) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="conclusion">Conclusion</label>
                        <textarea id="conclusion" name="conclusion" placeholder="Enter your conclusion" <?php echo $isDisabled; ?>><?= htmlspecialchars($conclusion) ?></textarea>
                    </div>
                </div>


                <!-- Section 6: Individual Report -->
                <div class="section">
                    <h2 class="section-title">
                        6. Individual Report (COCU Claimers)
                        <a href="../../assets/file/sampleIndividual.docx" download="individualReportSample.docx"
                            style="margin-left: 15px; color: #ac73ff; text-decoration: none; font-size: 14px;"
                            title="Download Sample Individual Report">
                            📥 Download Sample
                        </a>
                    </h2>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Position</th>
                                    <th>Upload Report (PDF)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($committeeMembers as $row): ?>
                                    <tr>
                                        <td><input type="text" value="<?= htmlspecialchars($row['Com_Name']) ?>" readonly />
                                        </td>
                                        <td><input type="text" value="<?= htmlspecialchars($row['Com_ID']) ?>" readonly />
                                        </td>
                                        <td><input type="text" value="<?= htmlspecialchars($row['Com_Position']) ?>"
                                                readonly /></td>
                                        <td>
                                            <input type="file" name="individualReport[<?= $row['Com_ID'] ?>]" accept=".pdf"
                                                <?= $isDisabled ?> />
                                            <?php if (!empty($individualReports[$row['Com_ID']])): ?>
                                                <div class="file-preview show">
                                                    <span
                                                        class="file-name"><?= htmlspecialchars($individualReports[$row['Com_ID']]) ?></span>
                                                    <button type="button" class="btn btn-secondary"
                                                        onclick="window.open('uploads/individual/<?= $individualReports[$row['Com_ID']] ?>', '_blank')">View</button>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>



                <!-- Navigation Buttons -->
                <div class="navigation-buttons" style="
              justify-content: space-between;
              align-items: center;
              gap: 10px;
            ">
                    <!-- Left side: Back button -->
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="goBack()">
                            Back
                        </button>
                    </div>

                    <!-- Right side: Preview + Next -->
                    <div style="display: flex; gap: 10px">
                        <button type="button" class="btn btn-secondary" onclick="previewForm()">
                            Preview
                        </button>
                        <button type="submit" class="btn btn-success">Next</button>
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
        <div style="
          background: white;
          padding: 20px;
          max-width: 600px;
          margin: 100px auto;
          border-radius: 10px;
          position: relative;
        ">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Description</h3>
            <textarea id="addDescriptionTextarea" placeholder="Enter description"
                style="width: 100%; height: 150px"></textarea>
            <div style="margin-top: 15px; text-align: right">
                <button class="btn btn-success" onclick="saveMeetingDescription()">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- View Description Modal -->
    <div id="viewDescriptionModal" class="modal">
        <div style="
          background: white;
          padding: 20px;
          max-width: 600px;
          margin: 100px auto;
          border-radius: 10px;
          position: relative;
        ">
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
    <div class="scroll-arrows">
        <button class="scroll-btn" onclick="scrollToTop()" title="Scroll to Top">
            ↑
        </button>
        <button class="scroll-btn" onclick="scrollToBottom()" title="Scroll to Bottom">
            ↓
        </button>
    </div>

    <script>
        // Event Flow Table Functions
        function addEventFlowRow() {
            const tableBody = document.querySelector("#eventFlowTable tbody");
            const row = document.createElement("tr");
            row.innerHTML = `
        <td><input type="time" name="eventTime[]" required></td>
        <td><input type="text" name="eventDescription[]" required></td>
        <td><button type="button" class="btn btn-danger" onclick="removeEventFlowRow(this)">Delete</button></td>
    `;
            tableBody.appendChild(row);
        }

        function removeEventFlowRow(button) {
            const row = button.closest("tr");
            row.remove();
        }

        // Meeting Functions
        function addMeetingRow() {
            const tableBody = document.querySelector("#meetingTable tbody");
            const row = document.createElement("tr");

            row.innerHTML = `
        <td><input type="date" name="meetingDate[]" required></td>
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
        }


        function addMeetingDescription(button) {
            const row = button.closest("tr");
            const description = row.querySelector(
                'textarea[name="meetingDescription[]"]'
            ).value;
            const date = row.querySelector('input[name="meetingDate[]"]').value;
            const startTime = row.querySelector(
                'input[name="meetingStartTime[]"]'
            ).value;
            const endTime = row.querySelector(
                'input[name="meetingEndTime[]"]'
            ).value;
            const location = row.querySelector(
                'input[name="meetingLocation[]"]'
            ).value;

            if (!date || !startTime || !endTime || !location) {
                alert(
                    "Please fill in all required fields (Date, Start Time, End Time, Location) before adding."
                );
                return;
            }

            alert("Meeting information added successfully!");
        }

        function viewMeetingDescription(button) {
            const row = button.closest("tr");
            const description = row.querySelector(
                'textarea[name="meetingDescription[]"]'
            ).value;
            const date = row.querySelector('input[name="meetingDate[]"]').value;
            const startTime = row.querySelector(
                'input[name="meetingStartTime[]"]'
            ).value;
            const endTime = row.querySelector(
                'input[name="meetingEndTime[]"]'
            ).value;
            const location = row.querySelector(
                'input[name="meetingLocation[]"]'
            ).value;

            const summary = `Date: ${date}\nStart Time: ${startTime}\nEnd Time: ${endTime}\nLocation: ${location}\nDescription: ${description}`;
            alert(summary);
        }

        function removeMeetingRow(button) {
            const row = button.closest("tr");
            row.remove();
        }

        // Photo Upload Functions
        document
            .getElementById("eventPhotos")
            .addEventListener("change", function (e) {
                const files = e.target.files;
                const preview = document.getElementById("photoPreview");

                if (files.length > 10) {
                    alert("Maximum 10 photos allowed");
                    e.target.value = "";
                    return;
                }

                preview.innerHTML = "";

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        const photoItem = document.createElement("div");
                        photoItem.className = "photo-item";
                        photoItem.innerHTML = `
                        <img src="${e.target.result}" alt="Event Photo" onclick="openModal(this)">
                        <button class="remove-btn" onclick="removePhoto(this, ${i})">&times;</button>
                    `;
                        preview.appendChild(photoItem);
                    };

                    reader.readAsDataURL(file);
                }
            });

        function removePhoto(button, index) {
            const photoItem = button.closest(".photo-item");
            photoItem.remove();
            // Note: In a real implementation, you'd need to update the file input
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

        // PDF Upload Functions
        document
            .getElementById("budgetStatement")
            .addEventListener("change", function (e) {
                const file = e.target.files[0];
                const preview = document.getElementById("budgetPreview");

                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert("File size must be less than 5MB");
                        e.target.value = "";
                        return;
                    }

                    preview.querySelector(".file-name").textContent = file.name;
                    preview.classList.add("show");
                }
            });

        function viewPDF(inputId) {
            const input = document.getElementById(inputId);
            const file = input.files[0];

            if (file) {
                const url = URL.createObjectURL(file);
                window.open(url, "_blank");
            }
        }

        // Individual Report Functions
        function addIndividualReportRow() {
            const tableBody = document.querySelector(
                "#individualReportTable tbody"
            );
            const row = document.createElement("tr");
            row.innerHTML = `
                <td><input type="text" name="committeeName[]" required></td>
                <td><input type="text" name="committeeId[]" required></td>
                <td><input type="text" name="position[]" required></td>
                <td>
                    <input type="file" name="individualReport[]" accept=".pdf" style="margin-bottom: 5px;">
                    <div class="file-preview">
                        <span class="file-name"></span>
                        <button type="button" class="btn btn-secondary" onclick="viewIndividualReport(this)" style="font-size: 12px; padding: 5px 10px;">View</button>
                    </div>
                </td>
                <td><button type="button" class="btn btn-danger" onclick="removeIndividualReportRow(this)">Delete</button></td>
            `;
            tableBody.appendChild(row);

            // Add event listener for the new file input
            const fileInput = row.querySelector('input[type="file"]');
            fileInput.addEventListener("change", function (e) {
                const file = e.target.files[0];
                const preview = this.parentNode.querySelector(".file-preview");

                if (file) {
                    preview.querySelector(".file-name").textContent = file.name;
                    preview.style.display = "block";
                }
            });
        }

        function removeIndividualReportRow(button) {
            const row = button.closest("tr");
            row.remove();
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
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to go back? Unsaved changes will be lost.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ac73ff',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, go back!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        }
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }

        // Updated Preview Function
        function previewForm() {
            showPreviewMessage();
        }

        function showPreviewMessage() {
            Swal.fire({
                icon: 'info',
                title: 'Feature Coming Soon!',
                text: 'Currently this feature hasn\'t been completed yet, for future it will be. Thank you #sharwinsk',
                confirmButtonText: 'Understood',
                confirmButtonColor: '#ac73ff'
            });
        }



        // Add event listener for individual report file inputs
        document
            .querySelectorAll('input[name="individualReport[]"]')
            .forEach(function (input) {
                input.addEventListener("change", function (e) {
                    const file = e.target.files[0];
                    const preview = this.parentNode.querySelector(".file-preview");

                    if (file) {
                        preview.querySelector(".file-name").textContent = file.name;
                        preview.style.display = "block";
                    }
                });
            });

        document.addEventListener("input", function (e) {
            // Only keep validation for location fields (meeting table)
            if (e.target.pattern === "[A-Za-z0-9\\s]+") {
                e.target.value = e.target.value.replace(/[^A-Za-z0-9\s]/g, "");
            }
        });

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

                parentCell.querySelectorAll("button").forEach((btn) => {
                    btn.setAttribute("data-description", newDesc);
                });

                // 🔥 Add a hidden field right now
                const row = currentAddButton.closest("tr");
                let hidden = row.querySelector("input[type=hidden][name^='meetingDescription']");
                if (!hidden) {
                    hidden = document.createElement("input");
                    hidden.type = "hidden";
                    hidden.name = `meetingDescription[${row.rowIndex - 1}]`;
                    row.appendChild(hidden);
                }
                hidden.value = newDesc;

                closeAddModal();
            }
        }


        function closeAddModal() {
            document.getElementById("addDescriptionModal").style.display = "none";
        }

        function showViewModal(button) {
            const desc =
                button.getAttribute("data-description") || "No description provided.";
            document.getElementById("viewDescriptionText").textContent = desc;
            document.getElementById("viewDescriptionModal").style.display = "block";
        }

        function closeViewModal() {
            document.getElementById("viewDescriptionModal").style.display = "none";
        }

        document.getElementById("postEventForm").addEventListener("submit", function (e) {
            e.preventDefault();

            Swal.fire({
                title: "Submit Post Event Report?",
                text: "You will not be able to modify it until reviewed.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, submit it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById("postEventForm");
                    const formData = new FormData(form);
                    document.querySelectorAll("#meetingTable tbody tr").forEach((row, i) => {
                        const desc = row.querySelector("button[data-description]")?.getAttribute("data-description") || "";
                        const hidden = document.createElement("input");
                        hidden.type = "hidden";
                        hidden.name = `meetingDescription[${i}]`;
                        hidden.value = desc;
                        document.getElementById("postEventForm").appendChild(hidden);
                    });

                    // Debug
                    const debugFormData = new FormData(document.getElementById("postEventForm"));
                    for (let pair of debugFormData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }


                    // Add Ev_ID manually
                    formData.append("event_id", "<?= $Ev_ID ?>");

                    fetch('storePostSession.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "Saved!",
                                    text: "Redirecting to mark attendance...",
                                    icon: "success"
                                }).then(() => {
                                    window.location.href = `markAttendance.php?Ev_ID=<?= $Ev_ID ?>`;
                                });
                            } else {
                                Swal.fire("Error", "Failed to store data in session", "error");
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire("Error", "Something went wrong", "error");
                        });

                }
            });
        });

    </script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>

</html>