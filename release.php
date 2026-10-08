<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/header.php';

// --- BERECHTIGUNGSPRÜFUNG ---
$loginActive = function_exists('isLoginEnabled') && isLoginEnabled();
$isAdmin = !$loginActive || (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin');

// --- RELEASES LADEN ---
// Admins sehen auch inaktive Releases, normale Nutzer nur aktive
if ($isAdmin) {
    $releases = $pdo->query("SELECT * FROM app_releases ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $releases = $pdo->query("SELECT * FROM app_releases WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container py-5 flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🚀 Versionshistorie & Changelog</h2>
            <p class="text-muted mb-0">Alle Neuerungen und Updates dieser Anwendung im Überblick.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">Zurück zur App</a>
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
                            <th style="width: 160px;">Datum</th>
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
                            <?php foreach ($releases as $r): ?>
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
                                            <?= date('d.m.Y H:i', strtotime($r['created_at'])) ?>
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

<!-- DETAIL MODAL -->
<div class="modal fade" id="releaseDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="releaseModalVersion">✨ Release Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h4 id="releaseModalTitle" class="fw-bold text-primary"></h4>
        <hr>
        <div id="releaseModalContent" class="release-markdown-body"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function viewReleaseModal(version, title, rawContent) {
    document.getElementById('releaseModalVersion').innerText = '✨ Version ' + version;
    document.getElementById('releaseModalTitle').innerText = title;
    
    if (typeof marked !== 'undefined') {
        document.getElementById('releaseModalContent').innerHTML = marked.parse(rawContent);
    } else {
        document.getElementById('releaseModalContent').innerText = rawContent;
    }

    const modal = new bootstrap.Modal(document.getElementById('releaseDetailModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
