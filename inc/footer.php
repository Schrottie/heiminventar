    </div>

 <!-- Hintergrund-Overlay beim Öffnen des FAB-Menüs (sorgt für Fokus & verhindert ungewollte Klicks) -->
<div id="fabBackdrop" class="fab-backdrop"></div>

<div id="fabContainer" class="fab-container">

    <!-- SUB-MENÜ: Tools (Wird sichtbar, wenn Tools aktiviert wird) -->
    <div id="toolsSubmenu" class="fab-submenu">
        <div class="fab-submenu-header">
            <i class="fa-solid fa-wrench me-1"></i> Tools
        </div>
        <a href="print.php" class="fab-sub-item">
            <i class="fa-solid fa-print"></i>
            <span>Druck</span>
        </a>
        <a href="inventory-audit.php" class="fab-sub-item">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Inventur</span>
        </a>
        <a href="drag-n-drop.php" class="fab-sub-item">
            <i class="fa-solid fa-arrows-up-down-left-right"></i>
            <span>Verschiebebahnhof</span>
        </a>
        <a href="history.php" class="fab-sub-item">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Protokoll</span>
        </a>
        <a href="archive.php" class="fab-sub-item">
            <i class="fa-solid fa-box-archive"></i>
            <span>Archiv</span>
        </a>
        <a href="import-export.php" class="fab-sub-item">
            <i class="fa-solid fa-file-arrow-down"></i>
            <span>Import / Export</span>
        </a>
        <a href="release.php" class="fab-sub-item">
            <i class="fa-brands fa-square-github"></i>
            <span>Release-Notes</span>
        </a>
    </div>

    <!-- HAUPTAKTIONEN (von oben nach unten strukturiert) -->

    <!-- Tools Trigger Button -->
    <button type="button" id="toolsFabBtn" class="fab-action fab-action-tools" title="Tools & Werkzeuge">
        <i class="fa-solid fa-wrench"></i>
        <span class="fab-label">Tools</span>
        <i class="fa-solid fa-chevron-left ms-auto fab-arrow"></i>
    </button>

    <!-- Dashboard -->
    <a href="dashboard.php" class="fab-action" title="Dashboard">
        <i class="fa-solid fa-chart-pie"></i>
        <span class="fab-label">Dashboard</span>
    </a>

    <!-- Einstellungen -->
    <a href="settings.php" class="fab-action" title="Einstellungen">
        <i class="fa-solid fa-gear"></i>
        <span class="fab-label">Einstellungen</span>
    </a>

    <!-- Lagerorte verwalten -->
    <a href="locations.php" class="fab-action" title="Lagerorte verwalten">
        <i class="fa-solid fa-sitemap"></i>
        <span class="fab-label">Lagerorte</span>
    </a>

    <!-- Gegenstand anlegen -->
    <a href="add-item.php" class="fab-action fab-action-primary" title="Gegenstand anlegen">
        <i class="fa-solid fa-plus"></i>
        <span class="fab-label">Gegenstand anlegen</span>
    </a>

    <!-- Übersicht -->
    <a href="index.php" class="fab-action" title="Übersicht">
        <i class="fa-solid fa-house"></i>
        <span class="fab-label">Übersicht</span>
    </a>

    <!-- Haupt-Auslöser Button -->
    <button type="button" id="fabMainBtn" class="fab-main" aria-label="Menü öffnen">
        <i class="fa-solid fa-bars fab-icon-main"></i>
        <i class="fa-solid fa-xmark fab-icon-close"></i>
    </button>
</div>


<footer class="footer mt-auto py-3 bg-light border-top fixed-bottom">
  <div class="container text-center text-muted">
    <small>
      &copy; <?= date('Y') ?> Was ist wo? &middot; 
      <a href="release.php" class="text-decoration-none text-muted" title="Changelog ansehen">
        v<?= htmlspecialchars($currentVersion ?? '1.0.0') ?>
      </a>
    </small>
  </div>
</footer>
    <!-- 1. Externe Bibliotheken -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.min.js"></script>
    
    <!-- 2. Eigene JavaScript-Module -->
     <script>
        window.unseenReleaseContent = <?= json_encode($unseenRelease['content'] ?? '') ?>;
    </script>
    <script src="js/ui.js"></script>
    <script>
        window.settingsOnlyDefaultUser = <?= json_encode($onlyDefaultUser ?? false) ?>;
    </script>
    <script src="js/settings.js"></script>
    <script src="js/fab.js"></script>
    <script src="js/inventory.js"></script>
    <script src="js/drag-n-drop.js"></script>
    <script src="js/locations.js"></script>
    <script src="js/tags.js"></script>
    <script src="js/qr.js"></script>
    <script src="js/logs.js"></script>
    <script src="js/app.js"></script>

</body>
</html>
