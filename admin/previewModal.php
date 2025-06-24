<?php
session_start();
include '../dbconfig.php';

/* ── read URL params ─────────────────── */
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$mode = $_GET['mode'] ?? 'view';     // default → view

$allowedType = ['proposal', 'report', 'completed'];
$allowedMode = ['view', 'edit'];

if (!in_array($type, $allowedType, true) || !in_array($mode, $allowedMode, true) || $id === '') {
    die('Invalid link.');
}

/* ── if form submitted (edit-mode save) ─ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'edit') {

    // example: update event name + date (extend as needed)
    if ($type === 'proposal' || $type === 'completed') {
        $stmt = $conn->prepare("
            UPDATE events
               SET Ev_Name = ?, Ev_Date = ?
             WHERE Ev_ID   = ?
        ");
        $stmt->bind_param('sss', $_POST['Ev_Name'], $_POST['Ev_Date'], $id);
        $stmt->execute();
    } elseif ($type === 'report') {
        $stmt = $conn->prepare("
            UPDATE eventpostmortem
               SET Rep_Conclusion = ?
             WHERE Rep_ID = ?
        ");
        $stmt->bind_param('ss', $_POST['Rep_Conclusion'], $id);
        $stmt->execute();
    }

    header("Location: previewModal.php?type=$type&id=$id&mode=view&msg=updated");
    exit();
}

/* ── fetch data for display ───────────── */
switch ($type) {
    case 'proposal':      // coming from events table
        $q = $conn->prepare("
            SELECT e.*, s.Stu_Name, c.Club_Name
            FROM events e
            LEFT JOIN student s ON e.Stu_ID  = s.Stu_ID
            LEFT JOIN club    c ON e.Club_ID = c.Club_ID
            WHERE e.Ev_ID = ?
        ");
        $q->bind_param('s', $id);
        break;

    case 'report':        // coming from eventpostmortem
        $q = $conn->prepare("
            SELECT ep.*, e.Ev_Name, s.Stu_Name
            FROM eventpostmortem ep
            JOIN events  e ON ep.Ev_ID = e.Ev_ID
            JOIN student s ON e.Stu_ID = s.Stu_ID
            WHERE ep.Rep_ID = ?
        ");
        $q->bind_param('s', $id);
        break;

    case 'completed':     // need both tables
        $q = $conn->prepare("
            SELECT e.*, ep.Rep_PostStatus, s.Stu_Name, c.Club_Name
            FROM events e
            JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
            LEFT JOIN student s ON e.Stu_ID  = s.Stu_ID
            LEFT JOIN club    c ON e.Club_ID = c.Club_ID
            WHERE e.Ev_ID = ?
        ");
        $q->bind_param('s', $id);
        break;
}
$q->execute();
$data = $q->get_result()->fetch_assoc();
if (!$data)
    die('Record not found.');
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
                    <a class="btn btn-primary" href="previewModal.php?type=<?= $type ?>&id=<?= $id ?>&mode=edit">
                        ✏️ Edit
                    </a>
                    <a class="btn btn-secondary" href="#" onclick="window.print()">🖨️ Print</a>
                <?php else: ?>
                    <button class="btn btn-primary" type="submit" form="editForm">💾 Save</button>
                    <a class="btn btn-outline" href="previewModal.php?type=<?= $type ?>&id=<?= $id ?>&mode=view">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="container">
        <!-- Event Information Section -->
        <div class="section">
            <h2 class="section-title">Event Information</h2>

            <div class="event-poster" onclick="openModal('posterModal')">
                <div style="text-align: center">
                    <div style="font-size: 48px; margin-bottom: 10px">🎭</div>
                    <div>Click to view poster</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Event ID</div>
                    <div class="info-value">EVT-2024-001</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Type Reference</div>
                    <div class="info-value">Academic Conference</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Reference Number</div>
                    <div class="info-value">NU-AC-2024-001</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Student Name</div>
                    <div class="info-value">Ahmad Fariz bin Abdullah</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Student ID</div>
                    <div class="info-value">NS21004567</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Club ID</div>
                    <div class="info-value">CLUB-CS-001</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Name</div>
                    <div class="info-value">Tech Innovation Summit 2024</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Nature</div>
                    <div class="info-value">Educational/Academic</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Date</div>
                    <div class="info-value">March 15, 2024</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estimated Participants</div>
                    <div class="info-value">150 people</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Venue</div>
                    <div class="info-value">Nilai University Main Auditorium</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Start Time</div>
                    <div class="info-value">9:00 AM</div>
                </div>
                <div class="info-item">
                    <div class="info-label">End Time</div>
                    <div class="info-value">5:00 PM</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Person in Charge</div>
                    <div class="info-value">Dr. Sarah Lee</div>
                </div>
                <div class="info-item">
                    <div class="info-label">PIC ID</div>
                    <div class="info-value">ST-2024-001</div>
                </div>
                <div class="info-item">
                    <div class="info-label">PIC Contact</div>
                    <div class="info-value">+60 12-345-6789</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Created At</div>
                    <div class="info-value">February 10, 2024</div>
                </div>
            </div>

            <div class="info-item" style="grid-column: 1/-1">
                <div class="info-label">Event Introduction</div>
                <div class="info-value">
                    An innovative summit bringing together technology enthusiasts,
                    students, and industry professionals to explore the latest trends in
                    technology and innovation.
                </div>
            </div>

            <div class="info-item" style="grid-column: 1/-1">
                <div class="info-label">Event Details</div>
                <div class="info-value">
                    The Tech Innovation Summit 2024 features keynote speakers from
                    leading tech companies, interactive workshops, networking sessions,
                    and exhibitions showcasing cutting-edge technologies. Participants
                    will have the opportunity to learn about emerging technologies,
                    startup ecosystems, and career opportunities in the tech industry.
                </div>
            </div>

            <div class="info-item" style="grid-column: 1/-1">
                <div class="info-label">Event Objectives</div>
                <div class="info-value">
                    • Foster innovation and creativity among students<br />
                    • Provide networking opportunities with industry professionals<br />
                    • Showcase latest technological developments<br />
                    • Encourage entrepreneurship and startup culture<br />
                    • Bridge the gap between academia and industry
                </div>
            </div>
        </div>

        <!-- Event Flow/Meeting Minutes Section -->
        <div class="section">
            <h2 class="section-title">Event Flow / Meeting Minutes</h2>
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
                        <tr>
                            <td>March 15, 2024</td>
                            <td>9:00 AM</td>
                            <td>10:00 AM</td>
                            <td>1.0</td>
                            <td>Registration & Welcome</td>
                            <td>Smooth registration process</td>
                        </tr>
                        <tr>
                            <td>March 15, 2024</td>
                            <td>10:00 AM</td>
                            <td>11:30 AM</td>
                            <td>1.5</td>
                            <td>Keynote Speech</td>
                            <td>Excellent speaker engagement</td>
                        </tr>
                        <tr>
                            <td>March 15, 2024</td>
                            <td>11:45 AM</td>
                            <td>12:45 PM</td>
                            <td>1.0</td>
                            <td>Workshop Session 1</td>
                            <td>Interactive and informative</td>
                        </tr>
                        <tr>
                            <td>March 15, 2024</td>
                            <td>2:00 PM</td>
                            <td>3:00 PM</td>
                            <td>1.0</td>
                            <td>Panel Discussion</td>
                            <td>Great Q&A session</td>
                        </tr>
                        <tr>
                            <td>March 15, 2024</td>
                            <td>3:15 PM</td>
                            <td>4:15 PM</td>
                            <td>1.0</td>
                            <td>Networking Session</td>
                            <td>Active participation</td>
                        </tr>
                        <tr>
                            <td>March 15, 2024</td>
                            <td>4:15 PM</td>
                            <td>5:00 PM</td>
                            <td>0.75</td>
                            <td>Closing Ceremony</td>
                            <td>Successful completion</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Committee Members Section -->
        <div class="section">
            <h2 class="section-title">Committee Members</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Committee ID</th>
                            <th>Position</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Phone Number</th>
                            <th>Job Scope</th>
                            <th>Claimers</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>COM-001</td>
                            <td>Chairperson</td>
                            <td>Ahmad Fariz bin Abdullah</td>
                            <td>Computer Science</td>
                            <td>+60 12-345-6789</td>
                            <td>Overall coordination</td>
                            <td>RM 500</td>
                        </tr>
                        <tr>
                            <td>COM-002</td>
                            <td>Vice Chairperson</td>
                            <td>Siti Nurhaliza bt Ahmad</td>
                            <td>Information Technology</td>
                            <td>+60 12-987-6543</td>
                            <td>Assist coordination</td>
                            <td>RM 300</td>
                        </tr>
                        <tr>
                            <td>COM-003</td>
                            <td>Secretary</td>
                            <td>Raj Kumar a/l Selvam</td>
                            <td>Computer Science</td>
                            <td>+60 12-456-7890</td>
                            <td>Documentation</td>
                            <td>RM 200</td>
                        </tr>
                        <tr>
                            <td>COM-004</td>
                            <td>Treasurer</td>
                            <td>Li Wei Ming</td>
                            <td>Business Administration</td>
                            <td>+60 12-567-8901</td>
                            <td>Financial management</td>
                            <td>RM 250</td>
                        </tr>
                        <tr>
                            <td>COM-005</td>
                            <td>Logistics Head</td>
                            <td>Fatimah bt Zainal</td>
                            <td>Engineering</td>
                            <td>+60 12-678-9012</td>
                            <td>Equipment & venue setup</td>
                            <td>RM 200</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Budget Section -->
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
                        <tr>
                            <td>Venue Rental</td>
                            <td>2,000.00</td>
                            <td>Expense</td>
                            <td>Main auditorium</td>
                        </tr>
                        <tr>
                            <td>Speaker Fees</td>
                            <td>5,000.00</td>
                            <td>Expense</td>
                            <td>3 keynote speakers</td>
                        </tr>
                        <tr>
                            <td>Catering</td>
                            <td>3,500.00</td>
                            <td>Expense</td>
                            <td>Lunch & refreshments</td>
                        </tr>
                        <tr>
                            <td>Marketing Materials</td>
                            <td>800.00</td>
                            <td>Expense</td>
                            <td>Banners, flyers, promotional items</td>
                        </tr>
                        <tr>
                            <td>Equipment Rental</td>
                            <td>1,200.00</td>
                            <td>Expense</td>
                            <td>AV equipment</td>
                        </tr>
                        <tr>
                            <td>Registration Fees</td>
                            <td>7,500.00</td>
                            <td>Income</td>
                            <td>150 participants @ RM50</td>
                        </tr>
                        <tr>
                            <td>Sponsorship</td>
                            <td>8,000.00</td>
                            <td>Income</td>
                            <td>Corporate sponsors</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="budget-summary">
                <div class="budget-row">
                    <span>Total Income:</span>
                    <span class="budget-total" style="color: green">RM 15,500.00</span>
                </div>
                <div class="budget-row">
                    <span>Total Expenses:</span>
                    <span class="budget-total" style="color: red">RM 12,500.00</span>
                </div>
                <div class="budget-row">
                    <span>Surplus:</span>
                    <span class="budget-total" style="color: var(--primary-color)">RM 3,000.00</span>
                </div>
                <div class="budget-row">
                    <span>Prepared by:</span>
                    <span><strong>Li Wei Ming (Treasurer)</strong></span>
                </div>
            </div>
        </div>

        <!-- Post Event Report Section -->
        <div class="section">
            <h2 class="section-title">Post Event Report</h2>

            <!-- Event Photos -->
            <h3 style="color: var(--primary-color); margin: 20px 0">
                Event Photos
            </h3>
            <div class="photo-gallery">
                <div class="photo-item" onclick="openModal('photoModal1')">
                    <span>📸 Opening Ceremony</span>
                </div>
                <div class="photo-item" onclick="openModal('photoModal2')">
                    <span>📸 Keynote Speech</span>
                </div>
                <div class="photo-item" onclick="openModal('photoModal3')">
                    <span>📸 Workshop Session</span>
                </div>
                <div class="photo-item" onclick="openModal('photoModal4')">
                    <span>📸 Networking</span>
                </div>
                <div class="photo-item" onclick="openModal('photoModal5')">
                    <span>📸 Group Photo</span>
                </div>
                <div class="photo-item" onclick="openModal('photoModal6')">
                    <span>📸 Closing Ceremony</span>
                </div>
            </div>

            <!-- Event Flow Timeline -->
            <h3 style="color: var(--primary-color); margin: 20px 0">
                Event Flow Timeline
            </h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Flow Time</th>
                            <th>Event Flow Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>9:00 AM - 10:00 AM</td>
                            <td>
                                Registration process went smoothly with efficient check-in
                                system. Participants received welcome kits and networking
                                materials.
                            </td>
                        </tr>
                        <tr>
                            <td>10:00 AM - 11:30 AM</td>
                            <td>
                                Keynote speaker delivered an inspiring presentation on "Future
                                of Technology Innovation" with excellent audience engagement
                                and interactive Q&A session.
                            </td>
                        </tr>
                        <tr>
                            <td>11:45 AM - 12:45 PM</td>
                            <td>
                                First workshop session on "AI and Machine Learning" was highly
                                interactive with hands-on activities. Participants showed
                                great enthusiasm and active participation.
                            </td>
                        </tr>
                        <tr>
                            <td>2:00 PM - 3:00 PM</td>
                            <td>
                                Panel discussion featuring industry experts discussing career
                                opportunities in tech. Excellent Q&A session with valuable
                                insights shared.
                            </td>
                        </tr>
                        <tr>
                            <td>3:15 PM - 4:15 PM</td>
                            <td>
                                Networking session facilitated meaningful connections between
                                students and industry professionals. Many business cards were
                                exchanged.
                            </td>
                        </tr>
                        <tr>
                            <td>4:15 PM - 5:00 PM</td>
                            <td>
                                Closing ceremony with certificate presentation and
                                appreciation to speakers and sponsors. Event concluded
                                successfully with positive feedback.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Challenges -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Challenges</h3>
            <div class="info-item">
                <div class="info-value">
                    • <strong>Technical Issues:</strong> Minor audio system glitches
                    during the first 15 minutes were quickly resolved by the technical
                    team.<br /><br />
                    • <strong>Venue Capacity:</strong> Higher than expected attendance
                    (165 vs 150 planned) required quick arrangement of additional
                    seating.<br /><br />
                    • <strong>Catering Delay:</strong> Lunch was delayed by 20 minutes
                    due to traffic, but participants were understanding and the schedule
                    was adjusted accordingly.<br /><br />
                    • <strong>Parking Issues:</strong> Limited parking spaces caused
                    some participants to arrive late. Alternative parking arrangements
                    were made for future events.
                </div>
            </div>

            <!-- Recommendations -->
            <h3 style="color: var(--primary-color); margin: 20px 0">
                Recommendations
            </h3>
            <div class="info-item">
                <div class="info-value">
                    • <strong>Technical Preparation:</strong> Conduct thorough sound
                    checks at least 2 hours before event start to prevent technical
                    issues.<br /><br />
                    • <strong>Capacity Planning:</strong> Always plan for 15-20%
                    over-capacity to accommodate unexpected additional registrations.<br /><br />
                    • <strong>Vendor Management:</strong> Establish backup catering
                    options and require vendors to arrive 30 minutes earlier than
                    scheduled.<br /><br />
                    • <strong>Parking Solutions:</strong> Secure additional parking
                    arrangements or provide shuttle service from nearby parking
                    facilities.<br /><br />
                    • <strong>Registration System:</strong> Implement QR code-based
                    registration for faster check-in process.<br /><br />
                    • <strong>Feedback Collection:</strong> Use digital feedback forms
                    for real-time feedback collection and analysis.
                </div>
            </div>

            <!-- Conclusion -->
            <h3 style="color: var(--primary-color); margin: 20px 0">Conclusion</h3>
            <div class="info-item">
                <div class="info-value">
                    The Tech Innovation Summit 2024 was a resounding success, achieving
                    all its primary objectives of fostering innovation, providing
                    networking opportunities, and bridging the gap between academia and
                    industry. Despite minor challenges, the event received
                    overwhelmingly positive feedback from participants, with 95% rating
                    it as "Excellent" or "Very Good."
                    <br /><br />
                    The event successfully attracted 165 participants, exceeding the
                    target by 10%. The diverse range of speakers and interactive format
                    contributed to high engagement levels throughout the day. The
                    networking sessions facilitated meaningful connections, with many
                    participants expressing interest in future collaborations and
                    internship opportunities.
                    <br /><br />
                    The financial outcome was positive, generating a surplus of RM 3,000
                    which will be allocated to future student development activities.
                    The organizing committee demonstrated excellent teamwork and
                    problem-solving skills, handling challenges professionally and
                    efficiently.
                    <br /><br />
                    This event has set a strong foundation for future technology-focused
                    events at Nilai University, and we recommend making it an annual
                    flagship event for the Computer Science department.
                </div>
            </div>

            <!-- Budget Statement and Individual Reports -->
            <h3 style="color: var(--primary-color); margin: 20px 0">
                Supporting Documents
            </h3>

            <div class="report-item">
                <span><strong>Budget Statement</strong></span>
                <button class="btn btn-primary" onclick="viewBudgetPDF()">
                    📄 View PDF
                </button>
            </div>

            <h4 style="color: var(--primary-color); margin: 15px 0">
                Individual Reports
            </h4>

            <div class="report-item">
                <div>
                    <strong>Ahmad Fariz bin Abdullah</strong><br />
                    <small>ID: NS21004567 - Chairperson Report</small>
                </div>
                <button class="btn btn-secondary" onclick="viewIndividualReport('ahmad')">
                    📄 View Report
                </button>
            </div>

            <div class="report-item">
                <div>
                    <strong>Siti Nurhaliza bt Ahmad</strong><br />
                    <small>ID: NS21004568 - Vice Chairperson Report</small>
                </div>
                <button class="btn btn-secondary" onclick="viewIndividualReport('siti')">
                    📄 View Report
                </button>
            </div>

            <div class="report-item">
                <div>
                    <strong>Raj Kumar a/l Selvam</strong><br />
                    <small>ID: NS21004569 - Secretary Report</small>
                </div>
                <button class="btn btn-secondary" onclick="viewIndividualReport('raj')">
                    📄 View Report
                </button>
            </div>

            <div class="report-item">
                <div>
                    <strong>Li Wei Ming</strong><br />
                    <small>ID: NS21004570 - Treasurer Report</small>
                </div>
                <button class="btn btn-secondary" onclick="viewIndividualReport('liwei')">
                    📄 View Report
                </button>
            </div>
        </div>
        <!-- End of Post Event Report Section -->
    </div>
    <!-- End of container -->

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
        function goBack() {
            window.history.back();
        }

        function editEvent() {
            // Redirect to edit page — adjust link as needed
            window.location.href = "editEvent.php?event_id=EVT-2024-001";
        }

        function openModal(id) {
            document.getElementById(id).style.display = "block";
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        function viewBudgetPDF() {
            // Adjust path accordingly
            window.open("uploads/reports/budget_statement.pdf", "_blank");
        }

        function viewIndividualReport(personID) {
            // Map personID to file path
            const reportMap = {
                ahmad: "uploads/reports/ahmad_report.pdf",
                siti: "uploads/reports/siti_report.pdf",
                raj: "uploads/reports/raj_report.pdf",
                liwei: "uploads/reports/liwei_report.pdf",
            };

            const filePath = reportMap[personID];
            if (filePath) {
                window.open(filePath, "_blank");
            } else {
                alert("Report not found.");
            }
        }

        // Close modal when clicking outside image
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