        <!-- Container für die interaktiven Tag-Pills und das Eingabefeld -->
        <div class="mb-3 mt-4 border-top pt-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label mb-0">Schlagwörter (Tags)</label>
                <small id="tagSaveStatus" class="text-muted" style="transition: opacity 0.3s; opacity: 0;">
                    <i class="fa-solid fa-check text-success me-1"></i>Gespeichert
                </small>
            </div>
       
            <input type="hidden" id="currentItemId" value="<?= (int)$item['id'] ?>">

            <div class="position-relative mt-1">
                <div id="tagContainer" class="form-control d-flex flex-wrap align-items-center gap-1 p-2" style="min-height: 38px; cursor: text;">
                    <input type="text" id="tagInputField" class="border-0 flex-grow-1 bg-transparent text-body p-0 shadow-none" placeholder="Tag eingeben, mit Komma trennen..." autocomplete="off" style="outline: none; min-width: 120px;">
                </div>
           
                <div id="tagSuggestions" class="dropdown-menu shadow-sm w-100" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050;"></div>
            </div>
       
            <input type="hidden" id="tagsHiddenInput" name="tags" value="<?= htmlspecialchars($currentTagsString) ?>">
       
            <div class="form-text mt-1">Tippen, mit Pfeiltasten wählen &amp; Enter drücken. Klicke auf das × zum Entfernen.</div>
        </div>
