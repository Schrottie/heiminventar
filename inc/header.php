<?php
require_once __DIR__ . '/../cfg/db.php';

// 1. Settings aus der DB laden
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Auth & Logout Logik ausführen (stellt $currentUser / $_SESSION bereit)
require_once __DIR__ . '/auth.php';

// 3. Theme-Einstellungen
$currentTheme = $settings['theme'] ?? 'light';
$isNight = ($currentTheme === 'dark');

// 4. Versionsnummer-Funktion
function getCurrentAppVersion(PDO $pdo): string
{
    static $version = null;
    if ($version === null) {
        $stmt = $pdo->query("SELECT version FROM app_releases WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $version = $stmt->fetchColumn() ?: '1.0.0';
    }
    return $version;
}

$currentVersion = getCurrentAppVersion($pdo);

// 5. Release-Notes Prüffunktion (unterstützt User & Gäste)
function getUnseenRelease(PDO $pdo, array|int|null $user = null): ?array
{
    // 1. Neuestes aktives Release holen
    $stmt = $pdo->query("SELECT * FROM app_releases WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $latestRelease = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$latestRelease) {
        return null;
    }

    $latestVersion = $latestRelease['version'];

    // 2. User-ID auflösen (egal ob als ID, Array oder aus der Session)
    $userId = null;
    $userLastSeen = null;

    if (is_numeric($user)) {
        $userId = (int)$user;
    } elseif (is_array($user)) {
        $userId = $user['id'] ?? null;
        $userLastSeen = $user['last_seen_version'] ?? null;
    } elseif (isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    }

    // --- FALL 1: Eingeloggter Nutzer ---
    if ($userId) {
        // Falls last_seen_version noch nicht bekannt ist, aus DB laden
        if ($userLastSeen === null) {
            $uStmt = $pdo->prepare("SELECT last_seen_version FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $userLastSeen = $uStmt->fetchColumn();
        }

        if (!$userLastSeen || version_compare($latestVersion, $userLastSeen, '>')) {
            return $latestRelease;
        }
        return null;
    }

    // --- FALL 2: Gast (Cookie-Prüfung) ---
    $cookieVersion = $_COOKIE['app_last_seen_version'] ?? null;
    if (!$cookieVersion || version_compare($latestVersion, $cookieVersion, '>')) {
        return $latestRelease;
    }

    return null;
}

// 6. Abfrage für das Template ausführen
// Pass dabei auf, wie deine auth.php den User bereitstellt ($currentUser oder $_SESSION['user'])
$userForCheck = $currentUser ?? $_SESSION['user'] ?? null;
$unseenRelease = getUnseenRelease($pdo);
?>

<!doctype html>
<html lang="de">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" sizes="32x32" href="img/inventory.png">

    <title>Was ist wo?</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

</head>

<body class="<?= $isNight ? 'theme-dark' : '' ?> d-flex">

    <header id="heroHeader" class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <span class="title-prefix">
                    <i class="fa-solid fa-boxes-stacked"></i> WAS
                </span>
                <span class="title-middle">
                    IST
                </span>
                <span class="title-suffix">
                    WO?
                </span>
            </h1>
            <div class="hero-subtitle">
                    Die smarte Lösung für Deine Inventarverwaltung <a href="#" class="help-link" data-bs-toggle="modal" data-bs-target="#helpModal"><i class="fa-solid fa-circle-question"></i></a>
            </div>
        </div>

            <!-- Abmelden-Button im Header (nur sichtbar, wenn Auth aktiv & eingeloggt) -->
    <?php if ($authEnabled && $isLoggedIn): ?>
        <a href="?logout=1" class="btn btn-outline-light btn-sm position-absolute top-0 end-0 m-3 d-flex align-items-center gap-2 shadow-sm" title="Abmelden">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="d-none d-md-inline">abmelden</span>
        </a>
    <?php endif; ?>
    </header>

    <div class="container py-3">