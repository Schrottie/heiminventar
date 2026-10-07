<?php
require_once __DIR__ . '/../cfg/db.php';

// 1. Settings aus der DB laden
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Auth & Logout Logik ausführen
require_once __DIR__ . '/auth.php';

// 3. Theme-Einstellungen
$currentTheme = $settings['theme'] ?? 'light';
$isNight = ($currentTheme === 'dark');
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

<body class="<?= $isNight ? 'theme-dark' : '' ?>">

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