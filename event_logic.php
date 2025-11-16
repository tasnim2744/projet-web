<?php
/**
 * event_logic.php (Model)
 * Fonctions utilitaires pour gérer les CRUD de la table events et contenus associés.
 * Réutilisable par n'importe quel controller PHP.
 */

// Configuration MySQL
const EVENT_DB_NAME = 'peaceconnect';
const EVENT_DB_HOST = 'localhost';
const EVENT_DB_USER = 'Projet2A';
const EVENT_DB_PASS = '123';

/**
 * Retourne une instance PDO initialisée (singleton basique).
 */
function get_event_pdo()
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO('mysql:host=' . EVENT_DB_HOST . ';charset=utf8mb4', EVENT_DB_USER, EVENT_DB_PASS, $config);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . EVENT_DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        $pdo = new PDO('mysql:host=' . EVENT_DB_HOST . ';dbname=' . EVENT_DB_NAME . ';charset=utf8mb4', EVENT_DB_USER, EVENT_DB_PASS, $config);
        
        // Créer les tables si besoin
        create_events_tables($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'error' => 'Erreur BD: ' . $e->getMessage()]));
    }
}

/**
 * Crée les tables d'événements et contenus
 */
function create_events_tables($pdo)
{
    $tables = [
        // Catégories d'événements
        "CREATE TABLE IF NOT EXISTS event_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50),
            color VARCHAR(20),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Thèmes
        "CREATE TABLE IF NOT EXISTS themes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Événements
        "CREATE TABLE IF NOT EXISTS events (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Inscriptions
        "CREATE TABLE IF NOT EXISTS event_registrations (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Articles/Contenus
        "CREATE TABLE IF NOT EXISTS articles (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Commentaires
        "CREATE TABLE IF NOT EXISTS comments (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Flags IA
        "CREATE TABLE IF NOT EXISTS ai_flags (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // La table existe peut-être déjà
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    // Insérer les catégories et thèmes par défaut
    insert_default_categories($pdo);
    insert_default_themes($pdo);
}

/**
 * Insère les catégories par défaut
 */
function insert_default_categories($pdo)
{
    $categories = [
        ['Ateliers de médiation', 'Ateliers pratiques de médiation et résolution de conflits', '🤝', '#4CAF50'],
        ['Formations', 'Sessions de formation et ateliers éducatifs', '📚', '#2196F3'],
        ['Campagnes de sensibilisation', 'Campagnes d\'information et de sensibilisation', '📢', '#FF9800'],
        ['Conférences', 'Conférences et présentations', '🎤', '#9C27B0'],
        ['Rencontres communautaires', 'Réunions et rencontres avec la communauté', '👥', '#00BCD4'],
    ];

    foreach ($categories as $cat) {
        try {
            $stmt = $pdo->prepare('INSERT IGNORE INTO event_categories (name, description, icon, color) VALUES (?, ?, ?, ?)');
            $stmt->execute($cat);
        } catch (PDOException $e) {
            // Ignorer les doublons
        }
    }
}

/**
 * Insère les thèmes par défaut
 */
function insert_default_themes($pdo)
{
    $themes = [
        ['Paix et résolution de conflits', 'Thèmes liés à la paix et la gestion des conflits', '☮️'],
        ['Justice et droits humains', 'Justice, droits humains et État de droit', '⚖️'],
        ['Inclusion et diversité', 'Inclusion sociale et acceptation de la diversité', '🌈'],
        ['Prévention de la violence', 'Prévention de la violence et promotion de la sécurité', '🛡️'],
        ['Dialogue intercommunautaire', 'Dialogue et compréhension entre communautés', '🤲'],
    ];

    foreach ($themes as $theme) {
        try {
            $stmt = $pdo->prepare('INSERT IGNORE INTO themes (name, description, icon) VALUES (?, ?, ?)');
            $stmt->execute($theme);
        } catch (PDOException $e) {
            // Ignorer les doublons
        }
    }
}

// ==================== CRUD ÉVÉNEMENTS ====================

/**
 * Récupère tous les événements triés par date
 */
function event_get_all($filters = [])
{
    $pdo = get_event_pdo();
    $sql = 'SELECT e.*, ec.name as category_name, t.name as theme_name FROM events e 
            LEFT JOIN event_categories ec ON e.category_id = ec.id 
            LEFT JOIN themes t ON e.theme_id = t.id WHERE 1=1';
    $params = [];

    if (isset($filters['status']) && !empty($filters['status'])) {
        $sql .= ' AND e.status = ?';
        $params[] = $filters['status'];
    }

    if (isset($filters['category_id']) && !empty($filters['category_id'])) {
        $sql .= ' AND e.category_id = ?';
        $params[] = $filters['category_id'];
    }

    if (isset($filters['visibility']) && !empty($filters['visibility'])) {
        $sql .= ' AND e.visibility = ?';
        $params[] = $filters['visibility'];
    }

    $sql .= ' ORDER BY e.event_date ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Récupère un événement par ID
 */
function event_get($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('SELECT e.*, ec.name as category_name, t.name as theme_name FROM events e 
                          LEFT JOIN event_categories ec ON e.category_id = ec.id 
                          LEFT JOIN themes t ON e.theme_id = t.id WHERE e.id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

/**
 * Crée un nouvel événement
 */
function event_create(array $data)
{
    $pdo = get_event_pdo();
    $payload = event_prepare_payload($data);

    $stmt = $pdo->prepare('INSERT INTO events (title, description, location, event_date, end_date, capacity, 
                          category_id, theme_id, organizer_id, status, visibility, image_url) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        $payload['title'],
        $payload['description'],
        $payload['location'],
        $payload['event_date'],
        $payload['end_date'],
        $payload['capacity'],
        $payload['category_id'],
        $payload['theme_id'],
        $payload['organizer_id'],
        $payload['status'],
        $payload['visibility'],
        $payload['image_url'],
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Met à jour un événement
 */
function event_update($id, array $data)
{
    $pdo = get_event_pdo();
    $payload = event_prepare_payload($data);

    $stmt = $pdo->prepare('UPDATE events SET title = ?, description = ?, location = ?, event_date = ?, 
                          end_date = ?, capacity = ?, category_id = ?, theme_id = ?, status = ?, 
                          visibility = ?, image_url = ? WHERE id = ?');
    
    return $stmt->execute([
        $payload['title'],
        $payload['description'],
        $payload['location'],
        $payload['event_date'],
        $payload['end_date'],
        $payload['capacity'],
        $payload['category_id'],
        $payload['theme_id'],
        $payload['status'],
        $payload['visibility'],
        $payload['image_url'],
        (int)$id,
    ]);
}

/**
 * Supprime un événement
 */
function event_delete($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

/**
 * Valide & tronque les données avant insertion/update
 */
function event_prepare_payload(array $data)
{
    $required = ['title', 'description', 'location', 'event_date', 'category_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new InvalidArgumentException('Le champ obligatoire "' . $field . '" est manquant.');
        }
    }

    $payload = [
        'title' => substr($data['title'], 0, 200),
        'description' => $data['description'],
        'location' => substr($data['location'], 0, 255),
        'event_date' => $data['event_date'],
        'end_date' => isset($data['end_date']) && !empty($data['end_date']) ? $data['end_date'] : null,
        'capacity' => max(1, (int)($data['capacity'] ?? 50)),
        'category_id' => (int)$data['category_id'],
        'theme_id' => isset($data['theme_id']) && !empty($data['theme_id']) ? (int)$data['theme_id'] : null,
        'organizer_id' => isset($data['organizer_id']) ? (int)$data['organizer_id'] : null,
        'status' => isset($data['status']) ? substr($data['status'], 0, 50) : 'planned',
        'visibility' => isset($data['visibility']) ? substr($data['visibility'], 0, 50) : 'public',
        'image_url' => isset($data['image_url']) ? substr($data['image_url'], 0, 255) : null,
    ];

    return $payload;
}

// ==================== CRUD INSCRIPTIONS ====================

/**
 * Récupère toutes les inscriptions pour un événement
 */
function registration_get_all_for_event($event_id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('SELECT * FROM event_registrations WHERE event_id = ? ORDER BY registration_date DESC');
    $stmt->execute([(int)$event_id]);
    return $stmt->fetchAll();
}

/**
 * Crée une nouvelle inscription
 */
function registration_create(array $data)
{
    $pdo = get_event_pdo();
    $required = ['event_id', 'full_name', 'email'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new InvalidArgumentException('Le champ "' . $field . '" est obligatoire.');
        }
    }

    $stmt = $pdo->prepare('INSERT INTO event_registrations (event_id, email, full_name, phone, user_id, status) 
                          VALUES (?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        (int)$data['event_id'],
        substr($data['email'], 0, 100),
        substr($data['full_name'], 0, 100),
        isset($data['phone']) ? substr($data['phone'], 0, 20) : null,
        isset($data['user_id']) ? (int)$data['user_id'] : null,
        isset($data['status']) ? $data['status'] : 'registered',
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Confirme la présence à un événement
 */
function registration_confirm_attendance($registration_id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('UPDATE event_registrations SET attendance_confirmed = TRUE, 
                          attendance_date = NOW() WHERE id = ?');
    return $stmt->execute([(int)$registration_id]);
}

/**
 * Supprime une inscription
 */
function registration_delete($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('DELETE FROM event_registrations WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

// ==================== CATÉGORIES ====================

/**
 * Récupère toutes les catégories
 */
function category_get_all()
{
    $pdo = get_event_pdo();
    $stmt = $pdo->query('SELECT * FROM event_categories ORDER BY name ASC');
    return $stmt->fetchAll();
}

/**
 * Crée une catégorie
 */
function category_create(array $data)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('INSERT INTO event_categories (name, description, icon, color) VALUES (?, ?, ?, ?)');
    
    $stmt->execute([
        substr($data['name'], 0, 100),
        isset($data['description']) ? $data['description'] : null,
        isset($data['icon']) ? substr($data['icon'], 0, 50) : null,
        isset($data['color']) ? substr($data['color'], 0, 20) : null,
    ]);

    return (int)$pdo->lastInsertId();
}

// ==================== THÈMES ====================

/**
 * Récupère tous les thèmes
 */
function theme_get_all()
{
    $pdo = get_event_pdo();
    $stmt = $pdo->query('SELECT * FROM themes ORDER BY name ASC');
    return $stmt->fetchAll();
}

/**
 * Crée un thème
 */
function theme_create(array $data)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('INSERT INTO themes (name, description, icon) VALUES (?, ?, ?)');
    
    $stmt->execute([
        substr($data['name'], 0, 100),
        isset($data['description']) ? $data['description'] : null,
        isset($data['icon']) ? substr($data['icon'], 0, 50) : null,
    ]);

    return (int)$pdo->lastInsertId();
}

// ==================== ARTICLES/CONTENUS ====================

/**
 * Récupère tous les articles avec filtres
 */
function article_get_all($filters = [])
{
    $pdo = get_event_pdo();
    $sql = 'SELECT a.*, ec.name as category_name, t.name as theme_name FROM articles a 
            LEFT JOIN event_categories ec ON a.category_id = ec.id 
            LEFT JOIN themes t ON a.theme_id = t.id WHERE 1=1';
    $params = [];

    if (isset($filters['status']) && !empty($filters['status'])) {
        $sql .= ' AND a.status = ?';
        $params[] = $filters['status'];
    }

    if (isset($filters['is_testimony']) && $filters['is_testimony'] !== '') {
        $sql .= ' AND a.is_testimony = ?';
        $params[] = $filters['is_testimony'] ? 1 : 0;
    }

    $sql .= ' ORDER BY a.published_date DESC, a.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Récupère un article par ID
 */
function article_get($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('SELECT a.*, ec.name as category_name, t.name as theme_name FROM articles a 
                          LEFT JOIN event_categories ec ON a.category_id = ec.id 
                          LEFT JOIN themes t ON a.theme_id = t.id WHERE a.id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

/**
 * Crée un article
 */
function article_create(array $data)
{
    $pdo = get_event_pdo();
    $payload = article_prepare_payload($data);

    $stmt = $pdo->prepare('INSERT INTO articles (title, slug, content, excerpt, author_id, author_name, 
                          category_id, theme_id, featured_image, status, is_testimony, requires_validation) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        $payload['title'],
        $payload['slug'],
        $payload['content'],
        $payload['excerpt'],
        $payload['author_id'],
        $payload['author_name'],
        $payload['category_id'],
        $payload['theme_id'],
        $payload['featured_image'],
        $payload['status'],
        $payload['is_testimony'],
        $payload['requires_validation'],
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Met à jour un article
 */
function article_update($id, array $data)
{
    $pdo = get_event_pdo();
    $payload = article_prepare_payload($data);

    $stmt = $pdo->prepare('UPDATE articles SET title = ?, slug = ?, content = ?, excerpt = ?, 
                          category_id = ?, theme_id = ?, featured_image = ?, status = ? WHERE id = ?');
    
    return $stmt->execute([
        $payload['title'],
        $payload['slug'],
        $payload['content'],
        $payload['excerpt'],
        $payload['category_id'],
        $payload['theme_id'],
        $payload['featured_image'],
        $payload['status'],
        (int)$id,
    ]);
}

/**
 * Supprime un article
 */
function article_delete($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

/**
 * Valide les données article
 */
function article_prepare_payload(array $data)
{
    $required = ['title', 'content'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new InvalidArgumentException('Le champ "' . $field . '" est obligatoire.');
        }
    }

    $slug = isset($data['slug']) ? $data['slug'] : generate_slug($data['title']);

    $payload = [
        'title' => substr($data['title'], 0, 255),
        'slug' => substr($slug, 0, 255),
        'content' => $data['content'],
        'excerpt' => isset($data['excerpt']) ? substr($data['excerpt'], 0, 500) : null,
        'author_id' => isset($data['author_id']) ? (int)$data['author_id'] : null,
        'author_name' => isset($data['author_name']) ? substr($data['author_name'], 0, 100) : 'Anonyme',
        'category_id' => isset($data['category_id']) ? (int)$data['category_id'] : null,
        'theme_id' => isset($data['theme_id']) ? (int)$data['theme_id'] : null,
        'featured_image' => isset($data['featured_image']) ? substr($data['featured_image'], 0, 255) : null,
        'status' => isset($data['status']) ? $data['status'] : 'draft',
        'is_testimony' => isset($data['is_testimony']) ? (bool)$data['is_testimony'] : false,
        'requires_validation' => isset($data['requires_validation']) ? (bool)$data['requires_validation'] : true,
    ];

    return $payload;
}

/**
 * Génère un slug à partir du titre
 */
function generate_slug($title)
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^\w\s-]/', '', $slug);
    $slug = preg_replace('/[\s_-]+/', '-', $slug);
    $slug = preg_replace('/^-+|-+$/', '', $slug);
    return substr($slug, 0, 255);
}

// ==================== COMMENTAIRES ====================

/**
 * Récupère tous les commentaires pour un article
 */
function comment_get_all_for_article($article_id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('SELECT * FROM comments WHERE article_id = ? AND status = "approved" ORDER BY created_at DESC');
    $stmt->execute([(int)$article_id]);
    return $stmt->fetchAll();
}

/**
 * Crée un commentaire
 */
function comment_create(array $data)
{
    $pdo = get_event_pdo();
    $required = ['article_id', 'author_name', 'content'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new InvalidArgumentException('Le champ "' . $field . '" est obligatoire.');
        }
    }

    $stmt = $pdo->prepare('INSERT INTO comments (article_id, user_id, author_name, content, status, ai_flag_status) 
                          VALUES (?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        (int)$data['article_id'],
        isset($data['user_id']) ? (int)$data['user_id'] : null,
        substr($data['author_name'], 0, 100),
        $data['content'],
        'pending',
        'clean',
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Approuve un commentaire
 */
function comment_approve($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('UPDATE comments SET status = "approved" WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

/**
 * Supprime un commentaire
 */
function comment_delete($id)
{
    $pdo = get_event_pdo();
    $stmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

?>
