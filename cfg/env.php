<?php

/**
 * Lädt Umgebungsvariablen aus einer .env-Datei in $_ENV und putenv()
 *
 * @param string $path Pfad zur .env-Datei
 * @throws Exception Wenn die Datei nicht existiert
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }

    // Datei zeilenweise einlesen (ohne Umbrüche und leere Zeilen)
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Kommentare (#) und leere Zeilen überspringen
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        // Schlüssel und Wert an der ersten '='-Stelle trennen
        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value, "\"' "); // Anführungszeichen und Leerzeichen entfernen

        // In globale Umgebungsvariablen schreiben
        $_ENV[$name] = $value;
        putenv("{$name}={$value}");
    }
}

// .env-Datei im aktuellen Verzeichnis laden
loadEnv(__DIR__ . '/.env');
