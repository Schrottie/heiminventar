<?php

require_once __DIR__ . '/env.php';

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME']
        ),
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
    
    // Migrationen automatisch ausführen
    require_once __DIR__ . '/../inc/migrations.php';
    runDatabaseMigrations($pdo);
}
catch (PDOException $e) {
    die('Datenbankverbindung fehlgeschlagen.');
}