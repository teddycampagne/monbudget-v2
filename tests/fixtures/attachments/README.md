# Fichiers de Test - Pièces Jointes

Ce dossier contient des fichiers de test pour valider le système de pièces jointes.

## 📋 Fichiers Disponibles

### ✅ Fichiers Valides (doivent passer)

1. **valid-pdf-2mb.pdf** (~2 MB)
   - Type MIME: `application/pdf`
   - Taille: < 5 MB ✓
   - Contenu: Lorem ipsum + métadonnées de facture
   - **Résultat attendu**: Upload réussi

2. **valid-image-1mb.png** (~10-50 KB)
   - Type MIME: `image/png`
   - Taille: < 5 MB ✓
   - Contenu: Image avec texte Lorem ipsum et infos facture
   - **Résultat attendu**: Upload réussi + preview possible

### ❌ Fichiers Invalides (doivent échouer)

3. **invalid-pdf-6mb.pdf** (~6 MB)
   - Type MIME: `application/pdf`
   - Taille: > 5 MB ✗
   - **Résultat attendu**: Erreur "Fichier trop volumineux (max 5 MB)"

4. **malicious-script.php** (~200 bytes)
   - Type MIME: `text/x-php` ou `application/x-httpd-php`
   - Extension: `.php` ✗
   - Contenu: Script PHP malveillant (eval, system)
   - **Résultat attendu**: Erreur "Type de fichier non autorisé"

5. **malicious-program.exe** (~1 KB)
   - Type MIME: `application/x-msdownload` ou `application/x-dosexec`
   - Extension: `.exe` ✗
   - Contenu: En-tête exécutable Windows (MZ signature)
   - **Résultat attendu**: Erreur "Type de fichier non autorisé"

## 🧪 Scénarios de Test

### Test 1: Upload Fichier Valide PDF
1. Ouvrir une transaction en édition
2. Glisser-déposer `valid-pdf-2mb.pdf` dans la zone d'upload
3. ✅ Vérifier: Barre de progression → Toast succès → Fichier apparaît dans la liste

### Test 2: Upload Fichier Valide Image
1. Ouvrir une transaction en édition
2. Glisser-déposer `valid-image-1mb.png` dans la zone d'upload
3. ✅ Vérifier: Upload réussi + bouton "Aperçu" visible
4. Cliquer sur "Aperçu"
5. ✅ Vérifier: Modal Bootstrap avec image affichée

### Test 3: Validation Taille (PDF > 5MB)
1. Tenter d'uploader `invalid-pdf-6mb.pdf`
2. ❌ Vérifier: Toast d'erreur "Fichier trop volumineux (max 5 MB)"
3. ✅ Vérifier: Fichier NON ajouté à la liste

### Test 4: Validation Type MIME (PHP)
1. Tenter d'uploader `malicious-script.php`
2. ❌ Vérifier: Toast d'erreur "Type de fichier non autorisé"
3. ✅ Vérifier: Fichier NON ajouté à la liste
4. ✅ Vérifier: Fichier NON présent sur le serveur

### Test 5: Validation Type MIME (EXE)
1. Tenter d'uploader `malicious-program.exe`
2. ❌ Vérifier: Toast d'erreur "Type de fichier non autorisé"
3. ✅ Vérifier: Fichier NON ajouté à la liste
4. ✅ Vérifier: Aucun fichier `.exe` dans `uploads/attachments/`

### Test 6: Suppression Pièce Jointe
1. Upload un fichier valide
2. Cliquer sur le bouton "Supprimer" (icône poubelle)
3. ✅ Vérifier: Confirmation demandée
4. Confirmer la suppression
5. ✅ Vérifier: Animation de disparition + Toast succès
6. ✅ Vérifier: Badge PJ dans la liste décrémenté

### Test 7: Téléchargement Pièce Jointe
1. Upload un fichier valide
2. Cliquer sur le bouton "Télécharger"
3. ✅ Vérifier: Fichier téléchargé avec nom original
4. ✅ Vérifier: Content-Disposition: attachment header

### Test 8: Sécurité - Ownership
1. Créer une transaction avec User A
2. Uploader une pièce jointe
3. Se connecter avec User B
4. Tenter d'accéder à l'URL de download de la PJ de User A
5. ❌ Vérifier: HTTP 403 Forbidden
6. ✅ Vérifier: Message "Accès non autorisé"

### Test 9: Sécurité - .htaccess
1. Uploader un fichier PDF
2. Vérifier que `.htaccess` existe dans `uploads/attachments/`
3. Tenter d'exécuter un script PHP dans ce dossier (si on en met un)
4. ❌ Vérifier: Accès interdit par .htaccess

## 🔒 Validations Attendues

| Critère | Valeur | Validé par |
|---------|--------|------------|
| Taille max | 5 MB | FileUploadService::validateFile() |
| MIME types | 12 types autorisés | FileUploadService::detectMimeType() |
| Extensions | 12 extensions | Attachment::ALLOWED_EXTENSIONS |
| Ownership | User ID match | TransactionController::uploadAttachment() |
| .htaccess | Auto-généré | FileUploadService::ensureDirectoryExists() |

## 📁 Emplacement Upload

Fichiers uploadés stockés dans:
```
uploads/attachments/{user_id}/{year}/{month}/
```

Exemple:
```
uploads/attachments/2/2025/11/a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6.pdf
```

## 🧹 Nettoyage

Pour supprimer tous les fichiers de test uploadés:
```powershell
Remove-Item -Recurse -Force C:\wamp64\www\monbudgetV2\uploads\attachments\*
```
