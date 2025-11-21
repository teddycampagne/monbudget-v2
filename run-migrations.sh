#!/bin/bash

# Script de migration automatique pour MonBudget v2
# Compatible Linux/macOS
# Usage: ./run-migrations.sh

set -e  # Arrêter en cas d'erreur

echo "=== MonBudget v2 - Exécution des migrations ==="
echo ""

# Charger la configuration depuis .env ou config/database.php
if [ -f .env ]; then
    source .env
    DB_HOST="${DB_HOST:-localhost}"
    DB_PORT="${DB_PORT:-3306}"
    DB_DATABASE="${DB_DATABASE:-monbudget_v2}"
    DB_USERNAME="${DB_USERNAME:-root}"
    DB_PASSWORD="${DB_PASSWORD:-}"
else
    echo "Fichier .env non trouvé. Veuillez configurer vos identifiants MySQL."
    read -p "Hôte MySQL [localhost]: " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    
    read -p "Port MySQL [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}
    
    read -p "Nom de la base de données [monbudget_v2]: " DB_DATABASE
    DB_DATABASE=${DB_DATABASE:-monbudget_v2}
    
    read -p "Utilisateur MySQL [root]: " DB_USERNAME
    DB_USERNAME=${DB_USERNAME:-root}
    
    read -sp "Mot de passe MySQL: " DB_PASSWORD
    echo ""
fi

# Construire la commande MySQL
MYSQL_CMD="mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME}"
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_CMD="$MYSQL_CMD -p${DB_PASSWORD}"
fi
MYSQL_CMD="$MYSQL_CMD ${DB_DATABASE}"

echo "Connexion à la base de données: ${DB_DATABASE}@${DB_HOST}:${DB_PORT}"
echo ""

# Vérifier la connexion
if ! echo "SELECT 1;" | $MYSQL_CMD > /dev/null 2>&1; then
    echo "❌ ERREUR: Impossible de se connecter à la base de données."
    exit 1
fi

# Créer la table _migrations si elle n'existe pas
echo "📋 Vérification de la table _migrations..."
echo "CREATE TABLE IF NOT EXISTS \`_migrations\` (
    \`id\` INT NOT NULL AUTO_INCREMENT,
    \`migration_name\` VARCHAR(255) NOT NULL,
    \`executed_at\` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (\`id\`),
    UNIQUE KEY \`migration_name\` (\`migration_name\`),
    KEY \`idx_executed_at\` (\`executed_at\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;" | $MYSQL_CMD

# Parcourir les fichiers de migration
MIGRATIONS_DIR="database/migrations"
EXECUTED_COUNT=0
SKIPPED_COUNT=0

if [ ! -d "$MIGRATIONS_DIR" ]; then
    echo "⚠️  Aucun dossier de migrations trouvé ($MIGRATIONS_DIR)"
    exit 0
fi

echo ""
echo "🔍 Recherche de nouvelles migrations..."
echo ""

for migration_file in "$MIGRATIONS_DIR"/*.sql; do
    if [ ! -f "$migration_file" ]; then
        continue
    fi
    
    migration_name=$(basename "$migration_file")
    
    # Vérifier si la migration a déjà été exécutée
    already_executed=$(echo "SELECT COUNT(*) FROM \`_migrations\` WHERE \`migration_name\` = '$migration_name';" | $MYSQL_CMD -s -N)
    
    if [ "$already_executed" -gt 0 ]; then
        echo "⏭️  Migration déjà exécutée: $migration_name"
        ((SKIPPED_COUNT++))
        continue
    fi
    
    echo "▶️  Exécution de: $migration_name"
    
    # Exécuter la migration
    if $MYSQL_CMD < "$migration_file"; then
        # Enregistrer la migration
        echo "INSERT INTO \`_migrations\` (\`migration_name\`) VALUES ('$migration_name');" | $MYSQL_CMD
        echo "✅ Migration réussie: $migration_name"
        ((EXECUTED_COUNT++))
    else
        echo "❌ ERREUR lors de l'exécution de: $migration_name"
        exit 1
    fi
    
    echo ""
done

echo ""
echo "=== Résumé ==="
echo "✅ Migrations exécutées: $EXECUTED_COUNT"
echo "⏭️  Migrations ignorées: $SKIPPED_COUNT"
echo ""
echo "✨ Terminé!"
