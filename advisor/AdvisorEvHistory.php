<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Advisor History - Nilai University CMS</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
      :root {
        --primary-purple: #ac73ff;
        --primary-dark: #6a4c93;
        --primary-medium: #8e44ad;
        --primary-light: #f8f9ff;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
      }

      body {
        background: linear-gradient(
          135deg,
          var(--primary-light) 0%,
          var(--primary-medium) 100%
        );
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
      }

      .navbar {
        background: linear-gradient(
          90deg,
          var(--primary-purple) 0%,
          var(--primary-dark) 100%
        );
        box-shadow: 0 4px 15px rgba(172, 115, 255, 0.2);
      }

      .navbar-brand {
        font-weight: bold;
        color: white !important;
        font-size: 1.3rem;
      }

      .navbar-nav .nav-link {
        color: white !important;
        font-weight: 500;
      }

      .dropdown-menu {
        background: var(--primary-light);
        border: none;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        right: 0;
        left: auto;
      }

      .offcanvas {
        background: linear-gradient(
          180deg,
          var(--primary-light) 0%,
          var(--primary-medium) 100%
        );
        border-right: 3px solid var(--primary-purple);
      }

      .offcanvas-header {
        background: var(--primary-purple);
        color: white;
      }

      .offcanvas-body {
        display: flex;
        flex-direction: column;
      }

      .sidebar-footer {
        padding: 10px;
      }

      .sidebar-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 12px;
        color: black;
        text-decoration: none;
        font-weight: 500;
        transition: 0.3s;
      }

      .sidebar-item:hover {
        background-color: var(--primary-purple);
        color: white;
      }

      .sidebar-item.active {
        background-color: var(--primary-purple);
        color: white;
      }

      .main-content {
        margin-top: 20px;
        padding: 0 20px;
      }

      .page-header {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
      }

      .page-title {
        color: var(--primary-dark);
        font-weight: bold;
        margin: 0;
      }

      .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }

      .summary-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s ease;
      }

      .summary-card:hover {
        transform: translateY(-5px);
      }

      .summary-card .icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
      }

      .summary-card.events .icon {
        color: var(--primary-purple);
      }

      .summary-card.students .icon {
        color: var(--info);
      }

      .summary-card h3 {
        font-size: 2rem;
        font-weight: bold;
        margin: 0;
        color: var(--primary-dark);
      }

      .summary-card p {
        margin: 0;
        color: #666;
        font-weight: 500;
      }

      .filter-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
      }

      .filter-title {
        color: var(--primary-dark);
        font-weight: bold;
        margin-bottom: 20px;
      }

      .btn-filter {
        background: var(--primary-purple);
        border: none;
        color: white;
        padding: 10px 25px;
        border-radius: 10px;
        font-weight: 500;
        transition: 0.3s;
      }

      .btn-filter:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
      }

      .btn-reset {
        background: #6c757d;
        border: none;
        color: white;
        padding: 10px 25px;
        border-radius: 10px;
        font-weight: 500;
        transition: 0.3s;
      }

      .btn-reset:hover {
        background: #5a6268;
        transform: translateY(-2px);
      }

      .events-table {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
      }

      .table {
        margin: 0;
      }

      .table thead th {
        background: var(--primary-light);
        color: var(--primary-dark);
        font-weight: bold;
        border: none;
        padding: 15px;
      }

      .table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-color: #f0f0f0;
      }

      .btn-view {
        background: var(--info);
        border: none;
        color: white;
        padding: 5px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: 0.3s;
      }

      .btn-view:hover {
        background: #138496;
        transform: translateY(-1px);
      }

      .btn-export {
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 500;
        transition: 0.3s;
        margin: 2px;
      }

      .btn-proposal {
        background: var(--success);
        border: none;
        color: white;
      }

      .btn-proposal:hover {
        background: #218838;
        transform: translateY(-1px);
      }

      .btn-post {
        background: var(--warning);
        border: none;
        color: white;
      }

      .btn-post:hover {
        background: #e0a800;
        transform: translateY(-1px);
      }

      .event-type-badge {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
      }

      .badge-seminar {
        background: #e3f2fd;
        color: #1976d2;
      }

      .badge-workshop {
        background: #f3e5f5;
        color: #7b1fa2;
      }

      .badge-conference {
        background: #e8f5e8;
        color: #388e3c;
      }

      .badge-competition {
        background: #fff3e0;
        color: #f57c00;
      }

      .results-info-section {
        background: white;
        border-radius: 10px;
        padding: 15px 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        border-left: 4px solid var(--primary-purple);
      }

      .results-info {
        color: var(--primary-dark);
        font-weight: 600;
        font-size: 1rem;
        margin: 0;
      }

      .results-info span {
        color: var(--primary-purple);
        font-weight: bold;
      }

      .pagination-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
      }

      .pagination {
        margin: 0;
      }

      .page-link {
        color: var(--primary-purple);
        border: 1px solid #dee2e6;
        padding: 8px 12px;
      }

      .page-link:hover {
        color: var(--primary-dark);
        background-color: var(--primary-light);
      }

      .page-item.active .page-link {
        background-color: var(--primary-purple);
        border-color: var(--primary-purple);
      }

      .pagination-info {
        margin-right: 20px;
        color: var(--primary-dark);
        font-weight: 500;
      }

      .form-control:focus,
      .form-select:focus {
        border-color: var(--primary-purple);
        box-shadow: 0 0 0 0.2rem rgba(172, 115, 255, 0.25);
      }

      @media (max-width: 768px) {
        .main-content {
          padding: 0 10px;
        }

        .summary-cards {
          grid-template-columns: 1fr;
        }

        .table-responsive {
          font-size: 0.9rem;
        }

        .btn-export {
          padding: 3px 8px;
          font-size: 0.7rem;
        }
      }
    </style>
  </head>
  <body>
    <!-- Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title">
          <i class="fas fa-chalkboard-teacher me-2"></i>
          Advisor Panel
        </h5>
        <button
          type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="offcanvas"
        ></button>
      </div>
      <div class="offcanvas-body">
        <a class="sidebar-item" href="../advisor/AdvisorDashboard.php">
          <i class="fas fa-home"></i>
          <span>Dashboard</span>
        </a>
        <a class="sidebar-item" href="../advisor/AdvisorProfile.php">
          <i class="fas fa-user"></i>
          <span>Profile</span>
        </a>
        <a class="sidebar-item" href="../advisor/AdvisorProgressView.php">
          <i class="fas fa-tasks"></i>
          <span>Event Ongoing</span>
        </a>
        <a class="sidebar-item active" href="../advisor/AdvisorEvHistory.php">
          <i class="fas fa-history"></i>
          <span>History</span>
        </a>
        <a class="sidebar-item" href="../advisor/AdvisorContact.php">
          <i class="fas fa-envelope"></i>
          <span>Contact Us</span>
        </a>

        <div class="sidebar-footer text-center mt-auto">
          <hr />
          <small style="color: black; font-size: 0.8rem">
            CMS v1.0 © 2025 Nilai University
          </small>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
      <div class="container-fluid">
        <button
          class="btn me-3"
          type="button"
          data-bs-toggle="offcanvas"
          data-bs-target="#sidebar"
        >
          <i class="fas fa-bars text-white"></i>
        </button>
        <a class="navbar-brand" href="../advisor/AdvisorDashboard.php">
          <i class="fas fa-university me-2"></i>
          Nilai University CMS
        </a>
        <div class="navbar-nav ms-auto">
          <div class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle"
              href="#"
              role="button"
              data-bs-toggle="dropdown"
            >
              <i class="fas fa-user-circle me-2"></i>
              Advisor Name
            </a>
            <ul class="dropdown-menu">
              <li>
                <a class="dropdown-item" href="#"
                  ><i class="fas fa-user me-2"></i>Profile</a
                >
              </li>
              <li>
                <hr class="dropdown-divider" />
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="#"
                  data-bs-toggle="modal"
                  data-bs-target="#logoutModal"
                >
                  <i class="fas fa-sign-out-alt me-2"></i>Log Out
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

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
          <h3 id="totalEvents">127</h3>
          <p>Total Events</p>
        </div>
        <div class="summary-card students">
          <div class="icon">
            <i class="fas fa-users"></i>
          </div>
          <h3 id="totalStudents">1,234</h3>
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
              <option value="seminar">Seminar</option>
              <option value="workshop">Workshop</option>
              <option value="conference">Conference</option>
              <option value="competition">Competition</option>
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
            <input
              type="text"
              class="form-control"
              id="filterStudentName"
              placeholder="Enter student name"
            />
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
          Showing <span id="showingStart">1</span> to
          <span id="showingEnd">50</span> of
          <span id="totalRecords">127</span> events
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
              <!-- Events will be populated here by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-container">
        <nav>
          <ul class="pagination" id="paginationList">
            <!-- Pagination will be generated by JavaScript -->
          </ul>
        </nav>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Sample data - replace with actual data from PHP/MySQL
      const sampleEvents = [
        {
          id: 1,
          eventName: "Digital Marketing Seminar",
          studentName: "Ahmad Rahman",
          eventDate: "2024-12-15",
          eventType: "seminar",
          referenceNumber: "REF001",
          status: "completed",
        },
        {
          id: 2,
          eventName: "Web Development Workshop",
          studentName: "Siti Nurhaliza",
          eventDate: "2024-11-20",
          eventType: "workshop",
          referenceNumber: "REF002",
          status: "completed",
        },
        {
          id: 3,
          eventName: "AI Conference 2024",
          studentName: "Chen Wei Ming",
          eventDate: "2024-10-10",
          eventType: "conference",
          referenceNumber: "REF003",
          status: "completed",
        },
        {
          id: 4,
          eventName: "Programming Competition",
          studentName: "Raj Kumar",
          eventDate: "2024-09-25",
          eventType: "competition",
          referenceNumber: "REF004",
          status: "completed",
        },
      ];

      // Generate more sample data for pagination testing
      let allEvents = [];
      for (let i = 0; i < 127; i++) {
        const baseEvent = sampleEvents[i % sampleEvents.length];
        allEvents.push({
          ...baseEvent,
          id: i + 1,
          referenceNumber: `REF${String(i + 1).padStart(3, "0")}`,
          eventName: `${baseEvent.eventName} ${i + 1}`,
          studentName: `${baseEvent.studentName} ${i + 1}`,
          eventDate: new Date(
            2024,
            Math.floor(Math.random() * 12),
            Math.floor(Math.random() * 28) + 1
          )
            .toISOString()
            .split("T")[0],
        });
      }

      let filteredEvents = [...allEvents];
      let currentPage = 1;
      const eventsPerPage = 50;

      function getEventTypeBadge(type) {
        const badges = {
          seminar: "badge-seminar",
          workshop: "badge-workshop",
          conference: "badge-conference",
          competition: "badge-competition",
        };
        return badges[type] || "badge-seminar";
      }

      function formatDate(dateString) {
        const options = { year: "numeric", month: "short", day: "2-digit" };
        return new Date(dateString).toLocaleDateString("en-US", options);
      }

      function renderEvents() {
        const tbody = document.getElementById("eventsTableBody");
        const startIndex = (currentPage - 1) * eventsPerPage;
        const endIndex = Math.min(
          startIndex + eventsPerPage,
          filteredEvents.length
        );
        const eventsToShow = filteredEvents.slice(startIndex, endIndex);

        tbody.innerHTML = eventsToShow
          .map(
            (event) => `
                <tr>
                    <td>${event.eventName}</td>
                    <td>${event.studentName}</td>
                    <td>${formatDate(event.eventDate)}</td>
                    <td>
                        <span class="event-type-badge ${getEventTypeBadge(
                          event.eventType
                        )}">
                            ${
                              event.eventType.charAt(0).toUpperCase() +
                              event.eventType.slice(1)
                            }
                        </span>
                    </td>
                    <td>${event.referenceNumber}</td>
                    <td>
                        <button class="btn btn-view" onclick="viewEvent(${
                          event.id
                        })">
                            <i class="fas fa-eye me-1"></i> View
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-proposal btn-export" onclick="exportDocument(${
                          event.id
                        }, 'proposal')">
                            <i class="fas fa-file-alt me-1"></i> Proposal
                        </button>
                        <button class="btn btn-post btn-export" onclick="exportDocument(${
                          event.id
                        }, 'post')">
                            <i class="fas fa-file-pdf me-1"></i> Post Event
                        </button>
                    </td>
                </tr>
            `
          )
          .join("");

        updatePaginationInfo();
        renderPagination();
      }

      function updatePaginationInfo() {
        const startIndex = (currentPage - 1) * eventsPerPage + 1;
        const endIndex = Math.min(
          currentPage * eventsPerPage,
          filteredEvents.length
        );

        document.getElementById("showingStart").textContent = startIndex;
        document.getElementById("showingEnd").textContent = endIndex;
        document.getElementById("totalRecords").textContent =
          filteredEvents.length;
      }

      function renderPagination() {
        const totalPages = Math.ceil(filteredEvents.length / eventsPerPage);
        const paginationList = document.getElementById("paginationList");

        let paginationHTML = "";

        // Previous button
        paginationHTML += `
                <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
                    <a class="page-link" href="#" onclick="changePage(${
                      currentPage - 1
                    })">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
          paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`;
          if (startPage > 2) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
          }
        }

        for (let i = startPage; i <= endPage; i++) {
          paginationHTML += `
                    <li class="page-item ${i === currentPage ? "active" : ""}">
                        <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
        }

        if (endPage < totalPages) {
          if (endPage < totalPages - 1) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
          }
          paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a></li>`;
        }

        // Next button
        paginationHTML += `
                <li class="page-item ${
                  currentPage === totalPages ? "disabled" : ""
                }">
                    <a class="page-link" href="#" onclick="changePage(${
                      currentPage + 1
                    })">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;

        paginationList.innerHTML = paginationHTML;
      }

      function changePage(page) {
        const totalPages = Math.ceil(filteredEvents.length / eventsPerPage);
        if (page >= 1 && page <= totalPages) {
          currentPage = page;
          renderEvents();
          window.scrollTo({ top: 0, behavior: "smooth" });
        }
      }

      function applyFilters() {
        const eventType = document.getElementById("filterEventType").value;
        const year = document.getElementById("filterYear").value;
        const month = document.getElementById("filterMonth").value;
        const studentName = document
          .getElementById("filterStudentName")
          .value.toLowerCase();

        filteredEvents = allEvents.filter((event) => {
          const eventDate = new Date(event.eventDate);
          const eventYear = eventDate.getFullYear().toString();
          const eventMonth = String(eventDate.getMonth() + 1).padStart(2, "0");

          return (
            (!eventType || event.eventType === eventType) &&
            (!year || eventYear === year) &&
            (!month || eventMonth === month) &&
            (!studentName ||
              event.studentName.toLowerCase().includes(studentName))
          );
        });

        currentPage = 1;
        updateSummaryCards();
        renderEvents();
      }

      function resetFilters() {
        document.getElementById("filterEventType").value = "";
        document.getElementById("filterYear").value = "";
        document.getElementById("filterMonth").value = "";
        document.getElementById("filterStudentName").value = "";

        filteredEvents = [...allEvents];
        currentPage = 1;
        updateSummaryCards();
        renderEvents();
      }

      function updateSummaryCards() {
        document.getElementById("totalEvents").textContent =
          filteredEvents.length;

        // Count unique students
        const uniqueStudents = new Set(
          filteredEvents.map((event) => event.studentName)
        );
        document.getElementById("totalStudents").textContent =
          uniqueStudents.size;
      }

      function viewEvent(eventId) {
        // This would typically open a modal or redirect to a detail page
        alert(`Viewing event with ID: ${eventId}`);
      }

      function exportDocument(eventId, type) {
        // This would typically trigger a download or redirect to export functionality
        alert(`Exporting ${type} document for event ID: ${eventId}`);
      }

      // Initialize page
      document.addEventListener("DOMContentLoaded", function () {
        updateSummaryCards();
        renderEvents();
      });
    </script>
  </body>
</html>
