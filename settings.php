
<?php
require_once __DIR__ . '/cfg/db.php';

$message = '';

// Formular-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';

    // Theme in der DB speichern
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                            VALUES ('theme', ?) 
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$theme]);

    $message = 'Einstellungen erfolgreich gespeichert!';
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fa-solid fa-sliders me-2"></i>Systemeinstellungen
            </div>
            <div class="card-body p-4">
                <form action="settings.php" method="POST">
                    
                    <!-- Theme Auswahl -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Erscheinungsbild (Theme)</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="theme" id="themeLight" value="light" <?= ($currentTheme === 'light') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="themeLight">
                                    <i class="fa-solid fa-sun fa-2x mb-2 d-block text-warning"></i>
                                    Hell
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="theme" id="themeDark" value="dark" <?= ($currentTheme === 'dark') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="themeDark">
                                    <i class="fa-solid fa-moon fa-2x mb-2 d-block text-primary"></i>
                                    Dunkel
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Button für Speichern -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Speichern
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
