<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');



if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$query_name = "SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?";
$stmt_name = $conn->prepare($query_name);
$stmt_name->bind_param("s", $coordinator_id);
$stmt_name->execute();
$result_name = $stmt_name->get_result();
$coordinator_name = ($result_name->num_rows > 0) ? $result_name->fetch_assoc()['Coor_Name'] : "Coordinator";

// Get filters from GET
$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_club = $_GET['club'] ?? '';
$filter_type = $_GET['type'] ?? '';

$query = "
    SELECT e.Ev_ID, e.Ev_Name, c.Club_Name, e.Ev_RefNum, e.Ev_TypeCode AS Ev_Type
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    JOIN club c ON e.Club_ID = c.Club_ID

    WHERE ep.Rep_PostStatus = 'Accepted'
";


$params = [];
$types = "";

if (!empty($filter_year)) {
    $query .= " AND YEAR(e.Ev_Date) = ?";
    $params[] = $filter_year;
    $types .= "s";
}

if (!empty($filter_month)) {
    $query .= " AND MONTH(e.Ev_Date) = ?";
    $params[] = $filter_month;
    $types .= "s";
}

if (!empty($filter_club)) {
    $query .= " AND c.Club_Name = ?";
    $params[] = $filter_club;
    $types .= "s";
}

if (!empty($filter_type)) {
    $query .= " AND e.EV_TypeCode = ?";
    $params[] = $filter_type;
    $types .= "s";
}

$query .= " ORDER BY ep.Updated_At DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Event History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        body {
            padding-top: 70px;
        }

        .table thead th {
            background-color: #54C392;
            color: #fff;
            text-align: center;
        }

        .table td,
        .table tr {
            background-color: #D2FF72;
            border-color: rgb(0, 0, 0);
            text-align: center;

        }

        .btn-primary {
            background-color: #32CD32;
            color: white;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            background-color: #15B392;
            transform: scale(1.05);
        }

        .btn-primary .fas {
            margin-right: 5px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="CoordinatorDashboard.php">
                <img src="NU logo.png" alt="Logo">
                History
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $coordinator_name; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="CoordinatorProfile.php">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="CoordinatorDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorProfile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorView.php">Proposals & Postmortems</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorProgressView.php">Event Ongoing</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorEventHistory.php">Event History</a></li>

            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2 class="text-center">Event History</h2>
        <?php include('FilteringModal.php'); ?>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Event Name</th>
                    <th>Club Name</th>
                    <th>Reference Number</th>
                    <th>Event Type</th> <!-- Ensure this column exists -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Ev_ID']; ?></td>
                        <td><?php echo $row['Ev_Name']; ?></td>
                        <td><?php echo $row['Club_Name']; ?></td>
                        <td><?php echo $row['Ev_RefNum']; ?></td>
                        <td><?php echo $row['Ev_Type']; ?></td> <!-- Ensure it's displayed -->
                        <td>
                            <a href="ExportPDF.php?event_id=<?php echo urlencode($row['Ev_ID']); ?>"
                                class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i>
                                Export PDF
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>

        </table>

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