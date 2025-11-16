<?php
/**
 * help_request_logic.php (Model)
 * Fonctions utilitaires pour gérer les CRUD de la table help_requests.
 * Réutilisable par n'importe quel controller PHP.
 */

// Configuration MySQL (utilisateur Projet2A créé dans phpMyAdmin)
const HR_DB_NAME = 'peaceconnect';
const HR_DB_HOST = 'localhost';
const HR_DB_USER = 'Projet2A';
const HR_DB_PASS = '123';

/**
 * Retourne une instance PDO initialisée (singleton basique).
 */
function get_pdo()
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

    // Connexion initiale pour créer la base si besoin
    try {
        $pdo = new PDO('mysql:host=' . HR_DB_HOST . ';charset=utf8mb4', HR_DB_USER, HR_DB_PASS, $config);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'error' => 'Connexion MySQL impossible : ' . $e->getMessage()]));
    }

    // Création de la base si elle n'existe pas
    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . HR_DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'error' => 'Création base échouée : ' . $e->getMessage()]));
    }

    // Reconnexion sur la base peaceconnect
    try {
        $pdo = new PDO('mysql:host=' . HR_DB_HOST . ';dbname=' . HR_DB_NAME . ';charset=utf8mb4', HR_DB_USER, HR_DB_PASS, $config);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'error' => 'Connexion à la base peaceconnect impossible : ' . $e->getMessage()]));
    }

    // Création de la table si besoin
    $create = "CREATE TABLE IF NOT EXISTS help_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        help_type VARCHAR(100) NOT NULL,
        urgency_level VARCHAR(50) NOT NULL,
        situation TEXT NOT NULL,
        location VARCHAR(100),
        contact_method VARCHAR(100),
        status VARCHAR(50) DEFAULT 'en_attente',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        responsable VARCHAR(100)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    try {
        $pdo->exec($create);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'error' => 'Création table échouée : ' . $e->getMessage()]));
    }

    return $pdo;
}

/**
 * Récupère toutes les demandes classées par date de création décroissante.
 */
function hr_get_all()
{
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT * FROM help_requests ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

/**
 * Récupère une seule demande.
 */
function hr_get($id)
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM help_requests WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

/**
 * Ajoute une demande et retourne l'identifiant créé.
 */
function hr_create(array $data)
{
    $pdo = get_pdo();
    $payload = hr_prepare_payload($data);

    $stmt = $pdo->prepare('INSERT INTO help_requests (help_type, urgency_level, situation, location, contact_method, status, responsable) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $payload['help_type'],
        $payload['urgency_level'],
        $payload['situation'],
        $payload['location'],
        $payload['contact_method'],
        $payload['status'],
        $payload['responsable'],
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Met à jour une demande.
 */
function hr_update($id, array $data)
{
    $pdo = get_pdo();
    $payload = hr_prepare_payload($data);

    $stmt = $pdo->prepare('UPDATE help_requests SET help_type = ?, urgency_level = ?, situation = ?, location = ?, contact_method = ?, status = ?, responsable = ? WHERE id = ?');
    return $stmt->execute([
        $payload['help_type'],
        $payload['urgency_level'],
        $payload['situation'],
        $payload['location'],
        $payload['contact_method'],
        $payload['status'],
        $payload['responsable'],
        (int)$id,
    ]);
}

/**
 * Supprime une demande.
 */
function hr_delete($id)
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM help_requests WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

/**
 * Nettoie les données POST brutes.
 */
function hr_get_post_data()
{
    $fields = ['help_type', 'urgency_level', 'situation', 'location', 'contact_method', 'status', 'responsable'];
    $data = [];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $data[$field] = trim($_POST[$field]);
        }
    }
    return $data;
}

/**
 * Valide & tronque les données avant insertion/update.
 */
function hr_prepare_payload(array $data)
{
    $required = ['help_type', 'urgency_level', 'situation'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new InvalidArgumentException('Le champ obligatoire "' . $field . '" est manquant.');
        }
    }

    $payload = [
        'help_type' => substr($data['help_type'], 0, 100),
        'urgency_level' => substr($data['urgency_level'], 0, 50),
        'situation' => $data['situation'],
        'location' => isset($data['location']) && $data['location'] !== '' ? substr($data['location'], 0, 100) : null,
        'contact_method' => isset($data['contact_method']) && $data['contact_method'] !== '' ? substr($data['contact_method'], 0, 100) : null,
        'status' => isset($data['status']) && $data['status'] !== '' ? substr($data['status'], 0, 50) : 'en_attente',
        'responsable' => isset($data['responsable']) && $data['responsable'] !== '' ? substr($data['responsable'], 0, 100) : null,
    ];

    return $payload;
}

?>