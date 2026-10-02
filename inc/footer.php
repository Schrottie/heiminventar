    </div>

    <!-- Floating Action Button (FAB) Menü -->
    <div id="fabContainer">
        <!-- Unteraktionen (Aktionen beim Ausklappen) -->
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

    <!-- Externe und eigene Skripte -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.min.js"></script>
    <script src="js/app.js"></script>

</body>
</html>
