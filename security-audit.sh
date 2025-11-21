#!/bin/bash

# Script d'audit de sécurité pour MonBudget v2
# Compatible Linux/macOS
# Usage: ./security-audit.sh

set -e

echo "=== MonBudget v2 - Audit de Sécurité ==="
echo ""

ERRORS=0
WARNINGS=0

# Vérifier database.sql ne contient pas de données sensibles
echo "🔍 Vérification de database.sql..."

if [ ! -f "database.sql" ]; then
    echo "⚠️  WARNING: database.sql non trouvé"
    ((WARNINGS++))
else
    # Vérifier absence de INSERT INTO
    if grep -qE "^INSERT INTO" database.sql; then
        echo "❌ ERREUR: database.sql contient des INSERT INTO (données présentes)"
        ((ERRORS++))
    else
        echo "✅ database.sql ne contient aucune donnée (structure uniquement)"
    fi
    
    # Vérifier absence d'IBAN
    if grep -qiE "FR[0-9]{2}[A-Z0-9]{23}" database.sql; then
        echo "❌ ERREUR: Possible IBAN détecté dans database.sql"
        ((ERRORS++))
    fi
    
    # Vérifier absence d'emails
    if grep -qE "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" database.sql 2>/dev/null | grep -vE "(userfirst@monbudget\.local|CONSTRAINT|FOREIGN KEY)" > /dev/null; then
        echo "⚠️  WARNING: Possible email détecté dans database.sql"
        ((WARNINGS++))
    fi
    
    # Vérifier encodage UTF-8 sans BOM
    if command -v file > /dev/null; then
        encoding=$(file -b --mime-encoding database.sql)
        if [ "$encoding" != "us-ascii" ] && [ "$encoding" != "utf-8" ]; then
            echo "⚠️  WARNING: database.sql n'est pas en UTF-8 (détecté: $encoding)"
            ((WARNINGS++))
        else
            echo "✅ database.sql est correctement encodé"
        fi
    fi
fi

# Vérifier .env n'est pas commité
echo ""
echo "🔍 Vérification des fichiers sensibles..."

if git ls-files --error-unmatch .env > /dev/null 2>&1; then
    echo "❌ ERREUR: .env est commité dans Git (risque de fuite de credentials)"
    ((ERRORS++))
else
    echo "✅ .env n'est pas commité"
fi

if git ls-files --error-unmatch config/installed.json > /dev/null 2>&1; then
    echo "⚠️  WARNING: config/installed.json est commité"
    ((WARNINGS++))
else
    echo "✅ config/installed.json n'est pas commité"
fi

# Vérifier les fichiers uploads/
echo ""
echo "🔍 Vérification du dossier uploads/..."

if [ -d "uploads" ]; then
    upload_count=$(find uploads -type f \( -name "*.csv" -o -name "*.ofx" \) 2>/dev/null | wc -l)
    if [ "$upload_count" -gt 0 ]; then
        echo "⚠️  WARNING: $upload_count fichiers CSV/OFX trouvés dans uploads/"
        ((WARNINGS++))
    else
        echo "✅ Aucun fichier CSV/OFX dans uploads/"
    fi
fi

# Vérifier permissions des fichiers sensibles
echo ""
echo "🔍 Vérification des permissions..."

if [ -f ".env" ]; then
    perms=$(stat -c "%a" .env 2>/dev/null || stat -f "%Lp" .env 2>/dev/null)
    if [ "$perms" != "600" ] && [ "$perms" != "400" ]; then
        echo "⚠️  WARNING: .env a les permissions $perms (recommandé: 600)"
        ((WARNINGS++))
    else
        echo "✅ .env a les bonnes permissions"
    fi
fi

# Résumé
echo ""
echo "=== Résumé de l'Audit ==="
echo "❌ Erreurs: $ERRORS"
echo "⚠️  Warnings: $WARNINGS"
echo ""

if [ $ERRORS -gt 0 ]; then
    echo "❌ AUDIT ÉCHOUÉ - Corrigez les erreurs avant de déployer"
    exit 1
else
    echo "✅ AUDIT RÉUSSI"
    if [ $WARNINGS -gt 0 ]; then
        echo "⚠️  Veuillez examiner les warnings"
    fi
    exit 0
fi
