/**
 * FAB-Menü Steuerung, Submenüs und barrierefreie Tastaturnavigation
 */
document.addEventListener('DOMContentLoaded', () => {
    const fabContainer = document.getElementById('fabContainer');
    const fabMainBtn = document.getElementById('fabMainBtn');
    const fabBackdrop = document.getElementById('fabBackdrop');
    const toolsFabBtn = document.getElementById('toolsFabBtn');
    const toolsSubmenu = document.getElementById('toolsSubmenu');

    if (!fabContainer || !fabMainBtn) return;

    // Menü öffnen/schließen als globale Variable innerhalb des Scopes oder an window hängend
    window.toggleFab = function(open) {
        const shouldOpen = open !== undefined ? open : !fabContainer.classList.contains('open');
       
        if (shouldOpen) {
            fabContainer.classList.add('open');
            if (fabBackdrop) fabBackdrop.classList.add('active');
        } else {
            fabContainer.classList.remove('open');
            if (fabBackdrop) fabBackdrop.classList.remove('active');
            closeToolsSubmenu();
        }
    };

    // Haupt-Aktionsbuttons ermitteln
    const getMainFabItems = () => {
        return Array.from(fabContainer.querySelectorAll('.fab-action')).filter(btn => {
            return window.getComputedStyle(btn).display !== 'none' && 
                   window.getComputedStyle(btn).visibility !== 'hidden';
        });
    };

    // Submenü-Items ermitteln
    const getSubmenuItems = () => {
        if (!toolsSubmenu || !toolsSubmenu.classList.contains('open')) return [];
        return Array.from(toolsSubmenu.querySelectorAll('.fab-sub-item'));
    };

    // Menü öffnen/schließen
    function toggleFab(open) {
        const shouldOpen = open !== undefined ? open : !fabContainer.classList.contains('open');
        
        if (shouldOpen) {
            fabContainer.classList.add('open');
            if (fabBackdrop) fabBackdrop.classList.add('active');
        } else {
            fabContainer.classList.remove('open');
            if (fabBackdrop) fabBackdrop.classList.remove('active');
            closeToolsSubmenu();
        }
    }

    // Tools-Submenü umschalten
    function toggleToolsSubmenu() {
        const isOpen = toolsSubmenu.classList.contains('open');
        if (isOpen) {
            closeToolsSubmenu();
        } else {
            toolsSubmenu.classList.add('open');
            toolsFabBtn.classList.add('active');
        }
    }

    function closeToolsSubmenu() {
        if (toolsSubmenu) toolsSubmenu.classList.remove('open');
        if (toolsFabBtn) toolsFabBtn.classList.remove('active');
    }

    function toggleFabMenuViaShortcut() {
        fabMainBtn.click();
        setTimeout(() => {
            const items = getMainFabItems();
            if (items.length > 0) items[0].focus();
        }, 100);
    }

    // Event-Listener für Klicks
    fabMainBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleFab();
    });

    if (toolsFabBtn) {
        toolsFabBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleToolsSubmenu();
        });
    }

    if (fabBackdrop) {
        fabBackdrop.addEventListener('click', () => toggleFab(false));
    }

    // Globale Tastenkombination: Strg+M oder Alt+M
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.altKey) && e.key.toLowerCase() === 'm') {
            e.preventDefault();
            toggleFabMenuViaShortcut();
        }
    });

    // Tastatursteuerung innerhalb des FAB-Containers
    fabContainer.addEventListener('keydown', (e) => {
        const isSubmenuOpen = toolsSubmenu && toolsSubmenu.classList.contains('open');
        const activeEl = document.activeElement;

        // Navigation im Tools-Submenü
        if (isSubmenuOpen && toolsSubmenu.contains(activeEl)) {
            const subItems = getSubmenuItems();
            const subIndex = subItems.indexOf(activeEl);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                subItems[(subIndex + 1) % subItems.length].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                subItems[(subIndex - 1 + subItems.length) % subItems.length].focus();
            } else if (e.key === 'ArrowRight' || e.key === 'Escape') {
                e.preventDefault();
                if (toolsFabBtn) {
                    toolsFabBtn.click();
                    toolsFabBtn.focus();
                }
            }
            return;
        }

        // Navigation in der Hauptebene des FABs
        const mainItems = getMainFabItems();
        if (mainItems.length === 0) return;
        const currentIndex = mainItems.indexOf(activeEl);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            mainItems[currentIndex === -1 ? 0 : (currentIndex + 1) % mainItems.length].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            mainItems[currentIndex === -1 ? mainItems.length - 1 : (currentIndex - 1 + mainItems.length) % mainItems.length].focus();
        } else if (e.key === 'ArrowLeft' && activeEl === toolsFabBtn) {
            e.preventDefault();
            if (!isSubmenuOpen) toolsFabBtn.click();
            setTimeout(() => {
                const subItems = getSubmenuItems();
                if (subItems.length > 0) subItems[0].focus();
            }, 100);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            if (isSubmenuOpen) {
                if (toolsFabBtn) {
                    toolsFabBtn.click();
                    toolsFabBtn.focus();
                }
            } else {
                fabMainBtn.click();
                fabMainBtn.focus();
            }
        }
    });
});
