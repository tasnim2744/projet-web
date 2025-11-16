# Module 5 - Événements & Contenus 📅

## 🚀 Guide de déploiement rapide

### Étape 1: Initialiser la base de données

1. Ouvrez phpMyAdmin: `http://localhost/phpmyadmin`
2. Allez dans l'onglet "SQL"
3. Copiez le contenu de `database/events_create.sql`
4. Collez dans phpMyAdmin et exécutez

**Ou via command line** :
```bash
mysql -h localhost -u Projet2A -p123 peaceconnect < database/events_create.sql
```

### Étape 2: Vérifier l'installation

1. Visitez `http://localhost/TasnimCrud/test_events.php`
2. Tous les tests doivent être ✅ en vert
3. Si erreurs, vérifiez:
   - Identifiants BD correctes dans `Model/event_logic.php`
   - SQL exécuté sans erreurs
   - Serveur MySQL actif

### Étape 3: Accéder aux interfaces

**Front Office (Utilisateurs):**
- Accueil: `http://localhost/TasnimCrud/index.html`
- Événements: `http://localhost/TasnimCrud/events.php`

**Back Office (Admin):**
- Dashboard: `http://localhost/TasnimCrud/dashboard.php`
- Gestion complète: `http://localhost/TasnimCrud/events.php?action=create`

---

## 📊 Structure des données

### Tables créées automatiquement:

```
peaceconnect
├── events                    (Événements)
├── event_registrations       (Inscriptions)
├── event_categories          (Catégories)
├── articles                  (Contenus)
├── comments                  (Commentaires)
├── themes                    (Thèmes)
└── ai_flags                  (Détection IA)
```

Chaque table s'auto-crée à la première connexion via `get_event_pdo()`.

---

## 🎯 Cas d'usage principaux

### 1️⃣ Créer un événement

**Via UI Admin:**
- Allez à: `events.php?action=create`
- Remplissez le formulaire
- Cliquez "Créer l'Événement"

**Via code PHP:**
```php
require 'Model/event_logic.php';

$event_id = event_create([
    'title' => 'Atelier de médiation',
    'description' => 'Description...',
    'location' => 'Salle A',
    'event_date' => '2025-01-20 14:00',
    'capacity' => 50,
    'category_id' => 1,
    'status' => 'planned'
]);
```

### 2️⃣ S'inscrire à un événement

**Via UI:**
- Accédez à `events.php`
- Cliquez sur un événement
- Remplissez nom, email, téléphone
- Confirmez

**Via code:**
```php
$registration_id = registration_create([
    'event_id' => 1,
    'full_name' => 'Jean Dupont',
    'email' => 'jean@example.com'
]);
```

### 3️⃣ Publier un article/témoignage

**Via admin:**
- Allez à `events.php?action=articles`
- Créez un nouvel article
- Cochez "Ceci est un témoignage" (optionnel)
- Publiez

**Via code:**
```php
$article_id = article_create([
    'title' => 'Mon témoignage',
    'content' => 'Contenu complet...',
    'author_name' => 'Sophie',
    'is_testimony' => true,
    'status' => 'published'
]);
```

### 4️⃣ Modérer les commentaires

```php
// Valider un commentaire
comment_approve($comment_id);

// Supprimer un commentaire abusif
comment_delete($comment_id);
```

---

## 🔍 Fonctions principales

### Événements
```php
event_get_all($filters)         // Lister tous les événements
event_get($id)                  // Récupérer un événement
event_create($data)             // Créer un événement
event_update($id, $data)        // Modifier un événement
event_delete($id)               // Supprimer un événement
```

### Inscriptions
```php
registration_get_all_for_event($event_id)  // Lister les inscriptions
registration_create($data)                 // S'inscrire
registration_confirm_attendance($reg_id)   // Confirmer présence
registration_delete($id)                   // Annuler inscription
```

### Articles
```php
article_get_all($filters)       // Lister les articles
article_get($id)                // Récupérer un article
article_create($data)           // Créer un article
article_update($id, $data)      // Modifier un article
article_delete($id)             // Supprimer un article
```

### Commentaires
```php
comment_get_all_for_article($article_id)  // Lister les commentaires
comment_create($data)                     // Créer un commentaire
comment_approve($id)                      // Approuver un commentaire
comment_delete($id)                       // Supprimer un commentaire
```

### Catégories & Thèmes
```php
category_get_all()              // Lister les catégories
category_create($data)          // Créer une catégorie
theme_get_all()                 // Lister les thèmes
theme_create($data)             // Créer un thème
```

---

## 🔐 Sécurité

✅ **Déjà implémenté:**
- Protection contre injections SQL (préparation des requêtes)
- Validation des champs obligatoires
- Tronçage des chaînes
- Gestion des erreurs

⚠️ **À implémenter:**
- Authentification utilisateur
- Autorisation (admin vs utilisateur)
- Rate limiting
- Validation IA (AI_FLAGS)

---

## 🐛 Dépannage

### "Table doesn't exist"
**Solution:** Exécutez `database/events_create.sql`

### "Access denied for user"
**Solution:** Vérifiez les identifiants dans `Model/event_logic.php`

### Pas de catégories/thèmes
**Solution:** C'est normal! Ils s'insèrent automatiquement à la première connexion

### Erreur lors de l'inscription
**Solution:** Vérifiez que l'email n'est pas déjà inscrit pour cet événement

---

## 📈 Statistiques disponibles

Via le dashboard, vous verrez:
- Nombre total d'événements
- Total des inscriptions
- Nombre d'articles publiés
- Événements à venir

---

## 🌐 URLs importantes

| URL | Description |
|-----|-------------|
| `events.php` | Liste des événements (front) |
| `events.php?action=create` | Créer un événement (admin) |
| `events.php?action=edit&id=1` | Éditer un événement |
| `events.php?action=articles` | Gestion des articles |
| `events.php?action=registrations&id=1` | Voir les inscriptions |
| `dashboard.php` | Tableau de bord admin |
| `test_events.php` | Tests automatisés |

---

## 📝 Données par défaut

### Catégories automatiques:
1. 🤝 Ateliers de médiation
2. 📚 Formations
3. 📢 Campagnes de sensibilisation
4. 🎤 Conférences
5. 👥 Rencontres communautaires

### Thèmes automatiques:
1. ☮️ Paix et résolution de conflits
2. ⚖️ Justice et droits humains
3. 🌈 Inclusion et diversité
4. 🛡️ Prévention de la violence
5. 🤲 Dialogue intercommunautaire

---

## 📞 Support & Aide

**Fichiers de référence:**
- `MODULE_5_EVENTS.md` - Documentation détaillée
- `Model/event_logic.php` - Code source avec commentaires
- `events.php` - Interface web
- `test_events.php` - Exemples fonctionnels

**Questions?**
1. Vérifiez le fichier test: `test_events.php`
2. Consultez les commentaires du code
3. Vérifiez l'onglet SQL du formulaire

---

## ✅ Checklist de déploiement

- [ ] Base de données créée
- [ ] SQL exécuté sans erreurs
- [ ] `test_events.php` affiche ✅ partout
- [ ] Peut créer un événement
- [ ] Peut créer une inscription
- [ ] Peut créer un article
- [ ] Dashboard affiche les statistiques
- [ ] Navigation mise à jour vers events.php

---

**Module:** 5 - Événements & Contenus  
**Responsable:** Tasnim Chehibi  
**ODD:** 11, 16  
**Statut:** ✅ Implémenté et testable  

