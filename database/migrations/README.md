# Database Migrations

## 📁 Organisation des fichiers SQL

### Fichiers Principaux

#### 1. `database.sql` (Racine du projet)
**Description:** Schema complet de la base de données MonBudget v2.0  
**Contenu:**
- Toutes les tables (users, comptes, transactions, categories, budgets, etc.)
- Indexes de base
- Foreign keys et contraintes
- Structure complète prête pour installation

**Utilisation:**
- Installation initiale de l'application
- Réinitialisation complète de la base
- Référence pour la structure complète

**Commande:**
```bash
mysql -u root monbudget_v2 < database.sql
```

---

#### 2. `database_sample_data.sql` (Racine du projet)
**Description:** Données d'exemple pour découvrir l'application  
**Contenu:**
- 2 banques (Crédit Agricole, Société Générale)
- 15 catégories + sous-catégories
- Structure pour 3 comptes, 10 tiers, 50 transactions

**Utilisation:**
- Mode démonstration
- Tests fonctionnels
- Onboarding nouveaux utilisateurs

**Note:** Nécessite un utilisateur admin créé (user_id défini)

**Commande:**
```bash
# Après installation de database.sql
mysql -u root monbudget_v2 < database_sample_data.sql
```

---

### Migrations

#### `database/migrations/add_performance_indexes.sql`
**Description:** Optimisation performance - 19 indexes stratégiques  
**Date:** 11 novembre 2025 (Session 7)  
**Impact:** +40-60% performance requêtes

**Contenu:**
- **Transactions (6 indexes):**
  - `idx_transactions_compte_id` - Requêtes par compte
  - `idx_transactions_user_id` - Filtrage utilisateur
  - `idx_transactions_categorie_id` - Filtrage catégorie
  - `idx_transactions_date` - Tri chronologique
  - `idx_transactions_type` - Filtrage crédit/débit
  - `idx_transactions_compte_date` - Composite compte+date

- **Comptes (4 indexes):**
  - `idx_comptes_user_id` - Requêtes utilisateur
  - `idx_comptes_banque_id` - Regroupement banque
  - `idx_comptes_actif` - Filtrage comptes actifs
  - `idx_comptes_type` - Filtrage par type

- **Catégories (3 indexes):**
  - `idx_categories_user_id`
  - `idx_categories_parent_id` - Navigation hiérarchique
  - `idx_categories_type`

- **Budgets (3 indexes):**
  - `idx_budgets_user_id`
  - `idx_budgets_categorie_id`
  - `idx_budgets_periode` - Recherche par période

- **Autres (3 indexes):**
  - `idx_tiers_user_id`
  - `idx_imports_user_id`
  - `idx_regles_automatisation_user_id`

**Utilisation:**
```bash
# À appliquer sur une base existante pour optimiser les performances
mysql -u root monbudget_v2 < database/migrations/add_performance_indexes.sql
```

**Note:** Peut être exécuté plusieurs fois sans danger (utilise `ADD INDEX IF NOT EXISTS`)

---

## 🚀 Installation Complète

### Installation Initiale (Base vide)
```bash
# 1. Créer la base de données
mysql -u root -e "CREATE DATABASE IF NOT EXISTS monbudget_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importer le schema complet
mysql -u root monbudget_v2 < database.sql

# 3. Appliquer les optimisations de performance
mysql -u root monbudget_v2 < database/migrations/add_performance_indexes.sql

# 4. (Optionnel) Charger les données d'exemple
mysql -u root monbudget_v2 < database_sample_data.sql
```

### Installation via Interface Web
L'application propose un installeur web qui:
1. Vérifie les prérequis
2. Crée la base de données
3. Importe `database.sql`
4. Applique les migrations
5. Crée l'utilisateur admin
6. (Optionnel) Charge les données d'exemple

**URL:** `http://localhost/monbudgetV2/setup`

---

## 📊 Performances

### Avant Optimisation (Session 7)
- Requêtes transactions: ~100-150ms
- Dashboard: ~500-700ms
- Rapports: ~1-2s

### Après Optimisation (19 indexes)
- Requêtes transactions: ~40-60ms (-60%)
- Dashboard: ~250-350ms (-50%)
- Rapports: ~600-900ms (-40%)

---

## 🔧 Maintenance

### Vérifier les indexes existants
```sql
SHOW INDEX FROM transactions;
SHOW INDEX FROM comptes;
SHOW INDEX FROM categories;
SHOW INDEX FROM budgets;
```

### Analyser les performances
```sql
EXPLAIN SELECT * FROM transactions WHERE user_id = 1 AND date_transaction > '2024-01-01';
```

### Optimiser les tables
```sql
OPTIMIZE TABLE transactions;
OPTIMIZE TABLE comptes;
OPTIMIZE TABLE categories;
```

---

## 📝 Historique des Migrations

| Date | Fichier | Description | Impact |
|------|---------|-------------|--------|
| 11/11/2025 | `add_performance_indexes.sql` | 19 indexes stratégiques | +40-60% perf |

---

## ⚠️ Notes Importantes

1. **Ordre d'exécution:** Toujours exécuter `database.sql` avant les migrations
2. **Données d'exemple:** `database_sample_data.sql` nécessite un user_id valide
3. **Performances:** Les indexes sont cruciaux pour une application avec >1000 transactions
4. **Backups:** Toujours faire une sauvegarde avant d'appliquer des migrations

---

## 🗂️ Structure Fichiers

```
monbudgetV2/
├── database.sql                          # Schema complet (440 lignes)
├── database_sample_data.sql             # Données démo (86 lignes)
└── database/
    └── migrations/
        ├── README.md                     # Ce fichier
        ├── add_performance_indexes.sql   # Optimisation Session 7 (160 lignes)
        └── optimize_database.php         # Classe DatabaseOptimizer
```

---

**Dernière mise à jour:** 11 novembre 2025  
**Version:** 2.0.0  
**Session:** 7 - Optimisation complète
