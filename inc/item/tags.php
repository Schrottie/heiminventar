<!-- Sektion: Schlagwörter (Tags) -->
<div class="card bg-light border-0 p-3 mb-3 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="text-secondary fw-bold mb-0">
            <i class="fa-solid fa-tags me-2"></i>Schlagwörter (Tags)
        </h6>
        <small id="tagSaveStatus" class="text-muted" style="transition: opacity 0.3s; opacity: 0;">
            <i class="fa-solid fa-check text-success me-1"></i>Gespeichert
        </small>
    </div>

    <input type="hidden" id="currentItemId" value="<?= (int)$item['id'] ?>">

    <div class="position-relative">
        <!-- Tag-Container im weißen Feld für optimalen Kontrast auf der grauen Karte -->
        <div id="tagContainer" class="form-control bg-white d-flex flex-wrap align-items-center gap-1 p-2" style="min-height: 38px; cursor: text;">
            <input type="text" id="tagInputField" class="border-0 flex-grow-1 bg-transparent text-body p-0 shadow-none" placeholder="Tag eingeben, mit Komma trennen..." autocomplete="off" style="outline: none; min-width: 120px;">
        </div>
   
        <div id="tagSuggestions" class="dropdown-menu shadow-sm w-100" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050;"></div>
    </div>

    <input type="hidden" id="tagsHiddenInput" name="tags" value="<?= htmlspecialchars($currentTagsString) ?>">

    <div class="form-text mt-2">Tippen, mit Pfeiltasten wählen &amp; Enter drücken. Klicke auf das × zum Entfernen.</div>
</div>
