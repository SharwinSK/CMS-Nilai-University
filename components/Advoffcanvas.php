

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-chalkboard-teacher me-2"></i>
            Advisor Panel
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <a class="sidebar-item <?= ($currentPage == 'dashboard') ? 'active' : '' ?>"
            href="../advisor/AdvisorDashboard.php">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'profile') ? 'active' : '' ?>" href="../advisor/AdvisorProfile.php">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'progress') ? 'active' : '' ?>"
            href="../advisor/AdvisorProgressView.php">
            <i class="fas fa-tasks"></i>
            <span>Event Ongoing</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'history') ? 'active' : '' ?>"
            href="../advisor/AdvisorEvHistory.php">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'contact') ? 'active' : '' ?>" href="../advisor/AdvisorContact.php">
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