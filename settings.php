<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/header.php';

// Access Control: Falls Login aktiv ist, dürfen nur Admins die Seite aufrufen
if (function_exists('isLoginEnabled') && isLoginEnabled()) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        // Option A: Weiterleitung zur Startseite oder Login
        header('Location: index.php');
        exit;
        
        /* 
        // Option B: Alternativ Abbruch mit Fehlermeldung:
        http_response_code(403);
        die('Zugriff verweigert. Diese Funktion steht nur Administratoren zur Verfügung.');
        */
    }
}

$msgSuccess = '';
$msgError = '';

// --- FORMULAR VERARBEITUNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    // 1. Theme, Bilder-Anzeige & Auth-Status
    if (isset($_POST['action_save_settings'])) {
        $theme = $_POST['theme'] ?? $settings['theme'] ?? 'light';
        $showImagesVal = $_POST['show_images'] ?? $settings['show_images'] ?? '1';
        $authEnabledVal = $_POST['auth_enabled'] ?? $settings['auth_enabled'] ?? '0';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['theme', $theme]);
        $stmt->execute(['show_images', $showImagesVal]);
        $stmt->execute(['auth_enabled', $authEnabledVal]);

        header("Location: settings.php?success=1");
        exit;
    }

    // 2. Neuen Benutzer anlegen
    if (isset($_POST['action_create_user'])) {
        $newUsername = trim($_POST['new_username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (!empty($newUsername) && !empty($newPassword)) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                $stmt->execute([$newUsername, $hash, $role]);
                $msgSuccess = "Benutzer '{$newUsername}' als '{$role}' angelegt.";
            } catch (PDOException $e) {
                $msgError = "Benutzername bereits vergeben!";
            }
        }
    }

    // 3. Benutzer Status ändern (Aktivieren / Deaktivieren)
    if (isset($_POST['action_toggle_user'])) {
        $userId = (int)$_POST['user_id'];
        $newStatus = (int)$_POST['target_status'];

        $activeOtherUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1 AND id != $userId")->fetchColumn();

        if ($newStatus === 0 && $activeOtherUsers == 0) {
            $msgError = "Aktion abgebrochen! Es muss mindestens ein aktiver Benutzer im System verbleiben.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            $msgSuccess = "Benutzerstatus aktualisiert.";
        }
    }

    // 4. Benutzer löschen
    if (isset($_POST['action_delete_user'])) {
        $deleteId = (int)$_POST['delete_user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_default = 0");
        $stmt->execute([$deleteId]);
        $msgSuccess = "Benutzer gelöscht.";
    }

    // 5. Benutzer bearbeiten
    if (isset($_POST['action_edit_user'])) {
        $editUserId = (int)$_POST['edit_user_id'];
        $newRole = $_POST['edit_role'] ?? 'user';
        $newPassword = $_POST['edit_password'] ?? '';

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $editUserId]);

        if (!empty($newPassword)) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $editUserId]);
        }

        $msgSuccess = "Benutzerkonto erfolgreich aktualisiert.";
    }
}

// Benutzerliste abrufen
$users = $pdo->query("SELECT * FROM users ORDER BY is_default DESC, id ASC")->fetchAll();

// Prüfen, ob NUR der Default-User existiert / aktiv ist
$totalUsersCount = count($users);
$onlyDefaultUser = ($totalUsersCount === 1 && $users[0]['is_default'] == 1);
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
       
        <?php if (isset($_GET['success']) || $msgSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($msgSuccess ?: 'Einstellungen gespeichert!') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($msgError): ?>
            <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Karte 1: Einstellungen -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><i class="fa-solid fa-sliders me-2"></i>Systemeinstellungen</div>
            <div class="card-body p-4">
                <form action="settings.php" method="POST" id="settingsForm">
                    <input type="hidden" name="action_save_settings" value="1">
               
                    <!-- Theme Einstellung -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Erscheinungsbild</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check auto-submit" name="theme" id="themeLight" value="light" <?= (($settings['theme'] ?? 'light') === 'light') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="themeLight">
                                    <i class="fa-solid fa-sun fa-2x mb-2 text-warning d-block"></i>Hell
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check auto-submit" name="theme" id="themeDark" value="dark" <?= (($settings['theme'] ?? 'light') === 'dark') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="themeDark">
                                    <i class="fa-solid fa-moon fa-2x mb-2 text-primary d-block"></i>Dunkel
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Bilder-Anzeige Einstellung (zwei große Schaltflächen) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Bilder & Medien</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check auto-submit" name="show_images" id="imagesDisabled" value="0" <?= (($settings['show_images'] ?? '1') === '0') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="imagesDisabled">
                                    <i class="fa-solid fa-image-portrait fa-2x mb-2 text-secondary d-block"></i>Deaktiviert
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check auto-submit" name="show_images" id="imagesEnabled" value="1" <?= (($settings['show_images'] ?? '1') === '1') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="imagesEnabled">
                                    <i class="fa-solid fa-image fa-2x mb-2 text-success d-block"></i>Aktiviert
                                </label>
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            <i class="fa-solid fa-circle-info me-1"></i> Wenn diese Option aktiviert ist, werden Vorschaubilder (sofern vorhanden) in der Übersicht angezeigt.
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Passwortschutz Einstellung -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">Passwortschutz / Zugriffsbeschränkung</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check auto-submit" name="auth_enabled" id="authDisabled" value="0" <?= (($settings['auth_enabled'] ?? '0') === '0') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="authDisabled">
                                    <i class="fa-solid fa-lock-open fa-2x mb-2 text-secondary d-block"></i>Deaktiviert
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="auth_enabled" id="authEnabled" value="1" <?= (($settings['auth_enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 p-3 text-center" for="authEnabled">
                                    <i class="fa-solid fa-shield-halved fa-2x mb-2 text-danger d-block"></i>Aktiviert
                                </label>
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            <i class="fa-solid fa-circle-info me-1"></i> Wenn das System öffentlich zugänglich ist (z.B. im Internet), sollte der Passwortschutz aktiviert werden, um unbefugten Zugriff zu verhindern.
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Karte 2: Benutzerverwaltung -->
        <div class="card shadow-sm">
            <div class="card-header"><i class="fa-solid fa-users me-2"></i>Benutzerverwaltung</div>
            <div class="card-body p-4">
               
                <h6 class="fw-bold mb-3">Registrierte Benutzer</h6>
                <div class="list-group mb-4">
                    <?php foreach ($users as $u): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($u['username']) ?></strong>
                                <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?> ms-1"><?= ucfirst($u['role']) ?></span>
                                <?php if ($u['is_default']): ?>
                                    <span class="badge bg-warning text-dark ms-1">Default</span>
                                <?php endif; ?>
                                <?php if (!$u['is_active']): ?>
                                    <span class="badge bg-outline-danger text-danger border ms-1">Deaktiviert</span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-userid="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" data-role="<?= $u['role'] ?>" title="Benutzer bearbeiten">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <form method="POST" action="settings.php">
                                    <input type="hidden" name="action_toggle_user" value="1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="target_status" value="<?= $u['is_active'] ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= $u['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $u['is_active'] ? 'Deaktivieren' : 'Aktivieren' ?>">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                </form>

                                <?php if (!$u['is_default']): ?>
                                    <form method="POST" action="settings.php" onsubmit="return confirm('Benutzer unwiderruflich löschen?');">
                                        <input type="hidden" name="action_delete_user" value="1">
                                        <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr>

                <!-- Neuen Benutzer anlegen -->
                <h6 class="fw-bold mb-3">Neuen Benutzer anlegen</h6>
                <form method="POST" action="settings.php" class="row g-2">
                    <input type="hidden" name="action_create_user" value="1">
                    <div class="col-12 col-md-4">
                        <input type="text" class="form-control" name="new_username" placeholder="Benutzername" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <input type="password" class="form-control" name="new_password" placeholder="Passwort" required>
                    </div>
                    <div class="col-12 col-md-2">
                        <select class="form-select" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-plus me-1"></i>Anlegen</button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>


<!-- JavaScript für Sofort-Speichern und Modal-Sicherheitsabfrage -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isOnlyDefaultUser = <?= json_encode($onlyDefaultUser) ?>;
    const settingsForm = document.getElementById('settingsForm');
    const authDisabledRadio = document.getElementById('authDisabled');
    const authEnabledRadio = document.getElementById('authEnabled');
   
    const enableAuthModalEl = document.getElementById('enableAuthModal');
    const enableAuthModal = enableAuthModalEl ? new bootstrap.Modal(enableAuthModalEl) : null;
    const confirmEnableAuthBtn = document.getElementById('confirmEnableAuth');

    // 1. Radiobuttons mit Klasse auto-submit schicken sofort ab
    document.querySelectorAll('.auto-submit').forEach(input => {
        input.addEventListener('change', function() {
            settingsForm.submit();
        });
    });

    // 2. Passwortschutz Deaktivieren -> Sofort abschicken
    if (authDisabledRadio) {
        authDisabledRadio.addEventListener('change', function() {
            settingsForm.submit();
        });
    }

    // 3. Passwortschutz Aktivieren -> Mit Prüfung auf Only-Default-User
    if (authEnabledRadio) {
        authEnabledRadio.addEventListener('change', function() {
            if (isOnlyDefaultUser) {
                // Radio-Auswahl vorübergehend optisch zurücksetzen, bis bestätigt wurde
                authDisabledRadio.checked = true;
               
                // Sicherheits-Modal anzeigen
                if (enableAuthModal) {
                    enableAuthModal.show();
                }
            } else {
                // Es gibt noch andere User -> direkt speichern
                settingsForm.submit();
            }
        });
    }

    // Modal Bestätigungs-Button
    if (confirmEnableAuthBtn) {
        confirmEnableAuthBtn.addEventListener('click', function() {
            authEnabledRadio.checked = true;
            settingsForm.submit();
        });
    }
});
</script>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
