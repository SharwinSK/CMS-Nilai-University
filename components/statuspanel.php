<div class="notification-panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h4 class="section-title" style="margin-bottom: 0;">
            <i class="fas fa-bell me-2"></i>
            Status Panel
        </h4>
        <small class="text-muted">Total: <?= $notification_result->num_rows ?>
            <?= $notification_result->num_rows == 1 ? 'event' : 'events' ?></small>
    </div>

    <?php if ($notification_result->num_rows > 0): ?>
        <?php while ($row = $notification_result->fetch_assoc()): ?>
            <div class="notification-item">
                <div>
                    <small class="text-muted"><?= $row['Type'] ?></small>
                    <strong><?= htmlspecialchars($row['Ev_Name']) ?></strong><br />
                    <?php
                    $status = $row['Status_Name'];
                    // Change the display name for this specific status
                    $display_status = ($status === 'Approved by Advisor (Pending Coordinator Review)') ? 'Pending Coordinator Review' : $status;

                    $badgeClass = 'status-pending';
                    if (stripos($status, 'Approved') !== false) {
                        $badgeClass = 'status-approved';
                    } elseif (stripos($status, 'Rejected') !== false || stripos($status, 'Sent Back') !== false) {
                        $badgeClass = 'status-rejected';
                    }
                    ?>
                    <span class="status-badge <?= $badgeClass ?>"><?= $display_status ?></span>
                </div>
                <button class="view-btn" onclick="window.location.href='../student/progresspage.php'">View</button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted">No recent status updates.</p>
    <?php endif; ?>
</div>