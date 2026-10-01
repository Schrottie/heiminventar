<?php

$error = '';
$success = false;

$envFile = __DIR__ . '/cfg/.env';
$sqlFile = __DIR__ . '/sql/db.sql';

// if (file_exists($envFile)) {
//     die('Die Anwendung wurde bereits installiert.');
// }

// Variablen für das Bootstrap Modal
$showCreateDbModal = false;
$postData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $host = trim($_POST['db_host'] ?? '');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = trim($_POST['db_pass'] ?? '');

    // Wurde das Anlegen der DB im Modal bestätigt?
    $createDbConfirm = isset($_POST['create_db_confirm']);
    $rootUser = trim($_POST['admin_user'] ?? 'root');
    $rootPass = trim($_POST['admin_pass'] ?? '');

    try {
        if ($createDbConfirm) {
            // 1. Verbindung als Admin/Root herstellen
            $pdoAdmin = new PDO(
                "mysql:host={$host};charset=utf8mb4",
                $rootUser,
                $rootPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 2. Datenbank anlegen
            $pdoAdmin->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace("`", "``", $name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // 3. Benutzer anlegen (falls er nicht schon existiert) & Rechte vergeben
            if ($user !== $rootUser) {
                // MariaDB / MySQL spezifisches Anlegen
                $pdoAdmin->exec("CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED BY " . $pdoAdmin->quote($pass) . ";");
                $pdoAdmin->exec("CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY " . $pdoAdmin->quote($pass) . ";");
                $pdoAdmin->exec("GRANT ALL PRIVILEGES ON `" . str_replace("`", "``", $name) . "`.* TO '{$user}'@'localhost';");
                $pdoAdmin->exec("GRANT ALL PRIVILEGES ON `" . str_replace("`", "``", $name) . "`.* TO '{$user}'@'%';");
                $pdoAdmin->exec("FLUSH PRIVILEGES;");
            }
        }

        // Reguläre Verbindung mit den App-Zugangsdaten aufbauen
        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,$pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        if (!file_exists($sqlFile)) {
            throw new Exception('SQL-Datei nicht gefunden.');
        }

        // DB-Tabellen importieren
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);

        // .env Datei schreiben
        $envContent = <<<ENV
DB_HOST={$host}
DB_NAME={$name}
DB_USER={$user}
DB_PASS={$pass}
ENV;

        if (!is_dir(__DIR__ . '/cfg')) {
            mkdir(__DIR__ . '/cfg', 0755, true);
        }

        file_put_contents($envFile, $envContent);$success = true;

    } catch (PDOException $e) {
        // Falls Verbindung scheitert und nicht bereits im Modal bestätigt wurde
        if (!$createDbConfirm) {$showCreateDbModal = true;
            $postData = [
                'db_host' => $host,
                'db_name' => $name,
                'db_user' => $user,
                'db_pass' => $pass
            ];
            $error = 'Verbindung fehlgeschlagen oder Datenbank existiert nicht: ' .$e->getMessage();
        } else {
            $error = 'Fehler beim automatischen Anlegen der DB/User: ' .$e->getMessage();
        }
    } catch (Exception $e) {
        $error =$e->getMessage();
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <?php if ($success): ?>

                <div class="card shadow">
                    <div class="card-body text-center">
                        <h2 class="text-success mb-3">
                            <i class="fa-solid fa-circle-check"></i>
                            Installation erfolgreich
                        </h2>
                        <p>
                            Die Datenbank wurde eingerichtet und die Konfiguration gespeichert.
                        </p>
                        <a href="index.php" class="btn btn-primary">
                            Anwendung öffnen
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="mb-0">Installation</h3>
                    </div>

                    <div class="card-body">

                        <?php if ($error && !$showCreateDbModal): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        $checks = [
                            'PDO MySQL' => extension_loaded('pdo_mysql'),
                            'GD' => extension_loaded('gd'),
                            'SQL-Datei vorhanden' => file_exists($sqlFile),
                            'img/items beschreibbar' => is_writable(__DIR__ . '/img/items')
                        ];
                        $allChecksOk = !in_array(false,$checks, true);
                        ?>

                        <div class="mb-4">
                            <h5>Systemprüfung</h5>
                            <ul class="list-group">
                                <?php foreach ($checks as $label =>$ok): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= $label ?>
                                        <?php if ($ok): ?>
                                            <span class="badge bg-success">OK</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Fehler</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Datenbank-Host</label>
                                <input type="text"
                                       name="db_host"
                                       class="form-control"
                                       value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Datenbankname</label>
                                <input type="text"
                                       name="db_name"
                                       class="form-control"
                                       value="<?= htmlspecialchars($_POST['db_name'] ?? 'inventory_db') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Datenbank-Benutzer</label>
                                <input type="text"
                                       name="db_user"
                                       class="form-control"
                                       value="<?= htmlspecialchars($_POST['db_user'] ?? 'inventory') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Datenbank-Passwort</label>
                                <input type="password"
                                       name="db_pass"
                                       value="<?= htmlspecialchars($_POST['db_pass'] ?? 'y13.dK7gx3.j~1') ?>"
                                       class="form-control">
                            </div>

                            <button class="btn btn-success w-100" <?= $allChecksOk ? '' : 'disabled' ?>>
                                Installation starten
                            </button>
                        </form>

                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal: Datenbank & Benutzer automatisch anlegen -->
<?php if ($showCreateDbModal): ?>
<div class="modal fade" id="createDbModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-database me-2"></i>Datenbank/Benutzer einrichten
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>
                        Die Verbindung zur Datenbank <strong><?= htmlspecialchars($postData['db_name']) ?></strong> mit dem Benutzer <strong><?= htmlspecialchars($postData['db_user']) ?></strong> ist fehlgeschlagen.
                    </p>

                    <div class="accordion mb-3" id="dbHelpAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#freshInstallHelp">
                                    <i class="fa-solid fa-circle-info me-2 text-info"></i>Frische Raspberry Pi OS Installation? (Wichtig!)
                                </button>
                            </h2>
                            <div id="freshInstallHelp" class="accordion-collapse collapse" data-bs-parent="#dbHelpAccordion">
                                <div class="accordion-body bg-light small">
                                    <p>Bei einer frischen MariaDB-Installation auf dem Raspberry Pi darf der Webserver (<code>www-data</code>) aus Sicherheitsgründen nicht direkt auf den <code>root</code>-User zugreifen.</p>
                                    <p class="mb-1"><strong>Führe einmalig folgenden Befehl im Raspi-Terminal aus:</strong></p>
                                    <pre class="bg-dark text-white p-2 rounded mb-2"><code>sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mariadb_native_password USING PASSWORD('rootpasswort'); FLUSH PRIVILEGES;"</code></pre>
                                    <span class="text-muted">(Ersetze <code>rootpasswort</code> durch ein Passwort deiner Wahl und trage es unten als Admin-Passwort ein).</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="fw-bold mb-2">
                        Admin-Zugangsdaten eingeben, um Datenbank & User automatisch anzuliegen:
                    </p>

                    <!-- Versteckte Felder mit den gewünschten App-Zugangsdaten -->
                    <input type="hidden" name="db_host" value="<?= htmlspecialchars($postData['db_host']) ?>">
                    <input type="hidden" name="db_name" value="<?= htmlspecialchars($postData['db_name']) ?>">
                    <input type="hidden" name="db_user" value="<?= htmlspecialchars($postData['db_user']) ?>">
                    <input type="hidden" name="db_pass" value="<?= htmlspecialchars($postData['db_pass']) ?>">
                    <input type="hidden" name="create_db_confirm" value="1">

                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Admin-Benutzer</label>
                            <input type="text" name="admin_user" class="form-control form-control-sm" value="root" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small">Admin-Passwort</label>
                            <input type="password" name="admin_pass" class="form-control form-control-sm" placeholder="Passwort des Admin-Users">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i>Jetzt anlegen & fortfahren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var createModal = new bootstrap.Modal(document.getElementById('createDbModal'));
    createModal.show();
});
</script>
<?php endif; ?>


<?php require_once __DIR__ . '/inc/footer.php'; ?>
