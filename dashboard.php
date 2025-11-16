<?php
/**
 * dashboard.php
 * Tableau de bord admin avec gestion des événements et contenus
 */

session_start();
require_once __DIR__ . '/Model/event_logic.php';

// Afficher les messages
$message = '';
$error = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Récupérer les données
$events = event_get_all();
$articles = article_get_all(['status' => 'published']);
$categories = category_get_all();
$themes = theme_get_all();

// Statistiques
$total_events = count($events);
$total_articles = count($articles);
$upcoming_events = count(array_filter($events, fn($e) => $e['status'] === 'planned'));
$total_registrations = array_sum(array_map(fn($e) => $e['current_registrations'], $events));
?>
?>
<!DOCTYPE HTML>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - PeaceConnect Admin</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--color-text);
            color: white;
            padding: 2rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid var(--color-secondary);
        }
        
        .main-content {
            flex: 1;
            background-color: var(--color-background);
            padding: 2rem;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: var(--color-primary);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.875rem;
        }
        
        .table-section {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background-color: var(--color-background);
        }
        
        table th {
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        table tr:hover {
            background-color: #f9f9f9;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                🕊️ PeaceConnect Admin
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Tableau de bord</a></li>
                <li><a href="dashboard.php?view=events">📅 Événements</a></li>
                <li><a href="dashboard.php?view=articles">📝 Contenus & Articles</a></li>
                <li><a href="dashboard.php?view=categories">🏷️ Catégories</a></li>
                <li><a href="dashboard.php?view=themes">🎨 Thèmes</a></li>
                <li><a href="reports-management.html">📋 Signalements</a></li>
                <li><a href="#">👥 Utilisateurs</a></li>
                <li><a href="#">⚙️ Paramètres</a></li>
                <li><a href="index.html" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 1rem; padding-top: 1rem;">← Retour au site</a></li>
            </ul>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <div class="admin-header">
                <div>
                    <h1>Tableau de bord - Événements & Contenus</h1>
                    <p style="color: var(--color-text-light);">Gestion des événements, inscriptions et contenus pédagogiques</p>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="grid grid-4" style="margin-bottom: 2rem;">
                <div class="form-section" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--color-primary);">📅</div>
                    <h3 style="margin: 0.5rem 0;"><?php echo $total_events; ?></h3>
                    <p style="color: var(--color-text-light); margin: 0;">Événements</p>
                </div>
                <div class="form-section" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: #FF9800;">👥</div>
                    <h3 style="margin: 0.5rem 0;"><?php echo $total_registrations; ?></h3>
                    <p style="color: var(--color-text-light); margin: 0;">Inscriptions</p>
                </div>
                <div class="form-section" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: #2196F3;">📝</div>
                    <h3 style="margin: 0.5rem 0;"><?php echo $total_articles; ?></h3>
                    <p style="color: var(--color-text-light); margin: 0;">Articles publiés</p>
                </div>
                <div class="form-section" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: #4CAF50;">📋</div>
                    <h3 style="margin: 0.5rem 0;"><?php echo $upcoming_events; ?></h3>
                    <p style="color: var(--color-text-light); margin: 0;">À venir</p>
                </div>
            </div>

            <!-- Accès rapide aux pages CRUD -->
            <div class="form-section" style="margin-bottom: 2rem;">
                <h2>Accès rapide</h2>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="events.php?action=create" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">➕ Créer un événement</a>
                    <a href="events.php?action=articles" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">📝 Gérer les contenus</a>
                    <a href="events.php" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">📅 Voir tous les événements</a>
                </div>
            </div>

            <!-- Événements à venir -->
            <div class="table-section">
                <h2>Événements à venir (<?php echo $upcoming_events; ?>)</h2>
                
                <?php 
                $upcoming = array_filter($events, fn($e) => $e['status'] === 'planned');
                if (!empty($upcoming)): 
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Lieu</th>
                                <th>Date</th>
                                <th>Catégorie</th>
                                <th>Inscriptions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($upcoming, 0, 5) as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['category_name']); ?></td>
                                    <td><?php echo $event['current_registrations'] . '/' . $event['capacity']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="events.php?action=edit&id=<?php echo $event['id']; ?>" class="btn btn-secondary btn-sm">Éditer</a>
                                            <a href="events.php?action=registrations&id=<?php echo $event['id']; ?>" class="btn btn-secondary btn-sm">Inscriptions</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-top: 1rem;"><a href="events.php">→ Voir tous les événements</a></p>
                <?php else: ?>
                    <p style="color: var(--color-text-light);">Aucun événement à venir. <a href="events.php?action=create">Créer un événement</a></p>
                <?php endif; ?>
            </div>

            <!-- Derniers articles -->
            <div class="table-section" style="margin-top: 2rem;">
                <h2>Derniers articles publiés (<?php echo count($articles); ?>)</h2>
                
                <?php if (!empty($articles)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Vues</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($articles, 0, 5) as $article): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($article['title']); ?></td>
                                    <td><?php echo htmlspecialchars($article['author_name']); ?></td>
                                    <td><?php echo $article['is_testimony'] ? '🎤 Témoignage' : '📝 Article'; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></td>
                                    <td><?php echo $article['views_count']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-secondary btn-sm" onclick="alert('Édition - à développer')">Éditer</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-top: 1rem;"><a href="events.php?action=articles">→ Gérer les contenus</a></p>
                <?php else: ?>
                    <p style="color: var(--color-text-light);">Aucun article publié. <a href="events.php?action=articles">Créer un article</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
