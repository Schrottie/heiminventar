/**
 * Zentrales Inventar-Filtersystem (Suchfeld, Status-Buttons & Tag-Filter mit UND/ODER)
 */
document.addEventListener('DOMContentLoaded', () => {
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    const expiredBtn = document.getElementById('toggleExpired');
    const maintenanceBtn = document.getElementById('toggleMaintenance');

    // Tag-Filter UI Elemente
    const filterTagContainer = document.getElementById('filterTagContainer');
    const filterTagInput = document.getElementById('filterTagInput');
    const filterTagSuggestions = document.getElementById('filterTagSuggestions');
    const tagModeContainer = document.getElementById('tagModeContainer');
    const modeOrBtn = document.getElementById('modeOrBtn');
    const modeAndBtn = document.getElementById('modeAndBtn');
    const clearFilterBtn = document.getElementById('clearFilterTags'); // X-Button

    let activeFilterTags = [];
    let filterMode = 'or'; // 'or' oder 'and'
    let selectedIndex = -1;
   
    // --- Tag-UI & Event-Steuerung ---
    if (filterTagContainer && filterTagInput) {
        filterTagContainer.addEventListener('click', () => filterTagInput.focus());

        function renderFilterTags() {
            // Nur noch die Filter-Pills im Container verwalten
            filterTagContainer.querySelectorAll('.badge-filter-tag').forEach(el => el.remove());

            activeFilterTags.forEach((tag, index) => {
                const pill = document.createElement('span');
                pill.className = 'badge bg-primary d-flex align-items-center gap-1 badge-filter-tag py-1 px-2';
                pill.innerHTML = `<span>${tag}</span> <button type="button" class="btn-close btn-close-white" style="font-size: 0.6rem;" data-index="${index}"></button>`;
                filterTagContainer.insertBefore(pill, filterTagInput);
            });

            // X-Button (Input-Group Addon) ein- oder ausblenden
            const clearBtn = document.getElementById('clearFilterTags');
            if (clearBtn) {
                if (activeFilterTags.length > 0) {
                    clearBtn.classList.remove('d-none');
                } else {
                    clearBtn.classList.add('d-none');
                }
            }

            // UND/ODER Umschalter anzeigen, wenn min. 2 Tags aktiv sind
            if (tagModeContainer) {
                if (activeFilterTags.length > 1) {
                    tagModeContainer.style.display = 'inline-flex';
                } else {
                    tagModeContainer.style.display = 'none';
                }
            }

            applyFilters(null);
        }

        // Event-Listener für den X-Button (einmalig binden)
        const clearBtn = document.getElementById('clearFilterTags');
        if (clearBtn && !clearBtn.dataset.bound) {
            clearBtn.dataset.bound = 'true';
            clearBtn.addEventListener('click', () => {
                activeFilterTags = [];
                renderFilterTags();
            });
        }

        filterTagContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-close')) {
                const index = parseInt(e.target.getAttribute('data-index'), 10);
                activeFilterTags.splice(index, 1);
                renderFilterTags();
            }
        });

        if (modeOrBtn && modeAndBtn) {
            modeOrBtn.addEventListener('click', () => {
                modeOrBtn.classList.add('active', 'btn-primary');
                modeOrBtn.classList.remove('btn-outline-primary');
                modeAndBtn.classList.remove('active', 'btn-primary');
                modeAndBtn.classList.add('btn-outline-primary');
                filterMode = 'or';
                applyFilters(null);
            });

            modeAndBtn.addEventListener('click', () => {
                modeAndBtn.classList.add('active', 'btn-primary');
                modeAndBtn.classList.remove('btn-outline-primary');
                modeOrBtn.classList.remove('active', 'btn-primary');
                modeOrBtn.classList.add('btn-outline-primary');
                filterMode = 'and';
                applyFilters(null);
            });
        }

        function addFilterTag(tagName) {
            const cleanName = tagName.trim();
            if (cleanName.length > 0 && !activeFilterTags.includes(cleanName)) {
                activeFilterTags.push(cleanName);
                renderFilterTags();
            }
            filterTagInput.value = '';
            hideSuggestions();
        }

        function hideSuggestions() {
            if (!filterTagSuggestions) return;
            filterTagSuggestions.style.display = 'none';
            filterTagSuggestions.classList.remove('show');
            selectedIndex = -1;
        }

        function updateActiveSuggestion() {
            const items = filterTagSuggestions.querySelectorAll('.tag-suggestion-item');
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        filterTagInput.addEventListener('input', function() {
            let val = this.value;

            if (val.includes(',')) {
                const parts = val.split(',');
                addFilterTag(parts[0]);
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
                    const filtered = suggestions.filter(s => !activeFilterTags.includes(s.name));

                    if (filtered.length === 0) {
                        hideSuggestions();
                        return;
                    }

                    let html = '';
                    filtered.forEach(s => {
                        html += `<button type="button" class="dropdown-item tag-suggestion-item" style="cursor: pointer;">${s.name}</button>`;
                    });

                    filterTagSuggestions.innerHTML = html;
                    filterTagSuggestions.style.display = 'block';
                    filterTagSuggestions.classList.add('show');
                    selectedIndex = -1;

                    filterTagSuggestions.querySelectorAll('.tag-suggestion-item').forEach(btn => {
                        btn.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            addFilterTag(this.textContent);
                            filterTagInput.focus();
                        });
                    });
                })
                .catch(err => console.error('Fehler bei Tag-Suche:', err));
        });

        filterTagInput.addEventListener('keydown', function(e) {
            const items = filterTagSuggestions.querySelectorAll('.tag-suggestion-item');
            const isVisible = filterTagSuggestions.style.display === 'block' && items.length > 0;

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
                    addFilterTag(items[selectedIndex].textContent);
                } else {
                    const val = this.value.trim().replace(/,/g, '');
                    if (val.length > 0) {
                        addFilterTag(val);
                    }
                }
            } else if (e.key === 'Escape') {
                hideSuggestions();
            } else if (e.key === 'Backspace' && this.value === '' && activeFilterTags.length > 0) {
                activeFilterTags.pop();
                renderFilterTags();
            }
        });

        document.addEventListener('click', (e) => {
            if (!filterTagContainer.contains(e.target) && !filterTagSuggestions.contains(e.target)) {
                hideSuggestions();
            }
        });
    }

    // --- Zentrale Filter-Logik ---
    function applyFilters(targetFilter = null) {
        const query = quickFilterInput ? quickFilterInput.value.toLowerCase().trim() : '';

        // Prüfen, welcher Button aktuell aktiv ist
        let lowStockActive = lowStockBtn ? lowStockBtn.classList.contains('active-filter') : false;
        let expiredActive = expiredBtn ? expiredBtn.classList.contains('active-filter') : false;
        let maintenanceActive = maintenanceBtn ? maintenanceBtn.classList.contains('active-filter') : false;

        // Wenn ein spezifischer Filter geklickt wurde
        if (targetFilter !== null) {
            if (targetFilter === 'lowStock') lowStockActive = !lowStockActive;
            if (targetFilter === 'expired') expiredActive = !expiredActive;
            if (targetFilter === 'maintenance') maintenanceActive = !maintenanceActive;

            if (targetFilter === 'lowStock' && lowStockActive) {
                expiredActive = false;
                maintenanceActive = false;
            }
            if (targetFilter === 'expired' && expiredActive) {
                lowStockActive = false;
                maintenanceActive = false;
            }
            if (targetFilter === 'maintenance' && maintenanceActive) {
                lowStockActive = false;
                expiredActive = false;
            }

            if (quickFilterInput) quickFilterInput.value = ''; // Suchfeld bei Button-Klick leeren
        }

        // Optischen Zustand der Buttons aktualisieren
        if (lowStockBtn) {
            lowStockBtn.classList.toggle('active-filter', lowStockActive);
            lowStockBtn.classList.toggle('btn-danger', lowStockActive);
            lowStockBtn.classList.toggle('btn-outline-danger', !lowStockActive);
        }
        if (expiredBtn) {
            expiredBtn.classList.toggle('active-filter', expiredActive);
            expiredBtn.classList.toggle('btn-warning', expiredActive);
            expiredBtn.classList.toggle('text-dark', expiredActive);
            expiredBtn.classList.toggle('btn-outline-warning', !expiredActive);
        }
        if (maintenanceBtn) {
            maintenanceBtn.classList.toggle('active-filter', maintenanceActive);
            maintenanceBtn.classList.toggle('btn-info', maintenanceActive);
            maintenanceBtn.classList.toggle('text-dark', maintenanceActive);
            maintenanceBtn.classList.toggle('btn-outline-secondary', !maintenanceActive);
            maintenanceBtn.classList.toggle('border-info', !maintenanceActive);
            maintenanceBtn.classList.toggle('text-info-emphasis', !maintenanceActive);
        }

        // Inventar-Elemente durchgehen und filtern
        document.querySelectorAll('.inventory-item').forEach(item => {
            const searchData = (item.getAttribute('data-search') || '').toLowerCase();
            const isLowStock = item.getAttribute('data-low-stock') === '1';
            const isExpired = item.getAttribute('data-expired') === '1';
            const isMaintenance = item.getAttribute('data-maintenance') === '1';
            const itemTags = JSON.parse(item.getAttribute('data-tags') || '[]');

            const matchesSearch = query === '' || searchData.includes(query);
            const matchesLowStock = !lowStockActive || isLowStock;
            const matchesExpired = !expiredActive || isExpired;
            const matchesMaintenance = !maintenanceActive || isMaintenance;

            // Tag-Filter Prüfung (UND / ODER Modus)
            let matchesTags = true;
            if (activeFilterTags.length > 0) {
                if (filterMode === 'and') {
                    matchesTags = activeFilterTags.every(t => itemTags.includes(t));
                } else {
                    matchesTags = activeFilterTags.some(t => itemTags.includes(t));
                }
            }

            if (matchesSearch && matchesLowStock && matchesExpired && matchesMaintenance && matchesTags) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Event-Listener verknüpfen
    if (quickFilterInput) {
        quickFilterInput.addEventListener('input', () => applyFilters(null));
    }
    if (lowStockBtn) {
        lowStockBtn.addEventListener('click', (e) => { e.preventDefault(); applyFilters('lowStock'); });
    }
    if (expiredBtn) {
        expiredBtn.addEventListener('click', (e) => { e.preventDefault(); applyFilters('expired'); });
    }
    if (maintenanceBtn) {
        maintenanceBtn.addEventListener('click', (e) => { e.preventDefault(); applyFilters('maintenance'); });
    }
});
