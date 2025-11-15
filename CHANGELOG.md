# Changelog - MonBudget v2.0

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/lang/fr/).

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
