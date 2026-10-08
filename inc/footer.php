    </div>

    <!-- Floating Action Button (FAB) Menü -->
    <div id="fabContainer">
        <!-- Unteraktionen (Aktionen beim Ausklappen) -->
         <?php
        // Prüfen, ob der Button sichtbar sein darf
        $canSeeFab = true;

        if (function_exists('isLoginEnabled') && isLoginEnabled()) {
            $canSeeFab = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
        }
        ?>

        <?php if ($canSeeFab): ?>
        <button id="historyFabBtn" class="btn btn-dark fab-action" title="Änderungsprotokoll aufrufen">
            <i class="fa-solid fa-gear"></i> Protokoll
        </button>

        <button id="settingsFabBtn" class="btn btn-light fab-action" title="Einstellungen anpassen">
            <i class="fa-solid fa-clock-rotate-left"></i> Einstellungen
        </button>
        <?php endif; ?>

        <button id="changeFabBtn" class="btn btn-danger fab-action" title="Gegenstände einfach verschieben">
            <i class="fa-solid fa-shuffle"></i> Verschiebebahnhof
        </button>

        <button id="locationsFabBtn" class="btn btn-secondary fab-action" title="Lagerorte">
            <i class="fa-solid fa-location-dot"></i> Lagerorte verwalten
        </button>

        <button id="addFabBtn" class="btn btn-success fab-action" title="Gegenstand anlegen">
            <i class="fa-solid fa-plus"></i> Gegenstand anlegen
        </button>

        <button id="indexFabBtn" class="btn btn-warning fab-action" title="Gesamtübersicht">
            <i class="fa-solid fa-boxes-stacked"></i> Übersicht
        </button>

        <!-- Haupt-Button zum Öffnen/Schließen des Menüs -->
        <button id="fabMainBtn" class="btn btn-primary fab-main">
            <i class="fa-solid fa-bars"></i>
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
    <!-- Externe und eigene Skripte -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="js/app.js"></script>

</body>
</html>
