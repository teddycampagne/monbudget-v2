# Sécurité - MonBudget v2

## ⚠️ RÈGLES CRITIQUES - À LIRE ABSOLUMENT

### 🤖 Pour les assistants IA (GitHub Copilot, ChatGPT, Claude, etc.)

**INTERDICTION ABSOLUE** de pusher sur GitHub les fichiers suivants **s'ils contiennent des données réelles** :

- ❌ `database.sql` avec INSERT INTO (données utilisateur)
- ❌ `database_*.sql` contenant IBAN, noms, adresses
- ❌ `config/app.php` avec mots de passe en clair
- ❌ `phpunit.xml` avec identifiants de connexion

**AVANT TOUT COMMIT** de fichier SQL ou config :
1. ✅ Vérifier : **STRUCTURE SEULE** (pas d'INSERT INTO avec données réelles)
2. ✅ Vérifier : **AUCUN mot de passe** en clair
3. ✅ Vérifier : **AUCUNE donnée personnelle** (IBAN, tél, email, adresse)

**EN CAS DE DOUTE** : Demander confirmation à l'utilisateur.

### 👨‍💻 Pour les développeurs

**RÈGLE #1** : NE JAMAIS utiliser de données réelles pour les tests
- ✅ Nom : `Jean Dupont`, `Marie Martin`
- ✅ Email : `test@example.com`
- ✅ IBAN : `FR7630006000011234567890189` (faux mais valide)
- ❌ Vos vrais IBAN, noms, téléphones

**RÈGLE #2** : Vérification avant push
```bash
# Vérifier database.sql (doit retourner 0)
grep -c "INSERT INTO" database.sql

# Vérifier absence IBAN/coordonnées
git diff --cached | grep -E "FR[0-9]{25}"
```

**Incident 18/11/2025** : Données IBAN/coordonnées exposées dans database.sql (résolu par purge historique Git)

---

## 🔒 Configuration des fichiers d'environnement

### Fichiers sensibles (JAMAIS commiter sur Git)

Les fichiers suivants contiennent des credentials et **NE DOIVENT JAMAIS** être commités :
- `.env` - Configuration production
- `.env.local` - Configuration développement local
- `.env.testing` - Configuration tests avec vrais credentials
- `.env.production` - Configuration serveur de production

### Fichiers templates (safe pour Git)

Ces fichiers sont des templates sans credentials réels :
- `.env.example` - Template pour configuration générale
- `.env.testing.example` - Template pour configuration de tests

## 📝 Configuration initiale

### 1. Créer votre fichier .env local

```bash
# Copier le template
cp .env.example .env

# Éditer avec vos vrais credentials
# NE JAMAIS commiter ce fichier !
```

### 2. Créer votre fichier .env.testing pour PHPUnit

```bash
# Copier le template
cp .env.testing.example .env.testing

# Remplacer YOUR_SECURE_PASSWORD_HERE par votre vrai mot de passe
# NE JAMAIS commiter ce fichier !
```

## 🛡️ Bonnes pratiques

### ✅ À FAIRE
- Utiliser des mots de passe forts (12+ caractères, mixte)
- Créer une base de données séparée pour les tests (`monbudget_test`)
- Changer régulièrement vos credentials (tous les 3 mois)
- Utiliser GitHub Secrets pour CI/CD
- Vérifier `.gitignore` avant chaque commit

### ❌ À NE JAMAIS FAIRE
- Commiter des fichiers `.env*` (sauf `.env.example`)
- Hardcoder des mots de passe dans le code PHP
- Utiliser le même mot de passe pour dev/test/prod
- Partager vos credentials par email/Slack
- Exposer votre serveur MySQL sur Internet sans firewall

## 🚨 En cas de leak de credentials

1. **Changer IMMÉDIATEMENT** tous les mots de passe compromis
2. Vérifier les logs d'accès pour détecter une intrusion
3. Nettoyer l'historique Git (voir section ci-dessous)
4. Notifier l'équipe si plusieurs personnes sont impactées

### Nettoyer l'historique Git (si un fichier .env a été commité)

```bash
# Installer git-filter-repo (recommandé)
pip install git-filter-repo

# Supprimer le fichier de tout l'historique
git filter-repo --path .env.testing --invert-paths

# Force push (⚠️ dangereux, prévenir l'équipe)
git push origin --force --all
```

**Ou avec filter-branch (méthode classique) :**

```bash
# Supprimer de l'historique
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch .env.testing" \
  --prune-empty --tag-name-filter cat -- --all

# Nettoyer les références
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Supprimer les backups
rm -rf .git/refs/original/

# Force push
git push origin --force --all
```

## 📊 GitHub Secrets (pour CI/CD)

Pour les tests automatisés sur GitHub Actions :

1. Aller dans `Settings` → `Secrets and variables` → `Actions`
2. Ajouter les secrets :
   - `DB_PASSWORD_TEST` : Mot de passe MySQL de test
   - `APP_KEY` : Clé secrète application

3. Utiliser dans `.github/workflows/tests.yml` :
```yaml
env:
  DB_PASSWORD: ${{ secrets.DB_PASSWORD_TEST }}
```

## 🔐 Chiffrement des backups

Si vous sauvegardez la base de données :

```bash
# Dump chiffré
mysqldump -u root -p monbudget_v2 | gzip | openssl enc -aes-256-cbc -salt -out backup.sql.gz.enc

# Restauration
openssl enc -d -aes-256-cbc -in backup.sql.gz.enc | gunzip | mysql -u root -p monbudget_v2
```

## 📞 Contact

En cas de problème de sécurité critique, contacter :
- Email : security@monbudget.local
- Issue privée GitHub : https://github.com/teddycampagne/monbudget-v2/security/advisories/new

---

**Dernière mise à jour** : 16 novembre 2025  
**Version** : v2.0.0
