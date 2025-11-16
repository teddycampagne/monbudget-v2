# Git Workflow - MonBudget v2

## 📋 Stratégie de Versioning

### Semantic Versioning (SemVer)

Format : **MAJEUR.MINEUR.CORRECTIF** (ex: 2.1.0)

- **MAJEUR** : Changements incompatibles avec les versions précédentes
  - Exemple : v1.x → v2.x (refonte architecture)
- **MINEUR** : Nouvelles fonctionnalités compatibles
  - Exemple : v2.0.x → v2.1.x (ajout features UX)
- **CORRECTIF** : Corrections de bugs uniquement
  - Exemple : v2.1.0 → v2.1.1 (bugfixes)

### Labels de Stabilité

- **legacy** : v1.x (ancienne version PHP procédural)
- **oldstable** : v2.0.x (dernière version stable précédente)
- **stable** : v2.1.x (version actuelle recommandée)
- **preview** : v2.2.0-beta, v3.0.0-alpha (versions de test)

---

## 🌿 Structure des Branches

### Branches Principales

#### `main` (production)
- **Rôle** : Code stable en production
- **Protection** : Merge uniquement depuis `develop` ou `hotfix/*`
- **Tags** : Toutes les releases (v2.0.0, v2.1.0, etc.)
- **Commits** : Interdits en direct (sauf urgences)

#### `develop` (développement)
- **Rôle** : Branche d'intégration pour la prochaine version
- **Protection** : Tests obligatoires avant merge
- **Origine** : Point de départ de toutes les features
- **Merge vers** : `main` lors d'une release

### Branches Temporaires

#### `feature/*` (fonctionnalités)
```bash
# Nomenclature
feature/todo-7-breadcrumbs
feature/date-picker-shortcuts
feature/export-pdf-reports

# Cycle de vie
git checkout develop
git checkout -b feature/nom-feature
# ... développement ...
git push origin feature/nom-feature
# PR vers develop
# Suppression après merge
```

#### `bugfix/*` (corrections)
```bash
# Nomenclature
bugfix/rapports-compte-filter
bugfix/transaction-cancel-button

# Cycle de vie
git checkout develop
git checkout -b bugfix/description-bug
# ... correction ...
git push origin bugfix/description-bug
# PR vers develop
```

#### `hotfix/*` (corrections urgentes production)
```bash
# Nomenclature
hotfix/v2.1.1-security-patch
hotfix/critical-sql-injection

# Cycle de vie (depuis main !)
git checkout main
git checkout -b hotfix/v2.1.1-description
# ... correction urgente ...
git push origin hotfix/v2.1.1-description
# PR vers main ET develop
# Tag immédiat après merge
```

#### `release/*` (préparation release)
```bash
# Nomenclature
release/v2.2.0

# Cycle de vie
git checkout develop
git checkout -b release/v2.2.0
# Finalisation (CHANGELOG, version, tests)
git push origin release/v2.2.0
# PR vers main
# Tag après merge
# Merge aussi dans develop
```

---

## 🔄 Workflow Standard

### 1. Développement d'une Feature

```bash
# 1. Partir de develop à jour
git checkout develop
git pull origin develop

# 2. Créer branche feature
git checkout -b feature/ma-fonctionnalite

# 3. Développer et commiter
git add .
git commit -m "feat: Description de la feature"

# 4. Pousser régulièrement
git push origin feature/ma-fonctionnalite

# 5. Créer PR sur GitHub (feature → develop)
# 6. Review + tests automatiques
# 7. Merge dans develop
# 8. Supprimer la branche feature
git checkout develop
git branch -d feature/ma-fonctionnalite
git push origin --delete feature/ma-fonctionnalite
```

### 2. Préparation d'une Release

```bash
# 1. Créer branche release depuis develop
git checkout develop
git pull origin develop
git checkout -b release/v2.2.0

# 2. Finaliser la version
# - Mettre à jour composer.json : "2.2.0-dev" → "2.2.0"
# - Compléter CHANGELOG.md avec date de release
# - Exécuter tous les tests
# - Corriger les derniers bugs

# 3. Commiter les changements de version
git commit -am "chore: Préparation release v2.2.0"
git push origin release/v2.2.0

# 4. PR vers main (review finale)
# 5. Merge dans main
git checkout main
git merge --no-ff release/v2.2.0

# 6. Créer le tag
git tag -a v2.2.0 -m "Release v2.2.0 - Titre de la version

Features principales:
- Feature 1
- Feature 2
- ...

Bugfixes:
- Fix 1
- Fix 2
- ...
"

# 7. Push main + tag
git push origin main --tags

# 8. Merge aussi dans develop
git checkout develop
git merge --no-ff release/v2.2.0
git push origin develop

# 9. Supprimer branche release
git branch -d release/v2.2.0
git push origin --delete release/v2.2.0

# 10. Créer GitHub Release (interface web)
```

### 3. Hotfix en Production

```bash
# 1. Partir de main (pas develop !)
git checkout main
git pull origin main
git checkout -b hotfix/v2.1.1-sql-injection

# 2. Corriger le bug critique
git commit -am "fix: Correction SQL injection dans recherche"

# 3. Push
git push origin hotfix/v2.1.1-sql-injection

# 4. PR vers main (review urgente)
# 5. Merge dans main
git checkout main
git merge --no-ff hotfix/v2.1.1-sql-injection

# 6. Tag immédiat
git tag -a v2.1.1 -m "Hotfix v2.1.1 - Sécurité

Correction critique:
- SQL injection dans module recherche
"
git push origin main --tags

# 7. Merge aussi dans develop
git checkout develop
git merge --no-ff hotfix/v2.1.1-sql-injection
git push origin develop

# 8. Supprimer branche hotfix
git branch -d hotfix/v2.1.1-sql-injection
git push origin --delete hotfix/v2.1.1-sql-injection
```

---

## 📝 Convention de Commits

### Format

```
<type>(<scope>): <description>

[corps optionnel]

[footer optionnel]
```

### Types

- **feat** : Nouvelle fonctionnalité
- **fix** : Correction de bug
- **docs** : Documentation uniquement
- **style** : Formatage, points-virgules manquants, etc.
- **refactor** : Refactoring du code (ni feature ni fix)
- **perf** : Amélioration des performances
- **test** : Ajout ou correction de tests
- **chore** : Tâches de maintenance (build, deps, etc.)

### Exemples

```bash
feat(transactions): Ajout bouton duplication
fix(rapports): Correction filtrage par compte
docs(readme): Mise à jour instructions installation
refactor(auth): Simplification logique de connexion
test(models): Ajout tests unitaires Compte
chore(deps): Mise à jour PHPUnit vers 10.5.58
```

### Scope (optionnel)

- `auth` : Authentication
- `transactions` : Module transactions
- `comptes` : Module comptes
- `rapports` : Module rapports
- `api` : API REST
- `ui` : Interface utilisateur
- `db` : Base de données

---

## 🏷️ Gestion des Tags

### Convention de Nommage

```
v<MAJEUR>.<MINEUR>.<CORRECTIF>[-<LABEL>]

Exemples:
v2.0.0         # Release stable
v2.1.0-beta.1  # Beta 1 de la v2.1.0
v2.2.0-alpha   # Alpha de la v2.2.0
v3.0.0-rc.1    # Release Candidate 1
```

### Créer un Tag Annoté

```bash
# Avec message complet
git tag -a v2.1.0 -m "Release v2.1.0 - UX Improvements

Nouvelles fonctionnalités:
- Breadcrumbs de navigation
- Date picker avec raccourcis
- Duplication de transactions

Corrections:
- Filtrage rapports par compte
- Route bouton annuler

Stats: +460 lignes, 7 commits
"

# Push des tags
git push origin --tags
```

### Lister les Tags

```bash
# Tous les tags
git tag -l

# Tags d'une version spécifique
git tag -l "v2.1.*"

# Voir le message d'un tag
git show v2.1.0
```

### Supprimer un Tag (DANGEREUX)

```bash
# Local
git tag -d v2.1.0

# Remote (ATTENTION : ne jamais faire sur tags publiés !)
git push origin --delete v2.1.0
```

---

## 🔒 Bonnes Pratiques

### ✅ À FAIRE

1. **Toujours partir de `develop` pour les features**
2. **Commiter souvent avec messages clairs**
3. **Tester avant de pousser**
4. **Créer des PR pour review**
5. **Mettre à jour CHANGELOG.md à chaque version**
6. **Vérifier `.gitignore` avant commits sensibles**
7. **Utiliser `--no-ff` pour les merges importants**

### ❌ À ÉVITER

1. **Commiter directement dans `main`** (sauf hotfix approuvé)
2. **Pousser des données sensibles** (.env, passwords, etc.)
3. **Modifier l'historique public** (rebase, force push)
4. **Créer des branches depuis `main`** (sauf hotfix)
5. **Oublier de merger hotfix dans `develop`**
6. **Supprimer des tags publiés**

---

## 📊 État Actuel du Projet

### Versions

- **Actuelle (stable)** : v2.0.0 (tag sur commit `e4dd350`)
- **En développement** : v2.1.0-dev (branche `develop`)
- **Legacy** : v1.x (non versionné Git, ancien monbudget/)

### Branches

```
main (production)
  ↓
  v2.0.0 (tag)
  
develop (intégration)
  ↓
  v2.1.0-dev (en cours)
```

### Prochaines Releases Prévues

- **v2.1.0** : UX Improvements Phase 1 (en cours)
  - Breadcrumbs, date picker, duplication (✅ fait)
  - Phase 2 à définir dans TODO-V2.1.md
  
- **v2.2.0** : Features avancées (Q1 2026)
  - PWA, mode hors-ligne, notifications push
  
- **v3.0.0** : Refonte majeure (futur)
  - Breaking changes potentiels

---

## 🆘 Commandes Utiles

```bash
# Statut actuel
git status
git branch -a
git tag -l

# Historique
git log --oneline --graph -10
git log --all --decorate --oneline --graph

# Différences
git diff develop main
git diff v2.0.0 v2.1.0

# Synchronisation
git fetch --all --tags
git pull origin develop

# Nettoyage branches locales supprimées remote
git fetch --prune
git branch -vv | grep ': gone]' | awk '{print $1}' | xargs git branch -d
```

---

## 📚 Références

- [Semantic Versioning](https://semver.org/lang/fr/)
- [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/)
- [Conventional Commits](https://www.conventionalcommits.org/fr/)
- [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/)

---

*Dernière mise à jour : 16 novembre 2025 - Session 14*
