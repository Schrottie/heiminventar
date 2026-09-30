<?php

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Standorte für das Formular und den Baum abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>

<div class="row">
    <!-- Formular: Neuer Standort -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Neuer Standort</div>

            <div class="card-body">
                <form action="api/location-save.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Bezeichnung</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Übergeordnet</label>
                        <select name="parent_id" class="form-select">
                            <option value="">- Oberste Ebene -</option>
                            <?php renderLocationOptions($locations); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-plus me-1"></i>Anlegen
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Übersicht: Standortstruktur (Baumansicht) -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">Standortstruktur</div>
            <div class="card-body">
                <?php renderLocationTree($locations); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
