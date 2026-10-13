/**
 * Sofort-Speichern und Sicherheitsabfragen im Einstellungs-Bereich
 */
document.addEventListener('DOMContentLoaded', function() {
    const isOnlyDefaultUser = window.settingsOnlyDefaultUser ?? false;
    const settingsForm = document.getElementById('settingsForm');
    const authDisabledRadio = document.getElementById('authDisabled');
    const authEnabledRadio = document.getElementById('authEnabled');
   
    const enableAuthModalEl = document.getElementById('enableAuthModal');
    const enableAuthModal = enableAuthModalEl ? new bootstrap.Modal(enableAuthModalEl) : null;
    const confirmEnableAuthBtn = document.getElementById('confirmEnableAuth');

    if (!settingsForm) return;

    // 1. Radiobuttons mit Klasse auto-submit sofort abschicken
    document.querySelectorAll('.auto-submit').forEach(input => {
        input.addEventListener('change', function() {
            settingsForm.submit();
        });
    });

    // 2. Passwortschutz Deaktivieren
    if (authDisabledRadio) {
        authDisabledRadio.addEventListener('change', function() {
            settingsForm.submit();
        });
    }

    // 3. Passwortschutz Aktivieren mit Prüfung auf Default-User
    if (authEnabledRadio) {
        authEnabledRadio.addEventListener('change', function() {
            if (isOnlyDefaultUser) {
                authDisabledRadio.checked = true;
                if (enableAuthModal) {
                    enableAuthModal.show();
                }
            } else {
                settingsForm.submit();
            }
        });
    }

    // Bestätigung im Modal zum Aktivieren der Authentifizierung
    if (confirmEnableAuthBtn) {
        confirmEnableAuthBtn.addEventListener('click', function() {
            authEnabledRadio.checked = true;
            settingsForm.submit();
        });
    }
});
