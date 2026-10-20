<?php
require_once __DIR__ . '/cfg/db.php';

// --- FORMULAR VERARBEITUNG & AJAX MÜSSEN VOR DEM HEADER STEHEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    // 1. Systemeinstellungen (Theme, Bilder, Startseite, Übersichts-Modus & Auth-Status)
    if (isset($_POST['action_save_settings'])) {
        $theme = $_POST['theme'] ?? 'light';
        $showImagesVal = $_POST['show_images'] ?? '1';
        $startPageVal = $_POST['start_page'] ?? 'dashboard';
        $overviewModeVal = $_POST['overview_mode'] ?? 'filter';
        $authEnabledVal = $_POST['auth_enabled'] ?? '0';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['theme', $theme]);
        $stmt->execute(['show_images', $showImagesVal]);
        $stmt->execute(['start_page', $startPageVal]);
        $stmt->execute(['overview_mode', $overviewModeVal]);
        $stmt->execute(['auth_enabled', $authEnabledVal]);

        // Falls es ein AJAX-Request war, SOFORT reines JSON senden und stoppen (VOR jedem HTML!)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

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

// Erst JETZT, nachdem eventuelle AJAX-Exit-Befehle durch sind, kommt der Header!
require_once __DIR__ . '/inc/header.php';

// Access Control: Falls Login aktiv ist, dürfen nur Admins die Seite aufrufen
if (function_exists('isLoginEnabled') && isLoginEnabled()) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

$msgSuccess = $msgSuccess ?? '';
$msgError = $msgError ?? '';

// Benutzerliste abrufen
$users = $pdo->query("SELECT * FROM users ORDER BY is_default DESC, id ASC")->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
       
        <?php if (isset($_GET['success']) || $msgSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($msgSuccess ?: 'Einstellungen gespeichert!') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($msgError): ?>
            <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Karte 1: Systemeinstellungen (Allgemein & Sicherheit) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><i class="fa-solid fa-sliders me-2"></i>Systemeinstellungen</div>
            <div class="card-body p-4">
                <form action="settings.php" method="POST" id="settingsForm">
                    <input type="hidden" name="action_save_settings" value="1">
               
                    <!-- Block 1: Allgemeine Einstellungen (Theme, Bilder, Startseite, Übersichts-Modus) -->
                    <?php require_once __DIR__ . '/inc/settings/general.php'; ?>

                    <hr class="my-4">

                    <!-- Block 2: Sicherheit & Zugriff -->
                    <?php require_once __DIR__ . '/inc/settings/security.php'; ?>

                </form>
            </div>
        </div>

        <!-- Karte 2: Benutzerverwaltung -->
        <?php require_once __DIR__ . '/inc/settings/users.php'; ?>

    </div>
</div>

<!-- Live-Update Skript für den Menü-Link & Theme via AJAX -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const settingsForm = document.getElementById('settingsForm');
    if (!settingsForm) return;

    settingsForm.querySelectorAll('.auto-submit').forEach(input => {
        input.addEventListener('change', function() {
            try {
                const formData = new FormData(settingsForm);

                // Theme direkt live umschalten
                if (this.name === 'theme') {
                    document.documentElement.setAttribute('data-bs-theme', this.value);
                }

                fetch('settings.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP-Fehler Status: ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            const startPage = settingsForm.querySelector('input[name="start_page"]:checked')?.value;
                            const overviewMode = settingsForm.querySelector('input[name="overview_mode"]:checked')?.value;
                            updateMenuOverviewLink(startPage, overviewMode);
                        }
                    } catch (e) {
                        console.warn("Server-Antwort war kein JSON:", text);
                    }
                })
                .catch(error => {
                    console.error('Fetch-Fehler:', error);
                });

            } catch (err) {
                console.error('Code-Fehler im Event:', err);
            }
        });
    });
});

function updateMenuOverviewLink(startPage, overviewMode) {
    const overviewLinks = document.querySelectorAll('a[href*="index.php"]');
    overviewLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes('index.php') && !href.includes('item.php')) {
            if (startPage === 'dashboard') {
                link.setAttribute('href', 'index.php?mode=' + (overviewMode || 'filter'));
            } else {
                link.setAttribute('href', 'index.php');
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
