# Système d'Exécution Automatique des Récurrences

## 📋 Vue d'ensemble

Le système d'exécution automatique des récurrences permet de générer automatiquement les transactions récurrentes (loyers, salaires, abonnements) sans intervention manuelle.

**Version** : 2.2.0  
**Date** : 16 novembre 2025

---

## 🎯 Fonctionnalités

### ✅ Protection Anti-Doublons Robuste
- Vérification par `recurrence_id` + `date_transaction`
- Plusieurs utilisateurs peuvent se connecter le même jour sans créer de duplicatas
- Logs détaillés pour traçabilité

### 🔄 Exécution Automatique au Login
- Déclenchée automatiquement à chaque connexion utilisateur
- Traite **toutes** les récurrences échues (tous utilisateurs)
- Silencieuse si aucune récurrence à exécuter
- Message de confirmation si exécutions réussies

### 📅 Gestion des Weekends
Trois modes de tolérance :
- `aucune` : Exécution le jour prévu (même weekend)
- `jour_ouvre_suivant` : Report au lundi suivant
- `jour_ouvre_precedent` : Report au vendredi précédent

### 📊 Statistiques et Logs
- Logs mensuels dans `storage/logs/recurrence_auto_YYYY-MM.log`
- Statistiques : vérifiées, exécutées, ignorées, erreurs
- Horodatage complet pour audit

---

## 🏗️ Architecture

### Fichiers créés

```
app/Services/RecurrenceService.php        # Service principal (logique métier)
database/migrations/2025_11_16_add_recurrence_id_to_transactions.sql
cli/execute_recurrences.php              # Script CLI pour tests/cron
docs/RECURRENCES-AUTO.md                 # Cette documentation
```

### Modifications

```
app/Controllers/AuthController.php        # Hook executeAllPendingRecurrences()
```

---

## 🚀 Utilisation

### 1. Migration BDD (À EXÉCUTER EN PREMIER)

Exécuter la migration pour ajouter la colonne `recurrence_id` :

```bash
# Depuis phpMyAdmin ou ligne de commande MySQL
mysql -u root monbudget < database/migrations/2025_11_16_add_recurrence_id_to_transactions.sql
```

Ou via phpMyAdmin :
1. Ouvrir la base `monbudget`
2. Onglet SQL
3. Copier le contenu de `2025_11_16_add_recurrence_id_to_transactions.sql`
4. Exécuter

### 2. Fonctionnement Automatique

**Aucune action requise !**

Le système s'exécute automatiquement lors de chaque connexion utilisateur :

```php
// Dans AuthController::login()
$recurrenceService = new RecurrenceService();
$stats = $recurrenceService->executeAllPendingRecurrences();
```

### 3. Exécution Manuelle (Tests)

Pour tester ou forcer une exécution :

```bash
cd c:\wamp64\www\monbudgetV2
php cli\execute_recurrences.php
```

**Sortie exemple** :

```
╔════════════════════════════════════════════════════════════╗
║  MonBudget - Exécution automatique des récurrences        ║
║  Version 2.2.0 - 2025-11-16 14:30:00                      ║
╚════════════════════════════════════════════════════════════╝

🔍 Recherche des récurrences échues...

📊 RÉSULTATS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   Récurrences vérifiées : 5
   ✓ Exécutées           : 3
   ⊘ Ignorées (doublons) : 2
   ✗ Erreurs             : 0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 DÉTAILS DES EXÉCUTIONS:
   ✓ [User:1] Récurrence #12 exécutée le 2025-11-16
     → Loyer appartement
   ✓ [User:2] Récurrence #34 exécutée le 2025-11-16
     → Abonnement Netflix
   ✓ [User:1] Récurrence #56 exécutée le 2025-11-16
     → Salaire mensuel

✅ Exécution terminée avec succès !
```

### 4. Cron Job (Optionnel)

Pour exécuter quotidiennement via cron (alternative au hook login) :

```bash
# Crontab
# Tous les jours à 6h du matin
0 6 * * * cd /var/www/monbudget && php cli/execute_recurrences.php >> storage/logs/cron_recurrences.log 2>&1
```

---

## 🔍 Détection de Doublons

### Algorithme

```php
// 1. Récupérer les récurrences échues
SELECT * FROM transactions 
WHERE est_recurrente = 1 
  AND recurrence_active = 1
  AND prochaine_execution <= CURDATE()

// 2. Pour chaque récurrence, vérifier si doublon
SELECT COUNT(*) FROM transactions
WHERE recurrence_id = ?          -- ID de la récurrence parente
  AND DATE(date_transaction) = ? -- Date prévue d'exécution

// 3. Si doublon trouvé : skip + update prochaine_execution
// 4. Si pas de doublon : créer transaction + update récurrence
```

### Cas d'usage

**Scénario** : 3 utilisateurs se connectent le même jour

1. **User A se connecte à 8h00**
   - Récurrence #12 (Loyer) échue → Créée ✅
   - `recurrence_id = 12`, `date_transaction = 2025-11-16`

2. **User B se connecte à 10h30**
   - Récurrence #12 déjà traitée → Ignorée ⊘
   - Détection doublon : `recurrence_id=12` + `date=2025-11-16` existe

3. **User C se connecte à 14h00**
   - Récurrence #12 déjà traitée → Ignorée ⊘
   - Pas de duplicata créé

**Résultat** : 1 seule transaction créée, pas de doublon ! 🎉

---

## 📋 Exemples de Code

### Appel dans un contrôleur

```php
use MonBudget\Services\RecurrenceService;

$service = new RecurrenceService();
$stats = $service->executeAllPendingRecurrences();

// $stats contient :
// [
//     'total_checked' => 10,
//     'total_executed' => 3,
//     'total_skipped' => 7,
//     'errors' => [],
//     'details' => [...]
// ]
```

### Récupérer les stats du dernier run

```php
$service = new RecurrenceService();
$lastStats = $service->getLastExecutionStats();

// null si aucune exécution ce mois-ci
// sinon :
// [
//     'checked' => 5,
//     'executed' => 2,
//     'skipped' => 3,
//     'errors' => 0,
//     'timestamp' => '2025-11-16 14:30:00'
// ]
```

---

## 🧪 Tests

### Test 1 : Première exécution

```sql
-- Créer une récurrence de test
INSERT INTO transactions (user_id, compte_id, est_recurrente, recurrence_active, 
                          libelle, montant, type_operation, frequence, intervalle,
                          prochaine_execution, date_debut)
VALUES (1, 1, 1, 1, 'Test Récurrence', 100.00, 'debit', 'mensuel', 1, '2025-11-16', '2025-11-16');
```

```bash
# Exécuter le script
php cli\execute_recurrences.php

# Vérifier
SELECT * FROM transactions WHERE recurrence_id = LAST_INSERT_ID();
```

### Test 2 : Doublon

```bash
# Exécuter 2 fois de suite
php cli\execute_recurrences.php
php cli\execute_recurrences.php

# La 2e exécution doit afficher "Ignorées (doublons): 1"
```

### Test 3 : Tolérance weekend

```sql
-- Récurrence prévue un samedi avec tolérance
UPDATE transactions 
SET prochaine_execution = '2025-11-23',  -- Samedi
    tolerance_weekend = 'jour_ouvre_suivant'
WHERE id = 123;
```

```bash
# Exécuter
php cli\execute_recurrences.php

# La transaction sera créée le lundi 2025-11-25
```

---

## 📊 Logs

### Emplacement

```
storage/logs/recurrence_auto_2025-11.log
```

### Format

```
[2025-11-16 14:30:00] AUTO-EXECUTION: Checked=5, Executed=3, Skipped=2, Errors=0
  ✓ Récurrence #12 (user:1) exécutée le 2025-11-16: Loyer appartement
  ✓ Récurrence #34 (user:2) exécutée le 2025-11-16: Abonnement Netflix
  ✓ Récurrence #56 (user:1) exécutée le 2025-11-16: Salaire mensuel
```

---

## 🔧 Configuration

Aucune configuration requise ! Le système utilise les paramètres définis dans chaque récurrence :

- `frequence` : quotidien, hebdomadaire, mensuel, annuel
- `intervalle` : tous les X jours/semaines/mois
- `tolerance_weekend` : gestion des weekends
- `auto_validation` : valider automatiquement ou non
- `nb_executions_max` : limite d'exécutions (optionnel)
- `date_fin` : date de fin (optionnel)

---

## ⚠️ Important

1. **Migration obligatoire** : Exécuter la migration `2025_11_16_add_recurrence_id_to_transactions.sql` avant utilisation

2. **Permissions** : Le dossier `storage/logs/` doit être accessible en écriture

3. **Performance** : Le système est optimisé (1 requête pour récupérer, 1 requête par vérification doublon)

4. **Erreurs silencieuses** : Les erreurs sont loggées mais ne bloquent pas la connexion utilisateur

5. **Cron vs Login** : 
   - **Login** : Simple, automatique, pas de config serveur
   - **Cron** : Plus fiable si peu de connexions quotidiennes

---

## 🐛 Dépannage

### Problème : Récurrences non exécutées

```sql
-- Vérifier les récurrences échues
SELECT id, libelle, prochaine_execution, recurrence_active
FROM transactions
WHERE est_recurrente = 1
  AND prochaine_execution <= CURDATE();
```

### Problème : Logs introuvables

```bash
# Créer le dossier si besoin
mkdir -p storage/logs
chmod 755 storage/logs
```

### Problème : Doublons malgré tout

```sql
-- Vérifier la colonne recurrence_id
SELECT id, recurrence_id, date_transaction, libelle
FROM transactions
WHERE recurrence_id IS NOT NULL
ORDER BY recurrence_id, date_transaction;
```

---

## 📚 Ressources

- **Code source** : `app/Services/RecurrenceService.php`
- **Documentation Transaction** : `app/Models/Transaction.php`
- **Tests** : `cli/execute_recurrences.php`

---

## 🎯 Prochaines Améliorations

- [ ] Dashboard admin avec statistiques d'exécution
- [ ] Notification email des exécutions quotidiennes
- [ ] Historique détaillé par récurrence
- [ ] Mode "dry-run" pour simulation
- [ ] API REST pour exécution externe

---

**Développé avec ❤️ pour MonBudget v2.2.0**
