-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: monbudget
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'ID utilisateur (NULL si non authentifi??)',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''action (login, update, delete, etc.)',
  `table_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Table concern??e',
  `record_id` int DEFAULT NULL COMMENT 'ID de l''enregistrement concern??',
  `old_values` text COLLATE utf8mb4_unicode_ci COMMENT 'Valeurs avant modification (JSON)',
  `new_values` text COLLATE utf8mb4_unicode_ci COMMENT 'Valeurs apr??s modification (JSON)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Adresse IP (IPv4 ou IPv6)',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User-Agent du navigateur',
  `request_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URI de la requ??te',
  `request_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'M??thode HTTP (GET, POST, etc.)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_user_action_date` (`user_id`,`action`,`created_at` DESC),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journalisation d''audit pour conformit?? PCI DSS (Exigence 10)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_logs`
--

DROP TABLE IF EXISTS `auth_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `action` enum('login_success','login_failed','logout','password_reset','account_locked') COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_created` (`created_at`),
  KEY `idx_action` (`action`)
) ENGINE=MyISAM AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banques`
--

DROP TABLE IF EXISTS `banques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_banque` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bic` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `adresse_ligne1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_ligne2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'France',
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beneficiaires`
--

DROP TABLE IF EXISTS `beneficiaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beneficiaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_beneficiaire` enum('crediteur','debiteur','mixte') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'crediteur',
  `categorie` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `favoris` tinyint(1) DEFAULT '0',
  `actif` tinyint(1) DEFAULT '1',
  `nb_transactions` int DEFAULT '0',
  `dernier_usage` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type_beneficiaire`),
  KEY `idx_categorie` (`categorie`),
  KEY `idx_nom` (`nom`),
  KEY `idx_actif` (`actif`),
  KEY `idx_favoris` (`favoris`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beneficiaires_backup`
--

DROP TABLE IF EXISTS `beneficiaires_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beneficiaires_backup` (
  `id` int NOT NULL DEFAULT '0',
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raison_sociale` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_beneficiaire` enum('particulier','entreprise','organisme') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'particulier',
  `iban` varchar(34) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bic` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_banque` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_compte` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_ligne1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_ligne2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'France',
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categorie` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `favoris` tinyint(1) DEFAULT '0',
  `actif` tinyint(1) DEFAULT '1',
  `total_virements` int DEFAULT '0',
  `montant_total_vire` decimal(15,2) DEFAULT '0.00',
  `dernier_virement` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `categorie_id` int DEFAULT NULL,
  `sous_categorie_id` int DEFAULT NULL,
  `montant_prevu` decimal(15,2) NOT NULL,
  `montant_depense` decimal(15,2) DEFAULT '0.00',
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `type_budget` enum('mensuel','trimestriel','semestriel','annuel','personnalise') COLLATE utf8mb4_unicode_ci DEFAULT 'mensuel',
  `alerte_seuil` decimal(5,2) DEFAULT '80.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sous_categorie_id` (`sous_categorie_id`),
  KEY `idx_categorie_id` (`categorie_id`),
  KEY `idx_periode` (`periode_debut`,`periode_fin`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budgets_suivi`
--

DROP TABLE IF EXISTS `budgets_suivi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets_suivi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `budget_id` int NOT NULL,
  `periode_debut` date NOT NULL COMMENT 'DÔö£┬«but de la pÔö£┬«riode de suivi',
  `periode_fin` date NOT NULL COMMENT 'Fin de la pÔö£┬«riode de suivi',
  `montant_prevu` decimal(10,2) NOT NULL COMMENT 'Montant budgÔö£┬«tÔö£┬« pour cette pÔö£┬«riode',
  `montant_depense` decimal(10,2) DEFAULT '0.00' COMMENT 'Montant rÔö£┬«ellement dÔö£┬«pensÔö£┬«',
  `montant_restant` decimal(10,2) GENERATED ALWAYS AS ((`montant_prevu` - `montant_depense`)) STORED,
  `pourcentage_utilise` decimal(5,2) GENERATED ALWAYS AS (((`montant_depense` / `montant_prevu`) * 100)) STORED,
  `statut` enum('ok','alerte','depassement') COLLATE utf8mb4_unicode_ci DEFAULT 'ok' COMMENT 'Statut basÔö£┬« sur seuil alerte',
  `derniere_maj` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_budget_periode` (`budget_id`,`periode_debut`,`periode_fin`),
  KEY `idx_suivi_periode` (`periode_debut`,`periode_fin`),
  KEY `idx_suivi_budget` (`budget_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Suivi en temps rÔö£┬«el des budgets vs dÔö£┬«penses rÔö£┬«elles';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007bff',
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('depense','recette','mixte') COLLATE utf8mb4_unicode_ci DEFAULT 'depense',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorization_rules`
--

DROP TABLE IF EXISTS `categorization_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorization_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `conditions` json NOT NULL,
  `categorie_id` int DEFAULT NULL,
  `sous_categorie_id` int DEFAULT NULL,
  `priorite` int DEFAULT '50',
  `confiance` int DEFAULT '80',
  `active` tinyint(1) DEFAULT '1',
  `nb_utilisations` int DEFAULT '0',
  `derniere_utilisation` datetime DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comptes`
--

DROP TABLE IF EXISTS `comptes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comptes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `banque_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_compte` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iban` varchar(34) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_compte` enum('courant','epargne','livret','pea','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'courant',
  `solde_initial` decimal(15,2) DEFAULT '0.00',
  `solde_actuel` decimal(15,2) DEFAULT '0.00',
  `devise` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'EUR',
  `date_ouverture` date DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `titulaire_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_banque_id` (`banque_id`),
  KEY `idx_actif` (`actif`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comptes_titulaires`
--

DROP TABLE IF EXISTS `comptes_titulaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comptes_titulaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compte_id` int NOT NULL,
  `titulaire_id` int NOT NULL,
  `type_titulaire` enum('principal','co_titulaire','mandataire') COLLATE utf8mb4_unicode_ci DEFAULT 'principal',
  `date_ajout` date DEFAULT (curdate()),
  `actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_compte_titulaire` (`compte_id`,`titulaire_id`),
  KEY `titulaire_id` (`titulaire_id`),
  KEY `idx_compte_titulaire` (`compte_id`,`titulaire_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imports`
--

DROP TABLE IF EXISTS `imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compte_id` int NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_fichier` enum('ofx','csv') COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille_fichier` int DEFAULT NULL,
  `nb_transactions` int DEFAULT '0',
  `nb_nouvelles_transactions` int DEFAULT '0',
  `nb_doublons` int DEFAULT '0',
  `statut` enum('en_cours','termine','erreur') COLLATE utf8mb4_unicode_ci DEFAULT 'en_cours',
  `message_erreur` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_compte_id` (`compte_id`),
  KEY `idx_statut` (`statut`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parametres`
--

DROP TABLE IF EXISTS `parametres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('string','integer','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_history`
--

DROP TABLE IF EXISTS `password_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash du mot de passe (bcrypt)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_created` (`user_id`,`created_at` DESC),
  CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des mots de passe utilisateurs (PCI DSS 8.2.5)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recurrences`
--

DROP TABLE IF EXISTS `recurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurrences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compte_id` int NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `categorie_id` int DEFAULT NULL,
  `sous_categorie_id` int DEFAULT NULL,
  `type_operation` enum('debit','credit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequence` enum('hebdomadaire','mensuelle','trimestrielle','semestrielle','annuelle') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jour_execution` int DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `derniere_execution` date DEFAULT NULL,
  `prochaine_execution` date DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categorie_id` (`categorie_id`),
  KEY `sous_categorie_id` (`sous_categorie_id`),
  KEY `idx_compte_id` (`compte_id`),
  KEY `idx_prochaine_execution` (`prochaine_execution`),
  KEY `idx_actif` (`actif`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sous_categories`
--

DROP TABLE IF EXISTS `sous_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sous_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categorie_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_categorie_id` (`categorie_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiers_identification_rules`
--

DROP TABLE IF EXISTS `tiers_identification_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tiers_identification_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `patterns` json NOT NULL,
  `tiers_id` int DEFAULT NULL,
  `tiers_nom_auto` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_beneficiaire_auto` enum('crediteur','debiteur','mixte') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priorite` int DEFAULT '1',
  `confiance` int DEFAULT '80',
  `active` tinyint(1) DEFAULT '1',
  `montant_min` decimal(15,2) DEFAULT NULL,
  `montant_max` decimal(15,2) DEFAULT NULL,
  `type_operation` enum('debit','credit','virement') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_utilisations` int DEFAULT '0',
  `derniere_utilisation` datetime DEFAULT NULL,
  `taux_succes` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`),
  KEY `idx_priorite` (`priorite`),
  KEY `idx_confiance` (`confiance`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `titulaires`
--

DROP TABLE IF EXISTS `titulaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titulaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `civilite` enum('M','Mme','Mlle') COLLATE utf8mb4_unicode_ci DEFAULT 'M',
  `adresse_ligne1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse_ligne2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pays` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'France',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `profession` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_titulaire` (`nom`,`prenom`,`date_naissance`),
  KEY `idx_nom_prenom` (`nom`,`prenom`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compte_id` int NOT NULL,
  `compte_destination_id` int DEFAULT NULL,
  `date_transaction` date NOT NULL,
  `date_valeur` date DEFAULT NULL,
  `montant` decimal(15,2) NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `numero_operation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_banque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categorie_id` int DEFAULT NULL,
  `sous_categorie_id` int DEFAULT NULL,
  `type_operation` enum('debit','credit','virement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `moyen_paiement` enum('virement','virement_interne','prelevement','carte','cheque','especes','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'autre',
  `statut_virement` enum('en_attente','effectue','echoue','annule') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beneficiaire` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrente` tinyint(1) DEFAULT '0',
  `recurrence_id` int DEFAULT NULL,
  `importee` tinyint(1) DEFAULT '0',
  `fichier_import` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validee` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rule_applied` int DEFAULT NULL COMMENT 'ID de la r├¿gle appliqu├®e pour auto-cat├®gorisation',
  PRIMARY KEY (`id`),
  KEY `sous_categorie_id` (`sous_categorie_id`),
  KEY `idx_compte_id` (`compte_id`),
  KEY `idx_date_transaction` (`date_transaction`),
  KEY `idx_categorie_id` (`categorie_id`),
  KEY `idx_montant` (`montant`),
  KEY `idx_type_operation` (`type_operation`),
  KEY `idx_recurrente` (`recurrente`),
  KEY `idx_recurrence_id` (`recurrence_id`),
  KEY `fk_rule_applied` (`rule_applied`),
  KEY `idx_compte_destination` (`compte_destination_id`)
) ENGINE=MyISAM AUTO_INCREMENT=512 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions_recurrentes`
--

DROP TABLE IF EXISTS `transactions_recurrentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions_recurrentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom descriptif de la r??currence',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Description d??taill??e',
  `compte_id` int NOT NULL COMMENT 'Compte concern??',
  `montant` decimal(15,2) NOT NULL COMMENT 'Montant de la transaction',
  `libelle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Libell?? standard',
  `categorie_id` int DEFAULT NULL COMMENT 'Cat??gorie par d??faut',
  `sous_categorie_id` int DEFAULT NULL COMMENT 'Sous-cat??gorie par d??faut',
  `type_operation` enum('debit','credit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''op??ration',
  `moyen_paiement` enum('virement','prelevement','carte','cheque','especes','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'autre' COMMENT 'Moyen de paiement',
  `beneficiaire` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'B??n??ficiaire/??metteur',
  `frequence` enum('quotidien','hebdomadaire','mensuel','trimestriel','semestriel','annuel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Fr??quence de r??p??tition',
  `intervalle` int DEFAULT '1' COMMENT 'Intervalle (ex: tous les 2 mois)',
  `jour_execution` int DEFAULT NULL COMMENT 'Jour du mois pour mensuel (1-31)',
  `jour_semaine` int DEFAULT NULL COMMENT 'Jour de la semaine pour hebdomadaire (1-7)',
  `date_debut` date NOT NULL COMMENT 'Date de d??but de la r??currence',
  `date_fin` date DEFAULT NULL COMMENT 'Date de fin optionnelle',
  `prochaine_execution` date NOT NULL COMMENT 'Date de la prochaine ex??cution',
  `derniere_execution` date DEFAULT NULL COMMENT 'Date de la derni??re ex??cution',
  `notification_avant` int DEFAULT '0' COMMENT 'Jours avant pour notification (0 = pas de notification)',
  `auto_validation` tinyint(1) DEFAULT '1' COMMENT 'Valider automatiquement les transactions g??n??r??es',
  `tolerance_weekend` enum('aucune','jour_ouvre_suivant','jour_ouvre_precedent') COLLATE utf8mb4_unicode_ci DEFAULT 'jour_ouvre_suivant' COMMENT 'Gestion des weekends',
  `active` tinyint(1) DEFAULT '1' COMMENT 'R??currence active',
  `nb_executions` int DEFAULT '0' COMMENT 'Nombre d''ex??cutions r??alis??es',
  `nb_executions_max` int DEFAULT NULL COMMENT 'Nombre maximum d''ex??cutions (optionnel)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_compte_id` (`compte_id`),
  KEY `idx_prochaine_execution` (`prochaine_execution`),
  KEY `idx_active` (`active`),
  KEY `idx_categorie_id` (`categorie_id`),
  KEY `idx_sous_categorie_id` (`sous_categorie_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='D??finition des transactions r??currentes automatiques';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions_recurrentes_executions`
--

DROP TABLE IF EXISTS `transactions_recurrentes_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions_recurrentes_executions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recurrence_id` int NOT NULL COMMENT 'R??f??rence vers la r??currence',
  `date_prevue` date NOT NULL COMMENT 'Date pr??vue d''ex??cution',
  `date_execution` datetime DEFAULT NULL COMMENT 'Date/heure r??elle d''ex??cution',
  `transaction_id` int DEFAULT NULL COMMENT 'ID de la transaction g??n??r??e',
  `statut` enum('planifiee','executee','echouee','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'planifiee' COMMENT 'Statut de l''ex??cution',
  `message_erreur` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Message d''erreur en cas d''??chec',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recurrence_id` (`recurrence_id`),
  KEY `idx_date_prevue` (`date_prevue`),
  KEY `idx_statut` (`statut`),
  KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des ex??cutions de transactions r??currentes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `login_attempts` int DEFAULT '0',
  `last_login_attempt` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `password_expires_at` datetime DEFAULT NULL COMMENT 'Date d''expiration du mot de passe (90 jours)',
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Nombre de tentatives ??chou??es cons??cutives',
  `locked_until` datetime DEFAULT NULL COMMENT 'Compte verrouill?? jusqu''?? cette date',
  `last_password_change` datetime DEFAULT NULL COMMENT 'Date du dernier changement de mot de passe',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Forcer le changement de mot de passe ?? la prochaine connexion',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_locked_until` (`locked_until`),
  KEY `idx_password_expires_at` (`password_expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `v_budgets_complets`
--

DROP TABLE IF EXISTS `v_budgets_complets`;
/*!50001 DROP VIEW IF EXISTS `v_budgets_complets`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_budgets_complets` AS SELECT 
 1 AS `id`,
 1 AS `nom`,
 1 AS `montant_prevu`,
 1 AS `periode_type`,
 1 AS `date_debut`,
 1 AS `date_fin`,
 1 AS `seuil_alerte`,
 1 AS `actif`,
 1 AS `categorie_nom`,
 1 AS `categorie_couleur`,
 1 AS `categorie_icone`,
 1 AS `sous_categorie_nom`,
 1 AS `sous_categorie_couleur`,
 1 AS `sous_categorie_icone`,
 1 AS `montant_depense`,
 1 AS `montant_restant`,
 1 AS `pourcentage_utilise`,
 1 AS `statut`,
 1 AS `periode_statut`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `virements_internes`
--

DROP TABLE IF EXISTS `virements_internes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `virements_internes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compte_source_id` int NOT NULL,
  `compte_destination_id` int NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `motif` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('en_attente','effectue','echoue','annule') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_programmee` datetime NOT NULL,
  `date_execution` datetime DEFAULT NULL,
  `tiers_id` int DEFAULT NULL,
  `categorie_id` int DEFAULT NULL,
  `sous_categorie_id` int DEFAULT NULL,
  `solde_source_avant` decimal(15,2) DEFAULT NULL,
  `solde_source_apres` decimal(15,2) DEFAULT NULL,
  `solde_dest_avant` decimal(15,2) DEFAULT NULL,
  `solde_dest_apres` decimal(15,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_compte_source` (`compte_source_id`),
  KEY `idx_compte_destination` (`compte_destination_id`),
  KEY `idx_date_programmee` (`date_programmee`),
  KEY `idx_statut` (`statut`),
  KEY `idx_tiers` (`tiers_id`),
  KEY `idx_categorie` (`categorie_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `v_budgets_complets`
--

/*!50001 DROP VIEW IF EXISTS `v_budgets_complets`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `v_budgets_complets` AS select `b`.`id` AS `id`,`b`.`nom` AS `nom`,`b`.`montant_prevu` AS `montant_prevu`,`b`.`type_budget` AS `periode_type`,`b`.`periode_debut` AS `date_debut`,`b`.`periode_fin` AS `date_fin`,`b`.`alerte_seuil` AS `seuil_alerte`,true AS `actif`,`c`.`nom` AS `categorie_nom`,`c`.`couleur` AS `categorie_couleur`,`c`.`icone` AS `categorie_icone`,`sc`.`nom` AS `sous_categorie_nom`,`sc`.`couleur` AS `sous_categorie_couleur`,`sc`.`icone` AS `sous_categorie_icone`,coalesce(`depenses`.`montant_depense`,0.00) AS `montant_depense`,(`b`.`montant_prevu` - coalesce(`depenses`.`montant_depense`,0.00)) AS `montant_restant`,(case when (`b`.`montant_prevu` > 0) then ((coalesce(`depenses`.`montant_depense`,0.00) / `b`.`montant_prevu`) * 100) else 0 end) AS `pourcentage_utilise`,(case when (`b`.`montant_prevu` <= 0) then 'invalide' when (coalesce(`depenses`.`montant_depense`,0.00) >= `b`.`montant_prevu`) then 'depassement' when ((coalesce(`depenses`.`montant_depense`,0.00) / `b`.`montant_prevu`) >= (`b`.`alerte_seuil` / 100)) then 'alerte' else 'ok' end) AS `statut`,(case when (`b`.`periode_fin` < curdate()) then 'expire' when (`b`.`periode_debut` > curdate()) then 'futur' else 'actuel' end) AS `periode_statut` from (((`budgets` `b` left join `categories` `c` on((`b`.`categorie_id` = `c`.`id`))) left join `sous_categories` `sc` on((`b`.`sous_categorie_id` = `sc`.`id`))) left join (select (case when (`b2`.`sous_categorie_id` is not null) then (case when ((`t`.`categorie_id` = `b2`.`categorie_id`) and (`t`.`sous_categorie_id` = `b2`.`sous_categorie_id`)) then `b2`.`id` else NULL end) else (case when (`t`.`categorie_id` = `b2`.`categorie_id`) then `b2`.`id` else NULL end) end) AS `budget_id`,sum((case when (`t`.`montant` > 0) then `t`.`montant` else 0 end)) AS `montant_depense` from (`transactions` `t` join `budgets` `b2`) where ((`t`.`date_transaction` between `b2`.`periode_debut` and `b2`.`periode_fin`) and (`t`.`montant` is not null) and (`t`.`montant` <> 0)) group by `budget_id` having (`budget_id` is not null)) `depenses` on((`b`.`id` = `depenses`.`budget_id`))) order by `b`.`periode_debut` desc,`b`.`nom` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-20 23:51:59
