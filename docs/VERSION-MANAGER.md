# Système de Mise à Jour Automatique

## 📋 Vue d'ensemble

Le système de mise à jour automatique permet de vérifier, notifier et déployer les nouvelles versions de MonBudget directement depuis l'interface web.

**Version** : 2.2.0  
**Date** : 16 novembre 2025

---

## 🎯 Fonctionnalités

### ✅ Vérification Automatique
- Interrogation GitHub API toutes les heures
- Cache local pour éviter surcharge API
- Comparaison sémantique des versions (SemVer)
- Détection automatique nouvelle version

### 🔔 Notification Utilisateur
- Badge dans le header (icône cloud + pastille "Nouveau")
- Toast notification à la première détection
- Modal détaillée avec changelog complet
- Stockage localStorage pour éviter spam

### 🚀 Déploiement One-Click
- Git checkout vers le tag de version
- Vérification pré-déploiement :
  - Git installé et accessible
  - Dépôt Git valide
  - Aucune modification locale non commitée
  - Tag existe sur origin
- Affichage temps réel de la sortie
- Gestion d'erreurs complète
- Rollback possible en cas d'échec

### 🔄 Post-Déploiement
- Vidage automatique du cache
- Rechargement page après succès
- Information sur migrations manuelles

---

## 🏗️ Architecture

### Fichiers créés

```
app/Services/VersionChecker.php           # Service vérification/déploiement
app/Controllers/VersionController.php     # Contrôleur API
assets/js/version-manager.js              # Frontend JavaScript
```

### Routes API

```php
GET  /version/check-update    # Vérifier mises à jour
POST /version/deploy           # Déployer version (admin only)
POST /version/rollback         # Rollback (admin only)
GET  /version/info             # Informations version
```

---

## 🔧 Utilisation

### Pour l'utilisateur final

1. **Connexion** : Se connecter à MonBudget
2. **Notification** : Si nouvelle version, badge apparaît dans header
3. **Consultation** : Cliquer sur badge pour voir détails
4. **Déploiement** : Cliquer "Déployer maintenant"
5. **Attente** : Observer sortie déploiement en temps réel
6. **Rechargement** : Page se recharge automatiquement

### Pour l'administrateur

**Déploiement manuel via API** :
```bash
# Vérifier version
curl -X GET http://localhost/monbudgetV2/version/check-update \
  -H "Cookie: PHPSESSID=..."

# Déployer
curl -X POST http://localhost/monbudgetV2/version/deploy \
  -H "Cookie: PHPSESSID=..." \
  -d "version=2.2.0"
```

**Rollback si nécessaire** :
```bash
curl -X POST http://localhost/monbudgetV2/version/rollback \
  -H "Cookie: PHPSESSID=..." \
  -d "commit=abc1234"
```

---

## ⚙️ Configuration

### GitHub API

Le service interroge :
- **Tags** : `https://api.github.com/repos/teddycampagne/monbudget-v2/tags`
- **Releases** : `https://api.github.com/repos/teddycampagne/monbudget-v2/releases/latest`

### Cache

- **Fichier** : `storage/cache/version_check.json`
- **Durée** : 1 heure (3600 secondes)
- **Structure** :
```json
{
  "checked_at": 1731772800,
  "update": {
    "version": "2.3.0",
    "tag_name": "v2.3.0",
    "changelog": "Release notes...",
    "published_at": "2025-11-20T10:00:00Z",
    "html_url": "https://github.com/...",
    "current_version": "2.2.0"
  }
}
```

### Sécurité

- **Authentification** : Requise pour toutes les routes
- **Autorisations** : Admin uniquement pour deploy/rollback
- **Validation** : Vérification modifications locales avant deploy
- **Git** : Utilise commandes Git natives (pas de shell injection)

---

## 📊 Workflow de Déploiement

```
1. Utilisateur clique "Déployer"
   ↓
2. Frontend envoie POST /version/deploy
   ↓
3. Backend vérifie :
   - Utilisateur authentifié ✓
   - Utilisateur admin ✓
   - Git installé ✓
   - Dépôt Git valide ✓
   - Aucune modif locale ✓
   ↓
4. git fetch origin --tags
   ↓
5. Vérification tag existe
   ↓
6. git checkout v2.x.x
   ↓
7. Vidage cache
   ↓
8. Retour succès
   ↓
9. Frontend recharge page
```

---

## 🐛 Gestion d'Erreurs

### Erreurs possibles

1. **Git non installé**
   - Message : "Git n'est pas installé ou n'est pas accessible"
   - Solution : Installer Git

2. **Modifications locales**
   - Message : "Des modifications locales non commitées existent"
   - Solution : Commiter ou annuler modifications

3. **Tag introuvable**
   - Message : "Le tag vX.Y.Z n'existe pas"
   - Solution : Vérifier version demandée, fetch origin

4. **Échec checkout**
   - Message : "Erreur lors du checkout"
   - Solution : Vérifier logs Git, conflits potentiels

### Rollback

En cas d'échec, le système :
1. Affiche l'erreur complète
2. Conserve le commit actuel dans réponse
3. Permet rollback manuel
4. Réactive bouton "Réessayer"

---

## 🔐 Sécurité

### Mesures implémentées

1. **Authentification obligatoire** : Toutes routes protégées
2. **Admin only** : Deploy/rollback réservés admins
3. **Validation Git** : Pas de shell injection
4. **Timeout curl** : 10 secondes max
5. **SSL verify** : Certificats HTTPS vérifiés
6. **Pas de force** : Refuse si modifications locales

### Recommandations

- ⚠️ **Backup BDD** : Toujours sauvegarder avant deploy
- ⚠️ **Migrations** : Exécuter manuellement si nécessaire
- ⚠️ **Test** : Tester en environnement dev d'abord
- ⚠️ **Monitoring** : Surveiller logs après deploy

---

## 📝 Changelog Integration

Le système récupère automatiquement les notes de version depuis GitHub Releases.

Format attendu dans GitHub Release :
```markdown
## [2.3.0] - 2025-11-20

### ✨ Ajouté
- Feature 1
- Feature 2

### 🐛 Corrigé
- Bug 1
- Bug 2
```

---

## 🧪 Tests

### Test vérification

```javascript
// Dans console navigateur
await fetch('/version/check-update')
  .then(r => r.json())
  .then(console.log);
```

### Test déploiement (DEV ONLY)

```bash
# Créer tag de test
git tag -a v2.2.1-test -m "Test deployment"
git push origin v2.2.1-test

# Déployer via UI ou API
# Vérifier logs
# Rollback
git checkout develop
```

---

## 🔮 Améliorations Futures

### V2.3.0
- [ ] Exécution automatique migrations SQL
- [ ] Notification email admin
- [ ] Backup automatique BDD avant deploy
- [ ] Diff visuel entre versions
- [ ] Historique déploiements

### V2.4.0
- [ ] Mode maintenance auto
- [ ] Tests pré-déploiement automatisés
- [ ] Rollback one-click
- [ ] Multi-environnements (dev/staging/prod)

---

*Dernière mise à jour : 16 novembre 2025 - Session 17.5 Part 4*
