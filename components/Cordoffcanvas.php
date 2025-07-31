<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-user-tie me-2"></i>
            Coordinator Panel
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="sidebar-content">
            <a class="sidebar-item <?= ($currentPage == 'dashboard') ? 'active' : '' ?>"
                href="../coordinator/CoordinatorDashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'profile') ? 'active' : '' ?>"
                href="../coordinator/CoordinatorProfile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'ongoing') ? 'active' : '' ?>"
                href="../coordinator/CoordinatorProgressView.php">
                <i class="fas fa-calendar-check"></i>
                <span>Ongoing Event</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'proposal') ? 'active' : '' ?>"
                href="../coordinator/CoordinatorView.php">
                <i class="fas fa-file-alt"></i>
                <span>Proposal & Post Event</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'history') ? 'active' : '' ?>"
                href="../coordinator/CoordinatorEventHistory.php">
                <i class="fas fa-history"></i>
                <span>History</span>
            </a>
            <a class="sidebar-item <?= ($currentPage == 'contact') ? 'active' : '' ?>" href="CoordinatorContact.php">
                <i class="fas fa-envelope"></i>
                <span>Contact Us</span>
            </a>
        </div>

        <div class="sidebar-footer text-center mt-auto">
            <hr />
            <small style="color: white; font-size: 0.8rem">
                CMS v1.0 © 2025 Nilai University
            </small>
        </div>
    </div>
</div>