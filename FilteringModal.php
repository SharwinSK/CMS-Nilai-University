<!-- filter_modal.php -->
<?php
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$club = $_GET['club'] ?? '';
$type = $_GET['type'] ?? '';

// Determine role
$role = '';
if (isset($_SESSION['Stu_ID'])) {
    $role = 'student';
    $user_id = $_SESSION['Stu_ID'];
} elseif (isset($_SESSION['Adv_ID'])) {
    $role = 'advisor';
    $user_id = $_SESSION['Adv_ID'];
} elseif (isset($_SESSION['Coor_ID'])) {
    $role = 'coordinator';
    $user_id = $_SESSION['Coor_ID'];
}
?>

<!-- Filter Icon Button -->
<div class="text-end mb-3">
    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#filterModal">
        <i class="fas fa-filter"></i> Filter
    </button>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="filterModalLabel">Filter Events</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Year -->
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <select name="year" id="year" class="form-select">
                            <option value="">All</option>
                            <?php for ($y = 2023; $y <= date('Y'); $y++): ?>
                                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Month -->
                    <div class="mb-3">
                        <label for="month" class="form-label">Month</label>
                        <select name="month" id="month" class="form-select">
                            <option value="">All</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Club -->
                    <div class="mb-3">
                        <label for="club" class="form-label">Club</label>
                        <select name="club" id="club" class="form-select">
                            <option value="">All</option>
                            <?php
                            if ($role == 'advisor') {
                                $club_query = $conn->query("SELECT c.Club_Name FROM club c 
                                    JOIN advisor_club ac ON c.Club_ID = ac.Club_ID 
                                    WHERE ac.Adv_ID = '$user_id'
                                    ORDER BY c.Club_Name");
                            } else {
                                $club_query = $conn->query("SELECT Club_Name FROM club ORDER BY Club_Name");
                            }
                            while ($club_row = $club_query->fetch_assoc()):
                                ?>
                                <option value="<?= $club_row['Club_Name'] ?>" <?= $club == $club_row['Club_Name'] ? 'selected' : '' ?>>
                                    <?= $club_row['Club_Name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Event Type -->
                    <div class="mb-3">
                        <label for="type" class="form-label">Event Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">All</option>
                            <option value="USR" <?= $type == 'USR' ? 'selected' : '' ?>>USR</option>
                            <option value="SDG" <?= $type == 'SDG' ? 'selected' : '' ?>>SDG</option>
                            <option value="CSR" <?= $type == 'CSR' ? 'selected' : '' ?>>CSR</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-success">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>