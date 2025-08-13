<?php
// normalize role for advisor pages so shared scripts (like PDFs) allow access
if (isset($_SESSION['Adv_ID']) && isset($_SESSION['Club_ID'])) {
    $_SESSION['user_type'] = 'coordinator'; // minimal: use an allowed role for exports
}

?>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <button class="btn me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
            <i class="fas fa-bars text-white"></i>
        </button>
        <a class="navbar-brand" href="../advisor/AdvisorDashboard.php">
            <i class="fas fa-university me-2"></i>
            Nilai University CMS
        </a>
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo $advisor_name; ?>

                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="../advisor/AdvisorProfile.php"><i
                                class="fas fa-user me-2"></i>Profile</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="fas fa-sign-out-alt me-2"></i>Log Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>