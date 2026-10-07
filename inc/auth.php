<?php
// 1. Session immer sicher starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Abmelde-Logik SOFORT ausführen, bevor irgendetwas anderes passiert
if (isset($_GET['logout'])) {
    // Alle Session-Variablen löschen
    $_SESSION = array();

    // Session-Cookie im Browser löschen (falls vorhanden)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Session auf dem Server komplett zerstören
    session_destroy();

    // Zur Login-Seite umleiten und Script sofort beenden
    header('Location: login.php');
    exit;
}

// 3. Status-Variablen definieren (werden in header.php benötigt)
$authEnabled = ($settings['auth_enabled'] ?? '0') === '1';
$isLoggedIn = isset($_SESSION['user_id']);

// 4. Zugriffsschutz-Prüfung
$currentPage = basename($_SERVER['PHP_SELF']);
if ($authEnabled && !$isLoggedIn && $currentPage !== 'login.php') {
    header('Location: login.php');
    exit;
}
