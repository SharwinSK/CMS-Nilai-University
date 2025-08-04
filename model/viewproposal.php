<?php
session_start();
include('../db/dbconfig.php');

// Get event ID from URL
$ev_id = $_GET['id'] ?? '';
if (empty($ev_id)) {
    die("Invalid event ID.");
}

// Detect user role (set this in your login logic)
if (!isset($_SESSION['user_type'])) {
    die("Unauthorized access.");
}
$user_type = $_SESSION['user_type'];

// Fetch main event info
$query = "
SELECT 
    e.*, 
    s.Stu_Name, s.Stu_ID, 
    c.Club_Name, 
    v1.Venue_Name AS MainVenue,
    v2.Venue_Name AS AltVenue,
    st.Status_Name
FROM events e
LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
LEFT JOIN club c ON e.Club_ID = c.Club_ID
LEFT JOIN venue v1 ON e.Ev_VenueID = v1.Venue_ID
LEFT JOIN venue v2 ON e.Ev_AltVenueID = v2.Venue_ID
LEFT JOIN eventstatus st ON e.Status_ID = st.Status_ID
WHERE e.Ev_ID = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Event not found.");
}

$event = $result->fetch_assoc();

// Determine status display (based on role)


$status_text = 'Ongoing';
$status_class = 'status-approved';
if ($user_type === 'student') {
    switch ((int) $event['Status_ID']) {
        case 1:
            $status_text = 'Pending Advisor';
            $status_class = 'status-pending';
            break;
        case 3:
            $status_text = 'Pending Coordinator';
            $status_class = 'status-pending';
            break;
        case 5:
            $status_text = 'Ongoing';
            $status_class = 'status-approved';
            break;
        default:
            $status_text = $event['Status_Name'] ?? 'Unknown';
            $status_class = 'status-pending';
    }
}

$pic_stmt = $conn->prepare("SELECT * FROM personincharge WHERE Ev_ID = ?");
$pic_stmt->bind_param("s", $ev_id);
$pic_stmt->execute();
$pic_result = $pic_stmt->get_result();
$pic = $pic_result->fetch_assoc();

$flow_result = $conn->query("SELECT * FROM eventminutes WHERE Ev_ID = '$ev_id' ORDER BY Date");
$totalHours = 0;

$com_stmt = $conn->prepare("SELECT * FROM committee WHERE Ev_ID = ?");
$com_stmt->bind_param("s", $ev_id);
$com_stmt->execute();
$com_result = $com_stmt->get_result();

$bud_result = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$ev_id'");
$summary_result = $conn->query("SELECT * FROM budgetsummary WHERE Ev_ID = '$ev_id'");
$summary = $summary_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Proposal - Nilai University CMS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/viewProposal.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <div class="container" id="proposalContent">
        <div class="header">
            <div class="status-badge <?= $status_class ?>"><?= $status_text ?></div>
            <h1>Event Proposal</h1>
            <p>Nilai University Content Management System</p>
            <div class="print-only">
                <p style="margin-top: 10px; font-size: 0.9em;">Generated on: <span id="printDate"></span></p>
            </div>
        </div>

        <div class="content">
            <!-- Section 1: Student Information -->
            <div class="section">
                <h2 class="section-title">Student Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Student Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Stu_Name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Student ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['Stu_ID']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Club</div>
                        <div class="info-value"><?= htmlspecialchars($event['Club_Name']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Event Details -->
            <div class="section">
                <h2 class="section-title">Event Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Event Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_Name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Nature</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_ProjectNature']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Date</div>
                        <div class="info-value"><?= date('F j, Y', strtotime($event['Ev_Date'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Time</div>
                        <div class="info-value"> <?= date('g:i A', strtotime($event['Ev_StartTime'])) ?> -
                            <?= date('g:i A', strtotime($event['Ev_EndTime'])) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Venue</div>
                        <div class="info-value"><?= htmlspecialchars($event['MainVenue']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estimated Participants</div>
                        <div class="info-value"><?= (int) $event['Ev_Pax'] ?> participants</div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <div class="info-label">Event Objectives</div>
                    <div class="text-area-content">
                        <?= nl2br(htmlspecialchars($event['Ev_Objectives'])) ?>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <div class="info-label">Introduction Event</div>
                    <div class="text-area-content">
                        <?= nl2br(htmlspecialchars($event['Ev_Intro'])) ?>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <div class="info-label">Purpose of Event</div>
                    <div class="text-area-content">
                        <?= nl2br(htmlspecialchars($event['Ev_Details'])) ?>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <div class="info-label">Event Poster</div>
                    <div class="poster-container">
                        <?php if (!empty($event['Ev_Poster'])): ?>
                            <?php
                            // Convert path if necessary
                            $posterPath = str_replace('../../', '../', $event['Ev_Poster']);
                            ?>
                            <img src="<?= $posterPath ?>" class="poster-image" alt="Event Poster" /> <?php else: ?>
                            <p>No poster uploaded.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Section 3: Person in Charge -->
            <div class="section">
                <h2 class="section-title">Person in Charge</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?= htmlspecialchars($pic['PIC_Name'] ?? '-') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ID</div>
                        <div class="info-value"><?= htmlspecialchars($pic['PIC_ID'] ?? '-') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= htmlspecialchars($pic['PIC_PhnNum'] ?? '-') ?></div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Event Flow -->
            <div class="section">
                <h2 class="section-title">Event Flow (Minutes of Meeting)</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th>Activity Description</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $flow_result->fetch_assoc()):
                                $totalHours += (int) $row['Hours']; ?>
                                <tr>
                                    <td><?= date('F j, Y', strtotime($row['Date'])) ?></td>
                                    <td><?= date('g:i A', strtotime($row['Start_Time'])) ?></td>
                                    <td><?= date('g:i A', strtotime($row['End_Time'])) ?></td>
                                    <td><?= $row['Hours'] ?></td>
                                    <td><?= htmlspecialchars($row['Activity']) ?></td>
                                    <td><?= htmlspecialchars($row['Remarks']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="hours-highlight <?= $totalHours >= 40 ? 'success' : '' ?>">
                    <strong><?= $totalHours >= 40 ? '✅' : '⚠️' ?> Total Hours: <?= $totalHours ?> hours</strong>
                    </p>
                </div>
            </div>

            <!-- Section 5: Committee Members -->
            <div class="section">
                <h2 class="section-title">Committee Members</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Phone</th>
                                <th>Job Scope</th>
                                <th>COCU Claimer</th>
                                <th>COCU Statement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $com_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Com_ID']) ?></td>
                                    <td><?= htmlspecialchars($row['Com_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Com_Position']) ?></td>
                                    <td><?= htmlspecialchars($row['Com_Department']) ?></td>
                                    <td><?= htmlspecialchars($row['Com_PhnNum']) ?></td>
                                    <td><?= htmlspecialchars($row['Com_JobScope']) ?></td>
                                    <td><?= ucfirst($row['Com_COCUClaimers']) ?></td>
                                    <td>
                                        <?php
                                        $cocuPath = str_replace('../../', '../', $row['student_statement']);
                                        ?>
                                        <?php if (!empty($row['student_statement'])): ?>
                                            <a href="<?= $cocuPath ?>" class="download-link" target="_blank">
                                                📄 Download PDF
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 6: Budget -->
            <div class="section">
                <h2 class="section-title">Budget</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount (RM)</th>
                                <th>Type</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $bud_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Bud_Desc']) ?></td>
                                    <td><?= number_format($row['Bud_Amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['Bud_Type']) ?></td>
                                    <td><?= htmlspecialchars($row['Bud_Remarks']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($summary): ?>
                    <div class="budget-summary">
                        <h4>Budget Summary</h4>
                        <div class="budget-row">
                            <span>Total Income:</span>
                            <span>RM <?= number_format($summary['Total_Income'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span>Total Expense:</span>
                            <span>RM <?= number_format($summary['Total_Expense'], 2) ?></span>
                        </div>
                        <div class="budget-row total">
                            <span>Surplus/Deficit:</span>
                            <span style="color: #27ae60;">RM <?= number_format($summary['Surplus_Deficit'], 2) ?></span>
                        </div>
                        <div style="margin-top: 15px;">
                            <div class="info-label">Prepared By:</div>
                            <div class="info-value"><?= htmlspecialchars($summary['Prepared_By'] ?? '-') ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Section 7: Additional Information -->
            <div class="section">
                <h2 class="section-title">Additional Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Alternative Date</div>
                        <div class="info-value">
                            <?= $event['Ev_AlternativeDate'] ? date('F j, Y', strtotime($event['Ev_AlternativeDate'])) : '-' ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Alternative Venue</div>
                        <div class="info-value"><?= htmlspecialchars($event['AltVenue']) ?></div>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <div class="info-label">Additional Documents</div>
                    <div class="info-value">
                        <?php
                        $docPath = str_replace('../../', '../', $event['Ev_AdditionalInfo']);
                        ?>
                        <?php if (!empty($event['Ev_AdditionalInfo'])): ?>
                            <a href="<?= $docPath ?>" class="download-link" target="_blank">📄 View
                                Document</a>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <!-- Back or Return Button -->
        <?php if ($user_type === 'student'): ?>
            <button class="btn btn-secondary" id="returnBtn" onclick="goBack()">Return</button>
        <?php else: ?>
            <button class="btn btn-secondary" onclick="goBack()">← Back</button>
        <?php endif; ?>

        <!-- Edit Button only for Student and status is pending/rejected -->
        <?php if ($user_type === 'student' && in_array((int) $event['Status_ID'], [1, 3])): ?>
            <button class="btn btn-primary" onclick="editProposal()">✏️ Edit</button>
        <?php endif; ?>

        <!-- Export PDF for all roles -->
        <a href="../components/pdf/generate_pdf.php?id=<?= urlencode($ev_id) ?>" target="_blank"
            class="btn btn-success">
            📄 Export PDF
        </a>
    </div>


    <script>
        // Set current date for print
        document.getElementById('printDate').textContent = new Date().toLocaleDateString();
        // PHP will insert the real Status_ID here



        const statusID = <?= (int) $event['Status_ID'] ?>;
        function editProposal() {
            if (![1, 3].includes(statusID)) {
                Swal.fire({
                    title: 'Not Allowed',
                    text: 'You can only edit this proposal when it is still under advisor or coordinator review.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            Swal.fire({
                title: 'Edit Proposal?',
                text: 'You will be redirected to the edit form.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Edit',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../student/proposal/editmodifyform.php?mode=edit&id=<?= $ev_id ?>`;
                }
            });
        }

        function goBack() {
            Swal.fire({
                title: 'Return?',
                text: 'Are you sure you want to go back?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Return',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const previousPage = document.referrer;
                    const userType = "<?= $user_type ?>"; // from PHP session

                    let fallbackPage = 'index.php'; // default if somehow userType unknown

                    if (userType === 'student') {
                        fallbackPage = '../student/StudentDashboard.php';
                    } else if (userType === 'advisor') {
                        fallbackPage = '../advisor/AdvisorDashboard.php';
                    } else if (userType === 'coordinator') {
                        fallbackPage = '../coordinator/CoordinatorDashboard.php';
                    }

                    // Use previous page if available, else fallback
                    const redirectPage = previousPage || fallbackPage;
                    window.location.href = redirectPage;
                }
            });
        }

        // Add smooth scrolling for better UX
        document.querySelectorAll('.info-item').forEach(item => {
            item.addEventListener('click', function () {
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-2px)';
                }, 200);
            });
        });

        // Add animation to tables
        document.querySelectorAll('table tbody tr').forEach((row, index) => {
            row.style.animation = `fadeIn 0.6s ease-out ${index * 0.1}s both`;
        });

        // Enhance poster image interaction
        document.querySelector('.poster-image').addEventListener('click', function () {
            Swal.fire({
                imageUrl: this.src,
                imageAlt: 'Event Poster',
                imageWidth: '80%',
                imageHeight: 'auto',
                showConfirmButton: false,
                showCloseButton: true,
                background: 'rgba(0,0,0,0.9)'
            });
        });


        // Add loading animation for heavy content
        window.addEventListener('load', function () {
            document.querySelectorAll('.section').forEach((section, index) => {
                setTimeout(() => {
                    section.style.opacity = '1';
                    section.style.transform = 'translateX(0)';
                }, index * 100);
            });
        });

        // Initialize sections with loading state
        document.querySelectorAll('.section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateX(-20px)';
            section.style.transition = 'all 0.6s ease-out';
        });

        // Add tooltip functionality for status badge
        document.querySelector('.status-badge').addEventListener('mouseenter', function () {
            const status = this.textContent.toLowerCase().trim();
            let message = '';

            switch (status) {
                case 'Pending Advisor':
                    message = 'This proposal is waiting for approval from the Coordinator.';
                    break;
                case 'Pending Coordinator':
                    message = 'This proposal has been approved and the event can proceed.';
                    break;
                case 'Ongoing':
                    message = 'This proposal was rejected. You can edit and resubmit.';
                    break;
            }

            if (message) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = message;
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 0.8em;
                    white-space: nowrap;
                    z-index: 1000;
                    top: 100%;
                    right: 0;
                    margin-top: 5px;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;

                this.style.position = 'relative';
                this.appendChild(tooltip);

                setTimeout(() => {
                    tooltip.style.opacity = '1';
                }, 10);
            }
        });

        document.querySelector('.status-badge').addEventListener('mouseleave', function () {
            const tooltip = this.querySelector('.tooltip');
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(() => {
                    tooltip.remove();
                }, 300);
            }
        });



        // Add dynamic status update functionality (for real implementation)




        // Add responsive table scrolling indicator
        function addScrollIndicators() {
            document.querySelectorAll('.table-container').forEach(container => {
                const table = container.querySelector('table');

                if (table.scrollWidth > container.clientWidth) {
                    const indicator = document.createElement('div');
                    indicator.textContent = '← Scroll to see more →';
                    indicator.style.cssText = `
                        text-align: center;
                        padding: 10px;
                        background: #f8f9ff;
                        color: #666;
                        font-size: 0.9em;
                        border-top: 1px solid #eee;
                    `;
                    container.appendChild(indicator);
                }
            });
        }

        // Check for scroll indicators on window resize
        window.addEventListener('resize', addScrollIndicators);
        document.addEventListener('DOMContentLoaded', addScrollIndicators);
    </script>
</body>

</html>