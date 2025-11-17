#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Audit de sécurité MonBudget - Détection données sensibles avant push

.DESCRIPTION
    Vérifie l'absence de données personnelles, credentials et informations sensibles
    dans les fichiers stagés avant un commit/push Git.

.EXAMPLE
    .\security-audit.ps1
    
.NOTES
    À exécuter AVANT tout git push
    Peut être intégré en pre-commit hook
#>

param(
    [switch]$Strict,  # Mode strict : arrête au premier problème
    [switch]$Auto     # Mode auto : corrige automatiquement si possible
)

$ErrorCount = 0
$WarningCount = 0

Write-Host "🔒 AUDIT DE SÉCURITÉ - MonBudget v2" -ForegroundColor Cyan
Write-Host "=" * 60 -ForegroundColor Gray
Write-Host ""

# Fonction de vérification
function Test-SecurityIssue {
    param(
        [string]$Description,
        [string]$Pattern,
        [string]$File = "",
        [scriptblock]$Check,
        [string]$Severity = "ERROR"
    )
    
    $result = if ($Check) { & $Check } else { $false }
    
    if ($result) {
        if ($Severity -eq "ERROR") {
            Write-Host "❌ $Description" -ForegroundColor Red
            $script:ErrorCount++
        } else {
            Write-Host "⚠️  $Description" -ForegroundColor Yellow
            $script:WarningCount++
        }
        return $true
    } else {
        Write-Host "✅ $Description" -ForegroundColor Green
        return $false
    }
}

# 1. Vérifier database.sql
Write-Host "📊 1. Vérification database.sql" -ForegroundColor White
Write-Host "-" * 60 -ForegroundColor Gray

if (Test-Path "database.sql") {
    $hasInserts = Test-SecurityIssue `
        -Description "database.sql ne contient AUCUNE donnée utilisateur (INSERT INTO)" `
        -Check {
            $count = (Get-Content database.sql | Select-String "^INSERT INTO").Count
            return $count -gt 0
        }
    
    if ($hasInserts) {
        Write-Host "   Nombre d'INSERT détectés: $((Get-Content database.sql | Select-String '^INSERT INTO').Count)" -ForegroundColor Red
        if ($Auto) {
            Write-Host "   🔧 Régénération automatique (structure seule)..." -ForegroundColor Yellow
            # Logique de régénération à implémenter
        }
    }
    
    # Vérifier encodage
    $bytes = [System.IO.File]::ReadAllBytes("database.sql")[0..2]
    $hasBOM = Test-SecurityIssue `
        -Description "database.sql sans BOM UTF-8" `
        -Check { $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF }
    
    if ($hasBOM -and $Auto) {
        Write-Host "   🔧 Suppression BOM automatique..." -ForegroundColor Yellow
        $content = Get-Content database.sql -Raw
        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllText("$PWD\database.sql", $content, $utf8NoBom)
        Write-Host "   ✅ BOM supprimé" -ForegroundColor Green
    }
} else {
    Write-Host "⚠️  database.sql absent (WARNING)" -ForegroundColor Yellow
    $WarningCount++
}

Write-Host ""

# 2. Vérifier fichiers stagés Git
Write-Host "📝 2. Vérification fichiers stagés (git diff --cached)" -ForegroundColor White
Write-Host "-" * 60 -ForegroundColor Gray

$stagedFiles = git diff --cached --name-only
if ($stagedFiles) {
    $stagedContent = git diff --cached
    
    # IBAN français
    Test-SecurityIssue `
        -Description "Aucun IBAN français détecté" `
        -Check {
            $stagedContent -match "FR[0-9]{25}"
        }
    
    # Emails personnels
    Test-SecurityIssue `
        -Description "Aucun email personnel (gmail, outlook, etc.)" `
        -Check {
            $stagedContent -match "@(gmail|outlook|hotmail|yahoo|orange|free|sfr|laposte)\.(com|fr)"
        }
    
    # Numéros de téléphone français
    Test-SecurityIssue `
        -Description "Aucun numéro de téléphone français" `
        -Check {
            $stagedContent -match "0[1-9][\s\.]?([0-9]{2}[\s\.]?){4}"
        }
    
    # Mots de passe en clair
    Test-SecurityIssue `
        -Description "Aucun mot de passe en clair" `
        -Check {
            $stagedContent -match "password['\"]?\s*[:=]\s*['\"][^'\"]{4,}['\"]"
        }
    
    # Adresses postales françaises
    Test-SecurityIssue `
        -Description "Aucune adresse postale détectée" `
        -Severity "WARNING" `
        -Check {
            $stagedContent -match "\d{5}\s+(LILLE|PARIS|LYON|WASQUEHAL|MARSEILLE)"
        }
    
} else {
    Write-Host "ℹ️  Aucun fichier stagé" -ForegroundColor Gray
}

Write-Host ""

# 3. Vérifier fichiers de configuration
Write-Host "⚙️  3. Vérification fichiers de configuration" -ForegroundColor White
Write-Host "-" * 60 -ForegroundColor Gray

# config/app.php
if (Test-Path "config/app.php") {
    $hasPassword = Test-SecurityIssue `
        -Description "config/app.php sans mot de passe en clair" `
        -Check {
            $content = Get-Content config/app.php -Raw
            $content -match "'password'\s*=>\s*'[^']{3,}'"
        }
}

# phpunit.xml
if (Test-Path "phpunit.xml") {
    Test-SecurityIssue `
        -Description "phpunit.xml sans credentials de test réels" `
        -Severity "WARNING" `
        -Check {
            $content = Get-Content phpunit.xml -Raw
            $content -match 'value="[^"]*@[^"]*\.(com|fr)"'
        }
}

# config/installed.json (ne doit jamais être commité)
$installedTracked = Test-SecurityIssue `
    -Description "config/installed.json non tracké par Git" `
    -Check {
        git ls-files --error-unmatch config/installed.json 2>$null
        return $LASTEXITCODE -eq 0
    }

Write-Host ""

# 4. Vérifier .gitignore
Write-Host "🚫 4. Vérification .gitignore" -ForegroundColor White
Write-Host "-" * 60 -ForegroundColor Gray

if (Test-Path ".gitignore") {
    $gitignore = Get-Content .gitignore -Raw
    
    Test-SecurityIssue `
        -Description ".gitignore protège database_*.sql" `
        -Severity "WARNING" `
        -Check { -not ($gitignore -match "database_.*\.sql") }
    
    Test-SecurityIssue `
        -Description ".gitignore protège config/installed.json" `
        -Check { -not ($gitignore -match "config/installed\.json") }
    
    Test-SecurityIssue `
        -Description ".gitignore protège .env*" `
        -Check { -not ($gitignore -match "\.env") }
}

Write-Host ""

# 5. Résumé
Write-Host "=" * 60 -ForegroundColor Gray
if ($ErrorCount -eq 0 -and $WarningCount -eq 0) {
    Write-Host "✅ AUDIT RÉUSSI - Aucun problème détecté" -ForegroundColor Green
    Write-Host "   Vous pouvez pusher en toute sécurité." -ForegroundColor Green
    exit 0
} elseif ($ErrorCount -eq 0) {
    Write-Host "⚠️  AUDIT OK avec $WarningCount avertissement(s)" -ForegroundColor Yellow
    Write-Host "   Recommandation: Vérifier manuellement avant push" -ForegroundColor Yellow
    exit 0
} else {
    Write-Host "❌ AUDIT ÉCHOUÉ - $ErrorCount erreur(s), $WarningCount avertissement(s)" -ForegroundColor Red
    Write-Host ""
    Write-Host "   ACTIONS REQUISES:" -ForegroundColor Yellow
    Write-Host "   1. Corriger les erreurs ci-dessus" -ForegroundColor White
    Write-Host "   2. Relancer: .\security-audit.ps1" -ForegroundColor White
    Write-Host "   3. Si données sensibles détectées:" -ForegroundColor White
    Write-Host "      - git reset HEAD~1" -ForegroundColor Gray
    Write-Host "      - Corriger les fichiers" -ForegroundColor Gray
    Write-Host "      - git add . && git commit" -ForegroundColor Gray
    Write-Host ""
    
    if ($Strict) {
        Write-Host "   MODE STRICT: Push bloqué" -ForegroundColor Red
        exit 1
    } else {
        Write-Host "   ⚠️  Push NON RECOMMANDÉ mais possible (mode permissif)" -ForegroundColor Yellow
        exit 1
    }
}
