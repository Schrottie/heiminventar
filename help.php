<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/header.php';

// Hilfskonfiguration laden
$helpSections = require_once __DIR__ . '/inc/help-config.php';
?>

<div class="container py-4">
    <!-- Seiten-Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa-solid fa-circle-question text-danger me-2"></i>Hilfe &amp; Bedienhinweise</h2>
            <p class="text-muted mb-0">Ausführliche Dokumentation und Anleitungen zur Inventar- und Lagerortverwaltung.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Zur Übersicht
        </a>
    </div>

    <div class="row">
        <!-- Schnellauswahl / Inhaltsverzeichnis (Desktop: Sticky Sidebar) -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-header bg-light fw-bold">
                    <i class="fa-solid fa-list me-2"></i>Inhalt
                </div>
                <div class="list-group list-group-flush small">
                    <?php foreach ($helpSections as $section): ?>
                        <a href="#<?= htmlspecialchars($section['id']) ?>" class="list-group-item list-group-item-action">
                            <i class="fa-solid <?= htmlspecialchars($section['icon']) ?> me-2"></i><?= htmlspecialchars($section['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Hauptinhalt der Hilfe -->
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <?php foreach ($helpSections as $index => $section): ?>
                        <section id="<?= htmlspecialchars($section['id']) ?>" class="<?= $index < count($helpSections) - 1 ? 'mb-5' : '' ?>">
                            <h5 class="text-danger d-flex align-items-center fw-bold">
                                <i class="fa-solid <?= htmlspecialchars($section['icon']) ?> me-2"></i> <?= htmlspecialchars($section['title']) ?>
                            </h5>
                            <?php 
                            if (file_exists($section['file'])) {
                                include $section['file'];
                            } else {
                                echo '<p class="small text-muted italic">Inhalt wird in Kürze ergänzt...</p>';
                            }
                            ?>
                        </section>

                        <?php if ($index < count($helpSections) - 1): ?>
                            <hr class="my-1 text-black-50">
                        <?php endif; ?>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
