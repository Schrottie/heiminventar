<?php
/**
 * Automatische, selbstheilende Schema-Migration mit Spalten- und Tabellen-Check
 */
function runDatabaseMigrations(PDO $pdo): void
{
    // ///////////////////////////////////////////////////////////
    // 1. Basis-Tabellen sicherstellen (läuft immer)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS locations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NULL,
            name VARCHAR(255) NOT NULL,
            CONSTRAINT fk_locations_parent FOREIGN KEY (parent_id) REFERENCES locations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS inventory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            location_id INT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_inventory_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\

    ");

    // ///////////////////////////////////////////////////////////
    // 2. Hilfsfunktion: Prüfen ob eine Spalte in einer Tabelle existiert
    $columnExists = function(PDO$pdo, string $table, string$column): bool {
        try {
            $stmt =$pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table,$column]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    };

    // ///////////////////////////////////////////////////////////
    // 3. Definition aller Schema-Migrationen
    $migrations = [
        '001_create_base_tables' => "
            CREATE TABLE IF NOT EXISTS item_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                CONSTRAINT fk_images_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",

        '002_create_settings_table' => "
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('theme', 'light');
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
            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('show_images', '1');
        ",

        '005_create_main_image_function' => "
            -- Wird dynamisch unten geprüft
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
            -- Wird dynamisch unten geprüft
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
            -- Spaltenprüfung für users erfolgt unten
        ",

        '0010_relerase_notes' => "
            -- Release
        ",

        '011_release_1_0_2' => "
            -- Release
        ",

        '012_add_archive_to_items' => "
            -- Spaltenprüfung erfolgt unten
        ",

        '013_release_1_0_3' => "
        ",

        '013_release_1_0_4' => "
        ",

        '014_add_functions' => "
            -- Spaltenprüfung erfolgt unten
        ",

        '015_release_1_0_4' => "
        ",

        '016_release_1_0_5' => "
        ",

        '017_add_functions' => "
        ",

        '017_add_maintenance_functions' => "
            -- Spaltenprüfung erfolgt unten
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
            -- Spaltenprüfung erfolgt unten
        ",

        '019_release_1_0_6' => "
        ",

        '020_release_1_0_7' => "
        ",

        '021_add_fields' => "
           -- Spaltenprüfung erfolgt unten
        ",

        '022_loan_system' => "
           -- Spaltenprüfung erfolgt unten
        ",

        '023_part_system' => "
           -- Tabelle wird unten gebaut, sofern nicht vorhanden
        ",

        '024_fiedl_added_for_part_system' => "
           -- Spaltenprüfung erfolgt unten
        ",

        '025_release_1_1_0' => "
        ",

        '026_release_1_1_1' => "
        ",

        '027_add_audit_schedules' => "
            CREATE TABLE IF NOT EXISTS user_inventory_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                slot_index TINYINT NOT NULL,
                inventory_date DATE NOT NULL,
                location_id INT DEFAULT NULL, 
                title VARCHAR(100) DEFAULT 'Reguläre Inventur',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY user_slot (user_id, slot_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        "
    ];

    // ///////////////////////////////////////////////////////////
    // 4. Cache-Check basierend auf dem Array
    $cacheFile = __DIR__ . '/../cfg/.migration_cache';
    $currentHash = md5(serialize($migrations));

    // ///////////////////////////////////////////////////////////
    // 5. UNABHÄNGIG VOM CACHE: Strukturelle Integrität prüfen und nachziehen (Selbstheilung)
    // Das fängt übersprungene Updates oder gelöschte Spalten ab!
    
    // item_images.is_main
    if (!$columnExists($pdo, 'item_images', 'is_main')) {
        $pdo->exec("ALTER TABLE item_images 
            ADD COLUMN is_main TINYINT(1) DEFAULT 0;");
    }
    // inventory_items.min_quantity
    if (!$columnExists($pdo, 'inventory_items', 'min_quantity')) {
        $pdo->exec("ALTER TABLE inventory_items 
            ADD COLUMN min_quantity INT DEFAULT 0 AFTER quantity;");
    }
    // users.last_seen_version
    if (!$columnExists($pdo, 'users', 'last_seen_version')) {
        $pdo->exec("ALTER TABLE users 
            ADD COLUMN last_seen_version VARCHAR(20) NULL AFTER role;");
    }
    // inventory_items.is_archived
    if (!$columnExists($pdo, 'inventory_items', 'is_archived')) {
        $pdo->exec("ALTER TABLE inventory_items 
            ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0, 
            ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL;");
    }
    // inventory_items Funktionsspalten (expiration_date, price, on_loan, loan_to)
    if (!$columnExists($pdo, 'inventory_items', 'expiration_date')) {
        $pdo->exec("ALTER TABLE inventory_items 
            ADD COLUMN expiration_date DATE DEFAULT NULL AFTER min_quantity, 
            ADD COLUMN price DECIMAL(10,2) DEFAULT NULL AFTER expiration_date, 
            ADD COLUMN on_loan TINYINT(1) DEFAULT 0 AFTER price, 
            ADD COLUMN loan_to VARCHAR(255) DEFAULT NULL AFTER on_loan;");
    }
    // inventory_items Wartungsspalten
    if (!$columnExists($pdo, 'inventory_items', 'maintenance_interval_months')) {
        $pdo->exec("ALTER TABLE inventory_items 
            ADD COLUMN maintenance_interval_months INT DEFAULT NULL AFTER loan_to, 
            ADD COLUMN last_maintenance_date DATE DEFAULT NULL AFTER maintenance_interval_months;");
    }
    // app_releases.release_date
    if (!$columnExists($pdo, 'app_releases', 'release_date')) {
        $pdo->exec("ALTER TABLE app_releases 
            ADD COLUMN release_date DATE DEFAULT NULL AFTER title;");
    }
    // inventory_items Funktionsspalten
        if (!$columnExists($pdo, 'inventory_items', 'purchase_date')) {
        $pdo->exec("ALTER TABLE inventory_items 
            ADD COLUMN purchase_date DATE DEFAULT NULL AFTER price,
            ADD COLUMN purchase_location VARCHAR(255) DEFAULT NULL AFTER purchase_date,
            ADD COLUMN manufacturer VARCHAR(255) DEFAULT NULL AFTER purchase_location,
            ADD COLUMN warranty_months INT DEFAULT NULL AFTER manufacturer,
            ADD COLUMN is_insured TINYINT(1) DEFAULT 0 AFTER warranty_months,
            ADD COLUMN loan_date DATE DEFAULT NULL AFTER loan_to;
        ");
    }
    // Spalte 'is_set' in inventory_items ergänzen, falls noch nicht vorhanden
    if (!$columnExists($pdo, 'inventory_items', 'is_set')) {
        $pdo->exec("ALTER TABLE inventory_items ADD COLUMN is_set TINYINT(1) NOT NULL DEFAULT 0 AFTER is_insured;");
    }
    // Set-Tabelle mit Name des Inhaltsteils, Set-Artikel-ID und Sortierindex
    if (!$pdo->query("SHOW TABLES LIKE 'item_set_items'")->fetch()) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS item_set_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                CONSTRAINT fk_set_parent_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    // Dokumententypen für item_documents ergänzen
    if (!$columnExists($pdo, 'item_documents', 'doc_type')) {
        $pdo->exec("ALTER TABLE item_documents 
            ADD COLUMN doc_type VARCHAR(50) NOT NULL DEFAULT 'sonstiges' AFTER title;");
    }
    // Dedizierte Einheiten- und Verleih-Verwaltung (item_units)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS item_units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            unit_number INT NOT NULL DEFAULT 1,
            status ENUM('available', 'lent') NOT NULL DEFAULT 'available',
            borrower_name VARCHAR(255) DEFAULT NULL,
            lent_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // Spalte 'is_bulk' in inventory_items ergänzen, falls noch nicht vorhanden
    if (!$columnExists($pdo, 'inventory_items', 'is_bulk')) {
        $pdo->exec("ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS is_bulk TINYINT(1) NOT NULL DEFAULT 0;");
    }
    // Tabelle für Anbauteile/Zubehörteile
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS item_parts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) DEFAULT NULL,
            has_own_invoice TINYINT(1) NOT NULL DEFAULT 1,
            invoice_filename VARCHAR(255) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // Spalte 'invoice_status' für item_parts ergänzen, falls noch nicht vorhanden
    if (!$columnExists($pdo, 'item_parts', 'invoice_status')) {
    $pdo->exec("ALTER TABLE item_parts ADD COLUMN invoice_status VARCHAR(20) NOT NULL DEFAULT 'own';");
    }

    // ///////////////////////////////////////////////////////////
    // 6. Standard-Migrationen ganz normal ausführen (falls Cache nicht greift)
    if (file_exists($cacheFile) && file_get_contents($cacheFile) ===$currentHash) {
        return;
    }

    $executed =$pdo->query("SELECT migration_name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($migrations as $name =>$sql) {
        if (!in_array($name, $executed, true) && trim($sql) !== '') {
            try {
                $pdo->exec($sql);
                $stmt =$pdo->prepare("INSERT IGNORE INTO schema_migrations (migration_name) VALUES (?)");
                $stmt->execute([$name]);
            } catch (PDOException $e) {
                // Fehler abfangen, falls Tabelle/Spalte durch Selbstheilung bereits da war
                $stmt =$pdo->prepare("INSERT IGNORE INTO schema_migrations (migration_name) VALUES (?)");
                $stmt->execute([$name]);
            }
        }
    }

    @file_put_contents($cacheFile,$currentHash);
}
