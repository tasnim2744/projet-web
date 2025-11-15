<?php
/**
 * events.php
 * Gestion complète des événements (CRUD) - Front et Back Office
 */

session_start();
require_once __DIR__ . '/Model/event_logic.php';

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ==================== TRAITEMENT POST ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_event'])) {
            // Créer un événement
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'location' => $_POST['location'] ?? '',
                'event_date' => $_POST['event_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'capacity' => $_POST['capacity'] ?? 50,
                'category_id' => $_POST['category_id'] ?? '',
                'theme_id' => $_POST['theme_id'] ?? '',
                'status' => $_POST['status'] ?? 'planned',
                'visibility' => $_POST['visibility'] ?? 'public',
            ];
            
            $event_id = event_create($data);
            $message = 'Événement créé avec succès! ID: ' . $event_id;
            $action = 'list';
            
        } elseif (isset($_POST['update_event'])) {
            // Mettre à jour un événement
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'location' => $_POST['location'] ?? '',
                'event_date' => $_POST['event_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'capacity' => $_POST['capacity'] ?? 50,
                'category_id' => $_POST['category_id'] ?? '',
                'theme_id' => $_POST['theme_id'] ?? '',
                'status' => $_POST['status'] ?? 'planned',
                'visibility' => $_POST['visibility'] ?? 'public',
            ];
            
            event_update($id, $data);
            $message = 'Événement mis à jour avec succès!';
            $action = 'list';
            
        } elseif (isset($_POST['delete_event'])) {
            // Supprimer un événement
            event_delete($id);
            $message = 'Événement supprimé avec succès!';
            $action = 'list';
            
        } elseif (isset($_POST['register_event'])) {
            // S'inscrire à un événement
            $data = [
                'event_id' => $_POST['event_id'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
            ];
            
            $reg_id = registration_create($data);
            $message = 'Inscription confirmée! Numéro: ' . $reg_id;
            
        } elseif (isset($_POST['create_article'])) {
            // Créer un article
            $data = [
                'title' => $_POST['article_title'] ?? '',
                'content' => $_POST['article_content'] ?? '',
                'excerpt' => $_POST['article_excerpt'] ?? '',
                'author_name' => $_POST['article_author'] ?? 'Anonyme',
                'category_id' => $_POST['article_category'] ?? '',
                'theme_id' => $_POST['article_theme'] ?? '',
                'is_testimony' => isset($_POST['is_testimony']) ? 1 : 0,
                'status' => $_POST['article_status'] ?? 'draft',
            ];
            
            $article_id = article_create($data);
            $message = 'Article créé avec succès! ID: ' . $article_id;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ==================== TRAITEMENT SUPPRESSION ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_confirm'])) {
    try {
        $delete_type = $_POST['delete_type'] ?? '';
        if ($delete_type === 'event') {
            event_delete($id);
            $message = 'Événement supprimé!';
        } elseif ($delete_type === 'registration') {
            registration_delete($id);
            $message = 'Inscription supprimée!';
        } elseif ($delete_type === 'article') {
            article_delete($id);
            $message = 'Article supprimé!';
        }
        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ==================== RÉCUPÉRATION DONNÉES ====================

$categories = category_get_all();
$themes = theme_get_all();
$events = event_get_all();
$articles = article_get_all(['status' => 'published']);
?>

<!DOCTYPE HTML>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - PeaceConnect</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <style>
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .event-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: #fff;
            transition: box-shadow 0.3s;
        }
        .event-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        .event-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-right: 0.5rem;
        }
        .badge-primary { background: #e3f2fd; color: #1976d2; }
        .badge-success { background: #e8f5e9; color: #388e3c; }
        .badge-warning { background: #fff3e0; color: #f57c00; }
        
        .form-section {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        table thead {
            background: #f5f5f5;
        }
        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background: #fafafa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="index.html" class="navbar-brand">
                    <span>🕊️</span>
                    <span>PeaceConnect</span>
                </a>
                <button class="navbar-toggle" type="button">☰</button>
                <ul class="navbar-menu">
                    <li><a href="index.html">Accueil</a></li>
                    <li><a href="forum.html">Forum</a></li>
                    <li><a href="events.php" class="active">Événements</a></li>
                    <li><a href="dashboard.php" class="btn btn-outline">Admin</a></li>
                    <li><a href="profile.html">Mon Profil</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h1>📅 Événements & Contenus</h1>
                <p>Gérez vos événements, inscriptions et contenus pédagogiques</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Tabs Navigation -->
            <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #ddd;">
                <a href="?action=list" class="btn <?php echo $action === 'list' ? 'btn-primary' : 'btn-outline'; ?>" style="border-bottom: none;">
                    📋 Liste des Événements
                </a>
                <a href="?action=create" class="btn <?php echo $action === 'create' ? 'btn-primary' : 'btn-outline'; ?>" style="border-bottom: none;">
                    ➕ Créer un Événement
                </a>
                <a href="?action=articles" class="btn <?php echo $action === 'articles' ? 'btn-primary' : 'btn-outline'; ?>" style="border-bottom: none;">
                    📝 Contenus & Articles
                </a>
            </div>

            <!-- ==================== LISTE ÉVÉNEMENTS ==================== -->
            <?php if ($action === 'list'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Événements à Venir</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($events)): ?>
                            <p style="color: #999;">Aucun événement disponible pour le moment.</p>
                        <?php else: ?>
                            <div class="grid">
                                <?php foreach ($events as $event): ?>
                                    <div class="event-card">
                                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                        
                                        <div class="event-meta">
                                            <span>📍 <?php echo htmlspecialchars($event['location']); ?></span>
                                            <span>📅 <?php echo date('d/m/Y H:i', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        
                                        <p><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                                        
                                        <div style="margin-bottom: 1rem;">
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                            <span class="badge badge-warning"><?php echo htmlspecialchars($event['status']); ?></span>
                                            <span class="badge badge-success"><?php echo $event['current_registrations'] . '/' . $event['capacity']; ?></span>
                                        </div>
                                        
                                        <div class="btn-group">
                                            <a href="?action=edit&id=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">✏️ Éditer</a>
                                            <a href="?action=registrations&id=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">👥 Inscriptions</a>
                                            <button class="btn btn-outline btn-sm btn-danger" onclick="confirmDelete('event', <?php echo $event['id']; ?>)">🗑️ Supprimer</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- ==================== CRÉER ÉVÉNEMENT ==================== -->
            <?php elseif ($action === 'create'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Créer un Nouvel Événement</h2>
                    </div>
                    <form method="post" action="events.php" style="padding: 1.5rem;">
                        <div class="form-section">
                            <h3>Informations de Base</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Titre <span style="color:red;">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="Ex: Atelier de médiation">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description <span style="color:red;">*</span></label>
                                <textarea name="description" class="form-control" rows="4" required placeholder="Décrivez l'événement, ses objectifs..."></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Lieu <span style="color:red;">*</span></label>
                                <input type="text" name="location" class="form-control" required placeholder="Ex: Salle A, Centre Communautaire">
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Date & Horaire</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Date & Heure de Début <span style="color:red;">*</span></label>
                                <input type="datetime-local" name="event_date" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Date & Heure de Fin</label>
                                <input type="datetime-local" name="end_date" class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Capacité Maximale</label>
                                <input type="number" name="capacity" class="form-control" value="50" min="1">
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Catégorisation</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Catégorie <span style="color:red;">*</span></label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Sélectionner une catégorie...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Thème</label>
                                <select name="theme_id" class="form-control">
                                    <option value="">Sélectionner un thème...</option>
                                    <?php foreach ($themes as $theme): ?>
                                        <option value="<?php echo $theme['id']; ?>">
                                            <?php echo htmlspecialchars($theme['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Configuration</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-control">
                                    <option value="planned">Planifié</option>
                                    <option value="ongoing">En cours</option>
                                    <option value="completed">Terminé</option>
                                    <option value="cancelled">Annulé</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Visibilité</label>
                                <select name="visibility" class="form-control">
                                    <option value="public">Public</option>
                                    <option value="private">Privé</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="create_event" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                                ✓ Créer l'Événement
                            </button>
                            <a href="?action=list" class="btn btn-outline" style="padding: 0.75rem 2rem;">Annuler</a>
                        </div>
                    </form>
                </div>

            <!-- ==================== ÉDITER ÉVÉNEMENT ==================== -->
            <?php elseif ($action === 'edit' && $id): ?>
                <?php $event = event_get($id); ?>
                <?php if ($event): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Éditer l'Événement</h2>
                        </div>
                        <form method="post" action="events.php?id=<?php echo $id; ?>" style="padding: 1.5rem;">
                            <div class="form-section">
                                <h3>Informations de Base</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Titre <span style="color:red;">*</span></label>
                                    <input type="text" name="title" class="form-control" required 
                                           value="<?php echo htmlspecialchars($event['title']); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Description <span style="color:red;">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Lieu <span style="color:red;">*</span></label>
                                    <input type="text" name="location" class="form-control" required 
                                           value="<?php echo htmlspecialchars($event['location']); ?>">
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Date & Horaire</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Date & Heure de Début <span style="color:red;">*</span></label>
                                    <input type="datetime-local" name="event_date" class="form-control" required 
                                           value="<?php echo substr($event['event_date'], 0, 16); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Date & Heure de Fin</label>
                                    <input type="datetime-local" name="end_date" class="form-control"
                                           value="<?php echo $event['end_date'] ? substr($event['end_date'], 0, 16) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Capacité Maximale</label>
                                    <input type="number" name="capacity" class="form-control" value="<?php echo $event['capacity']; ?>" min="1">
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Catégorisation</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Catégorie <span style="color:red;">*</span></label>
                                    <select name="category_id" class="form-control" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $cat['id'] == $event['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Thème</label>
                                    <select name="theme_id" class="form-control">
                                        <option value="">Aucun</option>
                                        <?php foreach ($themes as $theme): ?>
                                            <option value="<?php echo $theme['id']; ?>" 
                                                <?php echo $theme['id'] == $event['theme_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($theme['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Configuration</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-control">
                                        <option value="planned" <?php echo $event['status'] === 'planned' ? 'selected' : ''; ?>>Planifié</option>
                                        <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                        <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Visibilité</label>
                                    <select name="visibility" class="form-control">
                                        <option value="public" <?php echo $event['visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                                        <option value="private" <?php echo $event['visibility'] === 'private' ? 'selected' : ''; ?>>Privé</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" name="update_event" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                                    ✓ Mettre à Jour
                                </button>
                                <a href="?action=list" class="btn btn-outline" style="padding: 0.75rem 2rem;">Annuler</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            <!-- ==================== ARTICLES & CONTENUS ==================== -->
            <?php elseif ($action === 'articles'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Articles & Contenus Pédagogiques</h2>
                    </div>
                    <div style="padding: 1.5rem;">
                        <a href="#create-article" class="btn btn-primary" style="margin-bottom: 1rem;">➕ Créer un Article</a>
                        
                        <h3>Articles Publiés</h3>
                        <?php if (empty($articles)): ?>
                            <p style="color: #999;">Aucun article publié pour le moment.</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Auteur</th>
                                        <th>Catégorie</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles as $article): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($article['title']); ?></td>
                                            <td><?php echo htmlspecialchars($article['author_name']); ?></td>
                                            <td><?php echo htmlspecialchars($article['category_name'] ?? '-'); ?></td>
                                            <td><?php echo $article['is_testimony'] ? '🎤 Témoignage' : '📝 Article'; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="alert('Édition article - à développer')">✏️</button>
                                                <button class="btn btn-outline btn-sm btn-danger" onclick="confirmDelete('article', <?php echo $article['id']; ?>)">🗑️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <div id="create-article" class="form-section" style="margin-top: 2rem;">
                            <h3>Créer un Nouvel Article</h3>
                            <form method="post" action="events.php">
                                <div class="form-group">
                                    <label class="form-label">Titre <span style="color:red;">*</span></label>
                                    <input type="text" name="article_title" class="form-control" required placeholder="Titre de l'article">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Contenu <span style="color:red;">*</span></label>
                                    <textarea name="article_content" class="form-control" rows="6" required placeholder="Contenu principal..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Résumé/Extrait</label>
                                    <textarea name="article_excerpt" class="form-control" rows="2" placeholder="Court résumé de l'article..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Auteur</label>
                                    <input type="text" name="article_author" class="form-control" placeholder="Votre nom ou anonyme">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Catégorie</label>
                                    <select name="article_category" class="form-control">
                                        <option value="">Aucune</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Thème</label>
                                    <select name="article_theme" class="form-control">
                                        <option value="">Aucun</option>
                                        <?php foreach ($themes as $theme): ?>
                                            <option value="<?php echo $theme['id']; ?>">
                                                <?php echo htmlspecialchars($theme['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Statut</label>
                                    <select name="article_status" class="form-control">
                                        <option value="draft">Brouillon</option>
                                        <option value="published">Publié</option>
                                    </select>
                                </div>

                                <div style="margin-bottom: 1rem;">
                                    <label>
                                        <input type="checkbox" name="is_testimony" value="1">
                                        <span>Ceci est un témoignage</span>
                                    </label>
                                </div>

                                <button type="submit" name="create_article" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                                    ✓ Créer l'Article
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            <!-- ==================== INSCRIPTIONS ==================== -->
            <?php elseif ($action === 'registrations' && $id): ?>
                <?php $event = event_get($id); $registrations = registration_get_all_for_event($id); ?>
                <?php if ($event): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Inscriptions pour: <?php echo htmlspecialchars($event['title']); ?></h2>
                        </div>
                        <div style="padding: 1.5rem;">
                            <p style="color: #666; margin-bottom: 1rem;">
                                Total: <strong><?php echo count($registrations); ?>/<?php echo $event['capacity']; ?></strong> inscriptions
                            </p>

                            <?php if (empty($registrations)): ?>
                                <p style="color: #999;">Aucune inscription pour le moment.</p>
                            <?php else: ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Date d'Inscription</th>
                                            <th>Présence Confirmée</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $reg): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                                <td><?php echo htmlspecialchars($reg['phone'] ?? '-'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($reg['registration_date'])); ?></td>
                                                <td><?php echo $reg['attendance_confirmed'] ? '✓ Oui' : '✗ Non'; ?></td>
                                                <td>
                                                    <button class="btn btn-outline btn-sm" onclick="alert('À développer')">✏️</button>
                                                    <button class="btn btn-outline btn-sm btn-danger" onclick="confirmDelete('registration', <?php echo $reg['id']; ?>)">🗑️</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>

                            <div style="margin-top: 1.5rem;">
                                <a href="?action=list" class="btn btn-outline">← Retour à la liste</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 PeaceConnect. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:2rem; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.2); min-width:400px;">
            <h3>Confirmer la suppression?</h3>
            <p>Cette action est irréversible.</p>
            <form method="post" action="events.php" style="display:flex; gap:1rem; justify-content:flex-end; margin-top:1rem;">
                <input type="hidden" id="deleteId" name="delete_id">
                <input type="hidden" id="deleteType" name="delete_type">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-outline">Annuler</button>
                <button type="submit" name="delete_confirm" class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(type, id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteType').value = type;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
