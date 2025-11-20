# Version 2.3.0 - Infrastructure PCI DSS Complète 🔒

**Date de release** : 21 novembre 2025  
**Branche** : main  
**Tag** : v2.3.0

---

## 🎯 Vue d'ensemble

Cette version majeure introduit une **infrastructure de sécurité complète** conforme aux exigences **PCI DSS** pour la protection des données bancaires et des informations sensibles.

---

## ✨ Nouveautés Principales

### 🔐 Phase 1 - Services PCI DSS (3 services)

#### EncryptionService
- ✅ Chiffrement **AES-256-GCM** (authentifié)
- ✅ Protection IBAN et données sensibles
- ✅ Méthodes spécialisées : `encryptIBAN()`, `decryptIBAN()`, `maskIBAN()`
- ✅ Détection automatique données déjà chiffrées
- 📋 **Conformité** : PCI DSS Requirement 3 ✅

#### PasswordPolicyService
- ✅ Validation stricte : **12+ caractères** (majuscules, minuscules, chiffres, spéciaux)
- ✅ Expiration automatique : **90 jours**
- ✅ Historique **5 derniers mots de passe** (pas de réutilisation)
- ✅ Verrouillage après **5 tentatives** échouées
- ✅ Durée verrouillage : **15 minutes**
- 📋 **Conformité** : PCI DSS Requirements 8.1.6, 8.1.7, 8.2.3, 8.2.4, 8.2.5 ✅

#### AuditLogService
- ✅ Traçabilité complète : connexions, modifications, suppressions
- ✅ Capture automatique : IP, User-Agent, URI, méthode HTTP
- ✅ Sanitization automatique des mots de passe (`[REDACTED]`)
- ✅ Support oldValues/newValues (JSON)
- ✅ Rétention minimum 1 an
- 📋 **Conformité** : PCI DSS Requirements 10.2, 10.3 ✅

### 👤 Phase 2 - Profil & Administration

#### ProfileController (nouveau)
- ✅ Gestion profil utilisateur
- ✅ Changement mot de passe sécurisé avec validation temps réel
- ✅ Indicateurs expiration mot de passe (🔴 expiré, 🟠 < 7j, 🟢 valide)
- ✅ Vues : `show.php`, `change-password.php`

#### AdminController - Sécurité
- ✅ Page comptes verrouillés (`locked_users.php`)
- ✅ Déverrouillage compte (avec audit)
- ✅ Reset mot de passe avec politique PCI DSS
- ✅ Formulaires sécurisés avec CSRF

### 📝 Phase 3 - Audit CRUD Complet

#### TransactionController
- ✅ Audit virements internes (2 logs : débit + crédit)
- ✅ Audit transactions normales
- ✅ Log oldValues/newValues pour modifications

#### CompteController
- ✅ Audit création/modification/suppression comptes
- ✅ Traçabilité complète avec données bancaires

#### BudgetController
- ✅ Audit budgets mensuels et annuels
- ✅ Log multiple creates pour budgets annuels (12 mois)

### 🔐 Sécurité Import (CRITIQUE)

**Problème identifié** : Fichiers CSV/OFX stockés indéfiniment (données bancaires en clair)

**Solutions implémentées** :
- ✅ **Suppression immédiate** après import réussi (CSV + OFX)
- ✅ **Suppression sur erreur** (fichier vide, parse échoué)
- ✅ **Cleanup automatique** : fichiers > 1 heure auto-supprimés
- ✅ **Migration BDD** : Colonne `chemin_fichier` supprimée
- ✅ **Logs sécurité** : Toutes suppressions tracées
- 📋 **Conformité** : PCI DSS Requirement 3.1 ✅

### 📊 Base de Données

#### Nouvelles Tables

**audit_logs** (BIGINT, partitionnement optionnel)
```sql
- id, user_id, action, table_name, record_id
- old_values (JSON), new_values (JSON)
- ip_address, user_agent, request_uri, request_method
- created_at
```

**password_history** (FK CASCADE DELETE)
```sql
- id, user_id, password_hash, created_at
- Index composite: (user_id, created_at DESC)
```

#### Table users - Colonnes PCI DSS
- ✅ `password_expires_at` : Date expiration (90 jours)
- ✅ `failed_login_attempts` : Compteur tentatives
- ✅ `locked_until` : Date fin verrouillage
- ✅ `last_password_change` : Dernier changement
- ✅ `must_change_password` : Flag forçage changement

#### Migrations
- ✅ `001_create_password_history.sql`
- ✅ `002_create_audit_logs.sql`
- ✅ `003_alter_users_security_fields.sql`
- ✅ `20241120_remove_chemin_fichier_from_imports.sql`

#### Structure Complète
- ✅ `database.sql` mis à jour (structure v2.3.0, **0 données**)
- ✅ Conversion users : MyISAM → InnoDB (support foreign keys)

### 🧪 Tests Unitaires (88 tests)

#### EncryptionServiceTest.php (22 tests)
- ✅ Tests encrypt/decrypt round-trip
- ✅ Validation IV aléatoires (même plaintext → ciphertext différents)
- ✅ Test mauvaise clé (échec déchiffrement)
- ✅ Performance : 100 encryptions < 1s
- ✅ Gestion chaînes vides

#### PasswordPolicyServiceTest.php (27 tests)
- ✅ Validation critères (longueur, complexité)
- ✅ Vérification constantes (MIN_LENGTH=12, MAX_AGE_DAYS=90, etc.)
- ✅ Messages d'erreur en français
- ✅ Performance : 1000 validations < 0.5s
- ✅ Edge cases (null, vide, Unicode)

#### AuditLogServiceTest.php (39 tests)
- ✅ Vérification constantes actions
- ✅ Sanitization passwords (`[REDACTED]`)
- ✅ Tests log(), logCreate(), logUpdate(), logDelete()
- ✅ Performance : 50 logs < 2s
- ⚠️ Certains tests nécessitent BDD (`monbudget_test`)

### 📚 Documentation

#### Nouveaux Documents
- ✅ `docs/PCI-DSS-COMPLIANCE.md` : Référence complète
- ✅ `docs/INTEGRATION-PCI-DSS.md` : Guide intégration
- ✅ `docs/SESSION-PCI-DSS-20241120.md` : Notes session
- ✅ `docs/SESSION-INTEGRATION-PCI-DSS-20241120.md` : Notes intégration
- ✅ `docs/RELEASE-v2.3.0-INSTRUCTIONS.md` : Instructions release
- ✅ `docs/VERSION-MANAGER.md` : Gestion versions
- ✅ `docs/CHECKLIST-PRE-RELEASE-v2.3.0.md` : Validation finale

#### Documentation Utilisateur Mise à Jour
- ✅ `docs/user/README.md` : Version 2.3.0
- ✅ `docs/user/GUIDE.md` : Section sécurité enrichie (politique MDP, chiffrement, audit, auto-suppression imports)

### 🛠️ Scripts & Outils

#### run-migrations.ps1
- ✅ Exécution automatique migrations SQL
- ✅ Table tracking `_migrations` (évite ré-exécution)
- ✅ Gestion erreurs complète

#### security-audit.ps1
- ✅ Vérifications pré-push (database.sql, IBAN, emails)
- ✅ Détection BOM UTF-8 avec correction
- ✅ Mode strict (blocage push non sécurisés)

#### cli/migrate-encrypt-ibans.php
- ✅ Migration IBAN existants vers format chiffré
- ✅ Validation IBAN avant chiffrement
- ✅ Dry-run mode disponible

### 🧹 Nettoyage & Polish

**Fichiers supprimés** (964 lignes) :
- ❌ `test-version-manager.php` (349 lignes)
- ❌ `fix-database-encoding.php` (91 lignes)
- ❌ `database_clean.sql`, `database_structure.sql`, `database_with_data.sql` (redondants)

**Code nettoyé** :
- ✅ Suppression logs DEBUG (TransactionController)
- ✅ Mise à jour commentaires TODO (Compte.php)

---

## 📋 Conformité PCI DSS

| Requirement | Description | Statut |
|------------|-------------|---------|
| **3.1** | Minimiser rétention données | ✅ Import auto-cleanup |
| **3.4** | Chiffrement données sensibles | ✅ AES-256-GCM |
| **8.1.6** | Verrouillage après tentatives | ✅ 5 tentatives max |
| **8.1.7** | Durée verrouillage | ✅ 15 minutes |
| **8.2.3** | Force mots de passe | ✅ 12+ caractères |
| **8.2.4** | Expiration mots de passe | ✅ 90 jours |
| **8.2.5** | Historique mots de passe | ✅ 5 derniers |
| **10.2** | Audit trails | ✅ Tous événements |
| **10.3** | Détails audit | ✅ User, date, événement |

---

## 📊 Statistiques

- **Commits** : 10 (develop)
- **Fichiers modifiés** : 60+
- **Lignes ajoutées** : 2500+
- **Lignes supprimées** : 964
- **Services créés** : 3
- **Controllers modifiés** : 6
- **Tables BDD créées** : 2
- **Colonnes BDD ajoutées** : 5
- **Tests créés** : 88 (3 fichiers)
- **Documents créés** : 7
- **Migrations créées** : 4
- **Vulnérabilités corrigées** : 1 critique (import file retention)

---

## 🚀 Installation / Migration

### Depuis v2.2.x

```bash
# 1. Récupérer la version
git pull origin main
git checkout v2.3.0

# 2. Exécuter les migrations BDD
.\run-migrations.ps1

# 3. Configurer la clé de chiffrement
# Ajouter dans .env :
ENCRYPTION_KEY="votre-clé-base64-32-bytes"

# 4. (Optionnel) Chiffrer les IBAN existants
php cli/migrate-encrypt-ibans.php
```

### Nouvelle Installation

```bash
# 1. Cloner le repo
git clone https://github.com/teddycampagne/monbudget-v2.git
cd monbudget-v2

# 2. Installer dépendances
composer install

# 3. Créer la base de données
mysql -u root -p -e "CREATE DATABASE monbudget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p monbudget < database.sql

# 4. Configurer .env
cp .env.example .env
# Éditer .env et définir ENCRYPTION_KEY

# 5. Accéder à l'installation
http://localhost/monbudget-v2/setup
```

---

## ⚠️ Breaking Changes

**Aucun** - Cette version est **rétrocompatible** avec v2.2.x.

---

## 🔧 Configuration Requise

- **PHP** : 8.1+
- **MySQL** : 8.0+ (InnoDB obligatoire)
- **Extensions PHP** : mbstring, pdo_mysql, openssl
- **Composer** : 2.x
- **Nouvelle variable** : `ENCRYPTION_KEY` (dans .env)

---

## 📝 Notes Importantes

### Sécurité
- ⚠️ **ENCRYPTION_KEY** doit être généré et stocké de manière sécurisée
- ⚠️ Les fichiers CSV/OFX sont maintenant **supprimés automatiquement** après import
- ⚠️ Les mots de passe **expirent après 90 jours**
- ⚠️ Les comptes sont **verrouillés après 5 tentatives** échouées

### Base de Données
- ✅ La table `users` a été convertie de MyISAM vers InnoDB
- ✅ Nouvelles foreign keys avec CASCADE DELETE
- ✅ `database.sql` ne contient **aucune donnée** (structure uniquement)

### Tests
- ⚠️ Certains tests nécessitent une base `monbudget_test`
- ⚠️ Configurer `ENCRYPTION_KEY` dans .env.testing

---

## 🐛 Bugs Corrigés

- ✅ **Import file retention** : Fichiers CSV/OFX supprimés après traitement (PCI DSS)
- ✅ **Database.sql obsolète** : Structure mise à jour avec tables PCI DSS
- ✅ **Namespace issues** : Corrections ProfileController, AdminController
- ✅ **Database column names** : Adaptation ImportController (structure réelle)

---

## 👥 Contributeurs

- [@teddycampagne](https://github.com/teddycampagne)

---

## 📖 Documentation Complète

- [Guide Utilisateur v2.3.0](docs/user/GUIDE.md)
- [PCI DSS Compliance](docs/PCI-DSS-COMPLIANCE.md)
- [Guide Intégration PCI DSS](docs/INTEGRATION-PCI-DSS.md)
- [Checklist Pré-Release](docs/CHECKLIST-PRE-RELEASE-v2.3.0.md)

---

## 🔗 Liens Utiles

- **Changelog** : [CHANGELOG.md](CHANGELOG.md)
- **Security** : [SECURITY.md](SECURITY.md)
- **License** : [LICENSE](LICENSE)

---

**Full Changelog**: https://github.com/teddycampagne/monbudget-v2/compare/v2.2.0...v2.3.0
