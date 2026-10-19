<?php
// /inc/settings/general.php - Theme, Bilder, Startseite, Übersichts-Modus
?>
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

<!-- Bilder-Anzeige Einstellung -->
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

<!-- Startseite Einstellung -->
<div class="mb-4">
    <label class="form-label fw-bold">Startseite nach Anmeldung / Aufruf</label>
    <div class="row g-3">
        <div class="col-6">
            <input type="radio" class="btn-check auto-submit" name="start_page" id="startDashboard" value="dashboard" <?= (($settings['start_page'] ?? 'dashboard') === 'dashboard') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary w-100 p-3 text-center" for="startDashboard">
                <i class="fa-solid fa-chart-pie fa-2x mb-2 text-info d-block"></i>Dashboard
            </label>
        </div>
        <div class="col-6">
            <input type="radio" class="btn-check auto-submit" name="start_page" id="startOverview" value="overview" <?= (($settings['start_page'] ?? 'dashboard') === 'overview') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary w-100 p-3 text-center" for="startOverview">
                <i class="fa-solid fa-boxes-stacked fa-2x mb-2 text-success d-block"></i>Übersicht
            </label>
        </div>
    </div>
    <div class="form-text mt-2">
        <i class="fa-solid fa-circle-info me-1"></i> Legt fest, welche Ansicht beim Aufrufen der Hauptseite zuerst geladen wird.
    </div>
</div>

<hr class="my-4">

<!-- Übersichts-Modus Einstellung -->
<div class="mb-2">
    <label class="form-label fw-bold">Modus für die Artikel-Übersicht</label>
    <div class="row g-3">
        <div class="col-6">
            <input type="radio" class="btn-check auto-submit" name="overview_mode" id="modeFilter" value="filter" <?= (($settings['overview_mode'] ?? 'filter') === 'filter') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary w-100 p-3 text-center" for="modeFilter">
                <i class="fa-solid fa-filter fa-2x mb-2 text-primary d-block"></i>Live Filterbare Liste
            </label>
        </div>
        <div class="col-6">
            <input type="radio" class="btn-check auto-submit" name="overview_mode" id="modeSearch" value="search" <?= (($settings['overview_mode'] ?? 'filter') === 'search') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary w-100 p-3 text-center" for="modeSearch">
                <i class="fa-solid fa-magnifying-glass fa-2x mb-2 text-warning d-block"></i>Umfassende Suche
            </label>
        </div>
    </div>
    <div class="form-text mt-2">
        <i class="fa-solid fa-circle-info me-1"></i> Bestimmt das Verhalten und den Aufbau der Tabellen- bzw. Artikelansicht in der Übersicht.
    </div>
</div>
