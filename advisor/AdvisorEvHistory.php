<?php
session_start();
include('../db/dbconfig.php');
$currentPage = 'history';
if (!isset($_SESSION['Adv_ID']) || !isset($_SESSION['Club_ID'])) {
  header("Location: AdvisorLogin.php");
  exit();
}

$adv_id = $_SESSION['Adv_ID'];
$club_id = $_SESSION['Club_ID'];

$advisor_name = 'Advisor';
if (isset($_SESSION['Adv_ID'])) {
  $stmt = $conn->prepare("SELECT Adv_Name FROM advisor WHERE Adv_ID = ?");
  $stmt->bind_param("s", $_SESSION['Adv_ID']);
  $stmt->execute();
  $result = $stmt->get_result();
  $advisor_name = $result->fetch_assoc()['Adv_Name'] ?? 'Advisor';
}

$filter_type = $_GET['type'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_student = $_GET['student'] ?? '';
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$events_per_page = 50;
$offset = ($current_page - 1) * $events_per_page;

// Base query
$query = "
    SELECT 
        e.Ev_ID,
        e.Ev_Name,
        s.Stu_Name,
        e.Ev_Date,
        e.Ev_TypeCode,
        e.Ev_RefNum,
        ep.Rep_ID
    FROM events e
    INNER JOIN student s ON e.Stu_ID = s.Stu_ID
    INNER JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    INNER JOIN eventstatus es ON ep.Status_ID = es.Status_ID
    WHERE e.Club_ID = ?
      AND ep.Status_ID = 8
";

$params = [$club_id];
$types = 'i';

if (!empty($filter_type)) {
  $query .= " AND e.Ev_TypeCode = ?";
  $params[] = $filter_type;
  $types .= 's';
}

if (!empty($filter_year)) {
  $query .= " AND YEAR(e.Ev_Date) = ?";
  $params[] = $filter_year;
  $types .= 's';
}

if (!empty($filter_month)) {
  $query .= " AND MONTH(e.Ev_Date) = ?";
  $params[] = $filter_month;
  $types .= 's';
}

if (!empty($filter_student)) {
  $query .= " AND s.Stu_Name LIKE ?";
  $params[] = "%$filter_student%";
  $types .= 's';
}

$query .= " ORDER BY e.Ev_Date DESC LIMIT ? OFFSET ?";
$params[] = $events_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$event_result = $stmt->get_result();

$totalEvents = 0;

$eventCountQuery = "
    SELECT COUNT(*) AS total 
    FROM events e
    INNER JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE ep.Status_ID = 8 AND e.Club_ID = ?
";

$stmt = $conn->prepare($eventCountQuery);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$countResult = $stmt->get_result();
if ($row = $countResult->fetch_assoc()) {
  $totalEvents = $row['total'];
}

$totalStudents = 0;

$studentCountQuery = "
    SELECT COUNT(DISTINCT c.Com_ID) AS total
    FROM committee c
    INNER JOIN events e ON c.Ev_ID = e.Ev_ID
    INNER JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE ep.Status_ID = 8 AND c.Com_COCUClaimers = 'yes' AND e.Club_ID = ?
";

$stmt2 = $conn->prepare($studentCountQuery);
$stmt2->bind_param("i", $club_id);
$stmt2->execute();
$studentResult = $stmt2->get_result();
if ($row = $studentResult->fetch_assoc()) {
  $totalStudents = $row['total'];
}
$showingStart = $offset + 1;
$showingEnd = min($offset + $event_result->num_rows, $totalEvents);
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Advisor History - Nilai University CMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="../assets/css/advisor2.css?v=<?= time() ?>" rel="stylesheet" />


</head>

<body>
  <?php include('../components/Advoffcanvas.php'); ?>
  <?php include('../components/Advheader.php'); ?>
  <?php include('../model/LogoutDesign.php'); ?>


  <!-- Main Content -->
  <div class="container-fluid main-content">
    <!-- Page Header -->
    <div class="page-header">
      <h2 class="page-title">
        <i class="fas fa-history me-2"></i>
        Event History
      </h2>
      <p class="mb-0 text-muted">
        View and manage your past events and student participation
      </p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
      <div class="summary-card events">
        <div class="icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <h3 id="totalEvents"><?php echo $totalEvents; ?></h3>
        <p>Total Events</p>
      </div>
      <div class="summary-card students">
        <div class="icon">
          <i class="fas fa-users"></i>
        </div>
        <h3 id="totalStudents"><?php echo $totalStudents; ?></h3>
        <p>Total Students Joined</p>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <h5 class="filter-title">
        <i class="fas fa-filter me-2"></i>
        Filter Events
      </h5>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Event Type</label>
          <select class="form-select" id="filterEventType">
            <option value="">All Types</option>
            <option value="CSR">CSR</option>
            <option value="USR">USR</option>
            <option value="SDG">SDG</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Year</label>
          <select class="form-select" id="filterYear">
            <option value="">All Years</option>
            <option value="2025">2025</option>
            <option value="2024">2024</option>
            <option value="2023">2023</option>
            <option value="2022">2022</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Month</label>
          <select class="form-select" id="filterMonth">
            <option value="">All Months</option>
            <option value="01">January</option>
            <option value="02">February</option>
            <option value="03">March</option>
            <option value="04">April</option>
            <option value="05">May</option>
            <option value="06">June</option>
            <option value="07">July</option>
            <option value="08">August</option>
            <option value="09">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Student Name</label>
          <input type="text" class="form-control" id="filterStudentName" placeholder="Enter student name" />
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-filter me-2" onclick="applyFilters()">
            <i class="fas fa-search me-1"></i> Filter
          </button>
          <button class="btn btn-reset" onclick="resetFilters()">
            <i class="fas fa-redo me-1"></i> Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Results Info Section -->
    <div class="results-info-section">
      <div class="results-info">
        <i class="fas fa-info-circle me-2"></i>
        Showing <span id="showingStart"><?= $showingStart ?></span> to
        <span id="showingEnd"><?= $showingEnd ?></span> of
        <span id="totalRecords"><?= $totalEvents ?></span> events
      </div>
    </div>

    <!-- Events Table -->
    <div class="events-table">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Event Name</th>
              <th>Student Name</th>
              <th>Event Date</th>
              <th>Event Type</th>
              <th>Reference Number</th>
              <th>View</th>
              <th>Export Document</th>
            </tr>
          </thead>
          <tbody id="eventsTableBody">
            <?php if ($event_result->num_rows > 0): ?>
              <?php while ($row = $event_result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['Ev_Name']); ?></td>
                  <td><?php echo htmlspecialchars($row['Stu_Name']); ?></td>
                  <td><?php echo date("d M Y", strtotime($row['Ev_Date'])); ?></td>
                  <td>
                    <span class="event-type-badge badge-<?php echo strtolower($row['Ev_TypeCode']); ?>">
                      <?php echo htmlspecialchars($row['Ev_TypeCode']); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($row['Ev_RefNum']); ?></td>
                  <td class="text-nowrap">
                    <a href="../model/viewproposal.php?id=<?= urlencode($row['Ev_ID']) ?>" class="btn btn-view me-1">
                      <i class="fas fa-file-alt me-1"></i> Proposal
                    </a>

                    <a href="../model/viewpostevent.php?rep_id=<?= urlencode($row['Rep_ID']) ?>" class="btn btn-view">
                      <i class="fas fa-clipboard-list me-1"></i> Post-Event
                    </a>
                  </td>

                  <td class="text-nowrap">
                    <a href="../components/pdf/generate_pdf.php?id=<?= urlencode($row['Ev_ID']) ?>"
                      class="btn btn-proposal btn-export me-1" target="_blank">
                      <i class="fas fa-file-pdf me-1"></i> Proposal
                    </a>

                    <a href="../components/pdf/reportgeneratepdf.php?id=<?= urlencode($row['Rep_ID']) ?>"
                      class="btn btn-post btn-export" target="_blank">
                      <i class="fas fa-file-pdf me-1"></i> Post-Event
                    </a>
                  </td>

                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No completed events found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
      <nav>
        <ul class="pagination">
          <?php
          $totalPages = ceil($totalEvents / $events_per_page);

          if ($totalPages > 1):
            // Previous button
            $prevPage = max(1, $current_page - 1);
            $disabledPrev = $current_page == 1 ? 'disabled' : '';
            echo "<li class='page-item $disabledPrev'>
              <a class='page-link' href='?page=$prevPage&type=$filter_type&year=$filter_year&month=$filter_month&student=$filter_student'>
                <i class='fas fa-chevron-left'></i>
              </a>
            </li>";

            // Numbered pages
            for ($i = 1; $i <= $totalPages; $i++) {
              $active = $i == $current_page ? 'active' : '';
              echo "<li class='page-item $active'>
                <a class='page-link' href='?page=$i&type=$filter_type&year=$filter_year&month=$filter_month&student=$filter_student'>$i</a>
              </li>";
            }

            // Next button
            $nextPage = min($totalPages, $current_page + 1);
            $disabledNext = $current_page == $totalPages ? 'disabled' : '';
            echo "<li class='page-item $disabledNext'>
              <a class='page-link' href='?page=$nextPage&type=$filter_type&year=$filter_year&month=$filter_month&student=$filter_student'>
                <i class='fas fa-chevron-right'></i>
              </a>
            </li>";
          endif;
          ?>
        </ul>
      </nav>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>



    let currentPage = 1;
    const eventsPerPage = 50;

    function getEventTypeBadge(type) {
      const badges = {
        CSR: "badge-CSR",
        SDG: "badge-SDG",
        USR: "badge-USR",

      };
      return badges[type] || "badge-seminar";
    }

    function applyFilters() {
      const eventType = document.getElementById("filterEventType").value;
      const year = document.getElementById("filterYear").value;
      const month = document.getElementById("filterMonth").value;
      const student = document.getElementById("filterStudentName").value;

      const query = new URLSearchParams();
      if (eventType) query.append("type", eventType);
      if (year) query.append("year", year);
      if (month) query.append("month", month);
      if (student) query.append("student", student);

      window.location.href = `?${query.toString()}`;
    }

    function resetFilters() {
      window.location.href = "<?= basename($_SERVER['PHP_SELF']) ?>";
    }

    function formatDate(dateString) {
      const options = { year: "numeric", month: "short", day: "2-digit" };
      return new Date(dateString).toLocaleDateString("en-US", options);
    }


    function viewEvent(evId) {
      window.location.href = `EventView.php?Ev_ID=${evId}`;
    }

    function exportDocument(evId, type) {
      if (type === "proposal") {
        window.open(`../exports/generate_pdf.php?Ev_ID=${evId}`, '_blank');
      } else {
        window.open(`../exports/reportgeneratepdf.php?Ev_ID=${evId}`, '_blank');
      }
    }



    // Initialize page
    document.addEventListener("DOMContentLoaded", function () {
      document.getElementById("filterEventType").value = "<?= $filter_type ?>";
      document.getElementById("filterYear").value = "<?= $filter_year ?>";
      document.getElementById("filterMonth").value = "<?= $filter_month ?>";
      document.getElementById("filterStudentName").value = "<?= $filter_student ?>";
    });
  </script>
</body>

</html>