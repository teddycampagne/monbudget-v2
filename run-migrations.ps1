#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Exécute les migrations de base de données pour MonBudget v2

.DESCRIPTION
    Script d'application des migrations SQL dans l'ordre.
    Suit les migrations déjà appliquées via une table de tracking.

.PARAMETER Host
    Hôte MySQL (défaut: localhost)

.PARAMETER User
    Utilisateur MySQL (défaut: root)

.PARAMETER Password
    Mot de passe MySQL

.PARAMETER Database
    Nom de la base de données (défaut: monbudget_v2)

.PARAMETER MigrationsPath
    Chemin vers le dossier des migrations (défaut: database/migrations)

.EXAMPLE
    .\run-migrations.ps1 -Password "votre_password"
    
.EXAMPLE
    .\run-migrations.ps1 -Host "localhost" -User "root" -Password "password" -Database "monbudget_v2"
#>

param(
    [string]$DbHost = "localhost",
    [string]$User = "root",
    [Parameter(Mandatory=$true)]
    [string]$Password,
    [string]$Database = "monbudget_v2",
    [string]$MigrationsPath = "database/migrations"
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "Migration Base de Donnees - MonBudget v2" -ForegroundColor Cyan
Write-Host ("=" * 60) -ForegroundColor Gray
Write-Host ""

# Vérifier que le dossier migrations existe
if (-not (Test-Path $MigrationsPath)) {
    Write-Host "[ERREUR] Dossier migrations introuvable: $MigrationsPath" -ForegroundColor Red
    exit 1
}

# Vérifier que mysql est disponible
try {
    $null = Get-Command mysql -ErrorAction Stop
} catch {
    Write-Host "[ERREUR] mysql CLI non trouve. Installer MySQL Client." -ForegroundColor Red
    exit 1
}

Write-Host "[INFO] Connexion a la base de donnees..." -ForegroundColor Gray
Write-Host "  Hote: $DbHost" -ForegroundColor Gray
Write-Host "  Base: $Database" -ForegroundColor Gray
Write-Host ""

# Créer la table de tracking des migrations si elle n'existe pas
$createTrackingTable = @"
CREATE TABLE IF NOT EXISTS _migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"@

Write-Host "[1/3] Creation table de tracking..." -ForegroundColor White
$env:MYSQL_PWD = $Password
echo $createTrackingTable | mysql -h $DbHost -u $User $Database 2>&1 | Out-Null

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERREUR] Impossible de creer la table de tracking" -ForegroundColor Red
    exit 1
}
Write-Host "  Table _migrations prete" -ForegroundColor Green
Write-Host ""

# Récupérer les migrations déjà appliquées
Write-Host "[2/3] Verification migrations deja appliquees..." -ForegroundColor White
$appliedMigrations = mysql -h $DbHost -u $User $Database -N -e "SELECT migration_name FROM _migrations" 2>&1

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERREUR] Impossible de recuperer l'historique" -ForegroundColor Red
    exit 1
}

$appliedList = @()
if ($appliedMigrations) {
    $appliedList = $appliedMigrations -split "`n" | Where-Object { $_ -ne "" }
}

Write-Host "  $($appliedList.Count) migration(s) deja appliquee(s)" -ForegroundColor Green
Write-Host ""

# Récupérer tous les fichiers .sql dans le dossier migrations (triés)
$migrationFiles = Get-ChildItem -Path $MigrationsPath -Filter "*.sql" | Sort-Object Name

if ($migrationFiles.Count -eq 0) {
    Write-Host "[INFO] Aucun fichier de migration trouve" -ForegroundColor Yellow
    exit 0
}

Write-Host "[3/3] Application des migrations..." -ForegroundColor White
Write-Host ""

$newMigrationsCount = 0

foreach ($file in $migrationFiles) {
    $migrationName = $file.Name
    
    # Vérifier si déjà appliquée
    if ($appliedList -contains $migrationName) {
        Write-Host "  [SKIP] $migrationName (deja appliquee)" -ForegroundColor Gray
        continue
    }
    
    Write-Host "  [RUN]  $migrationName" -ForegroundColor Yellow -NoNewline
    
    # Exécuter la migration
    try {
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        
        # Exécuter le SQL
        echo $content | mysql -h $DbHost -u $User $Database 2>&1 | Out-Null
        
        if ($LASTEXITCODE -ne 0) {
            throw "Erreur execution SQL"
        }
        
        # Enregistrer dans la table de tracking
        $insertTracking = "INSERT INTO _migrations (migration_name) VALUES ('$migrationName')"
        echo $insertTracking | mysql -h $DbHost -u $User $Database 2>&1 | Out-Null
        
        if ($LASTEXITCODE -ne 0) {
            throw "Erreur enregistrement tracking"
        }
        
        Write-Host "`r  [OK]   $migrationName" -ForegroundColor Green
        $newMigrationsCount++
        
    } catch {
        Write-Host "`r  [FAIL] $migrationName" -ForegroundColor Red
        Write-Host "  Erreur: $_" -ForegroundColor Red
        exit 1
    }
}

# Nettoyer la variable d'environnement
Remove-Item Env:\MYSQL_PWD

Write-Host ""
Write-Host ("=" * 60) -ForegroundColor Gray

if ($newMigrationsCount -eq 0) {
    Write-Host "Aucune nouvelle migration a appliquer" -ForegroundColor Yellow
} else {
    Write-Host "$newMigrationsCount migration(s) appliquee(s) avec succes" -ForegroundColor Green
}

Write-Host ""
exit 0
