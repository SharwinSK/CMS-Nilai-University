<div class="notification-panel">
    <h4 class="section-title">
        <i class="fas fa-bell me-2"></i>
        Notifications
    </h4>

    <?php if ($notification_result->num_rows > 0): ?>
        <?php while ($row = $notification_result->fetch_assoc()): ?>
            <div class="notification-item">
                <div>
                    <small class="text-muted"><?= $row['Type'] ?> </small>
                    <strong><?= htmlspecialchars($row['Ev_Name']) ?></strong><br />
                    <?php
                    $status = $row['Status_Name'];

                    $badgeClass = 'status-pending';

                    if (stripos($status, 'Approved') !== false) {
                        $badgeClass = 'status-approved';
                    } elseif (stripos($status, 'Rejected') !== false || stripos($status, 'Sent Back') !== false) {
                        $badgeClass = 'status-rejected';
                    }
                    ?>

                    <span class="status-badge <?= $badgeClass ?>"><?= $status ?></span>

                </div>
                <button class="view-btn" onclick="viewNotification('<?= addslashes($row['Ev_Name']) ?>')">View</button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted">No recent notifications.</p>
    <?php endif; ?>
</div>