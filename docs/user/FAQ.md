# FAQ - Questions Fréquentes

## Table des matières

1. [Généralités](#generalites)
2. [Installation et configuration](#installation-et-configuration)
3. [Comptes bancaires](#comptes-bancaires)
4. [Transactions](#transactions)
5. [Import de fichiers](#import-de-fichiers)
6. [Catégorisation](#categorisation)
7. [Budgets](#budgets)
8. [Automatisation](#automatisation)
9. [Rapports](#rapports)
10. [Problèmes courants](#problemes-courants)
11. [Sécurité](#securite)
12. [Astuces](#astuces)

---

## Généralités

### Qu'est-ce que MonBudget ?

MonBudget est une application web open-source de gestion financière personnelle. Elle vous permet de suivre vos comptes, catégoriser vos dépenses, créer des budgets et générer des rapports détaillés.

### L'application est-elle gratuite ?

Oui, MonBudget est totalement gratuite et open-source. Vous pouvez l'installer sur votre propre serveur.

### Mes données sont-elles sécurisées ?

Oui, MonBudget s'installe sur votre propre serveur. Vos données restent sous votre contrôle et ne sont jamais envoyées à des tiers. L'application utilise des connexions sécurisées (HTTPS recommandé) et un chiffrement des mots de passe.

### Puis-je utiliser MonBudget hors ligne ?

Non, MonBudget est une application web qui nécessite une connexion internet pour fonctionner. Cependant, vous pouvez exporter vos données pour consultation hors ligne.

### Sur quels appareils puis-je utiliser MonBudget ?

MonBudget est accessible depuis n'importe quel navigateur web moderne (Chrome, Firefox, Safari, Edge) sur :
- Ordinateur (Windows, Mac, Linux)
- Tablette (iPad, Android)
- Smartphone (iOS, Android)

---

## Installation et configuration

### Quels sont les prérequis pour installer MonBudget ?

- **Serveur web** : Apache ou Nginx
- **PHP** : Version 8.1 ou supérieure
- **Base de données** : MySQL 5.7+ ou MariaDB 10.3+
- **Composer** : Pour les dépendances PHP
- **Modules PHP** : PDO, mbstring, json, xml

Voir le [Guide d'installation](INSTALL.md) pour plus de détails.

### Comment effectuer la première installation ?

1. Télécharger les fichiers sur votre serveur
2. Créer une base de données MySQL
3. Accéder à `http://votre-domaine/setup`
4. Suivre l'assistant d'installation
5. Créer le compte administrateur

### Comment mettre à jour MonBudget ?

1. Sauvegarder vos données (Export)
2. Sauvegarder la base de données (mysqldump)
3. Remplacer les fichiers par la nouvelle version
4. Exécuter les migrations : `php migrate.php`
5. Vider le cache

### Comment réinitialiser le mot de passe admin ?

En ligne de commande :

```bash
php reset-password.php --email=admin@example.com
```

Ou directement dans la base de données :

```sql
UPDATE users SET password = '$2y$10$...' WHERE email = 'admin@example.com';
```

(Utilisez `password_hash()` pour générer le hash)

---

## Comptes bancaires

### Combien de comptes puis-je créer ?

Il n'y a pas de limite au nombre de comptes. Vous pouvez créer autant de comptes que nécessaire.

### Puis-je gérer plusieurs banques ?

Oui, vous pouvez ajouter des comptes de différentes banques. Chaque compte peut être associé à sa propre banque.

### Comment gérer un compte joint ?

1. Créer le compte
2. Ajouter plusieurs titulaires au compte
3. Chaque titulaire apparaît sur le RIB généré

### Le RIB généré est-il officiel ?

Non, le RIB PDF généré par MonBudget est une **copie d'information** uniquement. Il ne remplace pas le RIB officiel fourni par votre banque. Utilisez-le pour vos archives personnelles.

### Puis-je modifier le solde d'un compte ?

Le solde est calculé automatiquement à partir des transactions. Pour ajuster le solde :
1. Créer une transaction de type "Ajustement"
2. Saisir la différence (positive ou négative)
3. Catégorie : "Ajustement de solde"

### Comment archiver un compte fermé ?

1. Ouvrir le compte
2. Modifier le statut en "Fermé" ou "Archivé"
3. Le compte disparaît des vues principales mais reste consultable

---

## Transactions

### Comment saisir une transaction rapidement ?

Utilisez le raccourci clavier `Ctrl + N` ou le bouton "+" en haut à droite.

### Quelle est la différence entre Débit, Crédit et Virement ?

- **Débit** : Sortie d'argent (dépense) - montant négatif
- **Crédit** : Entrée d'argent (revenu) - montant positif
- **Virement** : Transfert entre deux de vos comptes (neutre pour le solde total)

### Comment enregistrer un virement entre mes comptes ?

1. Créer une transaction de type "Virement"
2. Compte source : Compte débité
3. Compte destination : Compte crédité
4. Le système crée automatiquement les deux transactions liées

### Puis-je modifier une transaction déjà importée ?

Oui, toutes les transactions peuvent être modifiées :
1. Ouvrir la transaction
2. Modifier les champs nécessaires
3. Enregistrer

### Comment supprimer plusieurs transactions en une fois ?

1. Liste des transactions
2. Cocher les transactions à supprimer
3. Actions groupées → Supprimer
4. Confirmer

⚠️ **Attention** : Cette action est irréversible.

### Puis-je ajouter des pièces jointes aux transactions ?

Actuellement, MonBudget ne supporte pas les pièces jointes. Vous pouvez ajouter des notes détaillées dans le champ "Notes".

### Comment retrouver une vieille transaction ?

Utilisez la recherche avancée avec des filtres de date :
1. Transactions → Recherche avancée
2. Date de début : 01/01/2020
3. Date de fin : 31/12/2020
4. Autres critères si nécessaire
5. Rechercher

---

## Import de fichiers

### Quels formats de fichiers puis-je importer ?

- **CSV** : Format texte séparé par virgules (le plus courant)
- **OFX** : Format Open Financial Exchange
- **QIF** : Format Quicken Interchange Format

### Ma banque n'est pas dans la liste des formats prédéfinis

Vous pouvez créer un profil d'import personnalisé :
1. Import → Configuration
2. Nouveau profil
3. Mapper les colonnes de votre fichier CSV
4. Enregistrer le profil

### Comment éviter les doublons lors de l'import ?

MonBudget détecte automatiquement les doublons en comparant :
- Date de la transaction
- Montant exact
- Libellé

Les transactions déjà présentes sont ignorées lors de l'import.

### L'import échoue avec un message d'erreur

Vérifiez :
1. **Encodage du fichier** : UTF-8 recommandé
2. **Séparateur CSV** : Virgule ou point-virgule
3. **Format des dates** : Doit correspondre au format configuré
4. **Colonnes obligatoires** : Date, Montant, Libellé minimum

Consultez les logs pour plus de détails : `storage/logs/import.log`

### Puis-je importer des fichiers Excel (.xlsx) ?

Non, Excel n'est pas supporté directement. Convertissez votre fichier en CSV :
1. Ouvrir le fichier dans Excel
2. Fichier → Enregistrer sous
3. Format : CSV (séparateur : point-virgule)
4. Enregistrer

### Mon fichier CSV a des colonnes supplémentaires

Pas de problème. Lors du mapping, ignorez simplement les colonnes non nécessaires. Seules les colonnes Date, Montant et Libellé sont obligatoires.

---

## Catégorisation

### Dois-je catégoriser toutes mes transactions ?

Ce n'est pas obligatoire, mais fortement recommandé pour :
- Générer des rapports significatifs
- Suivre vos budgets
- Analyser vos dépenses

### Comment catégoriser rapidement des centaines de transactions ?

1. **Créez des règles d'automatisation** : Les nouvelles transactions seront catégorisées automatiquement
2. **Catégorisation par lot** : Sélectionnez plusieurs transactions similaires et catégorisez-les en une fois
3. **Apprentissage** : Le système suggère des catégories basées sur l'historique

### Puis-je changer la catégorie d'une transaction déjà catégorisée ?

Oui, à tout moment :
1. Ouvrir la transaction
2. Modifier la catégorie
3. Enregistrer

Le changement est immédiat et impacte les rapports.

### Une transaction peut-elle avoir plusieurs catégories ?

Non, une transaction ne peut avoir qu'une seule catégorie principale (et éventuellement une sous-catégorie). Si une dépense concerne plusieurs catégories, créez des transactions séparées.

Exemple : Courses de 100€ → Séparer en 70€ Alimentation + 30€ Hygiène

### Comment créer une sous-catégorie ?

1. Catégories → Ouvrir la catégorie parente
2. Section "Sous-catégories"
3. Ajouter
4. Saisir le nom
5. Enregistrer

### Puis-je réorganiser mes catégories ?

Oui, par glisser-déposer dans l'interface Catégories. L'ordre n'affecte que l'affichage, pas les fonctionnalités.

---

## Budgets

### Comment définir un budget réaliste ?

1. **Analysez vos dépenses passées** : Consultez les rapports sur 3-6 mois
2. **Calculez la moyenne** : Moyenne des dépenses par catégorie
3. **Ajoutez une marge** : +10-15% de sécurité
4. **Ajustez progressivement** : Affinez selon les résultats

### Que se passe-t-il si je dépasse mon budget ?

- Une alerte s'affiche sur le tableau de bord
- Le budget apparaît en rouge
- Vous recevez une notification (si activée)
- Les dépenses continuent d'être enregistrées normalement

MonBudget ne bloque pas les dépenses, il vous informe uniquement.

### Puis-je créer un budget sur plusieurs mois ?

Oui, choisissez la période lors de la création :
- Mensuel : Budget pour 1 mois
- Trimestriel : Budget pour 3 mois
- Annuel : Budget pour 12 mois

### Comment reporter le budget non utilisé au mois suivant ?

MonBudget ne reporte pas automatiquement. Chaque période redémarre à zéro. Cela encourage une gestion mensuelle saine.

Si vous souhaitez accumuler, créez un budget annuel plutôt que mensuel.

### Puis-je exclure certaines transactions d'un budget ?

Non, toutes les transactions de la catégorie comptent dans le budget. Si vous voulez exclure certaines dépenses, créez une sous-catégorie spécifique sans budget.

---

## Automatisation

### Comment fonctionne la catégorisation automatique ?

Lors de l'import ou de la création d'une transaction, le système :
1. Vérifie si une règle correspond (libellé, montant, tiers)
2. Applique la catégorie définie dans la règle
3. Si plusieurs règles correspondent, applique celle avec la priorité la plus haute

### Mes règles ne fonctionnent pas

Vérifiez :
1. **Ordre de priorité** : La règle est-elle assez prioritaire ?
2. **Critères** : Le libellé exact correspond-il ?
3. **Activation** : La règle est-elle active ?
4. **Tests** : Testez avec une transaction manuelle

Consultez les logs : `storage/logs/automation.log`

### Comment créer une règle pour plusieurs variantes d'un même libellé ?

Utilisez des expressions régulières ou créez plusieurs règles :

**Option 1 - Mot-clé générique** :
- Libellé contient "CARREFOUR" → Catche "CARREFOUR MARKET", "CARREFOUR EXPRESS", etc.

**Option 2 - Plusieurs règles** :
- Règle 1 : "CARREFOUR MARKET" → Alimentation
- Règle 2 : "CARREFOUR EXPRESS" → Alimentation
- Règle 3 : "CARREFOUR CITY" → Alimentation

### Les transactions récurrentes ne s'exécutent pas

Vérifiez :
1. **Cron job configuré** : La tâche planifiée est-elle active ?
2. **Date de début** : La récurrence a-t-elle commencé ?
3. **Date de fin** : La récurrence n'est-elle pas terminée ?
4. **Logs** : `storage/logs/recurrences.log`

Pour exécuter manuellement : Administration → Tâches → Exécuter les récurrences

### Puis-je désactiver temporairement une récurrence ?

Oui :
1. Transactions récurrentes → Ouvrir la récurrence
2. Désactiver (bouton toggle)
3. Enregistrer

La récurrence ne s'exécutera plus jusqu'à réactivation.

### Comment modifier une transaction récurrente déjà créée ?

Les transactions créées par récurrence sont des transactions normales. Modifiez-les individuellement si nécessaire. Modifier la récurrence n'affecte que les futures transactions.

---

## Rapports

### Pourquoi mes rapports sont vides ?

Causes possibles :
1. **Aucune transaction** sur la période sélectionnée
2. **Filtres trop restrictifs** : Élargissez les critères
3. **Transactions non catégorisées** : Les rapports par catégorie ignorent les transactions sans catégorie

### Comment comparer deux périodes ?

1. Rapports → Comparaison
2. Période 1 : Ex. Janvier 2025
3. Période 2 : Ex. Janvier 2024
4. Générer

Le rapport affiche les différences en montant et pourcentage.

### Puis-je partager un rapport avec quelqu'un ?

Oui :
1. Générer le rapport
2. Exporter en PDF
3. Envoyer le fichier PDF par email

Vous pouvez aussi programmer l'envoi automatique par email.

### Les graphiques ne s'affichent pas correctement

Vérifiez :
1. **Navigateur à jour** : Chrome, Firefox, Safari récents
2. **JavaScript activé** : Nécessaire pour les graphiques
3. **Bloqueur de publicités** : Peut bloquer certaines bibliothèques
4. **Console du navigateur** : F12 → Onglet Console pour voir les erreurs

### Comment personnaliser les couleurs des graphiques ?

Les couleurs sont définies par catégorie :
1. Catégories → Modifier la catégorie
2. Choisir une couleur
3. Enregistrer

Les graphiques utiliseront automatiquement cette couleur.

---

## Problèmes courants

### J'ai oublié mon mot de passe

1. Page de connexion → "Mot de passe oublié"
2. Saisir votre email
3. Recevoir le lien de réinitialisation
4. Créer un nouveau mot de passe

Si l'email ne fonctionne pas, contactez l'administrateur.

### L'application est lente

Causes et solutions :
1. **Trop de transactions** : Archivez les anciennes données
2. **Cache plein** : Vider le cache (Paramètres → Cache)
3. **Serveur sous-dimensionné** : Augmenter les ressources
4. **Base de données** : Optimiser les index

### Erreur 500 - Internal Server Error

1. **Consultez les logs** : `storage/logs/error.log`
2. **Vérifiez les permissions** : Les dossiers `storage/` et `uploads/` doivent être accessibles en écriture
3. **PHP** : Vérifiez que tous les modules requis sont installés
4. **Base de données** : La connexion fonctionne-t-elle ?

### Les emails ne sont pas envoyés

Vérifiez la configuration SMTP :
1. Administration → Configuration → Email
2. Paramètres SMTP :
   - Hôte : smtp.gmail.com
   - Port : 587 (TLS) ou 465 (SSL)
   - Utilisateur et mot de passe
3. Tester l'envoi

### L'import échoue systématiquement

1. **Format du fichier** : Vérifiez l'encodage (UTF-8)
2. **Taille** : Maximum 5 Mo
3. **Colonnes** : Au minimum Date, Montant, Libellé
4. **Logs** : `storage/logs/import.log` pour les détails

### Je ne vois pas mes nouvelles transactions

1. **Actualisez la page** : F5 ou Ctrl+R
2. **Vérifiez le compte** : La bonne vue est-elle active ?
3. **Période** : Le filtre de date inclut-il la transaction ?
4. **Cache** : Videz le cache du navigateur

---

## Sécurité

### Mes données bancaires sont-elles sécurisées ?

MonBudget n'a **jamais accès** à vos comptes bancaires réels. Vous saisissez ou importez uniquement l'historique des transactions. L'application ne peut pas effectuer d'opérations bancaires.

### Dois-je utiliser HTTPS ?

**Oui, fortement recommandé** pour :
- Chiffrer les communications
- Protéger vos mots de passe
- Sécuriser vos données

Utilisez Let's Encrypt pour un certificat SSL gratuit.

### Comment sauvegarder mes données ?

**Méthode 1 - Export application** :
1. Paramètres → Sauvegardes
2. Exporter toutes les données
3. Télécharger le fichier JSON

**Méthode 2 - Base de données** :
```bash
mysqldump -u user -p monbudget > backup.sql
```

**Méthode 3 - Automatique** :
Configurez une sauvegarde quotidienne via cron.

### Qui peut accéder à mes données ?

- **Vous** : Accès complet à vos données
- **Administrateur système** : Accès technique au serveur
- **Personne d'autre** : Les données ne sont pas partagées

Si vous partagez l'instance avec d'autres utilisateurs, chacun ne voit que ses propres données.

### Comment supprimer définitivement mon compte ?

1. Administration → Utilisateurs → Mon compte
2. Supprimer le compte
3. Confirmer

⚠️ **Irréversible** : Toutes vos données seront définitivement supprimées.

---

## Astuces

### Catégorisation rapide avec raccourcis

Assignez des raccourcis clavier à vos catégories fréquentes :
1. Paramètres → Raccourcis
2. Catégorie : Alimentation → Touche : A
3. Lors de la saisie, appuyez sur A pour catégoriser

### Créez des vues personnalisées

Enregistrez vos recherches fréquentes :
1. Transactions → Recherche avancée
2. Définir les filtres (ex: "Dépenses > 100€")
3. Enregistrer comme favori
4. Accès rapide depuis le menu

### Utilisez les tags pour un suivi spécifique

Ajoutez des tags dans les notes des transactions :
- `#vacances` pour les dépenses de vacances
- `#projet` pour un projet spécifique
- `#remboursable` pour les frais professionnels

Puis recherchez par tag.

### Automatisez les imports

Configurez un script pour importer automatiquement :
```bash
# Télécharge le fichier de la banque
curl -o transactions.csv https://ma-banque.fr/export
# Importe dans MonBudget
php import-cli.php --file=transactions.csv --compte=1
```

### Partagez vos règles avec d'autres utilisateurs

Exportez vos règles :
1. Automatisation → Règles → Exporter
2. Fichier JSON généré
3. Partagez le fichier
4. Import par autre utilisateur : Règles → Importer

### Créez un budget "Enveloppe"

Pour gérer en enveloppes :
1. Créez une catégorie "Enveloppe - Loisirs"
2. Budget mensuel : 200€
3. À chaque dépense loisir, catégorisez dans cette enveloppe
4. Visualisez le reste disponible

### Utilisez les notes pour la compatibilité fiscale

Pour les indépendants/professionnels :
- Ajoutez des notes détaillées (client, projet, TVA)
- Exportez en CSV
- Importez dans votre logiciel comptable

### Dupliquez vos budgets d'un mois à l'autre

1. Budgets → Sélectionner le mois terminé
2. Actions → Dupliquer pour le mois suivant
3. Ajustez si nécessaire
4. Enregistrer

---

## Besoin d'aide supplémentaire ?

Si votre question n'est pas dans cette FAQ :

1. **Documentation complète** : Consultez le [Guide utilisateur](GUIDE.md)
2. **Logs** : Vérifiez les fichiers de log dans `storage/logs/`
3. **Support communautaire** : Forum ou chat
4. **Signaler un bug** : GitHub Issues

---

**Dernière mise à jour** : 12 novembre 2025  
**Version** : 2.0.0
