<div class="carousel-container">
    <h4 class="section-title">
        <i class="fas fa-images me-2"></i> Event Posters
    </h4>

    <?php if (isset($carousel_result) && $carousel_result->num_rows > 0): ?>
        <?php
        // Rewind result for two passes (indicators + items)
        $items = [];
        while ($row = $carousel_result->fetch_assoc()) {
            $items[] = $row;
        }
        ?>
        <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
            <div class="carousel-indicators">
                <?php foreach ($items as $i => $_): ?>
                    <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                        aria-label="Slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($items as $i => $row):
                    $poster = '../uploads/posters/' . $row['Ev_Poster'];
                    $safeAlt = htmlspecialchars($row['Ev_Name'] ?? 'Event Poster', ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <img src="<?= $poster ?>" class="d-block" alt="<?= $safeAlt ?>" loading="lazy"
                            onerror="this.src='../assets/img/PlaceHolder.png';">
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    <?php else: ?>
        <div class="text-center">
            <img src="../assets/img/PlaceHolder.png" class="img-fluid" style="max-height: 300px;" alt="No events">
            <p class="mt-2 text-muted">No ongoing events at the moment.</p>
        </div>
    <?php endif; ?>
</div>