<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Proposal Form - Nilai University CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #aca8ff 0%, #e8e6ff 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #ac73ff, #8b5cf6);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9ff;
            border-radius: 10px;
            border-left: 5px solid #ac73ff;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .section-title {
            color: #ac73ff;
            font-size: 1.8em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ac73ff;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: "";
            width: 20px;
            height: 20px;
            background: #ac73ff;
            border-radius: 50%;
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-col {
            flex: 1;
            min-width: 250px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #ac73ff;
            box-shadow: 0 0 0 3px rgba(172, 168, 255, 0.1);
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .upload-area {
            border: 2px dashed #ac73ff;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .upload-area:hover {
            background: #aca8ff;
            color: white;
            transform: translateY(-2px);
        }

        .upload-area.dragover {
            background: #ac73ff;
            color: white;
            transform: scale(1.02);
        }

        .file-info {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #ac73ff;
            color: white;
            font-weight: 600;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            margin: 5px;
        }

        .btn-primary {
            background: #ac73ff;
            color: white;
        }

        .btn-primary:hover {
            background: #8b5cf6;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-add {
            background: #27ae60;
            color: white;
            margin-bottom: 20px;
        }

        .btn-add:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 10px;
        }

        .form-actions .btn {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .budget-summary {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .budget-summary h4 {
            color: #27ae60;
            margin-bottom: 10px;
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .form-col {
                min-width: 100%;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .table-container {
                font-size: 14px;
            }
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: #e74c3c;
        }

        .form-group.error .error-message {
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Proposal Form</h1>
            <p>Nilai University Content Management System</p>
        </div>

        <div class="form-container">
            <form id="proposalForm">
                <!-- Section 1: Student Information -->
                <div class="section">
                    <h2 class="section-title">Student Information</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="studentName" class="required">Student Name</label>
                                <input type="text" id="studentName" name="studentName" required />
                                <div class="error-message">Please enter student name</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="club" class="required">Club</label>
                                <select id="club" name="club" required>
                                    <option value="">Select Club</option>
                                    <option value="Computer Science Club">
                                        Computer Science Club
                                    </option>
                                    <option value="Business Club">Business Club</option>
                                    <option value="Engineering Society">
                                        Engineering Society
                                    </option>
                                    <option value="Arts & Design Club">
                                        Arts & Design Club
                                    </option>
                                    <option value="Sports Club">Sports Club</option>
                                    <option value="Debate Society">Debate Society</option>
                                    <option value="Music Club">Music Club</option>
                                    <option value="Photography Club">Photography Club</option>
                                </select>
                                <div class="error-message">Please select a club</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="studentId" class="required">Student ID</label>
                                <input type="text" id="studentId" name="studentId" required />
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
                                <input type="text" id="eventName" name="eventName" required />
                                <div class="error-message">Please enter event name</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventNature" class="required">Event Nature</label>
                                <select id="eventNature" name="eventNature" required>
                                    <option value="">Select Category</option>
                                    <option value="Category A">
                                        Category A: Games/Sports & Martial Arts
                                    </option>
                                    <option value="Category B">
                                        Category B: Club/Societies/Uniformed Units
                                    </option>
                                    <option value="Category C">
                                        Category C: Community Service
                                    </option>
                                </select>
                                <div class="error-message">Please select event nature</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventObjectives" class="required">Event Objectives</label>
                        <textarea id="eventObjectives" name="eventObjectives" required></textarea>
                        <div class="error-message">Please enter event objectives</div>
                    </div>

                    <div class="form-group">
                        <label for="eventIntroduction" class="required">Introduction Event</label>
                        <textarea id="eventIntroduction" name="eventIntroduction" style="min-height: 200px"
                            required></textarea>
                        <div class="error-message">Please enter event introduction</div>
                    </div>

                    <div class="form-group">
                        <label for="eventPurpose" class="required">Purpose of Event</label>
                        <textarea id="eventPurpose" name="eventPurpose" required></textarea>
                        <div class="error-message">Please enter event purpose</div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="estimatedParticipants" class="required">Estimated Participants</label>
                                <input type="number" id="estimatedParticipants" name="estimatedParticipants" min="1"
                                    required />
                                <div class="error-message">
                                    Please enter estimated participants
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventDate" class="required">Event Date</label>
                                <input type="date" id="eventDate" name="eventDate" required />
                                <div class="error-message">
                                    Please select event date (minimum 14 days from today)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="startTime" class="required">Start Time</label>
                                <input type="time" id="startTime" name="startTime" required />
                                <div class="error-message">Please enter start time</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="endTime" class="required">End Time</label>
                                <input type="time" id="endTime" name="endTime" required />
                                <div class="error-message">Please enter end time</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="venue" class="required">Venue</label>
                                <select id="venue" name="venue" required>
                                    <option value="">Select Venue</option>
                                    <option value="Main Hall">Main Hall</option>
                                    <option value="Conference Room A">Conference Room A</option>
                                    <option value="Conference Room B">Conference Room B</option>
                                    <option value="Sports Complex">Sports Complex</option>
                                    <option value="Library Hall">Library Hall</option>
                                    <option value="Outdoor Field">Outdoor Field</option>
                                    <option value="Auditorium">Auditorium</option>
                                </select>
                                <div class="error-message">Please select venue</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventPoster" class="required">Event Poster</label>
                        <div class="upload-area" id="posterUpload">
                            <div>
                                <p>📁 Drag and drop your poster here or click to browse</p>
                                <input type="file" id="eventPoster" name="eventPoster" accept=".jpg,.jpeg,.png"
                                    style="display: none" required />
                            </div>
                        </div>
                        <div class="file-info">
                            Maximum file size: 20MB | Accepted formats: JPEG, PNG
                        </div>
                        <div class="preview-container" id="posterPreview"></div>
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
                                <input type="text" id="picName" name="picName" required />
                                <div class="error-message">
                                    Please enter person in charge name
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picId" class="required">ID</label>
                                <input type="text" id="picId" name="picId" required />
                                <div class="error-message">
                                    Please enter person in charge ID
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picPhone" class="required">Phone Number</label>
                                <input type="tel" id="picPhone" name="picPhone" required />
                                <div class="error-message">Please enter phone number</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Event Flow -->
                <div class="section">
                    <h2 class="section-title">Event Flow (Minutes of Meeting)</h2>
                    <button type="button" class="btn btn-add" id="addEventFlowBtn">
                        + Add New Row
                    </button>
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
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 5: Committee Members -->
                <div class="section">
                    <h2 class="section-title">Committee Members</h2>
                    <div class="file-info">
                        Note: Shall upload PDF files only, maximum file size: 2MB
                    </div>
                    <button type="button" class="btn btn-add" id="addCommitteeBtn">
                        + Add New Row
                    </button>
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
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 6: Budget -->
                <div class="section">
                    <h2 class="section-title">Budget</h2>
                    <button type="button" class="btn btn-add" id="addBudgetBtn">
                        + Add New Row
                    </button>
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
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="budget-summary">
                        <h4>Budget Summary</h4>
                        <div class="budget-row">
                            <span>Total Income:</span>
                            <span id="totalIncome">RM 0.00</span>
                        </div>
                        <div class="budget-row">
                            <span>Total Expense:</span>
                            <span id="totalExpense">RM 0.00</span>
                        </div>
                        <div class="budget-row">
                            <strong>
                                <span id="surplusDeficitLabel">Surplus/Deficit:</span>
                                <span id="surplusDeficit">RM 0.00</span>
                            </strong>
                        </div>
                        <div class="form-group" style="margin-top: 15px">
                            <label for="preparedBy" class="required">Prepared By:</label>
                            <input type="text" id="preparedBy" name="preparedBy" required />
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
                                <input type="date" id="alternativeDate" name="alternativeDate" required />
                                <div class="error-message">
                                    Please select alternative date
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="alternativeVenue" class="required">Alternative Venue</label>
                                <select id="alternativeVenue" name="alternativeVenue" required>
                                    <option value="">Select Alternative Venue</option>
                                    <option value="Main Hall">Main Hall</option>
                                    <option value="Conference Room A">Conference Room A</option>
                                    <option value="Conference Room B">Conference Room B</option>
                                    <option value="Sports Complex">Sports Complex</option>
                                    <option value="Library Hall">Library Hall</option>
                                    <option value="Outdoor Field">Outdoor Field</option>
                                    <option value="Auditorium">Auditorium</option>
                                </select>
                                <div class="error-message">
                                    Please select alternative venue
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="additionalDocument">Additional Document (Optional)</label>
                        <div class="upload-area" id="additionalDocUpload">
                            <div>
                                <p>
                                    📁 Drag and drop additional document here or click to browse
                                </p>
                                <input type="file" id="additionalDocument" name="additionalDocument"
                                    style="display: none" />
                            </div>
                        </div>
                        <div class="file-info">Any file type accepted</div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="backBtn">
                        ← Back
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" id="previewBtn">
                            👁 Preview
                        </button>
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

    <script>
        // Initialize form
        document.addEventListener("DOMContentLoaded", function () {
            initializeForm();
            setupEventListeners();
            setMinimumDates();
        });

        function initializeForm() {
            // Add initial rows
            addEventFlowRow();
            addCommitteeRow();
            addBudgetRow();
        }

        function setupEventListeners() {
            // File upload handlers
            setupFileUpload("posterUpload", "eventPoster", handlePosterUpload);
            setupFileUpload("additionalDocUpload", "additionalDocument");

            // Button handlers
            document
                .getElementById("addEventFlowBtn")
                .addEventListener("click", addEventFlowRow);
            document
                .getElementById("addCommitteeBtn")
                .addEventListener("click", addCommitteeRow);
            document
                .getElementById("addBudgetBtn")
                .addEventListener("click", addBudgetRow);

            // Modal handlers
            setupModalHandlers();

            // Form submission
            document
                .getElementById("proposalForm")
                .addEventListener("submit", handleSubmit);
            document
                .getElementById("previewBtn")
                .addEventListener("click", handlePreview);
            document
                .getElementById("backBtn")
                .addEventListener("click", handleBack);

            // Budget calculation
            document
                .getElementById("budgetBody")
                .addEventListener("input", calculateBudget);
        }

        function setMinimumDates() {
            const today = new Date();
            const minDate = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000);
            const minDateStr = minDate.toISOString().split("T")[0];

            document.getElementById("eventDate").min = minDateStr;
            document.getElementById("alternativeDate").min = minDateStr;
        }

        function setupFileUpload(uploadAreaId, inputId, callback) {
            const uploadArea = document.getElementById(uploadAreaId);
            const fileInput = document.getElementById(inputId);

            uploadArea.addEventListener("click", () => fileInput.click());
            uploadArea.addEventListener("dragover", handleDragOver);
            uploadArea.addEventListener("dragleave", handleDragLeave);
            uploadArea.addEventListener("drop", (e) =>
                handleDrop(e, fileInput, callback)
            );

            fileInput.addEventListener("change", (e) => {
                if (callback) callback(e.target.files[0]);
            });
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add("dragover");
        }

        function handleDragLeave(e) {
            e.currentTarget.classList.remove("dragover");
        }

        function handleDrop(e, fileInput, callback) {
            e.preventDefault();
            e.currentTarget.classList.remove("dragover");

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                if (callback) callback(files[0]);
            }
        }

        function handlePosterUpload(file) {
            if (file) {
                // Validate file type
                const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
                if (!allowedTypes.includes(file.type)) {
                    alert("Please upload only JPEG or PNG files.");
                    return;
                }

                // Validate file size (20MB)
                if (file.size > 20 * 1024 * 1024) {
                    alert("File size must be less than 20MB.");
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById("posterPreview");
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Poster Preview" class="preview-image">
                        <p>✅ Poster uploaded successfully</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }

        function addEventFlowRow() {
            const tbody = document.getElementById("eventFlowBody");
            const row = document.createElement("tr");
            const rowId = "eventFlow_" + Date.now();

            row.innerHTML = `
                <td><input type="date" name="eventFlowDate[]" required></td>
                <td><input type="time" name="eventFlowStart[]" required></td>
                <td><input type="time" name="eventFlowEnd[]" required></td>
                <td><input type="number" name="eventFlowHours[]" step="0.5" min="0" readonly></td>
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

            // Calculate hours automatically
            const startInput = row.querySelector('input[name="eventFlowStart[]"]');
            const endInput = row.querySelector('input[name="eventFlowEnd[]"]');
            const hoursInput = row.querySelector('input[name="eventFlowHours[]"]');

            function calculateHours() {
                if (startInput.value && endInput.value) {
                    const start = new Date(`2000-01-01T${startInput.value}`);
                    const end = new Date(`2000-01-01T${endInput.value}`);
                    const diff = (end - start) / (1000 * 60 * 60);
                    hoursInput.value = diff > 0 ? diff.toFixed(1) : 0;
                }
            }

            startInput.addEventListener("change", calculateHours);
            endInput.addEventListener("change", calculateHours);
        }

        function addCommitteeRow() {
            const tbody = document.getElementById("committeeBody");
            const row = document.createElement("tr");
            const rowId = "committee_" + Date.now();

            row.innerHTML = `
                <td><input type="text" name="committeeId[]" required></td>
                <td><input type="text" name="committeeName[]" required></td>
                <td><input type="text" name="committeePosition[]" required></td>
                <td><input type="text" name="committeeDepartment[]" required></td>
                <td><input type="tel" name="committeePhone[]" required></td>
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
        }

        function addBudgetRow() {
            const tbody = document.getElementById("budgetBody");
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
        }

        function deleteRow(button) {
            if (confirm("Are you sure you want to delete this row?")) {
                button.closest("tr").remove();
                calculateBudget();
            }
        }

        function setupModalHandlers() {
            // Activity Description Modal
            const activityModal = document.getElementById("activityModal");
            const jobScopeModal = document.getElementById("jobScopeModal");

            // Close modals
            document.querySelectorAll(".close").forEach((closeBtn) => {
                closeBtn.addEventListener("click", function () {
                    this.closest(".modal").style.display = "none";
                });
            });

            // Close modal on outside click
            window.addEventListener("click", function (e) {
                if (e.target.classList.contains("modal")) {
                    e.target.style.display = "none";
                }
            });

            // Save activity description
            document
                .getElementById("saveActivityBtn")
                .addEventListener("click", function () {
                    const description = document.getElementById(
                        "activityDescription"
                    ).value;
                    if (description.trim()) {
                        const rowId = activityModal.getAttribute("data-row-id");
                        document.getElementById("activity_" + rowId).value = description;

                        // Enable view button
                        const viewBtn = document.querySelector(
                            `button[onclick="viewActivityDescription('${rowId}')"]`
                        );
                        viewBtn.disabled = false;

                        activityModal.style.display = "none";
                        document.getElementById("activityDescription").value = "";
                    }
                });

            // Save job scope
            document
                .getElementById("saveJobScopeBtn")
                .addEventListener("click", function () {
                    const description = document.getElementById(
                        "jobScopeDescription"
                    ).value;
                    if (description.trim()) {
                        const rowId = jobScopeModal.getAttribute("data-row-id");
                        document.getElementById("jobScope_" + rowId).value = description;

                        // Enable view button
                        const viewBtn = document.querySelector(
                            `button[onclick="viewJobScope('${rowId}')"]`
                        );
                        viewBtn.disabled = false;

                        jobScopeModal.style.display = "none";
                        document.getElementById("jobScopeDescription").value = "";
                    }
                });
        }

        function addActivityDescription(rowId) {
            const modal = document.getElementById("activityModal");
            modal.setAttribute("data-row-id", rowId);
            modal.style.display = "block";

            // Load existing description if any
            const existingDescription = document.getElementById(
                "activity_" + rowId
            ).value;
            document.getElementById("activityDescription").value =
                existingDescription;
        }

        function viewActivityDescription(rowId) {
            const description = document.getElementById("activity_" + rowId).value;
            if (description) {
                alert("Activity Description:\n\n" + description);
            }
        }

        function addJobScope(rowId) {
            const modal = document.getElementById("jobScopeModal");
            modal.setAttribute("data-row-id", rowId);
            modal.style.display = "block";

            // Load existing job scope if any
            const existingJobScope = document.getElementById(
                "jobScope_" + rowId
            ).value;
            document.getElementById("jobScopeDescription").value = existingJobScope;
        }

        function viewJobScope(rowId) {
            const jobScope = document.getElementById("jobScope_" + rowId).value;
            if (jobScope) {
                alert("Job Scope:\n\n" + jobScope);
            }
        }

        function toggleCocuUpload(select, rowId) {
            const fileInput = document.getElementById("cocuFile_" + rowId);
            fileInput.disabled = select.value !== "yes";
            if (select.value !== "yes") {
                fileInput.value = "";
            }
        }

        function calculateBudget() {
            const amounts = document.querySelectorAll(
                'input[name="budgetAmount[]"]'
            );
            const types = document.querySelectorAll('select[name="budgetType[]"]');

            let totalIncome = 0;
            let totalExpense = 0;

            amounts.forEach((amountInput, index) => {
                const amount = parseFloat(amountInput.value) || 0;
                const type = types[index].value;

                if (type === "income") {
                    totalIncome += amount;
                } else if (type === "expense") {
                    totalExpense += amount;
                }
            });

            const surplusDeficit = totalIncome - totalExpense;

            document.getElementById(
                "totalIncome"
            ).textContent = `RM ${totalIncome.toFixed(2)}`;
            document.getElementById(
                "totalExpense"
            ).textContent = `RM ${totalExpense.toFixed(2)}`;
            document.getElementById(
                "surplusDeficit"
            ).textContent = `RM ${surplusDeficit.toFixed(2)}`;

            // Change color based on surplus/deficit
            const surplusDeficitElement = document.getElementById("surplusDeficit");
            if (surplusDeficit > 0) {
                surplusDeficitElement.style.color = "#27ae60";
            } else if (surplusDeficit < 0) {
                surplusDeficitElement.style.color = "#e74c3c";
            } else {
                surplusDeficitElement.style.color = "#333";
            }
        }

        function validateForm() {
            const requiredFields = document.querySelectorAll(
                "input[required], select[required], textarea[required]"
            );
            let isValid = true;

            requiredFields.forEach((field) => {
                const formGroup = field.closest(".form-group");
                if (!field.value.trim()) {
                    formGroup.classList.add("error");
                    isValid = false;
                } else {
                    formGroup.classList.remove("error");
                }
            });

            // Custom validations

            // Event date validation (minimum 14 days from today)
            const eventDate = document.getElementById("eventDate");
            const today = new Date();
            const minDate = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000);

            if (eventDate.value && new Date(eventDate.value) < minDate) {
                eventDate.closest(".form-group").classList.add("error");
                isValid = false;
            }

            // Time validation (end time should be after start time)
            const startTime = document.getElementById("startTime");
            const endTime = document.getElementById("endTime");

            if (
                startTime.value &&
                endTime.value &&
                startTime.value >= endTime.value
            ) {
                endTime.closest(".form-group").classList.add("error");
                isValid = false;
            }

            // File validation
            const eventPoster = document.getElementById("eventPoster");
            if (!eventPoster.files.length) {
                eventPoster.closest(".form-group").classList.add("error");
                isValid = false;
            }

            // Table validations
            const eventFlowRows = document.querySelectorAll("#eventFlowBody tr");
            const committeeRows = document.querySelectorAll("#committeeBody tr");
            const budgetRows = document.querySelectorAll("#budgetBody tr");

            if (eventFlowRows.length === 0) {
                alert("Please add at least one event flow entry.");
                isValid = false;
            }

            if (committeeRows.length === 0) {
                alert("Please add at least one committee member.");
                isValid = false;
            }

            if (budgetRows.length === 0) {
                alert("Please add at least one budget entry.");
                isValid = false;
            }

            return isValid;
        }

        function handleSubmit(e) {
            e.preventDefault();

            if (validateForm()) {
                // Show loading state
                const submitBtn = document.getElementById("submitBtn");
                const originalText = submitBtn.textContent;
                submitBtn.textContent = "Submitting...";
                submitBtn.disabled = true;

                // Simulate form submission
                setTimeout(() => {
                    alert("Proposal submitted successfully!");
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;

                    // In a real application, you would send the form data to the server
                    // const formData = new FormData(e.target);
                    // fetch('/submit-proposal', { method: 'POST', body: formData })
                }, 2000);
            } else {
                alert("Please fill in all required fields correctly.");
            }
        }

        function handlePreview() {
            if (validateForm()) {
                alert(
                    "Preview functionality would open a new window/modal showing the formatted proposal."
                );
                // In a real application, you would generate and display a preview
            } else {
                alert("Please fill in all required fields before previewing.");
            }
        }

        function handleBack() {
            if (
                confirm(
                    "Are you sure you want to go back? Any unsaved changes will be lost."
                )
            ) {
                window.history.back();
            }
        }

        // Auto-save functionality (optional)
        let autoSaveTimer;
        document
            .getElementById("proposalForm")
            .addEventListener("input", function () {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // Auto-save logic here
                    console.log("Auto-saving form data...");
                }, 5000);
            });
    </script>
</body>

</html>