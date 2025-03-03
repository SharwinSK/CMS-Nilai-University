<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

$advisor_id = $_SESSION['Adv_ID'];
$club_id = $_SESSION['Club_ID'];

$stmt = $conn->prepare("SELECT Adv_Name FROM advisor WHERE Adv_ID = ?");
$stmt->bind_param('s', $advisor_id);
$stmt->execute();
$result = $stmt->get_result();
$advisor_name = $result->fetch_assoc()['Adv_Name'] ?? 'Unknown Advisor';

$query = "
    SELECT c.Club_Name
    FROM advisor a
    INNER JOIN club c ON a.Club_ID = c.Club_ID
    WHERE a.Adv_ID = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $advisor_id);
$stmt->execute();
$result = $stmt->get_result();

$club_name = $result->fetch_assoc()['Club_Name'] ?? 'Unknown Club';


$pending_proposals_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.created_at, s.Stu_Name
    FROM events e
    JOIN student s ON e.Stu_ID = s.Stu_ID
    WHERE e.Club_ID = ? AND e.Ev_Status = 'Pending Advisor Review'
";

$stmt = $conn->prepare($pending_proposals_query);
$stmt->bind_param('i', $club_id);
$stmt->execute();
$pending_proposals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$carousel_query = "
    SELECT DISTINCT e.Ev_ID, e.Ev_Poster 
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID AND ep.Rep_PostStatus = 'Accepted'
    WHERE e.Ev_Status = 'Approved by Coordinator' AND ep.Rep_PostStatus IS NULL
";

$carousel_result = $conn->query($carousel_query);
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styleMain.css">

    <style>
        body {
            padding-top: 70px;
        }

        .list-group-item {
            border-color: black;
            background-color: #D2FF72;
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .list-group-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .text-primary {
            font-weight: bold;
        }

        .btn-outline-primary {
            font-weight: bold;
            border: 2px solid #32CD32;
            color: rgb(122, 50, 205);
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: #73EC8B;
            color: rgb(122, 50, 205);
            transform: scale(1.05);
        }

        canvas {
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: #fff;
        }
    </style>
</head>

<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="StudentDashboard.php">
                <img src="NU logo.png" alt="Logo">
                Dashboard
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $advisor_name; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="AdvisorProfile.php">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a></li>


                </ul>
            </div>
        </div>
    </nav>

    <!--Side Navigation-->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul class="nav flex-column">
                <li class="nav-item"> <a class="nav-link active" href="AdvisorDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="AdvisorProfile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="AdvisorProgressView.php">Event Progress</a></li>
                <li class="nav-item"><a class="nav-link" href="AdvisorEvHistory.php">Event History</a></li>

            </ul>
        </div>
    </div>

    <!-- Event Poster -->
    <div id="main-content">
        <div class="container mt-4">
            <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php if ($carousel_result->num_rows > 0): ?>
                        <?php $isActive = true; ?>
                        <?php while ($event = $carousel_result->fetch_assoc()): ?>
                            <div class="carousel-item <?php echo $isActive ? 'active' : ''; ?>">
                                <img src="<?php echo $event['Ev_Poster']; ?>" class="carousel-img">
                            </div>
                            <?php $isActive = false; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <img src="PlaceHolder.png" class="carousel-img" alt="No Events">
                        </div>
                    <?php endif; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
            <!--Pending Proposal-->
            <div class="container mt-5">
                <h3 class="mb-4">Event Proposals</h3>
                <?php if (!empty($pending_proposals)): ?>
                    <div class="list-group">
                        <?php foreach ($pending_proposals as $proposal): ?>
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center shadow-sm mb-3 p-3 rounded">
                                <div>
                                    <h5 class="mb-1 text-primary"><?php echo $proposal['Ev_Name']; ?></h5>
                                    <p class="mb-0">
                                        <strong>Student:</strong> <?php echo $proposal['Stu_Name']; ?><br>
                                        <strong>Submitted:</strong>
                                        <?php echo date('d M Y', strtotime($proposal['created_at'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <a href="AdvisorDecision.php?event_id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending proposals found.</p>
                <?php endif; ?>
            </div>
            <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <?php
            // End time after processing the page
            $end_time = microtime(true);
            $page_load_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
            
            echo "<p style='color: green; font-weight: bold; text-align: center;'>
      Page Load Time: " . $page_load_time . " ms
      </p>";
            ?>
</body>

</html>