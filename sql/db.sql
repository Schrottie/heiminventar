CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    name VARCHAR(255) NOT NULL,

    CONSTRAINT fk_locations_parent
    FOREIGN KEY (parent_id)
    REFERENCES locations(id)
    ON DELETE CASCADE
);

CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    bezeichnung VARCHAR(255) NOT NULL,
    beschreibung TEXT NULL,

    location_id INT NULL,

    menge INT NOT NULL DEFAULT 1,

    erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_location
    FOREIGN KEY(location_id)
    REFERENCES locations(id)
);

-- CREATE TABLE categories (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL
-- );

-- CREATE TABLE manufacturers (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255)
-- );

CREATE TABLE item_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL
);

-- INSERT SAMPLE DATA
INSERT INTO `locations` (`id`, `parent_id`, `name`) VALUES
(1, NULL, 'Dachboden'),
(4, 1, 'Kleine Kammer'),
(5, 1, 'Große Kammer'),
(6, 5, 'Werkzeugkiste (rot)'),
(7, 5, 'Kramkiste (Blech)'),
(8, 4, 'Schrank 1'),
(9, 8, '1. Schubfach'),
(10, 8, '2. Schubfach'),
(11, 9, 'Nageldose'),
(12, 10, 'Grüne Keksdose'),
(14, 10, 'Gelbe Keksdose'),
(15, 10, 'Rote Kaffeedose'),
(16, NULL, 'Keller'),
(17, 16, 'Trockenraum'),
(18, 17, 'Wäschekiste'),
(22, NULL, 'Erdgeschoß'),
(23, 22, 'Kinderzimmer'),
(24, 23, 'Spielzeugkiste'),
(25, 23, 'Teddykiste'),
(26, 22, 'Schlafzimmer'),
(27, 26, 'Erwachsenenspielzeugschubfach');

INSERT INTO `inventory_items` (`id`, `name`, `description`, `location_id`, `quantity`, `created`) VALUES
(1, 'Drehmomentschlüssel groß', '', 6, 1, '2026-09-30 04:42:03'),
(2, 'Drehmomentschlüssel klein', '', 7, 1, '2026-09-30 04:42:35'),
(3, 'Zinknägel (6,5cm)', 'Alte Nägel, krumm wie Sau, aber zu schade zum wegwerfen.', 11, 500, '2026-09-30 05:09:34'),
(4, 'Hufnägel', '', 11, 17, '2026-09-30 05:52:27'),
(5, 'Wäscheklammern', '', 18, 199, '2026-09-30 10:09:07'),
(6, 'Alter Strumpf', '', 18, 1, '2026-09-30 11:06:29'),
(7, 'Analintruder', '', 27, 1, '2026-10-01 04:06:10'),
(8, 'Brummbär', '', 25, 1, '2026-10-01 08:14:50'),
(9, 'Monchihchi', '', 25, 1, '2026-10-01 08:15:04'),
(10, 'Steiffbär', '', 25, 1, '2026-10-01 08:19:31'),
(11, 'Plüschbär', '', 25, 1, '2026-10-01 08:19:53');

INSERT INTO `item_images` (`id`, `item_id`, `filename`) VALUES
(1, 3, 'item_6abc9a3ab8ad16.69568262.jpg');