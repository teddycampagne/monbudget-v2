# Guide Utilisateur MonBudget

## Table des matières

1. [Introduction](#introduction)
2. [Tableau de bord](#tableau-de-bord)
3. [Gestion des comptes](#gestion-des-comptes)
4. [Transactions](#transactions)
5. [Catégories](#categories)
6. [Budgets](#gestion-des-budgets)
7. [Automatisation](#automatisation)
8. [Rapports et statistiques](#rapports-et-statistiques)
9. [Profil et paramètres](#profil-et-parametres)

---

## Introduction

### Qu'est-ce que MonBudget ?

MonBudget est une application web de gestion financière personnelle qui vous permet de :
- 📊 Suivre vos comptes bancaires en temps réel
- 💰 Catégoriser automatiquement vos dépenses
- 📈 Créer et suivre des budgets mensuels
- 📑 Générer des rapports détaillés
- 🔄 Automatiser les tâches répétitives

### Concepts clés

**Compte bancaire** : Représente un compte réel (courant, épargne, etc.)  
**Transaction** : Opération bancaire (débit, crédit, virement)  
**Catégorie** : Classification des transactions (alimentation, loisirs, etc.)  
**Budget** : Limite de dépenses pour une catégorie sur une période  
**Règle d'automatisation** : Critère pour catégoriser automatiquement

---

## Tableau de bord

Le tableau de bord est votre page d'accueil. Il affiche :

### Vue d'ensemble
- **Solde total** : Somme de tous vos comptes
- **Solde par compte** : Détail pour chaque compte bancaire
- **Graphique d'évolution** : Évolution du solde sur le mois

### Transactions récentes
- Les 10 dernières transactions
- Filtre rapide par compte
- Accès direct à la fiche transaction

### Budgets du mois
- État des budgets en cours
- Alertes de dépassement
- Pourcentage de consommation

### Raccourcis
- Ajouter une transaction
- Importer un fichier bancaire
- Générer un rapport

---

## Gestion des comptes

### Créer un compte

1. **Menu** : Comptes → Nouveau compte
2. **Informations obligatoires** :
   - Nom du compte (ex: "Compte courant Boursorama")
   - Banque (sélectionner ou créer)
   - Type de compte (Courant, Épargne, Livret)
   - Solde initial
3. **Informations bancaires** (optionnel) :
   - Code banque (5 chiffres)
   - Code guichet (5 chiffres)
   - Numéro de compte (11 chiffres)
   - Clé RIB (2 chiffres)
   - IBAN
   - BIC
4. **Cliquer sur "Créer"**

💡 **Astuce** : Le RIB est automatiquement validé si vous remplissez tous les champs.

### Associer des titulaires

Un compte peut avoir plusieurs titulaires (compte joint) :

1. Ouvrir la fiche du compte
2. Section "Titulaires"
3. Cliquer "Ajouter un titulaire"
4. Sélectionner ou créer un titulaire
5. Enregistrer

### Générer un RIB PDF

1. Ouvrir la fiche du compte
2. Vérifier que les informations bancaires sont complètes
3. Cliquer sur "Télécharger le RIB"
4. Le PDF est généré avec logo de la banque et QR code

⚠️ **Attention** : Tous les champs bancaires doivent être remplis.

### Modifier un compte

1. Comptes → Cliquer sur le compte
2. Bouton "Modifier"
3. Mettre à jour les informations
4. Enregistrer

### Supprimer un compte

⚠️ **Action irréversible** : Toutes les transactions associées seront supprimées.

1. Comptes → Cliquer sur le compte
2. Bouton "Supprimer"
3. Confirmer la suppression

---

## Transactions

### Ajouter une transaction manuellement

1. **Menu** : Transactions → Nouvelle transaction
2. **Informations obligatoires** :
   - Date de la transaction
   - Compte bancaire
   - Type (Débit, Crédit, Virement)
   - Montant
   - Libellé/Description
3. **Informations optionnelles** :
   - Catégorie
   - Sous-catégorie
   - Tiers/Bénéficiaire
   - Notes
4. **Cliquer sur "Créer"**

💡 **Astuce** : Si le libellé correspond à une règle, la catégorie sera suggérée automatiquement.

### Import de transactions

MonBudget supporte plusieurs formats bancaires :

#### Formats supportés
- **CSV** : Format le plus courant
- **OFX** : Format standard américain
- **QIF** : Format Quicken

#### Procédure d'import

1. **Télécharger** le fichier depuis votre banque
2. **Menu** : Transactions → Importer
3. **Sélectionner le fichier**
4. **Choisir le compte** de destination
5. **Mapper les colonnes** (si CSV) :
   - Date → Colonne date
   - Montant → Colonne montant
   - Libellé → Colonne description
6. **Prévisualiser** les transactions
7. **Confirmer l'import**

✅ **Détection des doublons** : Les transactions déjà importées sont ignorées.

#### Configuration du mapping CSV

Si votre banque utilise un format spécifique :

1. Import → Configuration
2. Créer un nouveau profil
3. Définir les mappings :
   ```
   Date : Colonne 1
   Libellé : Colonne 2
   Montant : Colonne 3
   ```
4. Enregistrer le profil

### Catégoriser les transactions

#### Catégorisation manuelle

1. Ouvrir la transaction
2. Sélectionner une catégorie
3. Sélectionner une sous-catégorie (optionnel)
4. Enregistrer

#### Catégorisation automatique

Les transactions sont catégorisées automatiquement si :
- Une règle correspond au libellé
- Un tiers est reconnu
- Le montant correspond à un pattern

Pour créer une règle :
1. Menu → Automatisation → Règles de catégorisation
2. Nouvelle règle
3. Définir les critères :
   - Mot-clé dans le libellé
   - Montant exact ou plage
   - Tiers
4. Définir la catégorie cible
5. Enregistrer

### Rechercher des transactions

**Recherche simple** :
- Barre de recherche en haut
- Recherche dans libellé, montant, catégorie

**Recherche avancée** :
1. Transactions → Recherche avancée
2. Filtres disponibles :
   - Période (date de début/fin)
   - Compte(s)
   - Catégorie(s)
   - Montant (min/max)
   - Type (débit/crédit)
   - Tiers
3. Appliquer les filtres

💡 **Astuce** : Enregistrez vos recherches fréquentes comme favoris.

### Exporter des transactions

1. Appliquer les filtres souhaités
2. Cliquer "Exporter"
3. Choisir le format :
   - **CSV** : Import Excel/LibreOffice
   - **PDF** : Impression
   - **JSON** : Intégration externe
4. Télécharger le fichier

---

## Catégories

### Structure des catégories

MonBudget utilise une structure hiérarchique :

```
📁 Alimentation (Catégorie)
  ├─ 🍞 Courses
  ├─ 🍕 Restaurants
  └─ ☕ Cafés
```

### Catégories par défaut

L'application fournit des catégories pré-configurées :
- 🏠 Logement (Loyer, Charges, Assurance)
- 🚗 Transport (Essence, Assurance auto, Parking)
- 🍔 Alimentation (Courses, Restaurants)
- 💊 Santé (Médecin, Pharmacie, Mutuelle)
- 🎭 Loisirs (Sport, Culture, Voyages)
- 💰 Revenus (Salaire, Primes, Remboursements)

### Créer une catégorie personnalisée

1. Menu → Catégories → Nouvelle catégorie
2. Informations :
   - Nom de la catégorie
   - Type (Dépense ou Revenu)
   - Icône (emoji ou icon)
   - Couleur (pour les graphiques)
3. Enregistrer

### Créer une sous-catégorie

1. Ouvrir la catégorie parente
2. Section "Sous-catégories"
3. Ajouter une sous-catégorie
4. Renseigner le nom
5. Enregistrer

### Modifier/Supprimer une catégorie

⚠️ **Attention** : Supprimer une catégorie décatégorise toutes les transactions associées.

1. Catégories → Cliquer sur la catégorie
2. Modifier ou Supprimer
3. Confirmer

---

## Gestion des budgets

### Créer un budget

1. **Menu** : Budgets → Nouveau budget
2. **Informations** :
   - Catégorie concernée
   - Montant limite
   - Période (Mensuel, Trimestriel, Annuel)
   - Date de début
3. **Options** :
   - Alerte à X% de consommation
   - Notification par email
4. **Enregistrer**

### Suivre un budget

Le tableau de bord affiche :
- **Montant consommé** / Montant alloué
- **Pourcentage** de consommation
- **Reste disponible**
- **Prévision** de fin de mois

#### Code couleur
- 🟢 Vert : < 70% consommé
- 🟡 Jaune : 70-90% consommé
- 🔴 Rouge : > 90% consommé
- ⚫ Noir : Dépassement

### Alertes de budget

Vous recevez une alerte quand :
- Le budget atteint 75% (configurable)
- Le budget est dépassé
- Projection de dépassement en fin de période

Configuration :
1. Budgets → Paramètres
2. Seuils d'alerte
3. Notifications (email, in-app)
4. Enregistrer

### Rapports de budget

1. Budgets → Sélectionner un budget
2. Onglet "Rapport"
3. Affichage :
   - Graphique d'évolution
   - Transactions du budget
   - Comparaison avec périodes précédentes
   - Tendance

---

## Automatisation

### Règles de catégorisation

#### Créer une règle simple

**Exemple** : Catégoriser automatiquement "CARREFOUR" en "Alimentation > Courses"

1. Automatisation → Règles de catégorisation → Nouvelle règle
2. Conditions :
   - Libellé contient "CARREFOUR"
3. Actions :
   - Catégorie : Alimentation
   - Sous-catégorie : Courses
4. Enregistrer

#### Créer une règle avancée

**Exemple** : Loyer mensuel

1. Nouvelle règle
2. Conditions (ET) :
   - Libellé contient "LOYER"
   - Montant = -850.00€
   - Type = Débit
3. Actions :
   - Catégorie : Logement
   - Sous-catégorie : Loyer
   - Tiers : "Agence Immobilière"
4. Enregistrer

#### Priorité des règles

Les règles sont appliquées par ordre de priorité (1 = priorité max).

Pour réorganiser :
1. Règles de catégorisation
2. Glisser-déposer pour réordonner
3. Enregistrer

### Transactions récurrentes

#### Créer une récurrence

**Exemple** : Salaire mensuel

1. Automatisation → Transactions récurrentes → Nouvelle
2. Informations :
   - Nom : "Salaire"
   - Compte : Compte courant
   - Type : Crédit
   - Montant : 2500.00€
   - Catégorie : Revenus > Salaire
3. Récurrence :
   - Fréquence : Mensuelle
   - Jour du mois : 1er
   - Date de début : 01/01/2025
   - Date de fin : Illimitée
4. Enregistrer

#### Fréquences disponibles
- **Quotidienne** : Tous les X jours
- **Hebdomadaire** : Tous les lundis, mardis, etc.
- **Mensuelle** : Le Xème jour du mois
- **Annuelle** : Le JJ/MM de chaque année

#### Exécution des récurrences

Les récurrences sont exécutées automatiquement tous les jours à minuit.

Pour exécuter manuellement :
1. Transactions récurrentes
2. Sélectionner la récurrence
3. Bouton "Exécuter maintenant"

### Règles de tiers

Associer automatiquement un tiers selon le libellé :

1. Automatisation → Règles de tiers → Nouvelle
2. Conditions :
   - Libellé contient "CARREFOUR"
3. Actions :
   - Tiers : Carrefour (créer si nécessaire)
4. Enregistrer

---

## Rapports et statistiques

### Rapports prédéfinis

#### Rapport mensuel

Vue d'ensemble du mois :
- Total des dépenses
- Total des revenus
- Solde net
- Répartition par catégorie
- Évolution vs mois précédent

Accès : Rapports → Rapport mensuel

#### Rapport annuel

Synthèse de l'année :
- Évolution mensuelle
- Top 10 des dépenses
- Catégories principales
- Économies réalisées

Accès : Rapports → Rapport annuel

#### Rapport par catégorie

Détail d'une catégorie sur une période :
- Montant total
- Nombre de transactions
- Moyenne par transaction
- Évolution dans le temps

Accès : Rapports → Par catégorie

### Créer un rapport personnalisé

1. **Rapports** → Nouveau rapport
2. **Paramètres** :
   - Période (dates)
   - Comptes sélectionnés
   - Catégories incluses/exclues
   - Type de transactions
3. **Graphiques** :
   - Choisir les graphiques à afficher
   - Camembert, courbes, histogrammes
4. **Générer**

### Graphiques disponibles

- **Camembert** : Répartition par catégorie
- **Courbe** : Évolution dans le temps
- **Histogramme** : Comparaison par période
- **Jauge** : Budgets vs consommation
- **Tableau** : Données détaillées

### Exporter un rapport

Formats disponibles :
- **PDF** : Impression, archivage
- **Excel** : Analyse approfondie
- **CSV** : Import dans d'autres outils
- **Image PNG** : Partage des graphiques

Procédure :
1. Générer le rapport
2. Cliquer "Exporter"
3. Choisir le format
4. Télécharger

### Rapports programmés

Recevoir automatiquement un rapport par email :

1. Rapports → Programmation
2. Nouveau rapport programmé
3. Configuration :
   - Type de rapport
   - Fréquence (quotidien, hebdo, mensuel)
   - Destinataires
   - Format (PDF, Excel)
4. Enregistrer

Exemple : Rapport mensuel envoyé le 1er de chaque mois.

---

## Profil et paramètres

### Profil utilisateur

#### Informations personnelles

1. Menu utilisateur → Profil
2. Modifier :
   - Nom, prénom
   - Email
   - Avatar
3. Enregistrer

#### Changer le mot de passe

1. Profil → Sécurité
2. Mot de passe actuel
3. Nouveau mot de passe
4. Confirmer
5. Enregistrer

⚠️ **Sécurité** : Utilisez un mot de passe fort (8+ caractères, majuscules, chiffres, symboles).

### Paramètres de l'application

#### Préférences d'affichage

1. Paramètres → Affichage
2. Options :
   - Thème (Clair, Sombre, Auto)
   - Langue (Français, English)
   - Devise (EUR, USD, GBP)
   - Format de date
   - Premier jour de la semaine
3. Enregistrer

#### Notifications

1. Paramètres → Notifications
2. Activer/Désactiver :
   - Alertes de budget
   - Nouvelles transactions
   - Rapports programmés
   - Mises à jour
3. Canal (Email, In-app)
4. Enregistrer

#### Sauvegardes

1. Paramètres → Sauvegardes
2. Options :
   - Sauvegarde automatique (quotidienne)
   - Exporter toutes les données
   - Importer une sauvegarde
3. Télécharger la sauvegarde (JSON)

### Administration (Super Admin)

#### Gestion des utilisateurs

1. Administration → Utilisateurs
2. Actions :
   - Créer un utilisateur
   - Modifier les droits
   - Désactiver/Réactiver
   - Supprimer

#### Configuration système

1. Administration → Configuration
2. Paramètres :
   - Nom de l'application
   - Logo personnalisé
   - Devise par défaut
   - Format de date
   - Langue par défaut
3. Enregistrer

#### Logs et débogage

1. Administration → Logs
2. Consultation :
   - Logs d'application
   - Logs d'erreurs
   - Logs d'authentification
3. Filtrer par date/niveau
4. Télécharger les logs

---

## Astuces et bonnes pratiques

### 💡 Optimiser la catégorisation

1. **Créez des règles progressivement** : Commencez par les transactions les plus fréquentes
2. **Utilisez des mots-clés courts** : "CARREFOUR" plutôt que "CARREFOUR MARKET PARIS 15"
3. **Testez vos règles** : Vérifiez qu'elles ne catégorisent pas par erreur

### 💡 Gérer efficacement vos budgets

1. **Commencez large** : Budget global par grande catégorie
2. **Affinez progressivement** : Sous-catégories une fois le rythme pris
3. **Révisez mensuellement** : Ajustez selon votre consommation réelle
4. **Prévoyez une marge** : 10-15% de buffer

### 💡 Imports réguliers

1. **Importez hebdomadairement** : Évite l'accumulation
2. **Vérifiez le mapping** : Assurez-vous que les colonnes sont correctes
3. **Catégorisez rapidement** : Ne laissez pas s'accumuler les transactions non catégorisées

### 💡 Sécurité

1. **Sauvegardez régulièrement** : Export mensuel de vos données
2. **Utilisez un mot de passe fort** : Changez-le tous les 6 mois
3. **Vérifiez les accès** : Qui a accès à votre instance ?

---

## Raccourcis clavier

| Raccourci | Action |
|-----------|--------|
| `Ctrl + N` | Nouvelle transaction |
| `Ctrl + I` | Import de fichier |
| `Ctrl + S` | Enregistrer |
| `Ctrl + F` | Recherche |
| `Échap` | Fermer modal/annuler |
| `?` | Afficher l'aide contextuelle |

---

## Limites et contraintes

### Limites techniques

- **Import CSV** : 10 000 transactions max par fichier
- **Fichiers** : 5 Mo max
- **Comptes** : Illimité
- **Transactions** : Illimité
- **Règles** : 100 max recommandé

### Formats non supportés

- Fichiers PDF bancaires (extraire en CSV)
- Formats propriétaires spécifiques
- Images de relevés

### Performance

Pour de meilleures performances :
- Archivez les anciennes transactions (> 3 ans)
- Limitez le nombre de règles actives
- Évitez les recherches sur de très longues périodes

---

**Dernière mise à jour** : 12 novembre 2025  
**Version** : 2.0.0
