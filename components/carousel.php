<div class="carousel-container">
    <h4 class="section-title">
        <i class="fas fa-images me-2"></i> Event Posters
    </h4>

    <?php if (isset($carousel_result) && $carousel_result->num_rows > 0): ?>
        <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php $first = true; ?>
                <?php while ($row = $carousel_result->fetch_assoc()): ?>
                    <div class="carousel-item <?= $first ? 'active' : '' ?>">
                        <img src="../uploads/posters/<?= $row['Ev_Poster']; ?>" class="d-block w-100" />
                    </div>
                    <?php $first = false; ?>
                <?php endwhile; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    <?php else: ?>
        <div class="text-center">
            <img src="../assets/img/PlaceHolder.png" class="img-fluid" style="max-height: 300px;" />
            <p class="mt-2 text-muted">No ongoing events at the moment.</p>
        </div>
    <?php endif; ?>
</div>