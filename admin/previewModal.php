<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db/dbconfig.php';

$id = $_GET['id'] ?? null;
$mode = $_GET['mode'] ?? 'view';

if (!$id) {
    die("Event ID missing.");
}

// Handle form submission (edit mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_id = $_POST['status_id'] ?? null;
    $ev_date = $_POST['ev_date'] ?? null;
    $venue_id = $_POST['ev_venue_id'] ?? null;
    $start = $_POST['ev_start'] ?? null;
    $end = $_POST['ev_end'] ?? null;
    $created_at = $_POST['created_at'] ?? null;
    $rep_status = $_POST['rep_status'] ?? null;

    // Update event info
    if ($status_id && $ev_date && $start && $end && $created_at) {
        $stmt = $conn->prepare("UPDATE events SET Status_ID=?, Ev_Date=?, Ev_VenueID=?, Ev_StartTime=?, Ev_EndTime=?, created_at=? WHERE Ev_ID=?");
        if ($stmt) {
            $stmt->bind_param("issssss", $status_id, $ev_date, $venue_id, $start, $end, $created_at, $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Update post-event status if exists
    if ($rep_status) {
        $stmt2 = $conn->prepare("UPDATE eventpostmortem SET Status_ID=? WHERE Ev_ID=?");
        if ($stmt2) {
            $stmt2->bind_param("is", $rep_status, $id);
            $stmt2->execute();
            $stmt2->close();
        }
    }

    header("Location: previewModal.php?id=$id&mode=view");
    exit();
}

// Fetch event data with venue names
$eventQuery = "
    SELECT e.*, s.Stu_Name, c.Club_Name, st.Status_Name, p.PIC_Name, p.PIC_PhnNum, p.PIC_ID,
           v1.Venue_Name as Main_Venue, v2.Venue_Name as Alt_Venue
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN eventstatus st ON e.Status_ID = st.Status_ID
    LEFT JOIN personincharge p ON e.Ev_ID = p.Ev_ID
    LEFT JOIN venue v1 ON e.Ev_VenueID = v1.Venue_ID
    LEFT JOIN venue v2 ON e.Ev_AltVenueID = v2.Venue_ID
    WHERE e.Ev_ID = ?
";
$stmt = $conn->prepare($eventQuery);
if ($stmt) {
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    die("Error preparing event query: " . $conn->error);
}

// Get post-event data with status
$postEventQuery = "
    SELECT epm.*, es.Status_Name as Post_Status_Name
    FROM eventpostmortem epm
    LEFT JOIN eventstatus es ON epm.Status_ID = es.Status_ID
    WHERE epm.Ev_ID = ?
";
$stmt = $conn->prepare($postEventQuery);
$postevent = null;
if ($stmt) {
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $postevent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Extract Rep_ID safely
$rep_id = isset($postevent['Rep_ID']) ? $postevent['Rep_ID'] : null;

// Fetch post-event flow from eventflows
$flowResult = null;
if (!empty($rep_id)) {
    $flowStmt = $conn->prepare("SELECT * FROM eventflows WHERE Rep_ID = ? ORDER BY EvFlow_Time ASC");
    if ($flowStmt) {
        $flowStmt->bind_param("s", $rep_id);
        $flowStmt->execute();
        $flowResult = $flowStmt->get_result();
    }
}

// Fetch other data with error checking
$eventminutes = $conn->query("SELECT * FROM eventminutes WHERE Ev_ID = '$id'");
$committees = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$id'");
$budgets = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$id'");
$summary = $conn->query("SELECT * FROM budgetsummary WHERE Ev_ID = '$id'")->fetch_assoc();

// Fetch status dropdown options
$statusOptions = $conn->query("SELECT * FROM eventstatus WHERE Status_Type = 'proposal'");
$postStatusOptions = $conn->query("SELECT * FROM eventstatus WHERE Status_Type = 'postmortem'");
$venueOptions = $conn->query("SELECT * FROM venue ORDER BY Venue_Name");

$challenges = $postevent['Rep_ChallengesDifficulties'] ?? '';
$recommendation = $postevent['Rep_recomendation'] ?? '';
$conclusion = $postevent['Rep_Conclusion'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Preview - Nilai University CMS</title>
    <style>
        :root {
            --header-green: #25aa20;
            --hover-orange: #ff8645;
            --body-orange: rgb(253, 255, 112);
            --container-beige: #DDDAD0;
            --dark-bg: #2c3e50;
            --text-dark: #333;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--body-orange);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--header-green), #20a61c);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--body-orange);
            color: var(--text-dark);
            border: 2px solid var(--text-dark);
            font-weight: bold;
        }

        .btn-secondary {
            background-color: var(--hover-orange);
            color: white;
        }

        .btn-outline {
            background-color: var(--body-orange);
            color: var(--text-dark);
            border: 2px solid var(--text-dark);
            font-weight: bold;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .section {
            background: var(--container-beige);
            margin: 20px 0;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: var(--header-green);
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--hover-orange);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            border-left: 4px solid var(--header-green);
        }

        .info-label {
            font-weight: bold;
            color: var(--header-green);
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 16px;
            color: var(--text-dark);
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        /* Fixed Event Poster Styling */
        .event-poster {
            max-width: 300px;
            height: 400px;
            background: linear-gradient(135deg, white, var(--container-beige));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            cursor: pointer;
            transition: transform 0.3s ease;
            overflow: hidden;
            border: 3px solid var(--header-green);
        }

        .event-poster:hover {
            transform: scale(1.05);
        }

        .event-poster img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 7px;
        }

        /* Fixed Photo Gallery Styling */
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .photo-item {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, var(--hover-orange), white);
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--header-green);
            font-weight: bold;
            overflow: hidden;
            border: 2px solid var(--header-green);
        }

        .photo-item:hover {
            transform: scale(1.05);
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        /* Floating Scroll Buttons */
        .scroll-buttons {
            position: fixed;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .scroll-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--header-green);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .scroll-btn:hover {
            background: var(--hover-orange);
            transform: scale(1.1);
        }

        .table-container {
            overflow-x: auto;
            margin: 20px 0;
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
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--header-green);
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: var(--light-bg);
        }

        .budget-summary {
            background: linear-gradient(135deg, white, rgba(255, 255, 255, 0.8));
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px solid var(--header-green);
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: var(--container-beige);
            border-radius: 5px;
        }

        .budget-total {
            font-weight: bold;
            font-size: 18px;
            color: var(--header-green);
        }

        .report-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--light-bg);
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid var(--header-green);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: none;
            max-height: none;
            width: auto;
            height: auto;
        }

        .modal-content img {
            display: block;
            width: auto;
            height: auto;
            max-width: 95vw;
            max-height: 95vh;
            border-radius: 10px;
            object-fit: contain;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content-img {
            position: relative;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            max-width: 80%;
            max-height: 90vh;
            overflow: auto;
        }

        .close-btn {
            position: absolute;
            top: 6px;
            right: 14px;
            font-size: 28px;
            cursor: pointer;
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        /* Status Badge Styles */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-registered {
            background-color: var(--success);
            color: white;
        }

        .status-not-registered {
            background-color: var(--danger);
            color: white;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            .section {
                padding: 20px;
            }

            .scroll-buttons {
                right: 15px;
            }

            .scroll-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        @media print {

            .header,
            .action-buttons,
            .scroll-buttons {
                display: none;
            }

            .section {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Nilai University - Event Management System</div>
            <div class="action-buttons">
                <a class="btn btn-outline" href="javascript:history.back()">← Back</a>

                <?php if ($mode === 'view'): ?>
                    <a class="btn btn-primary" href="previewModal.php?id=<?= htmlspecialchars($id) ?>&mode=edit">✏️ Edit</a>
                <?php else: ?>
                    <button class="btn btn-primary" type="submit" form="editForm">💾 Save</button>
                    <a class="btn btn-outline" href="previewModal.php?id=<?= htmlspecialchars($id) ?>&mode=view">Cancel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Floating Scroll Buttons -->
    <div class="scroll-buttons">
        <button class="scroll-btn" onclick="scrollToTop()" title="Scroll to Top">↑</button>
        <button class="scroll-btn" onclick="scrollToBottom()" title="Scroll to Bottom">↓</button>
    </div>

    <?php if ($mode === 'edit'): ?>
        <form method="POST" id="editForm">
        <?php endif; ?>

        <div class="container">
            <!-- Event Information Section -->
            <div class="section">
                <h2 class="section-title">Event Information</h2>

                <div class="event-poster" onclick="openModal('posterModal')">
                    <?php
                    // Fixed poster path handling
                    $posterPath = '';
                    $foundPosterPath = null;

                    if (!empty($event['Ev_Poster'])) {
                        if (is_resource($event['Ev_Poster'])) {
                            $posterPath = stream_get_contents($event['Ev_Poster']);
                        } else {
                            $posterPath = $event['Ev_Poster'];
                        }

                        // Check multiple possible poster paths
                        $possiblePosterPaths = [
                            $posterPath,
                            'uploads/posters/' . basename($posterPath),
                            '../uploads/posters/' . basename($posterPath),
                            '../../uploads/posters/' . basename($posterPath),
                            '../' . $posterPath
                        ];

                        foreach ($possiblePosterPaths as $path) {
                            if (!empty($path) && $path !== '0' && file_exists($path)) {
                                $foundPosterPath = $path;
                                break;
                            }
                        }
                    }

                    if ($foundPosterPath): ?>
                        <img src="<?= htmlspecialchars($foundPosterPath) ?>" alt="Event Poster">
                    <?php elseif (!empty($posterPath)): ?>
                        <p class="text-muted">Poster file not found<br><small>Path:
                                <?= htmlspecialchars($posterPath) ?></small></p>
                    <?php else: ?>
                        <p class="text-muted">No poster uploaded.</p>
                    <?php endif; ?>
                </div>

                <div class="info-grid">
                    <!-- Event ID -->
                    <div class="info-item">
                        <div class="info-label">Event ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_ID'] ?? 'N/A') ?></div>
                    </div>

                    <!-- Status -->
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit' && $statusOptions): ?>
                                <select name="status_id" class="form-select">
                                    <?php while ($opt = $statusOptions->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($opt['Status_ID']) ?>"
                                            <?= ($opt['Status_ID'] == $event['Status_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['Status_Name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($event['Status_Name'] ?? 'No Status') ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Event Type Reference</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_TypeRef'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Event Reference Number</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_RefNum'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Student Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Stu_Name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Student ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['Stu_ID'] ?? 'N/A') ?></div>
                    </div>

                    <!-- NEW: Student Position -->
                    <div class="info-item">
                        <div class="info-label">Student Position</div>
                        <div class="info-value"><?= htmlspecialchars($event['Proposal_Position'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Club Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Club_Name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Event Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_Name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Event Nature</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_ProjectNature'] ?? 'N/A') ?></div>
                    </div>

                    <!-- NEW: Event Category -->
                    <div class="info-item">
                        <div class="info-label">Event Category</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_Category'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Event Date</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="date" name="ev_date" class="form-control"
                                    value="<?= htmlspecialchars($event['Ev_Date'] ?? '') ?>">
                            <?php else: ?>
                                <?= !empty($event['Ev_Date']) ? date('F j, Y', strtotime($event['Ev_Date'])) : 'N/A' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Estimated Participants</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_Pax'] ?? 'N/A') ?></div>
                    </div>

                    <!-- Fixed Venue Display -->
                    <div class="info-item">
                        <div class="info-label">Primary Venue</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit' && $venueOptions): ?>
                                <select name="ev_venue_id" class="form-select">
                                    <option value="">Select Venue</option>
                                    <?php while ($venue = $venueOptions->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($venue['Venue_ID']) ?>"
                                            <?= ($venue['Venue_ID'] == $event['Ev_VenueID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($venue['Venue_Name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($event['Main_Venue'] ?? 'No venue selected') ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alternative Venue -->
                    <?php if (!empty($event['Alt_Venue'])): ?>
                        <div class="info-item">
                            <div class="info-label">Alternative Venue</div>
                            <div class="info-value"><?= htmlspecialchars($event['Alt_Venue']) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-label">Start Time</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="time" name="ev_start" class="form-control"
                                    value="<?= htmlspecialchars($event['Ev_StartTime'] ?? '') ?>">
                            <?php else: ?>
                                <?= !empty($event['Ev_StartTime']) ? date('g:i A', strtotime($event['Ev_StartTime'])) : 'N/A' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">End Time</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="time" name="ev_end" class="form-control"
                                    value="<?= htmlspecialchars($event['Ev_EndTime'] ?? '') ?>">
                            <?php else: ?>
                                <?= !empty($event['Ev_EndTime']) ? date('g:i A', strtotime($event['Ev_EndTime'])) : 'N/A' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Fixed Person in Charge Display -->
                    <div class="info-item">
                        <div class="info-label">Person in Charge Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['PIC_Name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Person in Charge ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['PIC_ID'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Person in Charge Contact</div>
                        <div class="info-value"><?= htmlspecialchars($event['PIC_PhnNum'] ?? 'N/A') ?></div>
                    </div>

                    <!-- Fixed Created At Display -->
                    <div class="info-item">
                        <div class="info-label">Created At</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="datetime-local" name="created_at" class="form-control"
                                    value="<?= !empty($event['created_at']) ? date('Y-m-d\TH:i', strtotime($event['created_at'])) : '' ?>">
                            <?php else: ?>
                                <?= !empty($event['created_at']) ? date('F j, Y g:i A', strtotime($event['created_at'])) : 'N/A' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="info-item" style="grid-column: 1/-1">
                    <div class="info-label">Event Introduction</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($event['Ev_Intro'] ?? 'N/A')) ?></div>
                </div>

                <div class="info-item" style="grid-column: 1/-1">
                    <div class="info-label">Event Details</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($event['Ev_Details'] ?? 'N/A')) ?></div>
                </div>

                <div class="info-item" style="grid-column: 1/-1">
                    <div class="info-label">Event Objectives</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($event['Ev_Objectives'] ?? 'N/A')) ?></div>
                </div>
            </div>

            <!-- Event Flow / Meeting Minutes Section -->
            <div class="section">
                <h2 class="section-title">Event Flow / Meeting Minutes</h2>
                <div class="table-container">
                    <?php if ($eventminutes && $eventminutes->num_rows > 0): ?>
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
                                <?php while ($row = $eventminutes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('F j, Y', strtotime($row['Date'])) ?></td>
                                        <td><?= date('h:i A', strtotime($row['Start_Time'])) ?></td>
                                        <td><?= date('h:i A', strtotime($row['End_Time'])) ?></td>
                                        <td><?= htmlspecialchars($row['Hours']) ?></td>
                                        <td><?= htmlspecialchars($row['Activity']) ?></td>
                                        <td><?= htmlspecialchars($row['Remarks']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No meeting minutes recorded for this event.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Committee Members Section -->
            <div class="section">
                <h2 class="section-title">Committee Members</h2>
                <div class="table-container">
                    <?php if ($committees && $committees->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Committee ID</th>
                                    <th>Position</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Phone Number</th>
                                    <th>Job Scope</th>
                                    <th>COCU Claimers</th>
                                    <th>Registration Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($com = $committees->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($com['Com_ID']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Position']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Name']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Email']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Department']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_PhnNum']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_JobScope']) ?></td>
                                        <td><?= strtolower($com['Com_COCUClaimers']) == 'yes' ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <?php if (strtolower($com['Com_Register']) == 'yes'): ?>
                                                <span class="status-badge status-registered">Registered</span>
                                            <?php else: ?>
                                                <span class="status-badge status-not-registered">Not Registered</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No committee members found for this event.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Budget Section -->
            <div class="section">
                <h2 class="section-title">Budget</h2>
                <div class="table-container">
                    <?php if ($budgets && $budgets->num_rows > 0): ?>
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
                                <?php while ($b = $budgets->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($b['Bud_Desc']) ?></td>
                                        <td><?= number_format($b['Bud_Amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($b['Bud_Type']) ?></td>
                                        <td><?= htmlspecialchars($b['Bud_Remarks']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No budget records available for this event.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($summary)): ?>
                    <div class="budget-summary">
                        <div class="budget-row">
                            <span>Total Income:</span>
                            <span class="budget-total" style="color: var(--success)">RM
                                <?= number_format($summary['Total_Income'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span>Total Expenses:</span>
                            <span class="budget-total" style="color: var(--hover-orange)">RM
                                <?= number_format($summary['Total_Expense'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span><?= ($summary['Surplus_Deficit'] >= 0) ? 'Surplus:' : 'Deficit:' ?></span>
                            <span class="budget-total" style="color: var(--header-green)">RM
                                <?= number_format(abs($summary['Surplus_Deficit']), 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span>Prepared by:</span>
                            <span><strong><?= htmlspecialchars($summary['Prepared_By']) ?></strong></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Post Event Report Section -->
            <?php if ($postevent): ?>
                <div class="section">
                    <h2 class="section-title">Post Event Report</h2>

                    <!-- Fixed Post Event Status -->
                    <div class="info-item">
                        <div class="info-label">Post Event Status</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit' && $postStatusOptions): ?>
                                <select name="rep_status" class="form-select">
                                    <option value="">Select Status</option>
                                    <?php while ($status = $postStatusOptions->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($status['Status_ID']) ?>"
                                            <?= ($status['Status_ID'] == $postevent['Status_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status['Status_Name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($postevent['Post_Status_Name'] ?? 'No Status') ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Post Event Photos -->
                    <h3 style="color: var(--header-green); margin: 20px 0">Event Photos</h3>
                    <div class="photo-gallery">
                        <?php
                        $photos = [];
                        if (!empty($postevent['rep_photo'])) {
                            $decoded = json_decode($postevent['rep_photo'], true);
                            if (is_array($decoded)) {
                                $photos = $decoded;
                            }
                        }

                        if (!empty($photos)):
                            $index = 1;
                            foreach ($photos as $photoPath):
                                // Check different possible paths for photos
                                $possiblePaths = [
                                    $photoPath,
                                    'uploads/photos/' . basename($photoPath),
                                    '../uploads/photos/' . basename($photoPath),
                                    '../../uploads/photos/' . basename($photoPath),
                                    '../' . $photoPath
                                ];

                                $foundPath = null;
                                foreach ($possiblePaths as $path) {
                                    if (!empty($path) && file_exists($path)) {
                                        $foundPath = $path;
                                        break;
                                    }
                                }

                                if ($foundPath): ?>
                                    <div class="photo-item" onclick="openImageModal('<?= htmlspecialchars($foundPath) ?>')">
                                        <img src="<?= htmlspecialchars($foundPath) ?>" alt="Event Photo <?= $index ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="photo-item">
                                        <span>📸 Photo <?= $index ?></span>
                                        <small style="display: block; font-size: 12px; margin-top: 5px; color: var(--danger);">
                                            File not found
                                        </small>
                                    </div>
                                <?php endif;
                                $index++;
                            endforeach;
                        else: ?>
                            <p class="text-muted">No event photos uploaded.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Post Event Flow Timeline -->
                    <h3 style="color: var(--header-green); margin: 20px 0">Event Flow Timeline</h3>
                    <div class="table-container">
                        <?php if ($flowResult && $flowResult->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Flow Time</th>
                                        <th>Event Flow Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $flowResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('g:i A', strtotime($row['EvFlow_Time'])) ?></td>
                                            <td><?= htmlspecialchars($row['EvFlow_Description']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">No post-event flow data available.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Challenges -->
                    <h3 style="color: var(--header-green); margin: 20px 0">Challenges</h3>
                    <div class="info-item">
                        <div class="info-value">
                            <?php if (!empty($challenges)): ?>
                                <?= nl2br(htmlspecialchars($challenges)) ?>
                            <?php else: ?>
                                <span class="text-muted">No challenges recorded.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <h3 style="color: var(--header-green); margin: 20px 0">Recommendations</h3>
                    <div class="info-item">
                        <div class="info-value">
                            <?php if (!empty($recommendation)): ?>
                                <?= nl2br(htmlspecialchars($recommendation)) ?>
                            <?php else: ?>
                                <span class="text-muted">No recommendations recorded.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Conclusion -->
                    <h3 style="color: var(--header-green); margin: 20px 0">Conclusion</h3>
                    <div class="info-item">
                        <div class="info-value">
                            <?php if (!empty($conclusion)): ?>
                                <?= nl2br(htmlspecialchars($conclusion)) ?>
                            <?php else: ?>
                                <span class="text-muted">No conclusion recorded.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Supporting Documents -->
            <div class="section">
                <h2 class="section-title">Supporting Documents</h2>

                <?php
                // Fixed Budget Statement File Display
                if (!empty($summary['statement'])):
                    $statement_path = $summary['statement'];

                    // Check multiple possible paths
                    $possibleStatementPaths = [
                        $statement_path,
                        'uploads/statements/' . basename($statement_path),
                        '../uploads/statements/' . basename($statement_path),
                        '../../uploads/statements/' . basename($statement_path),
                        '../' . $statement_path
                    ];

                    $foundStatementPath = null;
                    foreach ($possibleStatementPaths as $path) {
                        if (!empty($path) && file_exists($path)) {
                            $foundStatementPath = $path;
                            break;
                        }
                    }

                    if ($foundStatementPath): ?>
                        <div class="report-item">
                            <span><strong>Budget Statement</strong></span>
                            <a class="btn btn-primary" href="<?= htmlspecialchars($foundStatementPath) ?>" target="_blank">
                                📄 View PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="report-item text-muted">
                            Budget statement file not found.
                            <small style="display: block;">Path: <?= htmlspecialchars($statement_path) ?></small>
                        </div>
                    <?php endif;
                else: ?>
                    <div class="report-item text-muted">No budget statement uploaded.</div>
                <?php endif; ?>

                <!-- COCU Individual Reports -->
                <h4 style="color: var(--header-green); margin: 15px 0">COCU Individual Reports</h4>

                <?php
                // Reset committees result pointer and check for COCU reports
                if ($committees) {
                    $committees->data_seek(0);
                    $hasReports = false;
                    while ($cocu = $committees->fetch_assoc()):
                        if (strtolower($cocu['Com_COCUClaimers']) === 'yes'):
                            $hasReports = true;
                            $name = $cocu['Com_Name'];
                            $id_number = $cocu['Com_ID'];
                            $position = $cocu['Com_Position'];

                            // Check if there's an individual report for this committee member
                            $individualReportPath = null;
                            if (!empty($rep_id)) {
                                $irQuery = "SELECT IR_File FROM individualreport WHERE Rep_ID = ? AND Com_ID = ?";
                                $irStmt = $conn->prepare($irQuery);
                                if ($irStmt) {
                                    $irStmt->bind_param("ss", $rep_id, $id_number);
                                    $irStmt->execute();
                                    $irResult = $irStmt->get_result();
                                    if ($irRow = $irResult->fetch_assoc()) {
                                        $individualReportPath = $irRow['IR_File'];
                                    }
                                    $irStmt->close();
                                }
                            }

                            if (!empty($individualReportPath)):
                                // Check multiple possible paths for individual reports
                                $possibleReportPaths = [
                                    $individualReportPath,
                                    'uploads/individual_reports/' . basename($individualReportPath),
                                    '../uploads/individual_reports/' . basename($individualReportPath),
                                    '../../uploads/individual_reports/' . basename($individualReportPath),
                                    'uploads/cocustatement/' . basename($individualReportPath),
                                    '../uploads/cocustatement/' . basename($individualReportPath),
                                    '../../uploads/cocustatement/' . basename($individualReportPath),
                                    '../' . $individualReportPath
                                ];

                                $foundReportPath = null;
                                foreach ($possibleReportPaths as $path) {
                                    if (!empty($path) && file_exists($path)) {
                                        $foundReportPath = $path;
                                        break;
                                    }
                                }

                                if ($foundReportPath): ?>
                                    <div class="report-item">
                                        <div>
                                            <strong><?= htmlspecialchars($name) ?></strong><br>
                                            <small>ID: <?= htmlspecialchars($id_number) ?> - <?= htmlspecialchars($position) ?>
                                                Report</small>
                                        </div>
                                        <a class="btn btn-secondary" href="<?= htmlspecialchars($foundReportPath) ?>" target="_blank">
                                            📄 View Report
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="report-item text-muted">
                                        <?= htmlspecialchars($name) ?>'s report file not found.
                                        <small style="display: block;">Path: <?= htmlspecialchars($individualReportPath) ?></small>
                                    </div>
                                <?php endif;
                            else: ?>
                                <div class="report-item text-muted">
                                    <?= htmlspecialchars($name) ?>'s individual report not uploaded yet.
                                </div>
                            <?php endif;
                        endif;
                    endwhile;

                    if (!$hasReports): ?>
                        <div class="report-item text-muted">No COCU individual reports available.</div>
                    <?php endif;
                } else { ?>
                    <div class="report-item text-muted">Unable to load committee information.</div>
                <?php } ?>
            </div>
        </div>

        <!-- Poster Modal -->
        <div id="posterModal" class="modal">
            <span class="close" onclick="closeModal('posterModal')">&times;</span>
            <div class="modal-content">
                <?php if ($foundPosterPath): ?>
                    <img src="<?= htmlspecialchars($foundPosterPath) ?>" alt="Event Poster">
                <?php elseif (!empty($posterPath)): ?>
                    <div style="color: white; text-align: center; padding: 50px;">
                        <h3>Poster not available</h3>
                        <p>File path: <?= htmlspecialchars($posterPath) ?></p>
                    </div>
                <?php else: ?>
                    <div style="color: white; text-align: center; padding: 50px;">
                        <h3>No poster uploaded</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Navigation and Modal Functions
            (function () {
                'use strict';

                /* Scroll Functions */
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

                /* Navigation */
                function goBack() {
                    history.back();
                }

                /* Legacy poster modal */
                function openModal(id) {
                    const modal = document.getElementById(id);
                    if (modal) {
                        modal.style.display = "block";
                    }
                }

                function closeModal(id) {
                    const modal = document.getElementById(id);
                    if (modal) {
                        modal.style.display = "none";
                    }
                }

                /* Modern image backdrop modal for photo gallery */
                function openImageModal(src) {
                    const backdrop = document.createElement("div");
                    backdrop.className = "modal-backdrop";
                    backdrop.innerHTML =
                        '<div class="modal-content-img">' +
                        '<span class="close-btn" data-close>&times;</span>' +
                        '<img src="' + src + '" alt="Event Photo" style="max-width:100%;height:auto;">' +
                        '</div>';
                    document.body.appendChild(backdrop);
                }

                /* Global click listener to close backdrop modals */
                document.addEventListener("click", function (e) {
                    if (e.target.matches("[data-close]") || e.target.classList.contains("modal-backdrop")) {
                        const backdrop = e.target.closest(".modal-backdrop");
                        if (backdrop) {
                            backdrop.remove();
                        }
                    }
                });

                /* Expose functions to global scope */
                window.scrollToTop = scrollToTop;
                window.scrollToBottom = scrollToBottom;
                window.goBack = goBack;
                window.openModal = openModal;
                window.closeModal = closeModal;
                window.openImageModal = openImageModal;
            })();
        </script>

        <?php if ($mode === 'edit'): ?>
        </form>
    <?php endif; ?>

</body>

</html>