/**
 * Allgemeine UI-Interaktionen (Theme-Switch, Benutzer-Modals)
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Toggle (Dark / Light Mode)
    const themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const body = document.body;
            const icon = themeBtn.querySelector('i');
            
            body.classList.toggle('theme-dark');
            const isDark = body.classList.contains('theme-dark');

            if (icon) {
                icon.classList.toggle('fa-moon', !isDark);
                icon.classList.toggle('fa-sun', isDark);
            }

            document.cookie = "theme=" + (isDark ? "dark" : "light") + ";path=/;max-age=31536000";
        });
    }

    // 2. Benutzer bearbeiten Modal (Datenübertragung)
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            document.getElementById('modalUserId').value = button.getAttribute('data-userid');
            document.getElementById('modalUsername').value = button.getAttribute('data-username');
            document.getElementById('modalRole').value = button.getAttribute('data-role');
        });
    }
});
