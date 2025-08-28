<?php
include '../db/dbconfig.php';


$id = $_GET['id'] ?? null;
$mode = $_GET['mode'] ?? 'view';

if (!$id) {
    die("Event ID missing.");
}

// Handle form submission (edit mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_id = $_POST['status_id'];
    $ev_date = $_POST['ev_date'];
    $venue = $_POST['ev_venue'];
    $start = $_POST['ev_start'];
    $end = $_POST['ev_end'];
    $created_at = $_POST['created_at'];
    $rep_status = $_POST['rep_status'] ?? null;

    // Update proposal info
    $stmt = $conn->prepare("UPDATE events SET Status_ID=?, Ev_Date=?, Ev_Venue=?, Ev_StartTime=?, Ev_EndTime=?, created_at=? WHERE Ev_ID=?");
    $stmt->bind_param("issssss", $status_id, $ev_date, $venue, $start, $end, $created_at, $id);
    $stmt->execute();
    $stmt->close();

    // Update post-event status if exists
    if ($rep_status) {
        $stmt2 = $conn->prepare("UPDATE eventpostmortem SET Rep_PostStatus=? WHERE Ev_ID=?");
        $stmt2->bind_param("ss", $rep_status, $id);
        $stmt2->execute();
        $stmt2->close();
    }

    header("Location: previewModal.php?id=$id&mode=view");
    exit();
}

// Fetch event data
$event = $conn->query("
    SELECT e.*, s.Stu_Name, c.Club_Name, st.Status_Name, p.PIC_Name, p.PIC_PhnNum
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN eventstatus st ON e.Status_ID = st.Status_ID
    LEFT JOIN personincharge p ON e.Ev_ID = p.Ev_ID
    WHERE e.Ev_ID = '$id'
")->fetch_assoc();


// Get post-event data
$postevent = $conn->query("SELECT * FROM eventpostmortem WHERE Ev_ID = '$id'")->fetch_assoc();

// Extract Rep_ID safely
$rep_id = isset($postevent['Rep_ID']) ? $postevent['Rep_ID'] : null;

// Fetch post-event flow from eventflows
$flowResult = null;
if (!empty($rep_id)) {
    $flowResult = $conn->query("SELECT * FROM eventflows WHERE Rep_ID = '$rep_id' ORDER BY EvFlow_Time ASC");
}

// Fetch event minutes
$eventminutes = $conn->query("SELECT * FROM eventminutes WHERE Ev_ID = '$id'");

// Fetch committee members
$committees = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$id'");

// Fetch budget
$budgets = $conn->query("SELECT * FROM budget WHERE Ev_ID = '$id'");
$summary = $conn->query("SELECT * FROM budgetsummary WHERE Ev_ID = '$id'")->fetch_assoc();

// Fetch status dropdown
$statusOptions = $conn->query("SELECT * FROM eventstatus");

$challenges = $postevent['Rep_ChallengesDifficulties'] ?? '';
$recommendation = $postevent['Rep_recomendation'] ?? '';
$conclusion = $postevent['Rep_Conclusion'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Preview - Nilai University CMS</title>
    <style>
        :root {
            --primary-color: #03a791;
            --secondary-color: #81e7af;
            --accent-color: #e9f5be;
            --warm-color: #f1ba88;
            --light-bg: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg,
                    var(--primary-color),
                    var(--secondary-color));
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
            background-color: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background-color: var(--warm-color);
            color: white;
        }

        .btn-outline {
            background-color: transparent;
            color: white;
            border: 2px solid white;
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
            background: white;
            margin: 20px 0;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--secondary-color);
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
            background-color: var(--light-bg);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .info-label {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 16px;
            color: #333;
        }

        .event-poster {
            max-width: 300px;
            height: 400px;
            background: linear-gradient(135deg,
                    var(--accent-color),
                    var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .event-poster:hover {
            transform: scale(1.05);
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
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: var(--light-bg);
        }

        .budget-summary {
            background: linear-gradient(135deg,
                    var(--accent-color),
                    rgba(255, 255, 255, 0.8));
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }

        .budget-total {
            font-weight: bold;
            font-size: 18px;
            color: var(--primary-color);
        }

        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .photo-item {
            aspect-ratio: 16/9;
            background: linear-gradient(45deg,
                    var(--secondary-color),
                    var(--accent-color));
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
        }

        .photo-item:hover {
            transform: scale(1.05);
        }

        .report-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--light-bg);
            border-radius: 8px;
            margin: 10px 0;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }

        .modal img {
            width: 100%;
            height: auto;
            border-radius: 10px;
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
        }

        @media print {

            .header,
            .action-buttons {
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
                    <a class="btn btn-primary" href="previewModal.php?type=<?= $type ?>&id=<?= $id ?>&mode=edit">✏️ Edit</a>

                <?php else: ?>
                    <button class="btn btn-primary" type="submit" form="editForm">💾 Save</button>
                    <a class="btn btn-outline" href="previewModal.php?event_id=<?= $id ?>&mode=view">Cancel</a>
                <?php endif; ?>

            </div>

        </div>
    </div>


    <?php if ($mode === 'edit'): ?>
        <form method="POST" id="editForm">
        <?php endif; ?>

        <div class="container">
            <!-- Event Information Section -->
            <div class="section">
                <h2 class="section-title">Event Information</h2>

                <div class="event-poster text-center mb-4" onclick="openModal('posterModal')">
                    <?php
                    // Use relative path for web display if full path is stored
                    $posterPath = isset($event['Ev_Poster']) ? $event['Ev_Poster'] : '';
                    $posterWebPath = $posterPath && file_exists($posterPath) ? $posterPath : null;
                    ?>

                    <?php if ($posterWebPath): ?>
                        <img src="<?= htmlspecialchars($posterWebPath) ?>" class="event-poster shadow rounded"
                            style="max-height: 300px; object-fit: contain;">
                    <?php else: ?>
                        <p class="text-muted fst-italic">No poster uploaded.</p>
                    <?php endif; ?>
                </div>

                <div class="info-grid">
                    <!-- Event ID -->
                    <div class="info-item">
                        <div class="info-label">Event ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_ID']) ?></div>
                    </div>
                    <!-- Status -->
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <select name="status_id" class="form-select">
                                    <?php while ($opt = $statusOptions->fetch_assoc()): ?>
                                        <option value="<?= $opt['Status_ID'] ?>" <?= $opt['Status_ID'] == $event['Status_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['Status_Name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($event['Status_Name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Event Type Reference</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_TypeRef']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Event Reference Number</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_RefNum']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Student Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Stu_Name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Student ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['Stu_ID']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Club Name</div>
                        <div class="info-value"><?= htmlspecialchars($event['Club_Name']) ?></div>
                    </div>
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
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="date" name="ev_date" class="form-control" value="<?= $event['Ev_Date'] ?>">
                            <?php else: ?>
                                <?= date('F j, Y', strtotime($event['Ev_Date'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Estimated Participants</div>
                        <div class="info-value"><?= htmlspecialchars($event['Ev_Pax']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Venue</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="text" name="ev_venue" class="form-control" value="<?= $event['Ev_Venue'] ?>">
                            <?php else: ?>
                                <?= htmlspecialchars($event['Ev_Venue']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Start Time</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="time" name="Ev_StartTime" class="form-control"
                                    value="<?= $event['Ev_StartTime'] ?>">
                            <?php else: ?>
                                <?= date('g:i A', strtotime($event['Ev_StartTime'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">End Time</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="time" name="Ev_EndTime" class="form-control"
                                    value="<?= $event['Ev_EndTime'] ?>">
                            <?php else: ?>
                                <?= date('g:i A', strtotime($event['Ev_EndTime'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Person in Charge Name </div>
                        <div class="info-value"><?= htmlspecialchars($event['PIC_Name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"> Person in Charge ID</div>
                        <div class="info-value"><?= htmlspecialchars($event['PIC_ID']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Person in Charge Contact</div>
                        <div class="info-value"><?= htmlspecialchars($event['PIC_PhnNum']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Created At</div>
                        <div class="info-value">
                            <?php if ($mode === 'edit'): ?>
                                <input type="datetime-local" name="created_at" class="form-control"
                                    value="<?= $event['created_At'] ?>">
                            <?php else: ?>
                                <?= date('F j, Y', strtotime($event['created_At'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <div class="info-item" style="grid-column: 1/-1">
                    <div class="info-label">Event Introduction</div>
                    <div class="info-value">
                        <?= htmlspecialchars($event['Ev_Intro']) ?>
                    </div>
                </div>

                <div class="info-item" style="grid-column: 1/-1">
                    <div class="info-label">Event Details</div>
                    <div class="info-value">
                        <?= htmlspecialchars($event['Ev_Details']) ?>
                    </div>
                </div>

                <div class="info-item" style="grid-column: 1/-1">
                    <div class="info-label">Event Objectives</div>
                    <div class="info-value">
                        <?= htmlspecialchars($event['Ev_Objectives']) ?>
                    </div>
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
                                        <!-- Date column -->
                                        <td><?= date('F j, Y', strtotime($row['Date'])) ?></td>

                                        <!-- Start & End times -->
                                        <td><?= date('h:i A', strtotime($row['Start_Time'])) ?></td>
                                        <td><?= date('h:i A', strtotime($row['End_Time'])) ?></td>

                                        <!-- Hours -->
                                        <td><?= htmlspecialchars($row['Hours']) ?></td>

                                        <!-- Activity & Remarks -->
                                        <td><?= htmlspecialchars($row['Activity']) ?></td>
                                        <td><?= htmlspecialchars($row['Remarks']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No meeting minutes recorded for this event.</p>
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
                                    <th>Department</th>
                                    <th>Phone Number</th>
                                    <th>Job Scope</th>
                                    <th>COCU Claimers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($com = $committees->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($com['Com_ID']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Position']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Name']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_Department']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_PhnNum']) ?></td>
                                        <td><?= htmlspecialchars($com['Com_JobScope']) ?></td>
                                        <td><?= $com['Com_COCUClaimers'] == '1' ? 'Yes' : 'No' ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No committee members found for this event.</p>
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
                        <p class="text-muted fst-italic">No budget records available for this event.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($summary)): ?>
                    <div class="budget-summary mt-4">
                        <div class="budget-row">
                            <span>Total Income:</span>
                            <span class="budget-total" style="color: green">RM
                                <?= number_format($summary['Total_Income'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span>Total Expenses:</span>
                            <span class="budget-total" style="color: red">RM
                                <?= number_format($summary['Total_Expense'], 2) ?></span>
                        </div>
                        <div class="budget-row">
                            <span><?= ($summary['Surplus_Deficit'] >= 0) ? 'Surplus:' : 'Deficit:' ?></span>
                            <span class="budget-total" style="color: var(--primary-color)">RM
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
                <div class="info-item">
                    <div class="info-label">Post Event Status</div>
                    <div class="info-value">
                        <?php if ($mode === 'edit'): ?>
                            <select name="rep_status" class="form-select">
                                <?php foreach (['Pending Coordinator Review', 'Accepted', 'Rejected'] as $rep): ?>
                                    <option value="<?= $rep ?>" <?= $rep === $postevent['Rep_PostStatus'] ? 'selected' : '' ?>>
                                        <?= $rep ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?= htmlspecialchars($postevent['Rep_PostStatus']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>


            <!-- Post Event Photos -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Event Photos</h3>
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
                        // Only display if file exists (optional)
                        if (file_exists($photoPath)):
                            ?>
                            <div class="photo-item" onclick="openImageModal('<?= htmlspecialchars($photoPath) ?>')">
                                <span>📸 Photo <?= $index ?></span>
                            </div>
                            <?php
                            $index++;
                        endif;
                    endforeach;
                else:
                    ?>
                    <p class="text-muted fst-italic">No event photos uploaded.</p>
                <?php endif; ?>
            </div>



            <!-- Post Event Flow Timeline -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Event Flow Timeline</h3>
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
                    <p class="text-muted fst-italic">No post-event flow data available.</p>
                <?php endif; ?>
            </div>


            <!-- Challenges -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Challenges</h3>
            <div class="info-item">
                <div class="info-value">
                    <?php if (!empty($challenges)): ?>
                        <?= nl2br(htmlspecialchars($challenges)) ?>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No challenges recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recommendations -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Recommendations</h3>
            <div class="info-item">
                <div class="info-value">
                    <?php if (!empty($recommendation)): ?>
                        <?= nl2br(htmlspecialchars($recommendation)) ?>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No recommendations recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Conclusion -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Conclusion</h3>
            <div class="info-item">
                <div class="info-value">
                    <?php if (!empty($conclusion)): ?>
                        <?= nl2br(htmlspecialchars($conclusion)) ?>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No conclusion recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Supporting Documents -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Supporting Documents</h3>

            <?php
            // 🔹 Budget Statement File (if exists)
            $statement_path = $budgetsummary['Statement'] ?? '';
            if (!empty($statement_path) && file_exists($statement_path)): ?>
                <div class="report-item">
                    <span><strong>Budget Statement</strong></span>
                    <a class="btn btn-primary" href="<?= $statement_path ?>" target="_blank">
                        📄 View PDF
                    </a>
                </div>
            <?php else: ?>
                <div class="report-item text-muted">No budget statement uploaded.</div>
            <?php endif; ?>

            <!-- 🔹 Individual Reports (from committee table where COCUClaimer = 1) -->
            <h4 style="color: var(--primary-color); margin: 15px 0">Individual Reports</h4>

            <?php
            $indiv_query = $conn->query("SELECT * FROM committee WHERE Ev_ID = '$id' AND Com_COCUClaimers = 1");
            if ($indiv_query->num_rows > 0):
                while ($cocu = $indiv_query->fetch_assoc()):
                    $name = $cocu['Com_Name'];
                    $id_number = $cocu['Com_ID'];
                    $position = $cocu['Com_Position'];
                    $filepath = $cocu['student_statement'];

                    if (!empty($filepath) && file_exists($filepath)): ?>
                        <div class="report-item">
                            <div>
                                <strong><?= htmlspecialchars($name) ?></strong><br />
                                <small>ID: <?= htmlspecialchars($id_number) ?> - <?= htmlspecialchars($position) ?>
                                    Report</small>
                            </div>
                            <a class="btn btn-secondary" href="<?= $filepath ?>" target="_blank">
                                📄 View Report
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="report-item text-muted">
                            <?= htmlspecialchars($name) ?>'s report not uploaded yet.
                        </div>
                    <?php endif;
                endwhile;
            else: ?>
                <div class="report-item text-muted">No COCU individual reports available.</div>
            <?php endif; ?>

            <!-- Poster Modal -->
            <div id="posterModal" class="modal">
                <span class="close" onclick="closeModal('posterModal')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/posters/event-poster.jpg" alt="Event Poster" />
                </div>
            </div>

            <!-- Photo Modals -->
            <div id="photoModal1" class="modal">
                <span class="close" onclick="closeModal('photoModal1')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/photos/opening.jpg" alt="Opening Ceremony" />
                </div>
            </div>
            <div id="photoModal2" class="modal">
                <span class="close" onclick="closeModal('photoModal2')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/photos/keynote.jpg" alt="Keynote Speech" />
                </div>
            </div>
            <div id="photoModal3" class="modal">
                <span class="close" onclick="closeModal('photoModal3')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/photos/workshop.jpg" alt="Workshop Session" />
                </div>
            </div>
            <div id="photoModal4" class="modal">
                <span class="close" onclick="closeModal('photoModal4')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/photos/networking.jpg" alt="Networking" />
                </div>
            </div>
            <div id="photoModal5" class="modal">
                <span class="close" onclick="closeModal('photoModal5')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/photos/group.jpg" alt="Group Photo" />
                </div>
            </div>
            <div id="photoModal6" class="modal">
                <span class="close" onclick="closeModal('photoModal6')">&times;</span>
                <div class="modal-content">
                    <img src="uploads/photos/closing.jpg" alt="Closing Ceremony" />
                </div>
            </div>

            <script>
                // 🛠️ Inline JavaScript for legacy and modern modals
                (() => {
                    /* 1️⃣  Navigation */
                    function goBack() {
                        history.back();
                    }

                    /* 2️⃣  Legacy poster modal (still used for #posterModal) */
                    function openModal(id) {
                        const m = document.getElementById(id);
                        if (m) m.style.display = "block";
                    }
                    function closeModal(id) {
                        const m = document.getElementById(id);
                        if (m) m.style.display = "none";
                    }

                    /* 3️⃣  Modern image backdrop modal (for dynamic photo gallery) */
                    function openImageModal(src) {
                        const backdrop = document.createElement("div");
                        backdrop.className = "modal-backdrop";
                        backdrop.innerHTML = `
            <div class="modal-content-img">
                <span class="close-btn" data-close>&times;</span>
                <img src="${src}" alt="Event Photo" style="max-width:100%;height:auto;">
            </div>`;
                        document.body.appendChild(backdrop);
                    }

                    /* 4️⃣  Global click listener to close any backdrop */
                    document.addEventListener("click", (e) => {
                        // click on X or on dark backdrop
                        if (
                            e.target.matches("[data-close]") ||
                            e.target.classList.contains("modal-backdrop")
                        ) {
                            e.target.closest(".modal-backdrop")?.remove();
                        }
                    });

                    /* 5️⃣  Expose the few helpers we actually call from inline HTML */
                    window.goBack = goBack;
                    window.openModal = openModal;       // for #posterModal
                    window.closeModal = closeModal;     // for #posterModal
                    window.openImageModal = openImageModal; // for photo gallery
                })();
            </script>


            <?php if ($mode === 'edit'): ?>
        </form>
    <?php endif; ?>

</body>

</html>