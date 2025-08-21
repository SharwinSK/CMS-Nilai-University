<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="adminSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="sidebar-nav">
            <a class="sidebar-item <?= ($currentPage == 'dashboard') ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Admin Dashboard</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'eventmanagement') ? 'active' : '' ?>"
                href="eventmanagement.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Event Management</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'clubmanagement') ? 'active' : '' ?>" href="clubmanagement.php">
                <i class="fas fa-users"></i>
                <span>Club Management</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'advisormanagement') ? 'active' : '' ?>"
                href="advisormanagement.php">
                <i class="fas fa-user-tie"></i>
                <span>Advisor Management</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'coordinatormanagement') ? 'active' : '' ?>"
                href="coordinatormanagement.php">
                <i class="fas fa-user-cog"></i>
                <span>Coordinator Management</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'usermanagement') ? 'active' : '' ?>" href="usermanagement.php">
                <i class="fas fa-user-friends"></i>
                <span>User Management</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'reportexport') ? 'active' : '' ?>" href="reportexport.php">
                <i class="fas fa-chart-bar"></i>
                <span>Report & Export</span>
            </a>
        </nav>

        <div class="sidebar-footer text-center mt-auto">
            <hr />
            <small style="color: black; font-size: 0.8rem">
                CMS v1.0 © 2025 Nilai University
            </small>
        </div>
    </div>
</div>