<!-- Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-user-graduate me-2"></i>
            Student Panel
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <a class="sidebar-item <?= ($currentPage == 'dashboard') ? 'active' : '' ?>"
            href="../student/StudentDashboard.php">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'profile') ? 'active' : '' ?>" href="../student/StudentProfile.php">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'proposal') ? 'active' : '' ?>"
            href="../student/proposal/ProposalEvent.php?mode=create">
            <i class="fas fa-file-alt"></i>
            <span>Create Proposal</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'postmortem') ? 'active' : '' ?>"
            href="../student/PostmortemView.php">
            <i class="fas fa-calendar-plus"></i>
            <span>Create Post Event</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'progress') ? 'active' : '' ?>" href="../student/ProgressPage.php">
            <i class="fas fa-chart-line"></i>
            <span>Track Progress</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'history') ? 'active' : '' ?>" href="../student/EventHistory.php">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'guide') ? 'active' : '' ?>" href="../student/userguide.php">
            <i class="fas fa-question-circle"></i>
            <span>User Guide</span>
        </a>
        <a class="sidebar-item <?= ($currentPage == 'contact') ? 'active' : '' ?>"
            href="../student/StudentDashboard.php">
            <i class="fas fa-phone"></i>
            <span>Contact</span>
        </a>
        <div class="sidebar-footer text-center mt-auto">
            <hr>
            <small style="color: black; font-size: 0.8rem">
                CMS v1.0 © 2025 Nilai University
            </small>
        </div>




    </div>

</div>