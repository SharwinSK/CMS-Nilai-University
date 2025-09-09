<?php
session_start();
include('../db/dbconfig.php'); // adjust path as needed
include('../model/sendMailTemplates.php');

// Check if advisor is logged in
if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

// Get Advisor Info (optional: for navbar or header name)
$advisor_id = $_SESSION['Adv_ID'];
$advisor_name = "Advisor";
if (!empty($advisor_id)) {
    $stmt = $conn->prepare("SELECT Adv_Name FROM advisor WHERE Adv_ID = ?");
    $stmt->bind_param("s", $advisor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $advisor_name = $row['Adv_Name'];
    }
}

// Step 2: Get Event ID
if (!isset($_GET['event_id'])) {
    die("Event ID is required.");
}
$event_id = $_GET['event_id'];

// Step 3: Fetch full event proposal info
$proposal_query = "
SELECT e.*, s.Stu_Name, c.Club_Name, 
       v1.Venue_Name AS MainVenue, 
       v2.Venue_Name AS AltVenue,
       bs.Total_Income, bs.Total_Expense, bs.Surplus_Deficit, bs.Prepared_By
FROM events e
LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
LEFT JOIN club c ON e.Club_ID = c.Club_ID
LEFT JOIN venue v1 ON e.Ev_VenueID = v1.Venue_ID
LEFT JOIN venue v2 ON e.Ev_AltVenueID = v2.Venue_ID
LEFT JOIN budgetsummary bs ON e.Ev_ID = bs.Ev_ID
WHERE e.Ev_ID = ?
";

$stmt = $conn->prepare($proposal_query);
$stmt->bind_param("s", $event_id); // use 's' because Ev_ID is varchar
$stmt->execute();
$proposal_result = $stmt->get_result();
$proposal = $proposal_result->fetch_assoc();

if (!$proposal) {
    die("Proposal not found.");
}

// 1. Fetch Person In Charge
$pic_stmt = $conn->prepare("SELECT * FROM personincharge WHERE Ev_ID = ?");
$pic_stmt->bind_param("s", $event_id);
$pic_stmt->execute();
$person_in_charge = $pic_stmt->get_result()->fetch_assoc();

// 2. Fetch Event Flow (Minutes of Meeting)
$flow_stmt = $conn->prepare("SELECT * FROM eventminutes WHERE Ev_ID = ?");
$flow_stmt->bind_param("s", $event_id);
$flow_stmt->execute();
$event_flows = $flow_stmt->get_result(); // this will be looped in HTML

// 3. Fetch Committee Members with Email and Register status
$committee_stmt = $conn->prepare("SELECT * FROM committee WHERE Ev_ID = ?");
$committee_stmt->bind_param("s", $event_id);
$committee_stmt->execute();
$committee_members = $committee_stmt->get_result();

// 4. Fetch Budget Details
$budget_stmt = $conn->prepare("SELECT * FROM budget WHERE Ev_ID = ?");
$budget_stmt->bind_param("s", $event_id);
$budget_stmt->execute();
$budget_details = $budget_stmt->get_result();

// ✅ STEP 2: Handle Advisor Action Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'];
    $advisor_feedback = $_POST['Ev_AdvisorComments'] ?? null;

    if ($decision === 'send_back') {
        $status = 'Rejected by Advisor';
    } elseif ($decision === 'approve') {
        $status = 'Approved by Advisor (Pending Coordinator Review)';
    } else {
        die("Invalid decision.");
    }

    // 1. Get the Status_ID from eventstatus table
    $status_query = "SELECT Status_ID FROM eventstatus WHERE Status_Name = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("s", $status);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();

    if ($status_result->num_rows === 0) {
        die("Status not found.");
    }

    $status_id = $status_result->fetch_assoc()['Status_ID'];

    // 2. Update the events table
    $update = $conn->prepare("UPDATE events SET Status_ID = ? WHERE Ev_ID = ?");
    $update->bind_param("is", $status_id, $event_id);
    $update->execute();

    // 3. If rejected, insert feedback into eventcomment table
    if ($decision === 'send_back') {
        $comment_stmt = $conn->prepare("INSERT INTO eventcomment (Ev_ID, Status_ID, Reviewer_Comment, Updated_By, Comment_Type) VALUES (?, ?, ?, 'Advisor', 'proposal')");
        $comment_stmt->bind_param("sis", $event_id, $status_id, $advisor_feedback);
        $comment_stmt->execute();
    }

    // === Prepare Data for Email ===
    $studentName = $proposal['Stu_Name'];
    $studentEmail = '';
    $coordinatorName = '';
    $coordinatorEmail = '';
    $eventName = $proposal['Ev_Name'];
    $clubName = $proposal['Club_Name'];

    // Get student email
    $stmt = $conn->prepare("SELECT Stu_Email FROM student WHERE Stu_ID = ?");
    $stmt->bind_param("s", $proposal['Stu_ID']);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    $studentEmail = $studentResult->fetch_assoc()['Stu_Email'];
    $stmt->close();

    // ✅ Get first coordinator (since no Club_ID-Coor_ID link exists)
    $stmt = $conn->prepare("SELECT Coor_Name, Coor_Email FROM coordinator LIMIT 1");
    $stmt->execute();
    $coorResult = $stmt->get_result();

    if ($coorRow = $coorResult->fetch_assoc()) {
        $coordinatorName = $coorRow['Coor_Name'];
        $coordinatorEmail = $coorRow['Coor_Email'];
    }
    $stmt->close();

    // === Send Emails Based on Decision ===
    if ($decision === 'approve') {
        advisorApproved($studentName, $eventName, $studentEmail, $coordinatorEmail, $clubName);

        echo "<script>
        setTimeout(() => {
            window.location.href = 'AdvisorDashboard.php';
        }, 3000); // Wait 3 seconds for loading screen effect
    </script>";
    } elseif ($decision === 'send_back') {
        advisorRejected($studentName, $eventName, $studentEmail);

        echo "<script>
        setTimeout(() => {
            window.location.href = 'AdvisorDashboard.php';
        }, 3000); // Wait 3 seconds for loading screen effect
    </script>";
    }

    // 4. Redirect to dashboard
    echo "<script>
        setTimeout(() => {
            window.location.href = 'AdvisorDashboard.php';
        }, 1500);
    </script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Advisor Proposal Review - Nilai University</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/advisor3.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> Review Proposal</h1>
            <div class="action-buttons">
                <button class="btn-icon" onclick="exportPDF()" title="Export PDF">
                    <i class="fas fa-file-pdf"></i>
                </button>
                <button class="btn-icon" onclick="returnProposal()" title="Return">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>

        <div class="content">

            <!-- Poster Section -->
            <div class="poster-section">
                <div class="poster" onclick="enlargePoster()" style="padding: 0; overflow: hidden;">
                    <img src="../uploads/posters/<?php echo $proposal['Ev_Poster']; ?>" alt="Event Poster"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px;" />
                </div>
            </div>

            <!-- Section 1: Event Information -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Event Information
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Submission Date</div>
                        <div class="info-value"><?php echo date("j M Y", strtotime($proposal['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Student Name</div>
                        <div class="info-value"><?php echo $proposal['Stu_Name']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Student Position</div>
                        <div class="info-value"><?php echo $proposal['Proposal_Position']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Club Name</div>
                        <div class="info-value"><?php echo $proposal['Club_Name']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Name</div>
                        <div class="info-value"><?php echo $proposal['Ev_Name']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Nature</div>
                        <div class="info-value"><?php echo $proposal['Ev_ProjectNature']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Category</div>
                        <div class="info-value"><?php echo $proposal['Ev_Category']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Objectives</div>
                        <div class="info-value">
                            <?php echo $proposal['Ev_Objectives']; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Introduction of Event</div>
                        <div class="info-value">
                            <?php echo $proposal['Ev_Intro']; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Purpose of Event</div>
                        <div class="info-value">
                            <?php echo $proposal['Ev_Details']; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Date</div>
                        <div class="info-value"><?php echo $proposal['Ev_Date']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Start Time - End Time</div>
                        <div class="info-value">
                            <?php echo date("g:i A", strtotime($proposal['Ev_StartTime'])) . ' - ' . date("g:i A", strtotime($proposal['Ev_EndTime'])); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estimated Participants</div>
                        <div class="info-value"><?php echo $proposal['Ev_Pax']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Person In Charge Name</div>
                        <div class="info-value"><?php echo $person_in_charge['PIC_Name']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Person In Charge ID</div>
                        <div class="info-value"><?php echo $person_in_charge['PIC_ID']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Person In Charge Number</div>
                        <div class="info-value"><?php echo $person_in_charge['PIC_PhnNum']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Event Flow/Minutes -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i>
                    Event Flow / Minutes of Meeting
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th>Activity</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalHours = 0;
                            while ($flow = $event_flows->fetch_assoc()):
                                $totalHours += (float) $flow['Hours'];
                                ?>
                                <tr>
                                    <td><?php echo date("d/m/Y", strtotime($flow['Date'])); ?></td>
                                    <td><?php echo date("h:i A", strtotime($flow['Start_Time'])); ?></td>
                                    <td><?php echo date("h:i A", strtotime($flow['End_Time'])); ?></td>
                                    <td><?php echo htmlspecialchars($flow['Hours']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($flow['Activity'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($flow['Remarks'])); ?></td>
                                </tr>
                            <?php endwhile; ?>

                            <tr class="total-row">
                                <td colspan="3"><strong>Total Hours</strong></td>
                                <td><strong><?php echo $totalHours; ?>hours</strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Committee Members -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-users"></i>
                    Committee Members
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Job Scope</th>
                                <th>Registered</th>
                                <th>Claimers</th>
                                <th>COCU Statement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($committee_members as $member): ?>
                                <tr>
                                    <td><?= $member['Com_Name'] ?></td>
                                    <td><?= $member['Com_Email'] ?></td>
                                    <td><?= $member['Com_Position'] ?></td>
                                    <td><?= $member['Com_Department'] ?></td>
                                    <td><?= $member['Com_JobScope'] ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($member['Com_Register']) === 'yes' ? 'status-yes' : 'status-no' ?>">
                                            <?= $member['Com_Register'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        echo (strtolower($member['Com_COCUClaimers']) === 'yes') ? 'Yes' : 'No';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($member['student_statement'])): ?>
                                            <button class="view-btn"
                                                onclick="viewCOCUStatement('<?= $member['student_statement'] ?>')">
                                                View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 4: Budget Details -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Budget Details
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount (RM)</th>
                                <th>Income/Expense</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budget_details as $item): ?>
                                <tr>
                                    <td><?= $item['Bud_Desc'] ?></td>
                                    <td><?= $item['Bud_Amount'] ?></td>
                                    <td><?= $item['Bud_Type'] ?></td>
                                    <td><?= $item['Bud_Remarks'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="budget-summary">
                    <div class="budget-item">
                        <span>Total Income:</span>
                        <span><?= $proposal['Total_Income'] ?? '0.00' ?></span>
                    </div>
                    <div class="budget-item">
                        <span>Total Expense:</span>
                        <span><?= $proposal['Total_Expense'] ?? '0.00' ?></span>
                    </div>
                    <div class="budget-item">
                        <span>Surplus/Deficit:</span>
                        <span class="deficit"><?= $proposal['Surplus_Deficit'] ?? '0.00' ?></span>
                    </div>
                    <div class="budget-item" style="
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(255, 255, 255, 0.3);
              ">
                        <span>Prepared By:</span>
                        <span><?= $proposal['Prepared_By'] ?? '-' ?></span>
                    </div>
                </div>
            </div>

            <!-- Section 5: Additional -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-plus-circle"></i>
                    Additional Information
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Alternative Venue</div>
                        <div class="info-value">
                            <?= $proposal['AltVenue'] ?? 'N/A' ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Alternative Date</div>
                        <div class="info-value"><?= $proposal['Ev_AlternativeDate'] ?? 'N/A' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Additional Documents</div>
                        <div class="info-value">
                            <button class="view-btn" onclick="viewAdditionalDoc()">
                                View Documents
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Action Buttons -->
            <div class="main-actions">
                <button class="btn btn-approve" onclick="approveProposal()">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn btn-reject" onclick="showRejectModal()">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        </div>
    </div>

    <!-- Poster Modal -->
    <div id="posterModal" class="modal poster-modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closePosterModal()">&times;</span>
            <img src="../uploads/posters/<?php echo $proposal['Ev_Poster']; ?>" alt="Event Poster"
                class="poster-large" />
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRejectModal()">&times;</span>
            <h2>
                <i class="fas fa-times-circle" style="color: #e74c3c"></i> Reject
                Proposal
            </h2>
            <p style="margin-bottom: 20px; color: #666">
                Please provide a reason for rejecting this proposal:
            </p>
            <textarea id="rejectReason" placeholder="Enter your reason for rejection..."></textarea>
            <div class="modal-actions">
                <button class="btn btn-reject" onclick="submitRejection()">
                    Submit Rejection
                </button>
                <button class="btn" style="background: #95a5a6; color: white" onclick="closeRejectModal()">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2 style="color: #27ae60; text-align: center">
                <i class="fas fa-check-circle" style="font-size: 3rem; display: block; margin-bottom: 20px"></i>
                Proposal Approved!
            </h2>
            <p style="
            text-align: center;
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 30px;
          ">
                Thank you for approving the proposal. It has been sent to the
                Coordinator for review.
            </p>
            <div style="text-align: center">
                <button class="btn btn-approve" onclick="closeSuccessModal()">
                    Continue
                </button>
            </div>
        </div>
    </div>
    
    <!-- Hidden Forms for Advisor Action -->
    <form id="approveForm" method="POST" style="display: none;">
        <input type="hidden" name="decision" value="approve">
    </form>

    <form id="rejectForm" method="POST" style="display: none;">
        <input type="hidden" name="decision" value="send_back">
        <input type="hidden" name="Ev_AdvisorComments" id="Ev_AdvisorComments">
    </form>

    <!-- Scroll Progress Bar -->
    <div class="scroll-progress">
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <!-- Floating Scroll Navigation -->
    <div class="scroll-navigation">
        <button class="scroll-btn up" id="scrollToTop" onclick="scrollToTop()" title="Scroll to Top">
            <i class="fas fa-chevron-up"></i>
        </button>
        <button class="scroll-btn down" id="scrollToBottom" onclick="scrollToBottom()" title="Scroll to Bottom">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="modal">
        <div class="modal-content loading-content">
            <div class="loading-spinner"></div>
            <h2 id="loadingTitle">Processing...</h2>
            <p id="loadingMessage">Please wait while we process your request.</p>
        </div>
    </div>
    
    <script>
        // Modal functions
        function enlargePoster() {
            document.getElementById("posterModal").style.display = "block";
        }

        function closePosterModal() {
            document.getElementById("posterModal").style.display = "none";
        }

        function showRejectModal() {
            document.getElementById("rejectModal").style.display = "block";
        }

        function closeRejectModal() {
            document.getElementById("rejectModal").style.display = "none";
            document.getElementById("rejectReason").value = "";
        }

        function submitRejection() {
            const reason = document.getElementById("rejectReason").value.trim();
            if (reason === "") {
                alert("Please provide a reason for rejection.");
                return;
            }

            // Close reject modal first
            closeRejectModal();

            // Show loading screen
            showLoadingScreen("Proposal Rejected!", "Sending feedback to student...");

            // Fill the hidden input and submit form
            document.getElementById("Ev_AdvisorComments").value = reason;
            document.getElementById("rejectForm").submit();
        }

        function approveProposal() {
            // Show success modal
            document.getElementById("successModal").style.display = "block";
        }

        function closeSuccessModal() {
            document.getElementById("successModal").style.display = "none";

            // Show loading screen
            showLoadingScreen("Proposal Approved!", "Sending notification to coordinator...");

            // Submit the form
            document.getElementById("approveForm").submit();
        }

        function viewCOCUStatement(filename) {
            window.open('../uploads/statements/' + filename, '_blank');
        }
        
        function showLoadingScreen(title, message) {
            document.getElementById("loadingTitle").textContent = title;
            document.getElementById("loadingMessage").textContent = message;
            document.getElementById("loadingModal").style.display = "block";
        }

        function hideLoadingScreen() {
            document.getElementById("loadingModal").style.display = "none";
        }
        
        function viewAdditionalDoc() {
            const filePath = "<?php echo isset($proposal['Ev_AdditionalInfo']) ? $proposal['Ev_AdditionalInfo'] : ''; ?>";
            if (filePath) {
                window.open(`viewpdf.php?file=${encodeURIComponent(filePath)}`, '_blank');
            } else {
                alert("No file uploaded.");
            }
        }

        function exportPDF() {
            const evId = "<?php echo $event_id; ?>";
            window.open(`../components/pdf/generate_pdf.php?id=${evId}`, '_blank');
        }

        function returnProposal() {
            window.location.href = 'AdvisorDashboard.php';
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            const modals = [
                "posterModal",
                "rejectModal",
                "successModal"
            ];
            modals.forEach((modalId) => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        };

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute("href")).scrollIntoView({
                    behavior: "smooth",
                });
            });
        });

        // Initialize when page loads
        document.addEventListener("DOMContentLoaded", function () {
            console.log("Advisor Proposal Review System Loaded");

            // Ensure all modals are hidden on page load
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });

            // Add hover effects for better interactivity
            const sections = document.querySelectorAll(".section");
            sections.forEach((section) => {
                section.addEventListener("mouseenter", function () {
                    this.style.transform = "translateY(-2px)";
                    this.style.boxShadow = "0 8px 25px rgba(0,0,0,0.12)";
                });

                section.addEventListener("mouseleave", function () {
                    this.style.transform = "translateY(0)";
                    this.style.boxShadow = "0 5px 20px rgba(0,0,0,0.08)";
                });
            });
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Smooth scroll to bottom function
        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }

        // Update scroll progress and button visibility
        function updateScrollElements() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / scrollHeight) * 100;

            // Update progress bar
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = scrollPercent + '%';

            // Show/hide buttons based on scroll position
            const scrollToTopBtn = document.getElementById('scrollToTop');
            const scrollToBottomBtn = document.getElementById('scrollToBottom');

            // Show/hide top button
            if (scrollTop > 300) {
                scrollToTopBtn.classList.remove('hidden');
            } else {
                scrollToTopBtn.classList.add('hidden');
            }

            // Show/hide bottom button
            if (scrollTop < scrollHeight - 300) {
                scrollToBottomBtn.classList.remove('hidden');
            } else {
                scrollToBottomBtn.classList.add('hidden');
            }
        }

        // Enhanced scroll functionality with section navigation
        function scrollToSection(direction) {
            const sections = document.querySelectorAll('.demo-section, .section');
            const currentScroll = window.pageYOffset;
            let targetSection = null;

            if (direction === 'next') {
                // Find next section below current scroll position
                for (let section of sections) {
                    if (section.offsetTop > currentScroll + 100) {
                        targetSection = section;
                        break;
                    }
                }
                // If no next section, scroll to bottom
                if (!targetSection) {
                    scrollToBottom();
                    return;
                }
            } else if (direction === 'prev') {
                // Find previous section above current scroll position
                for (let i = sections.length - 1; i >= 0; i--) {
                    if (sections[i].offsetTop < currentScroll - 100) {
                        targetSection = sections[i];
                        break;
                    }
                }
                // If no previous section, scroll to top
                if (!targetSection) {
                    scrollToTop();
                    return;
                }
            }

            // Scroll to target section
            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Home key - scroll to top
            if (e.key === 'Home') {
                e.preventDefault();
                scrollToTop();
            }
            // End key - scroll to bottom
            else if (e.key === 'End') {
                e.preventDefault();
                scrollToBottom();
            }
            // Page Up - scroll to previous section
            else if (e.key === 'PageUp') {
                e.preventDefault();
                scrollToSection('prev');
            }
            // Page Down - scroll to next section
            else if (e.key === 'PageDown') {
                e.preventDefault();
                scrollToSection('next');
            }
        });

        // Event listeners
        window.addEventListener('scroll', updateScrollElements);
        window.addEventListener('resize', updateScrollElements);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            updateScrollElements();

            // Add smooth hover effects
            const scrollBtns = document.querySelectorAll('.scroll-btn');
            scrollBtns.forEach(btn => {
                btn.addEventListener('mouseenter', function () {
                    this.style.transform = 'scale(1.1)';
                });

                btn.addEventListener('mouseleave', function () {
                    this.style.transform = 'scale(1)';
                });
            });
        });

        // Optional: Auto-hide navigation after inactivity
        let hideTimeout;
        function resetHideTimeout() {
            clearTimeout(hideTimeout);
            document.querySelector('.scroll-navigation').style.opacity = '1';

            hideTimeout = setTimeout(() => {
                if (window.pageYOffset > 100) {
                    document.querySelector('.scroll-navigation').style.opacity = '0.3';
                }
            }, 3000);
        }

        window.addEventListener('mousemove', resetHideTimeout);
        window.addEventListener('scroll', resetHideTimeout);
    </script>
</body>

</html>