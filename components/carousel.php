<div class="carousel-container">
    <h4 class="section-title">
        <i class="fas fa-images me-2"></i> Event Posters
    </h4>

    <?php if ($carousel_result && $carousel_result->num_rows > 0): ?>
        <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php while ($row = $carousel_result->fetch_assoc()): ?>
                    <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                        <img src="../uploads/posters/<?php echo $row['Ev_Poster']; ?>" class="d-block w-100"
                            alt="<?php echo htmlspecialchars($row['Ev_Name']); ?>" />
                        <div class="carousel-caption d-none d-md-block">
                            <h5><?php echo $row['Ev_Name']; ?></h5>
                            <p>Click to view more details</p>
                        </div>
                    </div>
                    <?php $first = false; ?>
                <?php endwhile; ?>
            </div>

            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    <?php else: ?>
        <div class="carousel-item active">
            <img src="https://via.placeholder.com/800x300/CCCCCC/000000?text=No+Upcoming+Event" class="d-block w-100" />
            <div class="carousel-caption d-none d-md-block">
                <h5>No Events Found</h5>
                <p>You're all caught up!</p>
            </div>
        </div>
    <?php endif; ?>
</div>