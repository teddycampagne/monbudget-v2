# Changelog - MonBudget

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/lang/fr/).

---

## [2.2.0] - 16 novembre 2025 (branche develop)

### 🎯 Version mineure - Automation & Database Optimization

#### ✨ Ajouté

**Session 17.5 - Système d'exécution automatique des récurrences**
- Service RecurrenceService (445 lignes)
  - Exécution automatique au login pour tous les utilisateurs
  - Protection anti-doublons robuste (recurrence_id + date_transaction)
  - Gestion intelligente des weekends (3 modes : aucune, jour_ouvré_suivant, jour_ouvré_précédent)
  - Calcul automatique prochaine exécution (quotidien, hebdomadaire, mensuel, trimestriel, semestriel, annuel)
  - Désactivation automatique si limite atteinte ou date_fin dépassée
  - Logs mensuels détaillés (storage/logs/recurrence_auto_YYYY-MM.log)
  - Statistiques complètes (vérifiées, exécutées, ignorées, erreurs)
- Script CLI execute_recurrences.php (120 lignes)
  - Affichage Unicode avec box drawing
  - Support cron pour automatisation serveur
  - Sortie formatée avec statistiques
- Migration BDD recurrence_id
  - Colonne recurrence_id dans transactions (FK vers recurrences.id)
  - Index idx_recurrence_id pour performances
  - Contrainte ON DELETE SET NULL (préserve transactions si récurrence supprimée)
- Hook AuthController::login()
  - Exécution automatique silencieuse
  - Message flash si transactions générées
- Documentation complète RECURRENCES-AUTO.md (363 lignes)
  - Guide d'utilisation
  - Architecture détaillée
  - Algorithme anti-doublons
  - Scénarios de test

**Session 17.5 Part 3 - Dashboard admin récurrences**
- Page d'administration complète /recurrences/admin
  - Statistiques globales (5 métriques) : total, actives, inactives, échues, transactions générées
  - Dernière exécution auto : timestamp, récurrences vérifiées/exécutées/ignorées, erreurs
  - Prochaines exécutions (7 jours) avec badges colorés (aujourd'hui=rouge, demain=jaune)
  - Top 10 récurrences : classement par nombre de transactions avec icônes trophées
  - Logs récents : affichage terminal-style des 5 dernières lignes
- Modèle Recurrence : 7 nouvelles méthodes statistiques
  - countTotal(), countActives(), countInactives()
  - countEchues(), countTransactionsGenerees()
  - getUpcoming($days), getTopByTransactions($limit)
- Interface responsive Bootstrap 5
  - Grille 2 colonnes (col-lg-6)
  - Cards avec icônes et couleurs
  - Tables hover avec détails complets
  - Badges contextuels (success/warning/danger)
- Navigation intégrée
  - Lien menu "Récurrences" dans header
  - Bouton "Administration" dans liste récurrences

**Session 17 - Tags personnalisés (Phase 4)**
- Système complet de tags multi-transactions
  - Tables tags + transaction_tags (many-to-many)
  - Model Tag (368 lignes) : CRUD, validation, search, stats, colors
  - TagController (342 lignes) : 10 méthodes + 3 API
  - 7 couleurs Bootstrap natives avec labels français
- Frontend JavaScript tag-selector.js (400+ lignes)
  - Autocomplete avec debounce 300ms
  - Multi-select avec badges colorés
  - Quick-create modal AJAX
  - Component réutilisable
- Intégration complète
  - Formulaires transactions (create/edit)
  - Liste transactions avec badges colorés
  - Dashboard : Widget nuage de tags top 10
  - Rapports : Analyse par tags avec totaux
  - Recherche avancée : Filtre multi-tags
  - Page détails tag : Transactions associées + stats

#### 🗑️ Supprimé

**Session 17.5 Part 2 - Optimisation BDD**
- 14 champs obsolètes dans table transactions
  - est_recurrente, frequence, intervalle, jour_execution, jour_semaine
  - date_debut, date_fin, prochaine_execution, derniere_execution
  - nb_executions, nb_executions_max, auto_validation
  - tolerance_weekend, recurrence_active
  - Raison : Récurrences maintenant dans table séparée `recurrences`
- Table beneficiaires (0 lignes, duplicate avec tiers)
- Migration 2025_11_16_cleanup_obsolete_fields.sql
- Fichiers obsolètes projet
  - database.sql.backup (57KB)
  - pwa-diagnostic.html (15KB)
  - docs/SESSION-15-RECAP.md
  - docs/SESSION-17.5-RECURRENCES-AUTO.md (doublon)

#### 🐛 Corrigé

**Bugs Session 17.5**
- RecurrenceService : Requête sur table transactions au lieu de recurrences
- Nom champs : actif → recurrence_active (monbudget_v2)
- Champs manquants : Restauration user_id, tiers_id, moyen_paiement, etc.
- Fréquences enum : mensuelle → mensuel (sans 'e')
- Admin dashboard : c.solde → c.solde_actuel (nom colonne correct)
- Admin dashboard : Fond/texte logs illisibles (bg-dark + text-dark)

**Bugs Session 17**
- TagController : Ajout use MonBudget\Core\Session
- Views tags : Ajout header/footer layouts
- Tag colors : Affichage labels français au lieu de hex
- syncTags() : Fix "no active transaction"
- Dashboard : Correction lien tags 404 → /tags/{id}
- Index tags : Correction lien usage count → /tags/{id}
- TagController show() : Ajout compte_id dans requête SQL

#### 📊 Statistiques

**Commits Session 17.5** :
- faafc0d : feat(recurrence): Système exécution auto avec anti-doublons
- 3e43ec0 : refactor(database): Nettoyage champs obsolètes + regeneration
- 8bfc29e : feat(recurrence): Page admin avec statistiques et monitoring
- 5fdab6c : chore: Nettoyage fichiers obsolètes

**Lignes de code Session 17.5** :
- RecurrenceService.php : 445 lignes
- execute_recurrences.php : 120 lignes
- RecurrenceController : +85 lignes (admin)
- Recurrence model : +115 lignes (7 méthodes stats)
- admin.php view : 375 lignes
- RECURRENCES-AUTO.md : 363 lignes
- Total : ~1,500+ lignes

**Commits Session 17** :
- 22 fichiers, +2,710 lignes

---

## [2.1.0] - 15 novembre 2025

### 🎯 Version mineure - UX Improvements & Attachments

#### ✨ Ajouté

**Session 15 - Pièces jointes transactions (Phase 2)**
- Système complet de gestion de pièces jointes
  - Upload drag & drop multi-fichiers (max 5 Mo par fichier)
  - Support PDF, images (JPG, PNG, GIF, WebP), Excel, Word, TXT, CSV
  - Stockage organisé : `uploads/attachments/{user_id}/{year}/{month}/`
  - Validation MIME réelle (sécurité contre spoofing)
  - Preview images avec lightbox Bootstrap
  - Téléchargement sécurisé avec ownership check
  - Suppression avec confirmation
  - Badge compteur dans liste transactions (icône trombone)
  - Component réutilisable `attachment-uploader.php`
- Sécurité fichiers
  - .htaccess auto-généré (bloque PHP, scripts)
  - Sanitization noms de fichiers
  - Whitelist extensions + types MIME
  - Génération noms uniques (hash 32 chars)
- API endpoints sécurisés
  - POST `/comptes/{id}/transactions/{id}/attachments/upload`
  - DELETE `/comptes/{id}/transactions/{id}/attachments/{id}`
  - GET `/comptes/{id}/transactions/{id}/attachments/{id}/download`
- JavaScript moderne
  - Classe AttachmentUploader (OOP)
  - Progress bars upload
  - Toasts Bootstrap pour feedback
  - Gestion erreurs AJAX

**Session 14 - Améliorations UX/UI (Phase 1)**
- Breadcrumbs de navigation globaux avec fil d'Ariane
  - Affichage hiérarchique (Banque → Compte → Transaction)
  - Navigation drill-down facilitée
  - Indicateur visuel de la position dans l'arborescence
- Création rapide depuis les listes avec modal
  - Catégories : Ajout rapide sans quitter la page
  - Tiers : Création inline dans les formulaires
  - Soumission AJAX avec actualisation automatique
- Gestion des sous-catégories améliorée
  - Navigation basée sur sessions (retour intelligent)
  - Formulaires dédiés création/édition
  - Liste avec drill-down par catégorie parente
- Bouton de duplication de transaction
  - Pré-remplissage automatique du formulaire
  - Date réinitialisée à aujourd'hui
  - Conversion en transaction simple (est_recurrente = 0)
  - Icône bi-files dans les listes de transactions
- Date picker avec raccourcis intelligents
  - Raccourcis date : Aujourd'hui, Hier, Il y a 7j, Début/Fin mois
  - Raccourcis période : Mois actuel, Mois dernier, Année actuelle, Année dernière
  - Auto-initialisation via attribut data-shortcuts
  - Composant JavaScript réutilisable (date-picker-shortcuts.js)
  - Appliqué aux transactions, recherche, récurrences, et rapports

#### 🐛 Corrigé

**Bugs Session 14**
- Rapports : Filtrage par compte ignoré dans les APIs
  - apiRepartitionCategories : Ajout filtrage compte_id + vérification propriété
  - apiDetailCategorie : Ajout filtrage compte_id + vérification propriété
  - apiBalances : Ajout filtrage compte_id + vérification propriété
  - apiTendanceEpargne : Ajout filtrage compte_id + vérification propriété
  - apiBudgetaire : Ajout compte_id dans réquisitions + vérification propriété
  - Avant : Affichait données de TOUS les comptes de l'utilisateur
  - Après : Filtrage correct par compte sélectionné
- Transactions : Bouton Annuler avec route 404
  - Correction redirection vers comptes/{id}/transactions
- Rapports : Fonction JavaScript chargerSuiviBudgetaire inexistante
  - Renommage vers chargerBudgetaire (nom correct)

#### 🔧 Modifié

**Architecture**
- `index.php` : Route GET /comptes/{id}/transactions/{id}/duplicate
- `app/Controllers/TransactionController.php` : Nouvelle méthode duplicate()
- `app/Views/transactions/create.php` : Support pré-remplissage + isDuplicate flag
- `app/Views/transactions/index.php` : Bouton dupliquer + correction annuler
- `app/Views/recherche/index.php` : Bouton dupliquer dans résultats recherche
- `assets/js/date-picker-shortcuts.js` : Nouveau composant (230 lignes)
- `app/Views/layouts/footer.php` : Chargement date-picker-shortcuts.js

**Rapports sécurisés**
- Vérification propriété compte avant filtrage (protection 403)
- Tous les graphiques se mettent à jour au changement de compte
- Cache navigateur/serveur nécessaire pour affichage correct

#### 📊 Statistiques

**Commits Session 14** : 6 commits
- `57fe677` : feat: Todo #5 - Bouton dupliquer transaction
- `cfeeb16` : fix: Correction route bouton Annuler
- `c99969f` : feat: Todo #6 - Date picker avec raccourcis
- `f9d4b5a` : feat: Raccourcis mois/année rapports
- `ceaab14` : chore: Retrait logs debug (confirmation fonctionnement)
- `e4dd350` : fix: Correction bug filtrage compte dans rapports
- `d4afdc3` : fix: Correction complète + nettoyage debug

**Lignes de code** :
- JavaScript : ~230 lignes (date-picker-shortcuts.js)
- PHP : ~150 lignes (TransactionController::duplicate + corrections)
- Vues : ~80 lignes (modifications formulaires)

---

## [2.0.0] - 15 novembre 2025

### 🎉 Version majeure - Session 13 complétée

#### ✨ Ajouté

**Session 12 - Dark Mode & Projections**
- Dark mode complet avec toggle persistant (localStorage)
- CSS personnalisé (730 lignes) avec variables CSS pour thème clair/sombre
- Adaptation de tous les composants Bootstrap (cards, tables, forms, modals, etc.)
- Synchronisation Charts.js avec dark mode via CustomEvent
- Module Projections budgétaires avec algorithme sophistiqué
  - Calcul basé sur récurrences actives + tendances historiques
  - Moyennes glissantes sur 3/6/12 mois
  - Interface avec graphique interactif
  - Support des filtres par compte et catégorie
- Refactoring de 4 vues avec helpers UI (-150 lignes de code)

**Session 13 - Tests & Validation**
- Infrastructure PHPUnit 10.5.58 complète
  - 3 testsuites (Unit, Controllers, Models)
  - TestCase de base avec helpers de création de données
  - Configuration .env.testing avec base monbudget_test
- 17 tests unitaires (5 Categorie, 5 Compte, 4 Transaction, 3 Example)
- Méthodes modèles ajoutées :
  - `Database::lastInsertId()` pour récupération d'ID
  - `Compte::getById()` pour lecture d'un compte
  - `Categorie::getById()` pour lecture d'une catégorie
  - `Transaction::getById()` pour lecture d'une transaction
- Validation dark mode sur toutes les pages
- Validation projections avec 4 récurrences mensuelles réelles

#### 🔧 Modifié

**Session 12**
- `app/Views/layouts/header.php` : Ajout toggle dark mode + lien CSS
- `app/Views/layouts/footer.php` : Script de gestion du thème (165 lignes JS)
- `app/Views/comptes/index.php` : Refactoring avec UI helpers
- `app/Views/comptes/create.php` : Refactoring avec UI helpers
- `app/Views/tiers/index.php` : Refactoring avec UI helpers
- `app/Views/tiers/create.php` : Refactoring avec UI helpers

**Session 13**
- `phpunit.xml` : Configuration complète avec 3 testsuites
- `.env.testing` : Environnement de test isolé
- `tests/TestCase.php` : Helpers createTestUser(), createTestCompte(), etc.
- Correction assertions dans tests (assertEquals vs assertTrue)
- Isolation emails dans tests (microtime unique)
- Ajout user_id dans création de transactions pour contraintes FK

#### 🐛 Corrigé

**Bugs Dark Mode (Session 12)**
- Navbar : Fond blanc + texte blanc en dark mode → Résolu avec `.navbar-dark` override
- Cards headers : Restaient blancs en dark mode → Résolu avec `.card-header` background
- Graphiques Charts.js : Ne changeaient pas avec le toggle → Résolu avec CustomEvent `themeChanged`
- Tables : Lignes alternées illisibles → Résolu avec `.table-striped` override

**Bugs Tests (Session 13)**
- Namespace tests : Tests\ → MonBudget\Tests\ pour autoload
- Database::lastInsertId() manquant → Ajouté dans app/Core/Database.php
- Categorie::getById() manquant → Ajouté avec SELECT simple
- Compte::getById() manquant → Ajouté avec LEFT JOIN banques
- Transaction::getById() manquant → Ajouté avec SELECT simple
- CompteTest assertion incorrecte : solde_actuel → solde_initial
- Emails hardcodés dans tests → Auto-générés avec microtime
- Foreign key violations : user_id manquant dans transactions → Ajouté dans helpers

#### 📊 Statistiques

**Lignes de code ajoutées (Sessions 12-13)** :
- CSS : ~730 lignes (dark-mode.css)
- JavaScript : ~326 lignes (footer.php inline + dark-mode-charts.js)
- PHP Models : ~600 lignes (Projection.php)
- PHP Tests : ~660 lignes (5 fichiers de tests)
- **Total : ~2 300 lignes**

**Tests** :
- 17/17 tests passent (100%)
- Couverture : Models (Categorie, Compte, Transaction)

**Données réelles** :
- 28 transactions (3-12 novembre 2025)
- 4 récurrences mensuelles actives
- Projections testées sur 3/6/12 mois

---

## [1.9.0] - Sessions 1-11 (historique)

### Sessions précédentes
- Infrastructure MVC native
- Modules Comptes, Transactions, Catégories, Budgets
- Gestion des récurrences avec table dédiée
- Import/Export CSV
- API REST
- Authentication & Authorization
- Rapports et graphiques

---

## 🔮 Roadmap

### Session 14 (à venir)
- Amélioration projections (saisonnalité, ML basique)
- Tests d'intégration (parcours utilisateur complets)
- Optimisation performances (cache, indexes)
- Documentation API (Swagger/OpenAPI)

### Version 2.1.0 (Q1 2026)
- PWA (Progressive Web App)
- Mode hors-ligne
- Notifications push
- Export PDF rapports
- Widgets dashboard personnalisables

---

## 📝 Notes de version

### Version 2.0.0 - Détails

**Améliorations majeures** :
1. **Dark Mode** : Expérience utilisateur moderne avec thème sombre complet
2. **Projections** : Anticipation budgétaire basée sur données réelles
3. **Tests** : Qualité code assurée avec 17 tests unitaires
4. **Refactoring** : Code plus maintenable avec helpers UI

**Compatibilité** :
- PHP : 8.4+ (testé sur PHP 8.4.0)
- MySQL : 8.0+
- Navigateurs : Chrome 120+, Firefox 120+, Edge 120+

**Breaking changes** : Aucun (compatibilité totale avec v1.9)

**Migration depuis v1.9** : Aucune action requise, compatible base de données existante

---

## 🙏 Contributeurs

**Session 12** :
- Dark mode CSS/JS (730 + 165 lignes)
- Module Projections (600 lignes)
- Refactoring vues (économie 150 lignes)

**Session 13** :
- Infrastructure PHPUnit (configuration + helpers)
- 17 tests unitaires (660 lignes)
- Correction 8 bugs tests + 4 bugs dark mode

---

*Pour plus de détails, consultez les fichiers de documentation dans `/docs/`*
