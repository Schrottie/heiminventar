<?php
/**
 * Automatische Schema-Migration mit File-Cache Check
 */
function runDatabaseMigrations(PDO $pdo): void
{
    // 1. Definition aller reinen Schema-Migrationen
    $migrations = [
        '001_create_base_tables' => "
            CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT NULL,
                name VARCHAR(255) NOT NULL,
                CONSTRAINT fk_locations_parent
                FOREIGN KEY (parent_id) REFERENCES locations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS inventory_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                location_id INT NULL,
                quantity INT NOT NULL DEFAULT 1,
                created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_inventory_location
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS item_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                CONSTRAINT fk_images_item
                FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",

        '002_create_settings_table' => "
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            INSERT IGNORE INTO settings (setting_key, setting_value)
            VALUES ('theme', 'light');
        ",

        '003_create_auth_system' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('auth_enabled', '0');

            INSERT IGNORE INTO users (id, username, password_hash, role, is_active, is_default)
            VALUES (1, 'admin', '$2y$10$8k9.aX1xN4U5hYV/Xg/3eO5aA1P3n.l7gY8R1f5k8U9z0q2W4e6yS', 'admin', 1, 1);
        ",

        '004_create_image_setting' => "
            INSERT IGNORE INTO settings (setting_key, setting_value)
            VALUES ('show_images', '1');
        ",

        '005_create_main_image_function' => "
            ALTER TABLE item_images ADD COLUMN is_main TINYINT(1) DEFAULT 0;
        ",

        '006_create_documents_table' => "
            CREATE TABLE IF NOT EXISTS item_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",

        '007_create_min_quantity_function' => "
            ALTER TABLE inventory_items ADD COLUMN min_quantity INT DEFAULT 0 AFTER quantity;
        ",

        '008_create_history_function' => "
            CREATE TABLE IF NOT EXISTS `inventory_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `item_id` INT DEFAULT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `action` VARCHAR(50) NOT NULL,
                `field_changed` VARCHAR(50) DEFAULT NULL,
                `qty_change` INT DEFAULT 0,
                `new_qty` INT DEFAULT 0,
                `from_location_id` INT DEFAULT NULL,
                `to_location_id` INT DEFAULT NULL,
                `old_value` VARCHAR(255) DEFAULT NULL,
                `new_value` VARCHAR(255) DEFAULT NULL,
                `details` TEXT DEFAULT NULL,
                `user_name` VARCHAR(100) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `item_id` (`item_id`),
                KEY `from_location_id` (`from_location_id`),
                KEY `to_location_id` (`to_location_id`),
                CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL,
                CONSTRAINT `inventory_logs_ibfk_2` FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
                CONSTRAINT `inventory_logs_ibfk_3` FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",

        '009_create_release_notes_system' => "
            CREATE TABLE IF NOT EXISTS app_releases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(20) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                release_date DATE DEFAULT NULL,
                content TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            ALTER TABLE users ADD COLUMN last_seen_version VARCHAR(20) NULL AFTER role;

            INSERT IGNORE INTO settings (setting_key, setting_value)
            VALUES ('app_version', '1.0.7');
        ",

        '012_add_archive_to_items' => "
            ALTER TABLE inventory_items
            ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL;
        ",

        '014_add_functions' => "
            ALTER TABLE inventory_items
                ADD COLUMN expiration_date DATE DEFAULT NULL AFTER min_quantity,
                ADD COLUMN price DECIMAL(10,2) DEFAULT NULL AFTER expiration_date,
                ADD COLUMN on_loan TINYINT(1) DEFAULT 0 AFTER price,
                ADD COLUMN loan_to VARCHAR(255) DEFAULT NULL AFTER on_loan;
        ",

        '017_add_maintenance_functions' => "
            ALTER TABLE inventory_items
                ADD COLUMN maintenance_interval_months INT DEFAULT NULL AFTER loan_to,
                ADD COLUMN last_maintenance_date DATE DEFAULT NULL AFTER maintenance_interval_months;
        ",

        '018_add_tags_to_items' => "
            CREATE TABLE IF NOT EXISTS tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            CREATE TABLE IF NOT EXISTS item_tags (
                item_id INT NOT NULL,
                tag_id INT NOT NULL,
                PRIMARY KEY (item_id, tag_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",

        '019_add_release_date' => "
            ALTER TABLE app_releases 
                ADD COLUMN release_date DATE DEFAULT NULL AFTER title;
        "
    ];

    // 2. Cache-Check
    $cacheFile = __DIR__ . '/../cfg/.migration_cache';
    $currentHash = md5(serialize($migrations));

    if (file_exists($cacheFile) && file_get_contents($cacheFile) ===$currentHash) {
        return;
    }

    // 3. Migrationen ausführen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $executed =$pdo->query("SELECT migration_name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($migrations as $name =>$sql) {
        if (!in_array($name,$executed, true)) {
            try {
                $pdo->exec($sql);
                $stmt =$pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
                $stmt->execute([$name]);
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate column name')) {
                    $stmt =$pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
                    $stmt->execute([$name]);
                    continue;
                }
                die("Fehler bei Datenbank-Migration '{$name}': " . $e->getMessage());
            }
        }
    }

    @file_put_contents($cacheFile,$currentHash);
}
