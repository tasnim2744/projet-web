-- Création des tables pour le Module 5 - Événements & Contenus

-- Créer la base de données et l'utiliser
CREATE DATABASE IF NOT EXISTS peaceconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE peaceconnect;

-- Table des catégories d'événements
CREATE TABLE IF NOT EXISTS event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des thèmes/contenus
CREATE TABLE IF NOT EXISTS themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des événements
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    event_date DATETIME NOT NULL,
    end_date DATETIME,
    capacity INT NOT NULL DEFAULT 50,
    current_registrations INT DEFAULT 0,
    organizer_id INT,
    category_id INT NOT NULL,
    theme_id INT,
    image_url VARCHAR(255),
    status VARCHAR(50) DEFAULT 'planned',
    visibility VARCHAR(50) DEFAULT 'public',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES event_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des inscriptions aux événements
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'registered',
    attendance_confirmed BOOLEAN DEFAULT FALSE,
    attendance_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    INDEX idx_email (email),
    UNIQUE KEY unique_registration (event_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des articles/contenus pédagogiques
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(500),
    author_id INT,
    author_name VARCHAR(100),
    category_id INT,
    theme_id INT,
    featured_image VARCHAR(255),
    status VARCHAR(50) DEFAULT 'draft',
    published_date DATETIME,
    views_count INT DEFAULT 0,
    is_testimony BOOLEAN DEFAULT FALSE,
    requires_validation BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES event_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_published (published_date),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des commentaires
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT,
    author_name VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    ai_flag_status VARCHAR(50) DEFAULT 'clean',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    INDEX idx_article (article_id),
    INDEX idx_status (status),
    INDEX idx_ai_flag (ai_flag_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de flags IA pour contenus suspects
CREATE TABLE IF NOT EXISTS ai_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50) NOT NULL,
    content_id INT NOT NULL,
    flag_type VARCHAR(100) NOT NULL,
    severity VARCHAR(50) DEFAULT 'medium',
    description TEXT,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by VARCHAR(100),
    resolved_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_content (content_type, content_id),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des catégories par défaut
INSERT IGNORE INTO event_categories (name, description, icon, color) VALUES
('Ateliers de médiation', 'Ateliers pratiques de médiation et résolution de conflits', '🤝', '#4CAF50'),
('Formations', 'Sessions de formation et ateliers éducatifs', '📚', '#2196F3'),
('Campagnes de sensibilisation', 'Campagnes d\'information et de sensibilisation', '📢', '#FF9800'),
('Conférences', 'Conférences et présentations', '🎤', '#9C27B0'),
('Rencontres communautaires', 'Réunions et rencontres avec la communauté', '👥', '#00BCD4');

-- Insertion des thèmes par défaut
INSERT IGNORE INTO themes (name, description, icon) VALUES
('Paix et résolution de conflits', 'Thèmes liés à la paix et la gestion des conflits', '☮️'),
('Justice et droits humains', 'Justice, droits humains et État de droit', '⚖️'),
('Inclusion et diversité', 'Inclusion sociale et acceptation de la diversité', '🌈'),
('Prévention de la violence', 'Prévention de la violence et promotion de la sécurité', '🛡️'),
('Dialogue intercommunautaire', 'Dialogue et compréhension entre communautés', '🤲');

-- ==================================================================================
-- Triggers & Procédures stockées (CRUD simplifié pour faciliter l'utilisation)
-- ==================================================================================

-- Triggers pour maintenir correctement le compteur current_registrations
DROP TRIGGER IF EXISTS trg_increment_registrations;
DROP TRIGGER IF EXISTS trg_decrement_registrations;
DELIMITER $$
CREATE TRIGGER trg_increment_registrations AFTER INSERT ON event_registrations
FOR EACH ROW
BEGIN
    UPDATE events SET current_registrations = current_registrations + 1 WHERE id = NEW.event_id;
END$$

CREATE TRIGGER trg_decrement_registrations AFTER DELETE ON event_registrations
FOR EACH ROW
BEGIN
    UPDATE events SET current_registrations = GREATEST(0, current_registrations - 1) WHERE id = OLD.event_id;
END$$
DELIMITER ;

-- Procédures: évènements (create/read/update/delete)
DROP PROCEDURE IF EXISTS sp_create_event;
DROP PROCEDURE IF EXISTS sp_get_event;
DROP PROCEDURE IF EXISTS sp_update_event;
DROP PROCEDURE IF EXISTS sp_delete_event;
DELIMITER $$
CREATE PROCEDURE sp_create_event(
    IN p_title VARCHAR(200), IN p_description TEXT, IN p_location VARCHAR(255),
    IN p_event_date DATETIME, IN p_end_date DATETIME, IN p_capacity INT,
    IN p_category_id INT, IN p_theme_id INT, IN p_organizer_id INT,
    IN p_status VARCHAR(50), IN p_visibility VARCHAR(50), IN p_image_url VARCHAR(255)
)
BEGIN
    INSERT INTO events (title, description, location, event_date, end_date, capacity, category_id, theme_id, organizer_id, status, visibility, image_url)
    VALUES (p_title, p_description, p_location, p_event_date, p_end_date, IFNULL(p_capacity,50), p_category_id, p_theme_id, p_organizer_id, p_status, p_visibility, p_image_url);
    SELECT LAST_INSERT_ID() AS event_id;
END$$

CREATE PROCEDURE sp_get_event(IN p_id INT)
BEGIN
    SELECT e.*, ec.name AS category_name, t.name AS theme_name FROM events e
    LEFT JOIN event_categories ec ON e.category_id = ec.id
    LEFT JOIN themes t ON e.theme_id = t.id
    WHERE e.id = p_id;
END$$

CREATE PROCEDURE sp_update_event(
    IN p_id INT, IN p_title VARCHAR(200), IN p_description TEXT, IN p_location VARCHAR(255),
    IN p_event_date DATETIME, IN p_end_date DATETIME, IN p_capacity INT,
    IN p_category_id INT, IN p_theme_id INT, IN p_status VARCHAR(50), IN p_visibility VARCHAR(50), IN p_image_url VARCHAR(255)
)
BEGIN
    UPDATE events SET title = p_title, description = p_description, location = p_location, event_date = p_event_date,
        end_date = p_end_date, capacity = p_capacity, category_id = p_category_id, theme_id = p_theme_id,
        status = p_status, visibility = p_visibility, image_url = p_image_url WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$

CREATE PROCEDURE sp_delete_event(IN p_id INT)
BEGIN
    DELETE FROM events WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$
DELIMITER ;

-- Procédures: inscriptions (create/delete)
DROP PROCEDURE IF EXISTS sp_create_registration;
DROP PROCEDURE IF EXISTS sp_delete_registration;
DELIMITER $$
CREATE PROCEDURE sp_create_registration(
    IN p_event_id INT, IN p_user_id INT, IN p_email VARCHAR(100), IN p_full_name VARCHAR(100), IN p_phone VARCHAR(20)
)
BEGIN
    IF EXISTS (SELECT 1 FROM event_registrations WHERE event_id = p_event_id AND email = p_email) THEN
        SELECT -1 AS error_code, 'duplicate_registration' AS message;
    ELSE
        INSERT INTO event_registrations (event_id, user_id, email, full_name, phone) VALUES (p_event_id, p_user_id, p_email, p_full_name, p_phone);
        SELECT LAST_INSERT_ID() AS registration_id;
    END IF;
END$$

CREATE PROCEDURE sp_delete_registration(IN p_id INT)
BEGIN
    DELETE FROM event_registrations WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$
DELIMITER ;

-- Procédures: articles (create/update/delete)
DROP PROCEDURE IF EXISTS sp_create_article;
DROP PROCEDURE IF EXISTS sp_update_article;
DROP PROCEDURE IF EXISTS sp_delete_article;
DELIMITER $$
CREATE PROCEDURE sp_create_article(
    IN p_title VARCHAR(255), IN p_slug VARCHAR(255), IN p_content TEXT, IN p_excerpt VARCHAR(500),
    IN p_author_id INT, IN p_author_name VARCHAR(100), IN p_category_id INT, IN p_theme_id INT,
    IN p_featured_image VARCHAR(255), IN p_status VARCHAR(50), IN p_is_testimony BOOLEAN, IN p_requires_validation BOOLEAN
)
BEGIN
    INSERT INTO articles (title, slug, content, excerpt, author_id, author_name, category_id, theme_id, featured_image, status, is_testimony, requires_validation)
    VALUES (p_title, p_slug, p_content, p_excerpt, p_author_id, p_author_name, p_category_id, p_theme_id, p_featured_image, p_status, p_is_testimony, p_requires_validation);
    SELECT LAST_INSERT_ID() AS article_id;
END$$

CREATE PROCEDURE sp_update_article(
    IN p_id INT, IN p_title VARCHAR(255), IN p_slug VARCHAR(255), IN p_content TEXT, IN p_excerpt VARCHAR(500),
    IN p_category_id INT, IN p_theme_id INT, IN p_featured_image VARCHAR(255), IN p_status VARCHAR(50)
)
BEGIN
    UPDATE articles SET title = p_title, slug = p_slug, content = p_content, excerpt = p_excerpt,
        category_id = p_category_id, theme_id = p_theme_id, featured_image = p_featured_image, status = p_status
        WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$

CREATE PROCEDURE sp_delete_article(IN p_id INT)
BEGIN
    DELETE FROM articles WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$
DELIMITER ;

-- Procédures: commentaires (create/approve/delete)
DROP PROCEDURE IF EXISTS sp_create_comment;
DROP PROCEDURE IF EXISTS sp_approve_comment;
DROP PROCEDURE IF EXISTS sp_delete_comment;
DELIMITER $$
CREATE PROCEDURE sp_create_comment(
    IN p_article_id INT, IN p_user_id INT, IN p_author_name VARCHAR(100), IN p_content TEXT
)
BEGIN
    INSERT INTO comments (article_id, user_id, author_name, content, status, ai_flag_status)
    VALUES (p_article_id, p_user_id, p_author_name, p_content, 'pending', 'clean');
    SELECT LAST_INSERT_ID() AS comment_id;
END$$

CREATE PROCEDURE sp_approve_comment(IN p_id INT)
BEGIN
    UPDATE comments SET status = 'approved' WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$

CREATE PROCEDURE sp_delete_comment(IN p_id INT)
BEGIN
    DELETE FROM comments WHERE id = p_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$
DELIMITER ;

-- Fin des procédures stockées
