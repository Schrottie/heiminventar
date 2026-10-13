/**
 * Interaktives Tagging-System mit Live-Autocomplete, Pfeiltasten-Navigation, Komma-Trennung und AJAX-Save
 */
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('tagContainer');
    const inputField = document.getElementById('tagInputField');
    const hiddenInput = document.getElementById('tagsHiddenInput');
    const suggestionsBox = document.getElementById('tagSuggestions');
    const itemIdInput = document.getElementById('currentItemId');

    if (!container || !inputField || !hiddenInput) return;

    const itemId = itemIdInput ? parseInt(itemIdInput.value, 10) : 0;
    let tags = hiddenInput.value ? hiddenInput.value.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];
    let selectedIndex = -1; // Für die Pfeiltasten-Navigation

    function saveTagsToBackend() {
        if (itemId <= 0) return;

        fetch('api/tags-save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId, tags: tags.join(', ') })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Fehler beim Speichern der Tags:', data.error);
            }
        })
        .catch(err => console.error('Netzwerkfehler:', err));
    }

    function renderTags() {
        container.querySelectorAll('.badge-tag').forEach(el => el.remove());

        tags.forEach((tag, index) => {
            const pill = document.createElement('span');
            pill.className = 'badge bg-secondary d-flex align-items-center gap-1 badge-tag py-1 px-2';
            pill.innerHTML = `<span>${tag}</span> <button type="button" class="btn-close btn-close-white" style="font-size: 0.6rem;" data-index="${index}"></button>`;
            container.insertBefore(pill, inputField);
        });

        hiddenInput.value = tags.join(', ');
        saveTagsToBackend();
    }

    // Initial Pills zeichnen
    container.querySelectorAll('.badge-tag').forEach(el => el.remove());
    tags.forEach((tag, index) => {
        const pill = document.createElement('span');
        pill.className = 'badge bg-secondary d-flex align-items-center gap-1 badge-tag py-1 px-2';
        pill.innerHTML = `<span>${tag}</span> <button type="button" class="btn-close btn-close-white" style="font-size: 0.6rem;" data-index="${index}"></button>`;
        container.insertBefore(pill, inputField);
    });

    container.addEventListener('click', () => inputField.focus());

    container.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-close')) {
            const index = parseInt(e.target.getAttribute('data-index'), 10);
            tags.splice(index, 1);
            renderTags();
        }
    });

    function addTag(tagName) {
        const cleanName = tagName.trim();
        if (cleanName.length > 0 && !tags.includes(cleanName)) {
            tags.push(cleanName);
            renderTags();
        }
        inputField.value = '';
        hideSuggestions();
    }

    function hideSuggestions() {
        suggestionsBox.style.display = 'none';
        suggestionsBox.classList.remove('show');
        selectedIndex = -1;
    }

    function updateActiveSuggestion() {
        const items = suggestionsBox.querySelectorAll('.tag-suggestion-item');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Event: Eingabe im Tag-Feld
    inputField.addEventListener('input', function() {
        let val = this.value;

        if (val.includes(',')) {
            const parts = val.split(',');
            addTag(parts[0]);
            this.value = parts.slice(1).join(',').trimStart();
            hideSuggestions();
            return;
        }

        const query = val.trim();
        if (query.length < 1) {
            hideSuggestions();
            return;
        }

        fetch(`api/tags-search.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(suggestions => {
                const filtered = suggestions.filter(s => !tags.includes(s.name));

                if (filtered.length === 0) {
                    hideSuggestions();
                    return;
                }

                let html = '';
                filtered.forEach(s => {
                    html += `<button type="button" class="dropdown-item tag-suggestion-item" style="cursor: pointer;">${s.name}</button>`;
                });

                suggestionsBox.innerHTML = html;
                suggestionsBox.style.display = 'block';
                suggestionsBox.classList.add('show');
                selectedIndex = -1; // Reset selection on new query

                // Klick auf Vorschlag
                document.querySelectorAll('.tag-suggestion-item').forEach(btn => {
                    btn.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        addTag(this.textContent);
                        inputField.focus();
                    });
                });
            })
            .catch(err => console.error('Fehler bei Tag-Suche:', err));
    });

    // Event: Tastatur-Navigation (Pfeiltasten, Enter, Backspace)
    inputField.addEventListener('keydown', function(e) {
        const items = suggestionsBox.querySelectorAll('.tag-suggestion-item');
        const isVisible = suggestionsBox.style.display === 'block' && items.length > 0;

        if (e.key === 'ArrowDown') {
            if (isVisible) {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateActiveSuggestion();
            }
        } else if (e.key === 'ArrowUp') {
            if (isVisible) {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateActiveSuggestion();
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (isVisible && selectedIndex >= 0 && items[selectedIndex]) {
                // Aktiv markierten Vorschlag per Enter übernehmen
                addTag(items[selectedIndex].textContent);
            } else {
                // Aktuellen Text im Feld als neuen Tag übernehmen
                const val = this.value.trim().replace(/,/g, '');
                if (val.length > 0) {
                    addTag(val);
                }
            }
        } else if (e.key === 'Escape') {
            hideSuggestions();
        } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
            tags.pop();
            renderTags();
        }
    });

    // Schließen bei Klick außerhalb
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target) && !suggestionsBox.contains(e.target)) {
            hideSuggestions();
        }
    });
});
