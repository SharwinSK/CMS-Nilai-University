<?php if (!empty($pending_proposals)): ?>
    <?php foreach ($pending_proposals as $proposal): ?>
        <div class="notification-item">
            <div>
                <small class="text-muted">Proposal Submission</small><br />
                <strong><?php echo htmlspecialchars($proposal['Ev_Name']); ?></strong><br />
                <span class="status-badge-small status-pending">PENDING REVIEW</span>
            </div>
            <a href="AdvisorDecision.php?event_id=<?= $proposal['Ev_ID'] ?>" class="view-btn">View</a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="notification-item">
        <div>
            <strong>No pending proposals.</strong><br />
            <span class="text-muted">You're all caught up 🎉</span>
        </div>
    </div>
<?php endif; ?>