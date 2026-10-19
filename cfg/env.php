<?php

/**
 * PHP-8-Funktionen für ältere PHP-Versionen bereitstellen
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

/**
 * Lädt Umgebungsvariablen aus einer .env-Datei in $_ENV und putenv()
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }

    // Datei zeilenweise einlesen
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Kommentare und ungültige Zeilen überspringen
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        // Schlüssel und Wert an der ersten '='-Stelle trennen
        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value, "\"' ");

        // Umgebungsvariablen setzen
        $_ENV[$name] = $value;
        putenv("{$name}={$value}");
    }
}

// .env-Datei im aktuellen Verzeichnis laden
loadEnv(__DIR__ . '/.env');
