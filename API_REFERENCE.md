<?php
/**
 * API_REFERENCE.md - Référence complète des fonctions
 * 
 * Ce fichier documente toutes les fonctions disponibles dans event_logic.php
 */
?>

# 📚 Référence API - Module 5 Événements

## 📖 Table des matières

1. [Événements](#événements)
2. [Inscriptions](#inscriptions)
3. [Articles](#articles)
4. [Commentaires](#commentaires)
5. [Catégories](#catégories)
6. [Thèmes](#thèmes)
7. [Exemples](#exemples)

---

## 🎯 Événements

### `event_get_all($filters = [])`

Récupère tous les événements avec filtres optionnels.

**Paramètres:**
```php
$filters = [
    'status' => 'planned|ongoing|completed|cancelled',
    'category_id' => 1,
    'visibility' => 'public|private'
];
```

**Retour:** Array d'objets événements

**Exemple:**
```php
$events = event_get_all(['status' => 'planned']);
foreach ($events as $event) {
    echo $event['title'] . " - " . $event['event_date'];
}
```

### `event_get($id)`

Récupère un événement spécifique avec ses détails.

**Paramètres:** `$id` (int)

**Retour:** Objet événement ou null

**Exemple:**
```php
$event = event_get(1);
if ($event) {
    echo $event['title'];
    echo $event['category_name'];
}
```

### `event_create(array $data)`

Crée un nouvel événement.

**Champs obligatoires:**
```php
$data = [
    'title' => 'Atelier de médiation',           // (100 caractères max)
    'description' => 'Description complète',    // TEXT
    'location' => 'Salle A',                    // (255 caractères)
    'event_date' => '2025-01-20 14:00:00',      // DateTime
    'category_id' => 1                          // (int)
];
```

**Champs optionnels:**
```php
[
    'end_date' => '2025-01-20 17:00:00',        // DateTime
    'capacity' => 50,                           // Défaut: 50
    'theme_id' => 1,                            // int ou null
    'organizer_id' => null,                     // int ou null
    'status' => 'planned',                      // Défaut: 'planned'
    'visibility' => 'public',                   // Défaut: 'public'
    'image_url' => 'http://...'                 // 255 caractères max
]
```

**Retour:** ID du nouvel événement (int)

**Exemple:**
```php
$event_id = event_create([
    'title' => 'Atelier de médiation',
    'description' => 'Session pratique...',
    'location' => 'Centre communautaire',
    'event_date' => date('Y-m-d H:i', strtotime('+7 days 14:00')),
    'capacity' => 30,
    'category_id' => 1,
    'theme_id' => 1
]);
echo "Événement créé: $event_id";
```

### `event_update($id, array $data)`

Met à jour un événement existant.

**Paramètres:**
- `$id` (int) - ID de l'événement
- `$data` (array) - Mêmes champs que event_create()

**Retour:** bool (true = succès)

**Exemple:**
```php
$success = event_update(1, [
    'title' => 'Atelier de médiation (ANNULÉ)',
    'status' => 'cancelled'
]);
```

### `event_delete($id)`

Supprime un événement (cascade: inscriptions aussi supprimées).

**Paramètres:** `$id` (int)

**Retour:** bool

**Exemple:**
```php
event_delete(1);
```

---

## 👥 Inscriptions

### `registration_get_all_for_event($event_id)`

Récupère toutes les inscriptions pour un événement.

**Paramètres:** `$event_id` (int)

**Retour:** Array d'inscriptions

**Exemple:**
```php
$registrations = registration_get_all_for_event(1);
echo count($registrations) . " inscriptions";
```

### `registration_create(array $data)`

Crée une nouvelle inscription à un événement.

**Champs obligatoires:**
```php
$data = [
    'event_id' => 1,              // (int)
    'full_name' => 'Jean Dupont', // (100 caractères max)
    'email' => 'jean@example.com' // (100 caractères max)
];
```

**Champs optionnels:**
```php
[
    'phone' => '+33612345678',    // (20 caractères max)
    'user_id' => null,            // int ou null
    'status' => 'registered'      // Défaut: 'registered'
]
```

**Retour:** ID de l'inscription (int)

**Validation:** UNIQUE(event_id, email) - Une seule inscription par email par événement

**Exemple:**
```php
try {
    $reg_id = registration_create([
        'event_id' => 1,
        'full_name' => 'Sophie Martin',
        'email' => 'sophie@example.com',
        'phone' => '+33687654321'
    ]);
    echo "Inscription confirmée: $reg_id";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### `registration_confirm_attendance($registration_id)`

Confirme la présence d'une personne à l'événement.

**Paramètres:** `$registration_id` (int)

**Retour:** bool

**Exemple:**
```php
registration_confirm_attendance(5);
// Met à jour: attendance_confirmed = TRUE, attendance_date = NOW()
```

### `registration_delete($id)`

Annule une inscription.

**Paramètres:** `$id` (int)

**Retour:** bool

**Exemple:**
```php
registration_delete(5);
```

---

## 📝 Articles

### `article_get_all($filters = [])`

Récupère tous les articles avec filtres optionnels.

**Filtres disponibles:**
```php
$filters = [
    'status' => 'draft|published',
    'is_testimony' => true|false
];
```

**Retour:** Array d'articles

**Exemple:**
```php
$articles = article_get_all(['status' => 'published']);
$testimonies = article_get_all(['is_testimony' => true]);
```

### `article_get($id)`

Récupère un article spécifique.

**Paramètres:** `$id` (int)

**Retour:** Objet article ou null

**Exemple:**
```php
$article = article_get(1);
echo $article['title'];
echo $article['content'];
```

### `article_create(array $data)`

Crée un nouvel article ou témoignage.

**Champs obligatoires:**
```php
$data = [
    'title' => 'Titre de l\'article',    // (255 caractères max)
    'content' => 'Contenu complet...'    // TEXT
];
```

**Champs optionnels:**
```php
[
    'excerpt' => 'Résumé court',         // (500 caractères max)
    'author_id' => null,                 // int ou null
    'author_name' => 'Anonyme',          // Défaut: 'Anonyme'
    'category_id' => null,               // int ou null
    'theme_id' => null,                  // int ou null
    'featured_image' => 'http://...',    // URL image (255 max)
    'status' => 'draft',                 // 'draft' ou 'published'
    'is_testimony' => false,             // bool - Défaut: false
    'requires_validation' => true        // bool - Défaut: true
]
```

**Retour:** ID du nouvel article (int)

**Slug:** Généré automatiquement à partir du titre

**Exemple:**
```php
$article_id = article_create([
    'title' => 'Mon témoignage: Comment j\'ai trouvé la paix',
    'content' => 'Après 5 ans de conflit, j\'ai découvert...',
    'excerpt' => 'Mon parcours vers la paix et l\'inclusion',
    'author_name' => 'Sophie Martin',
    'is_testimony' => true,
    'status' => 'published',
    'category_id' => 1,
    'theme_id' => 1
]);
```

### `article_update($id, array $data)`

Met à jour un article.

**Paramètres:**
- `$id` (int)
- `$data` (array) - Mêmes champs que article_create()

**Retour:** bool

### `article_delete($id)`

Supprime un article (cascade: commentaires aussi supprimés).

**Paramètres:** `$id` (int)

**Retour:** bool

---

## 💬 Commentaires

### `comment_get_all_for_article($article_id)`

Récupère les commentaires approuvés d'un article.

**Paramètres:** `$article_id` (int)

**Retour:** Array de commentaires

**Exemple:**
```php
$comments = comment_get_all_for_article(5);
foreach ($comments as $comment) {
    echo $comment['author_name'] . ": " . $comment['content'];
}
```

### `comment_create(array $data)`

Crée un nouveau commentaire (en attente de modération par défaut).

**Champs obligatoires:**
```php
$data = [
    'article_id' => 1,                    // (int)
    'author_name' => 'Jean Lectur',       // (100 caractères max)
    'content' => 'Commentaire...'         // TEXT
];
```

**Champs optionnels:**
```php
[
    'user_id' => null                     // int ou null
]
```

**Retour:** ID du commentaire (int)

**Exemple:**
```php
$comment_id = comment_create([
    'article_id' => 1,
    'author_name' => 'Alice',
    'content' => 'Merci pour ce témoignage inspirant!'
]);
```

### `comment_approve($id)`

Approuve un commentaire pour qu'il soit visible.

**Paramètres:** `$id` (int)

**Retour:** bool

**Exemple:**
```php
comment_approve(10);
// Met à jour: status = 'approved'
```

### `comment_delete($id)`

Supprime un commentaire.

**Paramètres:** `$id` (int)

**Retour:** bool

**Exemple:**
```php
comment_delete(10);
```

---

## 🏷️ Catégories

### `category_get_all()`

Récupère toutes les catégories d'événements.

**Retour:** Array de catégories

**Exemple:**
```php
$categories = category_get_all();
// Retourne les 5 catégories par défaut
```

### `category_create(array $data)`

Crée une nouvelle catégorie.

**Champs:**
```php
$data = [
    'name' => 'Ma catégorie',      // (100 caractères max, UNIQUE)
    'description' => 'Description',
    'icon' => '🎤',                 // Emoji ou icône
    'color' => '#FF9800'            // Couleur hex
];
```

**Retour:** ID de la catégorie (int)

**Exemple:**
```php
$cat_id = category_create([
    'name' => 'Débats',
    'description' => 'Débats et discussions',
    'icon' => '💬',
    'color' => '#FF5722'
]);
```

---

## 🎨 Thèmes

### `theme_get_all()`

Récupère tous les thèmes.

**Retour:** Array de thèmes

### `theme_create(array $data)`

Crée un nouveau thème.

**Champs:**
```php
$data = [
    'name' => 'Mon thème',         // (100 caractères max, UNIQUE)
    'description' => 'Description',
    'icon' => '🌈'                 // Emoji ou icône
];
```

**Retour:** ID du thème (int)

---

## 💡 Utilitaires

### `generate_slug($title)`

Génère un slug à partir d'un titre.

**Paramètres:** `$title` (string)

**Retour:** slug (string, max 255)

**Exemple:**
```php
$slug = generate_slug('Mon Article Sympa!');
// Retourne: "mon-article-sympa"
```

### `get_event_pdo()`

Récupère l'instance PDO (connexion BD).

**Retour:** PDO instance

**Exemple:**
```php
$pdo = get_event_pdo();
$result = $pdo->query("SELECT COUNT(*) FROM events");
```

---

## 📋 Exemples complets

### Exemple 1: Créer un événement complet

```php
<?php
require 'Model/event_logic.php';

try {
    // 1. Créer l'événement
    $event_id = event_create([
        'title' => 'Atelier de médiation - Janvier',
        'description' => 'Apprenez les techniques de médiation pour résoudre les conflits de manière constructive.',
        'location' => 'Centre culturel - Salle A',
        'event_date' => '2025-01-25 14:00:00',
        'end_date' => '2025-01-25 17:30:00',
        'capacity' => 40,
        'category_id' => 1,  // Ateliers de médiation
        'theme_id' => 1,     // Paix et résolution de conflits
        'status' => 'planned',
        'visibility' => 'public'
    ]);
    
    // 2. Afficher confirmation
    echo "Événement créé avec succès! ID: $event_id\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
```

### Exemple 2: Inscrire des participants

```php
<?php
require 'Model/event_logic.php';

$event_id = 1;
$participants = [
    ['Sophie Martin', 'sophie@example.com', '+33612345678'],
    ['Jean Dupont', 'jean@example.com', '+33687654321'],
    ['Alice Durand', 'alice@example.com', null]
];

foreach ($participants as [$name, $email, $phone]) {
    try {
        $reg_id = registration_create([
            'event_id' => $event_id,
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone
        ]);
        echo "$name inscrit - ID: $reg_id\n";
    } catch (Exception $e) {
        echo "Erreur pour $name: " . $e->getMessage() . "\n";
    }
}
?>
```

### Exemple 3: Publier un témoignage

```php
<?php
require 'Model/event_logic.php';

$article_id = article_create([
    'title' => 'Comment la médiation m\'a sauvé ma vie',
    'content' => 'Avant, j\'étais pris dans un cycle de conflits. Grâce au programme, j\'ai appris...',
    'excerpt' => 'Un témoignage puissant de transformation personnelle',
    'author_name' => 'Baptiste Leclerc',
    'category_id' => 3,
    'theme_id' => 1,
    'is_testimony' => true,
    'status' => 'published',
    'requires_validation' => false
]);

echo "Témoignage publié: $article_id\n";
?>
```

### Exemple 4: Modérer les commentaires

```php
<?php
require 'Model/event_logic.php';

// Récupérer les commentaires en attente
$article_id = 1;
$comments = $pdo->query(
    "SELECT * FROM comments WHERE article_id = ? AND status = 'pending'"
)->fetchAll();

foreach ($comments as $comment) {
    // Vérifier avec IA si le contenu est approprié
    if (isContentAppropriate($comment['content'])) {
        comment_approve($comment['id']);
    } else {
        comment_delete($comment['id']);
    }
}
?>
```

---

## 🔍 Validations

### Champs obligatoires

**Événements:** title, description, location, event_date, category_id

**Inscriptions:** event_id, full_name, email

**Articles:** title, content

**Commentaires:** article_id, author_name, content

### Limitations

| Champ | Limite | Type |
|-------|--------|------|
| title (event) | 200 | VARCHAR |
| title (article) | 255 | VARCHAR |
| description | Illimité | TEXT |
| email | 100 | VARCHAR |
| capacity | ≥ 1 | INT |
| slug | 255 | VARCHAR |

---

## 🚨 Gestion des erreurs

Toutes les fonctions lancent des **InvalidArgumentException** en cas d'erreur.

```php
try {
    $event_id = event_create($data);
} catch (InvalidArgumentException $e) {
    // Champ obligatoire manquant
    echo "Erreur validation: " . $e->getMessage();
} catch (PDOException $e) {
    // Erreur BD
    echo "Erreur BD: " . $e->getMessage();
} catch (Exception $e) {
    // Autre erreur
    echo "Erreur: " . $e->getMessage();
}
```

---

## 📞 Support

Pour plus d'informations:
- Consultez `MODULE_5_EVENTS.md`
- Testez avec `test_events.php`
- Parcourez le code commenté de `Model/event_logic.php`

