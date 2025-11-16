# Release Notes - v2.0.0

## 🎉 MonBudget v2.0 - Refonte MVC Complète

**Date de release** : 16 novembre 2025  
**Tag GitHub** : `v2.0.0`  
**Branche** : `main`

---

## 📋 Vue d'ensemble

MonBudget v2.0 marque une refonte complète de l'application avec une architecture MVC moderne, une amélioration significative de l'expérience utilisateur et une base solide pour les futures évolutions.

Cette version stable représente le point de départ de la nouvelle stratégie de versioning sémantique du projet.

---

## ✨ Fonctionnalités principales

### 🏗️ Architecture & Qualité

- **Architecture MVC complète** avec routeur, controllers, models, services
- **Autoloading PSR-4** via Composer
- **Template engine Twig** pour les vues
- **Gestion d'erreurs centralisée** avec logs structurés
- **Tests unitaires PHPUnit** (17/17 tests passent ✅)
- **Analyse statique PHPStan** niveau 5
- **Standards PSR-12** avec PHP CodeSniffer
- **Base de données migrée** vers structure normalisée

### 📊 Modules fonctionnels

- **Dashboard moderne** avec widgets interactifs
- **Gestion des comptes bancaires** (CRUD complet)
- **Gestion des banques** avec logos et informations RIB
- **Transactions** avec import/export CSV
- **Catégories et sous-catégories** hiérarchiques
- **Budgets mensuels** avec suivi en temps réel
- **Rapports financiers** avec graphiques Chart.js
- **Projections financières** sur 12 mois
- **Transactions récurrentes** automatisées
- **Règles de catégorisation** intelligentes
- **Profil utilisateur** avec paramètres personnalisables

### 🎨 UX Improvements (Phase 1 - v2.1.0-dev)

- **Breadcrumbs de navigation** sur toutes les pages
- **Création rapide** (modals pour catégories/tiers)
- **Drill-down Banque → Comptes** depuis liste banques
- **Gestion sous-catégories** avec sélecteur parent
- **Duplication de transaction** en un clic
- **Date picker avec raccourcis** (Aujourd'hui, Semaine, Mois, Trimestre, Année)

### 🔒 Sécurité

- **Authentification sécurisée** avec sessions PHP
- **Protection CSRF** sur tous les formulaires
- **Validation des données** côté serveur
- **Sanitization** des entrées utilisateur
- **Séparation des environnements** (.env)
- **Audit de sécurité** complet effectué (0 données sensibles exposées)

---

## 📊 Statistiques

- **~15 000 lignes de code PHP** réécrites
- **150+ fichiers** organisés en structure MVC
- **17 tests unitaires** (100% de succès)
- **8 Composer packages** intégrés
- **10 commits** Session 14 (v2.0.0 + préparation v2.1.0)
- **460+ lignes de code** ajoutées en Session 14

---

## 🚀 Installation

### Prérequis

- PHP >= 8.3
- MySQL >= 8.0
- Composer >= 2.0
- Apache/Nginx avec mod_rewrite

### Installation depuis GitHub

```bash
# Cloner le repository
git clone https://github.com/teddycampagne/monbudget-v2.git
cd monbudget-v2

# Checkout de la version stable
git checkout v2.0.0

# Installation des dépendances
composer install --no-dev

# Configuration
cp .env.example .env
# Éditer .env avec vos paramètres MySQL

# Import de la base de données
mysql -u root -p < database.sql

# Permissions
chmod 775 storage/logs storage/cache storage/sessions
chmod 775 uploads/imports uploads/logos
```

### Premier lancement

1. Accéder à `http://localhost/monbudget-v2/`
2. Se connecter avec vos identifiants
3. Vérifier la configuration dans Paramètres

---

## 🔄 Migration depuis v1.x

MonBudget v2.0 est **compatible** avec les données de la version v1.x.

### Procédure de migration

1. **Sauvegarde** de votre base de données v1.x
2. **Installation** de v2.0 dans un nouveau répertoire
3. **Import** de vos données via la migration automatique
4. **Vérification** des comptes, transactions, catégories
5. **Test** de toutes les fonctionnalités critiques

> ⚠️ **Important** : Conservez votre installation v1.x jusqu'à validation complète de la migration.

---

## 🐛 Bugs corrigés (Session 14)

### Critique

- **Rapports** : Filtrage par compte ignoré dans les APIs
  - Tous les rapports affichaient les mêmes données quel que soit le compte sélectionné
  - APIs corrigées : `apiRepartitionCategories`, `apiDetailCategorie`, `apiBalances`, `apiTendanceEpargne`, `apiBudgetaire`
  - Ajout vérification propriété compte (sécurité 403)
  - **Impact** : 5 méthodes corrigées dans `RapportController.php`

### Mineur

- **Transactions** : Bouton "Annuler" avec route 404
  - Correction route : `/transactions/liste` → `/transactions`
  
- **Rapports** : Fonction JavaScript `chargerSuiviBudgetaire` inexistante
  - Renommage : `chargerSuiviBudgetaire` → `chargerBudgetaire`

---

## 📝 Changelog complet

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique détaillé des modifications.

### Commits Session 14

1. `57fe677` - feat: Todo #5 - Bouton dupliquer transaction
2. `cfeeb16` - fix: Correction route bouton Annuler
3. `c99969f` - feat: Todo #6 - Date picker avec raccourcis
4. `f9d4b5a` - feat: Raccourcis mois/année rapports + debug logs
5. `ceaab14` - chore: Retrait logs debug - fonctionnement confirmé
6. `e4dd350` - fix: Correction bug filtrage compte ⭐ **TAG v2.0.0**
7. `d4afdc3` - fix: Correction complète + nettoyage debug
8. `2404f26` - chore: Préparation version 2.1.0-dev

---

## 👥 Contributeurs

- **teddycampagne** - Développement principal et architecture
- **GitHub Copilot** - Assistance au développement

---

## 🔗 Liens utiles

- **Repository** : https://github.com/teddycampagne/monbudget-v2
- **Issues** : https://github.com/teddycampagne/monbudget-v2/issues
- **Discussions** : https://github.com/teddycampagne/monbudget-v2/discussions
- **Documentation** : Voir dossier `/docs`

---

## 📅 Roadmap

### v2.1.0 (En cours - branche develop)

**Phase 1** : UX Improvements ✅ (6/6 complétées)
- Breadcrumbs navigation
- Création rapide
- Drill-down
- Sous-catégories
- Duplication transaction
- Date picker raccourcis

**Phase 2** : À planifier

### v2.2.0 (Q1 2026)

- Notifications par email/SMS
- Mode PWA (Progressive Web App)
- Alertes budgétaires automatiques
- Export PDF avancé

### v2.3.0 (Q2 2026)

- Cache Redis pour performances
- Support multi-devises
- API REST publique
- Monitoring avancé

### v3.0.0 (Q3 2026)

- Architecture microservices
- Mobile-first redesign
- Recherche full-text
- Intégration bancaire Open Banking

### v4.0.0 (Q4 2026)

- Dashboard personnalisable
- Thèmes customisables
- Rapports avancés avec IA
- Authentification 2FA

---

## 📄 Licence

Voir fichier [LICENSE](LICENSE) pour les détails.

---

## 🙏 Remerciements

Merci à tous les utilisateurs de MonBudget v1.x pour leurs retours et suggestions qui ont permis de construire cette v2.0 encore plus robuste et performante.

---

## ⚠️ Notes importantes

### Environnement de développement

- **main** : Branche de production (stable)
- **develop** : Branche d'intégration (v2.1.0-dev)
- **feature/\*** : Branches de fonctionnalités
- **bugfix/\*** : Branches de corrections
- **hotfix/\*** : Corrections urgentes production

### Versioning sémantique

MonBudget suit le [Semantic Versioning 2.0.0](https://semver.org/lang/fr/) :

- **MAJEUR** : Changements incompatibles avec l'API
- **MINEUR** : Ajout de fonctionnalités rétrocompatibles
- **CORRECTIF** : Corrections de bugs rétrocompatibles

### Labels de stabilité

- **legacy** : v1.x (ancienne version)
- **oldstable** : v2.0.x (version stable précédente)
- **stable** : v2.1.x (version stable actuelle)
- **preview** : beta/alpha (versions de test)

---

**Merci d'utiliser MonBudget v2.0 !** 🚀

Pour toute question ou problème, n'hésitez pas à ouvrir une issue sur GitHub.
