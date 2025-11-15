# MonBudget v2.0 - Application de Gestion Budgétaire

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.4-blue.svg)](https://www.php.net/)
[![Tests](https://img.shields.io/badge/tests-17%20passing-brightgreen.svg)](tests/)
[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](CHANGELOG.md)

> 🎉 **Version 2.0.0** - Application de gestion budgétaire moderne avec dark mode, projections et tests unitaires

## 🚀 Fonctionnalités

### ✅ Modules Disponibles

- **🏠 Dashboard** : Vue d'ensemble financière avec statistiques
- **💳 Comptes** : Gestion multi-comptes bancaires
- **💸 Transactions** : CRUD complet avec filtres et recherche
- **📊 Catégories** : Hiérarchie personnalisable avec icônes et couleurs
- **💼 Budgets** : Création, suivi et alertes de dépassement
- **🔄 Récurrences** : Transactions récurrentes (mensuel, hebdo, quotidien)
- **📈 Projections** : Prévisions budgétaires basées sur récurrences + historique
- **📑 Rapports** : Graphiques et statistiques avec Charts.js
- **📥 Import** : Support CSV, OFX
- **👥 Tiers** : Gestion des bénéficiaires
- **🔍 Recherche** : Recherche avancée multi-critères
- **⚙️ Automatisation** : Règles de catégorisation automatique

### ⭐ Nouveautés Version 2.0

**Dark Mode** (Session 12)
- Thème sombre complet avec toggle persistant
- 730 lignes CSS avec variables personnalisées
- Synchronisation automatique des graphiques Charts.js
- Transitions fluides entre modes

**Projections Budgétaires** (Session 12)
- Algorithme sophistiqué : récurrences + tendances historiques
- Moyennes glissantes 3/6/12 mois
- Graphique interactif avec filtres
- Support compte et catégorie spécifiques

**Tests Unitaires** (Session 13)
- PHPUnit 10.5.58 avec 17 tests (100% pass)
- Base de test isolée (monbudget_test)
- Couverture Models : Categorie, Compte, Transaction

## 📋 Architecture

### Structure MVC

```
app/
├── Controllers/     # Logique métier et actions
├── Models/         # Accès données et modèles
├── Views/          # Templates HTML/PHP
├── Core/           # Router, Database, Helpers
├── Services/       # Services métier (Projection, Import, etc.)
└── Middleware/     # Auth, CSRF, Logging

config/
├── app.php         # Configuration générale
├── database.php    # Configuration BDD
└── routes.php      # Définition routes

tests/
├── Models/         # Tests modèles
├── Controllers/    # Tests contrôleurs  
└── Unit/           # Tests unitaires
```

## 🛠️ Stack Technique

- **Backend** : PHP 8.4+ (MVC natif)
- **Frontend** : Bootstrap 5.3.2, Vanilla JavaScript ES6+
- **Database** : MySQL 8.0+ (base : monbudget_v2)
- **Charts** : Charts.js 4.x avec adaptation dark mode
- **Tests** : PHPUnit 10.5.58
- **Dependencies** : Composer (vlucas/phpdotenv, monolog, firebase/jwt)

## 🚀 Installation

### Prérequis

- PHP >= 8.4.0
- MySQL >= 8.0
- Composer
- Serveur web (Apache/WAMP ou Nginx)

### Installation rapide

```bash
# Cloner le projet
git clone https://github.com/[username]/monbudget-v2.git
cd monbudget-v2

# Installer dépendances
composer install

# Configuration
cp .env.example .env
# Éditer .env avec vos paramètres DB

# Base de données
mysql -u root -p < database.sql

# Permissions (Linux/Mac)
chmod -R 775 storage/
chmod -R 775 uploads/

# Lancer l'application
# Via serveur web : http://localhost/monbudget-v2
# Via serveur intégré : php -S localhost:8000
```

### Tests

```bash
# Lancer tous les tests
vendor/bin/phpunit

# Tests avec détails
vendor/bin/phpunit --testdox

# Tests spécifiques
vendor/bin/phpunit tests/Models/CompteTest.php
```

## 📚 Documentation

- **[CHANGELOG.md](CHANGELOG.md)** : Historique des versions
- **[docs/TODO.md](docs/TODO.md)** : Roadmap et progression
- **[docs/INSTALL.md](docs/INSTALL.md)** : Guide d'installation détaillé
- **[docs/user/](docs/user/)** : Documentation utilisateur

## 🔗 Accès

- **Application** : <http://localhost/monbudgetV2>
- **Tests** : `vendor/bin/phpunit --testdox`
- **Base de données** : `monbudget_v2` (production), `monbudget_test` (tests)

---

## 📈 Progression

**Sessions complétées** : 13/13  
**Version actuelle** : 2.0.0  
**Dernière mise à jour** : 15 novembre 2025

### Historique

- **Sessions 1-11** : Infrastructure MVC, modules core
- **Session 12** : Dark mode + Projections budgétaires
- **Session 13** : Tests PHPUnit + Validation production

---

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 License

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

*MonBudget v2.0 - Gestion budgétaire moderne et élégante*  
*© 2025 - Développé avec ❤️ et partagé avec la communauté open-source*
