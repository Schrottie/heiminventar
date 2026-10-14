<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// --- DATENBANK-MIGRATION SICHERSTELLEN (release_date Spalte) ---
try {
    $pdo->exec("ALTER TABLE app_releases ADD COLUMN release_date DATE DEFAULT NULL");
} catch (Exception $e) {
    // Spalte existiert bereits, Fehler ignorieren
}

// --- BERECHTIGUNGSPRÜFUNG ---
$loginActive = function_exists('isLoginEnabled') && isLoginEnabled();
$isAdmin = !$loginActive || (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin');

// --- RELEASES LADEN ---
if ($isAdmin) {
    $releases =$pdo->query("SELECT * FROM app_releases ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $releases =$pdo->query("SELECT * FROM app_releases WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="container py-5 flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🚀 Versionshistorie & Changelog</h2>
            <p class="text-muted mb-0">Alle Neuerungen und Updates dieser Anwendung im Überblick.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">← Zur Übersicht</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold py-3">
            📋 Bisherige Releases
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Version</th>
                            <th>Titel</th>
                            <?php if ($isAdmin): ?>
                                <th style="width: 100px;">Status</th>
                            <?php endif; ?>
                            <th style="width: 160px;">Release-Datum</th>
                            <th style="width: 120px;" class="text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($releases)): ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? 5 : 4 ?>" class="text-center text-muted py-4">
                                    Noch keine Releases in der Datenbank vorhanden.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($releases as$r): ?>
                                <?php 
                                    // Fallback auf created_at, falls kein release_date gepflegt wurde
                                    $displayDate = !empty($r['release_date']) ? date('d.m.Y', strtotime($r['release_date'])) : date('d.m.Y', strtotime($r['created_at']));
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-dark font-monospace fs-6">
                                            v<?= htmlspecialchars($r['version']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($r['title']) ?></td>
                                    <?php if ($isAdmin): ?>
                                        <td>
                                            <?php if ($r['is_active']): ?>
                                                <span class="badge bg-success">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inaktiv</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-calendar-days me-1"></i> <?= $displayDate ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="viewReleaseModal(<?= htmlspecialchars(json_encode($r['version'])) ?>, <?= htmlspecialchars(json_encode($r['title'])) ?>, <?= htmlspecialchars(json_encode($r['content'])) ?>)">
                                            🔍 Ansehen
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailansicht Release-Modal -->
 <div class="modal fade" id="releaseDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="releaseModalVersion">✨ Release Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h4 id="releaseModalTitle" class="fw-bold text-primary"></h4>
        <div class="text-muted small mb-3" id="releaseModalDate"></div>
        <hr>
        <div id="releaseModalContent" class="markdown-body"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
