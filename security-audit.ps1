#!/usr/bin/env pwsh
# Audit de securite MonBudget - Detection donnees sensibles avant push

param(
    [switch]$Strict,
    [switch]$Auto
)

$ErrorCount = 0
$WarningCount = 0

Write-Host ""
Write-Host "AUDIT DE SECURITE - MonBudget v2" -ForegroundColor Cyan
Write-Host ("=" * 60)
Write-Host ""

# 1. Verifier database.sql
Write-Host "1. Verification database.sql" -ForegroundColor White
Write-Host ("-" * 60)

if (Test-Path "database.sql") {
    $insertCount = (Get-Content database.sql | Select-String "^INSERT INTO").Count
    
    if ($insertCount -gt 0) {
        Write-Host "[ERREUR] database.sql contient $insertCount INSERT INTO" -ForegroundColor Red
        $ErrorCount++
    } else {
        Write-Host "[OK] database.sql sans donnees utilisateur" -ForegroundColor Green
    }
    
    $bytes = [System.IO.File]::ReadAllBytes("database.sql")
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        Write-Host "[ERREUR] database.sql contient un BOM UTF-8" -ForegroundColor Red
        $ErrorCount++
        
        if ($Auto) {
            Write-Host "  Suppression BOM automatique..." -ForegroundColor Yellow
            $content = Get-Content database.sql -Raw
            $utf8NoBom = New-Object System.Text.UTF8Encoding $false
            [System.IO.File]::WriteAllText("$PWD\database.sql", $content, $utf8NoBom)
            Write-Host "  BOM supprime" -ForegroundColor Green
            $ErrorCount--
        }
    } else {
        Write-Host "[OK] database.sql sans BOM UTF-8" -ForegroundColor Green
    }
}

Write-Host ""

# 2. Verifier fichiers stages Git
Write-Host "2. Verification fichiers stages" -ForegroundColor White
Write-Host ("-" * 60)

$stagedFiles = git diff --cached --name-only 2>$null
if ($stagedFiles) {
    $stagedContent = git diff --cached
    
    # IBAN francais
    if ($stagedContent -match "FR[0-9]{25}") {
        Write-Host "[ERREUR] IBAN francais detecte" -ForegroundColor Red
        $ErrorCount++
    } else {
        Write-Host "[OK] Aucun IBAN francais" -ForegroundColor Green
    }
    
    # Emails personnels
    if (($stagedContent -match "@gmail\.") -or ($stagedContent -match "@outlook\.") -or ($stagedContent -match "@hotmail\.")) {
        Write-Host "[ERREUR] Email personnel detecte" -ForegroundColor Red
        $ErrorCount++
    } else {
        Write-Host "[OK] Aucun email personnel" -ForegroundColor Green
    }
    
    # Telephones
    if ($stagedContent -match "0[1-9] [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}") {
        Write-Host "[ERREUR] Numero de telephone detecte" -ForegroundColor Red
        $ErrorCount++
    } else {
        Write-Host "[OK] Aucun numero de telephone" -ForegroundColor Green
    }
} else {
    Write-Host "[INFO] Aucun fichier stage" -ForegroundColor Gray
}

Write-Host ""

# 3. Verifier config/installed.json
Write-Host "3. Verification fichiers sensibles" -ForegroundColor White
Write-Host ("-" * 60)

$null = git ls-files --error-unmatch config/installed.json 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "[ERREUR] config/installed.json est tracke par Git" -ForegroundColor Red
    $ErrorCount++
} else {
    Write-Host "[OK] config/installed.json non tracke" -ForegroundColor Green
}

Write-Host ""

# Resume
Write-Host ("=" * 60)
if ($ErrorCount -eq 0 -and $WarningCount -eq 0) {
    Write-Host "AUDIT REUSSI - Aucun probleme detecte" -ForegroundColor Green
    Write-Host "Vous pouvez pusher en toute securite." -ForegroundColor Green
    Write-Host ""
    exit 0
} elseif ($ErrorCount -eq 0) {
    Write-Host "AUDIT OK avec $WarningCount avertissement(s)" -ForegroundColor Yellow
    Write-Host ""
    exit 0
} else {
    Write-Host "AUDIT ECHOUE - $ErrorCount erreur(s), $WarningCount avertissement(s)" -ForegroundColor Red
    Write-Host ""
    Write-Host "ACTIONS REQUISES:" -ForegroundColor Yellow
    Write-Host "1. Corriger les erreurs ci-dessus" -ForegroundColor White
    Write-Host "2. Relancer: .\security-audit.ps1" -ForegroundColor White
    Write-Host ""
    
    if ($Strict) {
        Write-Host "MODE STRICT: Push bloque" -ForegroundColor Red
        exit 1
    } else {
        Write-Host "Push NON RECOMMANDE (mode permissif)" -ForegroundColor Yellow
        exit 1
    }
}
