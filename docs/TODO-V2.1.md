# TODO - MonBudget V2.1

## 📋 Vue d'ensemble
Version V2.1 - Améliorations ergonomiques et nouvelles fonctionnalités

**Statut global Phase 1** : 6/6 features complétées (100%) ✅  
**Statut global Phase 2** : 1/1 feature complétée (100%) ✅  
**Statut global Phase 3** : 1/1 feature complétée (100%) ✅  
**Statut global Phase 4** : 1/1 feature complétée (100%) ✅ **NOUVEAU**

**Session actuelle** : Session 17 (16 nov 2025)  
**Dernière mise à jour** : 16 novembre 2025

---

## ✅ PHASE 4 - COMPLÉTÉE (1/1) - Session 17

### 1. Tags personnalisés ✅

**Backend complet** :
- ✅ Migration BDD : Tables `tags` + `transaction_tags` (pivot many-to-many)
- ✅ Model `Tag` : 368 lignes, 16 méthodes (CRUD, validation, search, stats, colors)
- ✅ Model `Transaction` : 5 méthodes tags (attachTags, detachTags, syncTags, getTagIds, hasTag)
- ✅ Controller `TagController` : 283 lignes, 10 méthodes (CRUD + show + 3 API)
- ✅ Routes : 10 routes (6 CRUD + 1 show + 3 API)
- ✅ Système couleurs : 7 couleurs Bootstrap natives avec labels français

**Frontend complet** :
- ✅ JavaScript `tag-selector.js` : 400+ lignes (autocomplete, multi-select, quick-create modal)
- ✅ CSS `tag-selector.css` : Styles composant
- ✅ Views Tags : index, create, edit, show (4 vues complètes)
- ✅ Component : Tag selector réutilisable avec badges colorés

**Intégration Transactions** :
- ✅ Formulaires : Sélecteur tags dans create.php + edit.php
- ✅ Liste : Colonne tags avec badges colorés dans index.php
- ✅ Backend : Gestion syncTags() dans store() et update()
- ✅ Query : LEFT JOIN avec GROUP_CONCAT pour récupération tags

**Dashboard & Rapports** :
- ✅ Widget nuage de tags : Top 10 des 3 derniers mois avec statistiques
- ✅ Rapport détaillé : Section analyse par tags dans rapports/index.php
- ✅ API endpoint : `/api/rapports/tags` avec totaux (débits/crédits/balance)
- ✅ Page détails tag : Vue complète avec transactions associées et stats

**Recherche avancée** :
- ✅ Filtre par tags : Select multiple dans page recherche
- ✅ Support multi-sélection : Ctrl/Cmd pour sélectionner plusieurs tags
- ✅ Intégration API : Filtre tags dans `/api/recherche`

**Palette couleurs (7 couleurs Bootstrap)** :
```php
'primary' => ['label' => 'Bleu', 'hex' => '#0d6efd']
'secondary' => ['label' => 'Gris', 'hex' => '#6c757d']
'success' => ['label' => 'Vert', 'hex' => '#198754']
'danger' => ['label' => 'Rouge', 'hex' => '#dc3545']
'warning' => ['label' => 'Jaune', 'hex' => '#ffc107']
'info' => ['label' => 'Cyan', 'hex' => '#0dcaf0']
'dark' => ['label' => 'Noir', 'hex' => '#212529']
```

**Bugs corrigés Session 17** :
- ✅ TagController : Ajout `use MonBudget\Core\Session`
- ✅ Views tags : Ajout header/footer layouts
- ✅ Tag colors : Affichage labels français au lieu de hex
- ✅ syncTags() : Fix erreur "no active transaction"
- ✅ Dashboard : Correction lien tags (404) → `/tags/{id}`
- ✅ Index tags : Correction lien usage count → `/tags/{id}`
- ✅ TagController show() : Ajout `compte_id` dans requête SQL

**Fonctionnalités** :
- ✅ CRUD complet : Création, modification, suppression tags
- ✅ Multi-tags : Plusieurs tags par transaction (many-to-many)
- ✅ Autocomplete : Recherche tags en temps réel (300ms debounce)
- ✅ Quick-create : Modal AJAX pour créer tags à la volée
- ✅ Statistiques : Usage count, totaux débits/crédits par tag
- ✅ Navigation : Drill-down tags → transactions filtrées
- ✅ Dashboard : Widget nuage de tags cliquable
- ✅ Rapports : Analyse financière par tag
- ✅ Recherche : Filtre multi-tags dans recherche avancée

**Fichiers créés** :
- `database/migrations/2025_11_16_create_tags_tables.sql`
- `app/Models/Tag.php` (368 lignes)
- `app/Controllers/TagController.php` (342 lignes)
- `app/Views/tags/index.php` (214 lignes)
- `app/Views/tags/create.php` (85 lignes)
- `app/Views/tags/edit.php` (96 lignes)
- `app/Views/tags/show.php` (191 lignes)
- `assets/js/tag-selector.js` (400+ lignes)
- `assets/css/tag-selector.css` (58 lignes)

**Fichiers modifiés** :
- `app/Models/Transaction.php` : +5 méthodes tags, modification getByCompte()
- `app/Controllers/TransactionController.php` : Tags dans create/edit/store/update
- `app/Controllers/HomeController.php` : Query top_tags pour dashboard
- `app/Controllers/RapportController.php` : Méthode apiRapportTags()
- `app/Controllers/RechercheController.php` : Filtre par tags
- `app/Views/transactions/create.php` : Intégration tag selector
- `app/Views/transactions/edit.php` : Intégration tag selector
- `app/Views/transactions/index.php` : Colonne tags
- `app/Views/home/dashboard.php` : Widget nuage de tags
- `app/Views/rapports/index.php` : Section tags + JS
- `app/Views/recherche/index.php` : Select tags
- `index.php` : +10 routes tags, +1 route rapport

**Total Session 17** : ~2,000 lignes de code

---

## ✅ PHASE 2 - COMPLÉTÉE (1/1) - Session 15

### 1. Pièces jointes transactions ✅

**Backend complet** :
- ✅ Migration BDD : Table `attachments` avec foreign key `transaction_id`
- ✅ Model `Attachment` : CRUD, validation MIME, helpers (icon, size format)
- ✅ Service `FileUploadService` : Upload sécurisé, .htaccess auto, sanitization
- ✅ Controller : 3 endpoints (upload, delete, download) avec ownership check
- ✅ Routes : POST upload, DELETE suppression, GET download

**Frontend complet** :
- ✅ JavaScript `AttachmentUploader` : Drag&drop, AJAX, progress, preview images
- ✅ Component `attachment-uploader.php` : Zone upload réutilisable
- ✅ View `transactions/edit.php` : Intégration zone upload
- ✅ View `transactions/index.php` : Badge compteur (icône trombone + nombre)

**Sécurité** :
- ✅ Validation MIME réelle (anti-spoofing)
- ✅ Whitelist extensions : PDF, images, Excel, Word, TXT, CSV
- ✅ Taille max : 5 Mo par fichier
- ✅ .htaccess auto-généré (bloque PHP, scripts)
- ✅ Sanitization noms fichiers (anti-XSS, path traversal)
- ✅ Génération noms uniques (hash 32 chars)
- ✅ Ownership check sur tous les endpoints

**Stockage** :
- ✅ Organisation : `uploads/attachments/{user_id}/{year}/{month}/`
- ✅ Cleanup automatique sur suppression transaction (CASCADE)

**Corrections post-tests (Session 15)** :
- ✅ Namespaces : `App\Models` → `MonBudget\Models` (cohérence architecture)
- ✅ Database API : `getInstance()` → `getConnection()` (méthode correcte)
- ✅ URLs JavaScript : Ajout `baseUrl` pour sous-dossier `/monbudgetV2`
- ✅ Chemins fichiers : Correction path absolu → relatif (`attachments/...`)
- ✅ PHP limits : `upload_max_filesize` 6M dans `.htaccess`
- ✅ CSS manquant : Création `attachment-uploader.css`
- ✅ Preview image : Ajout attributs `data-*` + listeners JavaScript
- ✅ Controller JSON : Ajout champ `path` dans réponse upload

**Tests validés** :
- ✅ Upload PDF 2MB - OK
- ✅ Upload JPG - OK
- ✅ Rejet PDF 6MB - OK (taille)
- ✅ Rejet .exe - OK (type)
- ✅ Rejet .php - OK (type)
- ✅ Aperçu image - OK (modal Bootstrap)
- ✅ Téléchargement - OK (nom original)
- ✅ Suppression - OK (confirmation + animation)
- ✅ Badge compteur - OK (liste transactions)

**Fichiers créés/modifiés** :
- Créés : `database/migrations/2025_11_16_create_attachments_table.sql`
- Créés : `app/Models/Attachment.php` (273 lignes)
- Créés : `app/Services/FileUploadService.php` (276 lignes)
- Créés : `assets/js/attachment-uploader.js` (338 lignes)
- Créés : `assets/css/attachment-uploader.css` (58 lignes)
- Créés : `app/Views/components/attachment-uploader.php` (143 lignes)
- Créés : `tests/fixtures/attachments/README.md` (guide tests)
- Modifiés : `app/Controllers/TransactionController.php` (+195 lignes)
- Modifiés : `index.php` (+3 routes)
- Modifiés : `app/Views/transactions/edit.php` (+4 lignes)
- Modifiés : `app/Views/transactions/index.php` (+19 lignes)
- Modifiés : `.htaccess` (+5 lignes, limites upload)
- Modifiés : `.gitignore` (+10 lignes, uploads/attachments)

**Total Session 15** : ~1,323 lignes de code ajoutées

**Commits Session 15** :
- `7d310a0` - feat: Pièces jointes transactions - Backend complet
- `9933cb7` - feat: Pièces jointes transactions - Frontend & docs complets
- `dad19c5` - docs: Ajout récapitulatif complet Session 15
- `10ca3e6` - fix: Corrections pièces jointes - Namespaces, URLs et chemins
- `08c90e0` - chore: Ajouter uploads/attachments et fixtures tests au .gitignore

---

## ✅ PHASE 1 - COMPLÉTÉE (6/6) - Session 14

### 1. Breadcrumbs globaux ✅
- ✅ Composant breadcrumbs réutilisable (`app/Views/components/breadcrumbs.php`)
- ✅ Intégration sur toutes les pages principales
- ✅ Navigation contextuelle avec URL helper
- ✅ Fichiers modifiés : banques/show, comptes/index, categories/index, transactions/index

### 2. Création rapide Catégorie/Tiers ✅
- ✅ Modals AJAX dans formulaire Transaction (create/edit)
- ✅ Endpoints API : `/api/categories-add`, `/api/tiers-add`
- ✅ Rechargement automatique des select après création
- ✅ Validation côté serveur et retour JSON

### 3. Drill-down Banque → Comptes ✅
- ✅ Vue détail banque (`app/Views/banques/show.php`)
- ✅ Liste des comptes associés avec bouton "Voir les transactions"
- ✅ Pré-sélection banque lors de création compte depuis vue banque
- ✅ Gestion old() retournant chaîne vide (correction bug)

### 4. Gestion sous-catégories ✅
- ✅ Navigation basée sur sessions (retour intelligent après actions)
- ✅ Formulaires dédiés création/édition sous-catégories
- ✅ Liste avec drill-down par catégorie parente
- ✅ Parent fixé et en lecture seule dans formulaires
- ✅ Breadcrumbs contextuels (Catégories → [Parent] → Sous-catégorie)

### 5. Bouton dupliquer transaction ✅
- ✅ Bouton "Dupliquer" dans transactions/index.php (icône bi-files)
- ✅ Bouton dans recherche/index.php
- ✅ Route GET /comptes/{id}/transactions/{tid}/duplicate
- ✅ Pré-remplissage formulaire création avec données source
- ✅ Date réinitialisée à aujourd'hui
- ✅ Conversion en transaction simple (est_recurrente = 0)
- ✅ Flag isDuplicate pour message d'information

### 6. Date picker avec raccourcis ✅
- ✅ Composant JavaScript réutilisable (`assets/js/date-picker-shortcuts.js`)
- ✅ Raccourcis date : Aujourd'hui, Hier, Il y a 7j, Début/Fin mois
- ✅ Raccourcis période rapports : Mois actuel, Mois dernier, Année actuelle/dernière
- ✅ Auto-initialisation via attribut data-shortcuts
- ✅ Appliqué sur : transactions (create/edit), recherche, récurrences, rapports
- ✅ Style Bootstrap : btn-sm btn-outline-secondary

**Bugs corrigés Session 14** :
- ✅ Rapports : Filtrage par compte ignoré dans toutes les APIs
- ✅ Transactions : Bouton Annuler avec route 404
- ✅ Rapports : Fonction chargerSuiviBudgetaire inexistante (→ chargerBudgetaire)

---

## ✅ PHASE 3 - COMPLÉTÉE (1/1) - Session PWA (16 nov 2025)

### 1. Progressive Web App (PWA) ✅

**Configuration PWA** :
- ✅ `manifest.json` : Configuration app installable (standalone, shortcuts, screenshots)
- ✅ `service-worker.js` : Cache Network First + fallback offline (v2.1.0)
- ✅ `offline.html` : Page hors ligne avec auto-reconnect (5s polling)
- ✅ `pwa-install.js` : Enregistrement SW + prompt installation + toasts

**Icônes et Favicons** :
- ✅ 8 icônes PWA (72px à 512px) - Dégradé violet + symbole €
- ✅ 2 icônes maskable Android (192px, 512px)
- ✅ 4 favicons (16x16, 32x32, 48x48 PNG + ICO multi-résolution)
- ✅ 1 icône Apple Touch (180px)
- ✅ Script générateur Python (`setup/generate-pwa-icons.py`)

**Intégration** :
- ✅ Header : PWA meta tags + favicons + manifest (v2.1)
- ✅ Footer : Script PWA installation
- ✅ Login : Favicons + manifest pour cohérence
- ✅ `.htaccess` : Règles de réécriture pour fichiers PWA

**Fonctionnalités** :
- ✅ Installation desktop/mobile via bouton ou menu navigateur
- ✅ Mode hors ligne fonctionnel avec cache intelligent
- ✅ Détection connexion online/offline avec toasts
- ✅ Badge "App" en mode standalone
- ✅ Modal instructions navigateur (Edge/Chrome/Opera) si prompt indisponible
- ✅ Page diagnostic (`pwa-diagnostic.html`) pour troubleshooting

**Outils de développement** :
- ✅ `setup/generate-pwa-icons.py` : Régénération automatique icônes
- ✅ `setup/README.md` : Documentation script
- ✅ `pwa-diagnostic.html` : Checks PWA complets (SW, manifest, beforeinstallprompt)

**Fichiers créés** :
- `public/manifest.json` (104 lignes)
- `public/service-worker.js` (121 lignes)
- `public/offline.html` (98 lignes)
- `assets/js/pwa-install.js` (203 lignes)
- `pwa-diagnostic.html` (263 lignes)
- `setup/generate-pwa-icons.py` (286 lignes)
- `setup/README.md`
- 15 fichiers d'icônes (assets/icons/ + public/)
- Copies à la racine : manifest.json, service-worker.js, offline.html, favicons

**Fichiers modifiés** :
- `app/Views/layouts/header.php` : PWA meta tags + favicons v2.1
- `app/Views/layouts/footer.php` : Script pwa-install.js
- `app/Views/auth/login.php` : Favicons + manifest + v2.1
- `.htaccess` : Revert règles (fichiers PWA à la racine)
- `database.sql` : Ajout table attachments

**Total Session PWA** : ~1,900 lignes de code

**Commits Session PWA** :
- `8ea27c2` - feat: Implémentation PWA complète avec icônes et favicon (24 fichiers)
- `f87bb1c` - fix: Correction PWA - Installation fonctionnelle (5 fichiers)
- `3348b3e` - fix: Ajout favicons à la racine et paramètre de version (6 fichiers)
- `53bc8e9` - fix: Ajout favicons et manifest sur page login
- `fbfd7e5` - chore: Ajout table attachments dans database.sql

---

## 🐛 BUGS CORRIGÉS (Session QA du 15-16 nov 2025)

### Imports
- ✅ Bouton "Confirmer et importer" orphelin hors formulaire (CSV preview)
- ✅ Lien Annuler avec URL hardcodée

### Budgets  
- ✅ Catégories non chargées (filtre `is_system` incorrect)
- ✅ Affichage sous-catégories au lieu de catégories parentes

### Recherche
- ✅ Colonnes `est_valide`/`est_rapproche` inexistantes (utilisation `validee`)
- ✅ Calcul stats fragile avec `substr()` (refactorisation requête unique)
- ✅ Alert() générique → Modals Bootstrap personnalisés
- ✅ Suppression filtre rapprochement (non implémenté)

### Profil
- ✅ Champs `nom`/`prenom` inexistants dans table users
- ✅ Formulaire simplifié : username + email uniquement

### Breadcrumbs
- ✅ URLs hardcodées → url() helper (4 fichiers)

### Helper Functions
- ✅ Suppression ui-helpers.php obsolète
- ✅ Remplacement linkButton/submitButton/cancelButton par HTML Bootstrap (6 fichiers)

### Validation Transactions
- ✅ Dates "0000-00-00" invalides
- ✅ jour_execution avec value="0" alors que min="1"
- ✅ Désactivation required sur champs recurrence masqués

### Divers
- ✅ Icon picker : 4 icônes au lieu de 120 (création API endpoint)
- ✅ Banque pré-sélection non fonctionnelle (gestion old())
- ✅ Import preview : addEventListener sur null

**Total : 20+ bugs corrigés**

---

## 📊 Modules testés et validés (100%)

✅ Titulaires (banques) - create, edit, show  
✅ Comptes - index, create, edit, transactions  
✅ Transactions - index, create, edit, récurrentes  
✅ Catégories - index, create, edit  
✅ Tiers - index, create (tous types), edit  
✅ Automatisation - index, create, edit, règles  
✅ Imports - upload, preview (OFX + CSV), process  
✅ Budgets - index, create, edit, generate  
✅ Projections - index, analyse  
✅ Rapports - index, drill-down, relevé  
✅ Recherche - filtres avancés, export CSV  
✅ Documentation - guide, FAQ, install  
✅ Profil - affichage, update  
✅ Administration - users, roles, icons, restore  

---

## 🎯 Priorités V2.2+

### Urgent
- Aucun bug bloquant identifié

### Haute
1. ~~**🏷️ Tags personnalisés**~~ ✅ **COMPLÉTÉ Session 17**
2. **🔔 Système d'alertes** - Notifications dépassements budgets par email
3. **🌍 Multi-devises** - Support EUR/USD/GBP avec taux de change

### Moyenne  
4. **🔍 Recherche full-text** - Indexation MySQL + opérateurs avancés
5. **⌨️ Raccourcis clavier** - Ctrl+N, Ctrl+K, actions bulk
6. **📊 Monitoring** - Logs structurés + métriques + APM

### Basse
7. **🔐 API REST** - Endpoints v1 + JWT + OpenAPI/Swagger
8. **2FA** - TOTP Google Authenticator pour admins

---

## 📈 Statistiques V2.1

**Phase 1 (Session 14)** :
- Commit : `3f47d6f` (16 nov 2025)
- Fichiers modifiés : 26
- Lignes : +973 / -495

**Phase 2 (Session 15 - Attachments)** :
- Commits : 5 commits
- Fichiers créés : 7
- Lignes : +1,323

**Phase 3 (Session 16 - PWA)** :
- Commits : 5 commits  
- Fichiers créés : 22
- Lignes : +1,900

**Phase 4 (Session 17 - Tags)** :
- Fichiers créés : 9
- Fichiers modifiés : 12
- Lignes : +2,000

**Total V2.1** : ~6,200 lignes ajoutées sur 4 sessions

**Total V2.1** : ~4,200 lignes ajoutées sur 3 sessions

---

## 📝 Notes de développement

### Pattern API AJAX utilisé
```javascript
// Création rapide depuis modal
const response = await fetch(url('api/categories-add'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
});
const result = await response.json();
```

### Pattern Breadcrumbs
```php
$breadcrumbs = [
    ['text' => 'Accueil', 'url' => url('/')],
    ['text' => 'Banques', 'url' => url('banques')],
    ['text' => $banque['nom'], 'url' => null] // Page actuelle
];
require __DIR__ . '/../components/breadcrumbs.php';
```

### Pattern Modal erreur personnalisé
```javascript
function showErrorModal(titre, message) {
    // Modal Bootstrap avec header bg-danger
    // Remplace alert() natif
}
```

---

## 🚀 FONCTIONNALITÉS FUTURES (V2.2+)

### 💡 Nouvelles fonctionnalités potentielles

#### 🔔 Système d'alertes et notifications
- [ ] **Alertes email/SMS** : Notifications pour dépassements budgets
- [ ] **Centre de notifications** : Widget dans application avec badge compteur
- [ ] **Récapitulatif mensuel automatique** : Email synthétisant le mois
- [ ] **Configuration SMTP** : Interface admin pour paramétrer serveur email
- [ ] **Templates personnalisables** : Emails HTML avec logo et couleurs

#### 🔐 API REST sécurisée
- [ ] **Endpoints REST** : `/api/v1/{resource}` avec authentification JWT
- [ ] **Documentation OpenAPI** : Swagger UI intégré
- [ ] **Rate limiting** : Protection contre abus (100 req/min)
- [ ] **OAuth2** : Support connexion applications tierces
- [ ] **Webhooks** : Notifications événements (nouvelle transaction, etc.)

#### 🌍 Multi-devises
- [ ] **Table devises** : Support EUR, USD, GBP, CHF, etc.
- [ ] **Taux de change** : API externe (Fixer.io, ECB) avec cache
- [ ] **Conversion automatique** : Affichage montants dans devise préférée
- [ ] **Historique taux** : Graphique évolution EUR/USD sur 12 mois
- [ ] **Comptes multi-devises** : Solde par devise + équivalent total
- [ ] **Validation MIME** : Whitelist types autorisés (PDF, images, XLS, DOCX)
- [ ] **Compteur** : Badge nombre pièces jointes dans liste transactions
- [ ] **Recherche** : Filtre "Avec/Sans pièces jointes" dans recherche avancée
- [ ] **Download groupé** : ZIP de toutes PJ d'une période (ex: factures du mois)
- [ ] **Suppression cascade** : Auto-delete fichiers si transaction supprimée
- [ ] **Quota utilisateur** : Limite 100MB total par user (admin configurable)
- [ ] **Viewer PDF intégré** : PDF.js pour visualiser factures sans téléchargement
- [ ] **OCR futur** : Extraction données facture (V7 avec IA)

#### 🏷️ Tags personnalisés
- [ ] **Table tags** : Étiquettes libres complémentaires aux catégories
- [ ] **Multi-tags** : Table pivot `transaction_tags` (many-to-many)
- [ ] **Autocomplete** : Suggestions tags existants lors saisie
- [ ] **Filtres** : Recherche avancée par tags combinés
- [ ] **Couleurs** : Tag badges personnalisables (16 couleurs prédéfinies)
- [ ] **Cloud de tags** : Widget dashboard avec tailles proportionnelles

#### 🔍 Recherche full-text avancée
- [ ] **Indexation MySQL** : FULLTEXT index sur libellé + description
- [ ] **Support opérateurs** : AND, OR, NOT, guillemets, wildcards
- [ ] **Recherche floue** : Tolérance fautes de frappe (Levenshtein)
- [ ] **Suggestions** : "Vouliez-vous dire..." pour corrections
- [ ] **Historique recherches** : 10 dernières recherches sauvegardées

### 🛠️ Optimisations techniques avancées

#### ⚡ Performance et cache
- [ ] **Redis Cache** : Mise en cache requêtes fréquentes (stats dashboard)
- [ ] **Query Builder optimisé** : Eager loading relations (N+1 queries)
- [ ] **Pagination lazy** : Infinite scroll au lieu de boutons pagination
- [ ] **CDN assets** : Bootstrap/Charts.js depuis CDN avec fallback local
- [ ] **Minification** : CSS/JS minifiés en production (gulp/webpack)
- [ ] **Image optimization** : Compression logos banques (WebP)
- [ ] **HTTP/2** : Server push CSS/JS critiques

#### 📊 Monitoring et observabilité
- [ ] **Logs structurés** : Monolog avec rotation quotidienne
- [ ] **Dashboard métriques** : Temps réponse, erreurs, utilisateurs actifs
- [ ] **APM** : Application Performance Monitoring (New Relic, Datadog)
- [ ] **Error tracking** : Sentry pour exceptions PHP/JS
- [ ] **Analytics** : Matomo/Plausible pour stats usage (RGPD compliant)

### 🔐 Administration avancée

#### 📋 Gestion avancée
- [ ] **Viewer de logs web** : Interface filtres date/niveau/utilisateur
- [ ] **Analyseur imports échoués** : Debug CSV/OFX avec highlighting erreurs
- [ ] **Rapport santé BDD** : Tables orphelines, index manquants, taille tables
- [ ] **Audit trail** : Log toutes actions sensibles (suppressions, exports, etc.)
- [ ] **2FA (Two-Factor Auth)** : TOTP (Google Authenticator) pour admins
- [ ] **Sessions actives** : Liste devices connectés + déconnexion forcée

#### 🔧 Maintenance automatisée
- [ ] **Cron jobs** : Exécution récurrences, emails mensuels, cleanup
- [ ] **Health checks** : Endpoint `/health` (status DB, disk, memory)
- [ ] **Auto-backup** : Dump SQL quotidien avec rotation 7 jours
- [ ] **Migrations versionnées** : Phinx/Doctrine pour évolutions schema
- [ ] **Feature flags** : Activation/désactivation features sans déploiement

### 🎨 Améliorations UX/UI

#### ⌨️ Raccourcis et productivité
- [ ] **Raccourcis clavier** : 
  - `Ctrl+N` : Nouvelle transaction
  - `Ctrl+K` : Focus recherche
  - `Ctrl+D` : Toggle dark mode
  - `Esc` : Fermer modal
  - `/` : Focus recherche globale
- [ ] **Actions bulk** : Sélection multiple (checkbox) + actions groupées
  - Validation masse
  - Suppression masse
  - Changement catégorie masse
  - Export sélection
- [ ] **Drag & drop** : Upload fichiers import par glisser-déposer
- [ ] **Undo/Redo** : Annuler dernière action (15 sec timeout)

#### 📱 Mobile first
- [ ] **Bottom navigation** : Menu fixe bas écran mobile
- [ ] **Swipe actions** : Swipe gauche/droite pour éditer/supprimer
- [ ] **Touch gestures** : Pinch zoom graphiques
- [ ] **Clavier numérique** : Auto-focus montants avec clavier adapté
- [ ] **Camera API** : Scan documents pour import (OCR future)

#### 🎨 Personnalisation avancée
- [ ] **Thèmes personnalisés** : Editeur couleurs primaires/secondaires
- [ ] **Layout configurable** : Drag & drop widgets dashboard
- [ ] **Favoris** : Épingler pages fréquentes dans menu
- [ ] **Vue compacte/étendue** : Toggle densité affichage tables
- [ ] **Export préférences** : Sauvegarde/import configuration JSON

---

## 🔗 Ressources

- **Repo GitHub** : https://github.com/teddycampagne/monbudget-v2
- **Branche principale** : `main`
- **Documentation** : `/docs`
- **Changelog** : `CHANGELOG.md`
- **Tests** : PHPUnit 17/17 passing (100%)
- **Session TODO** : `/docs/TODO.md` (13 sessions complétées)

---

## 📅 Planning prévisionnel

### V2.1 - Quick Wins (Sprint actuel) ✨
- **Durée** : 2-3 jours
- **Focus** : Ergonomie (todos #4, #5, #6)
- **Livraison** : Mi-novembre 2025
- **Objectifs** : Gestion sous-catégories inline, dupliquer transaction, date picker raccourcis

### V2.2 - Notifications & PWA 🔔
- **Durée** : 1 semaine
- **Focus** : Système alertes + Progressive Web App
- **Livraison** : Fin novembre 2025
- **Objectifs** : 
  - Alertes email/SMS dépassements budgets
  - Application installable (manifest.json + service worker)
  - Mode offline avec sync automatique
  - Push notifications natives

### V2.3 - Performance & Scale ⚡
- **Durée** : 1 semaine
- **Focus** : Redis cache + Optimisations + Multi-devises
- **Livraison** : Début décembre 2025
- **Objectifs** :
  - Cache Redis pour requêtes fréquentes
  - Support EUR/USD/GBP/CHF avec conversion automatique
  - Optimisations assets (minification, CDN, WebP)
  - Monitoring APM et logs structurés

### V3.0 - API & Extensions 🔐
- **Durée** : 2 semaines
- **Focus** : API REST + Tags + Recherche full-text + Mobile first
- **Livraison** : Décembre 2025
- **Objectifs** :
  - API REST sécurisée avec JWT et documentation OpenAPI
  - Tags personnalisés multi-assignables
  - Recherche full-text MySQL avec suggestions
  - Interface mobile optimisée (swipe, bottom nav, gestures)
  - Raccourcis clavier globaux

### V4.0 - UX Premium & Analytics 📊
- **Durée** : 3 semaines
- **Focus** : Expérience utilisateur avancée + Business Intelligence
- **Livraison** : Janvier 2026
- **Objectifs** :
  - Dashboard personnalisable (drag & drop widgets)
  - Thèmes personnalisés (éditeur couleurs)
  - Rapports avancés (multi-périodes, comparatifs, forecasting)
  - Export multi-formats (Excel, JSON, XML)
  - Audit trail complet (traçabilité toutes actions)
  - 2FA (TOTP Google Authenticator)

---

## 🚀 Vision Long Terme (2026-2028)

### V5.0 - Gestion Professionnelle 💼
**Cible** : Artisans, TPE, PME, Associations  
**Timeline** : T2 2026 (3 mois)

#### Nouvelles entités métier
- **Clients/Fournisseurs** : Gestion contacts professionnels (SIRET, TVA intra)
- **Devis/Factures** : Génération PDF conformes (mentions légales, CGV)
- **TVA multi-taux** : Calculs automatiques 5.5%, 10%, 20%
- **Analytique multi-axes** : Centres de coûts, projets, départements
- **Immobilisations** : Amortissements linéaires/dégressifs
- **Stocks** : Valorisation FIFO/LIFO/CUMP
- **Paie simplifiée** : Salaires, charges sociales (hors DSN)
- **Déclarations** : Exports comptables (FEC, DAS2, CA12)

#### Architecture technique
- **Multi-sociétés** : Isolation données par entité juridique
- **Droits avancés** : Rôles granulaires (comptable, gestionnaire, lecteur)
- **Workflow validation** : Circuit approbation dépenses/factures
- **Connecteurs bancaires** : PSD2 DSP2 pour réconciliation automatique
- **Conformité** : RGPD, LPF art. 286 (archivage 10 ans)

#### UI/UX pro
- **Interface comptable** : Plan comptable personnalisable, journaux, grand-livre
- **Tableaux de bord métier** : SIG (Soldes Intermédiaires de Gestion), KPI sectoriels
- **Bilan/Compte de résultat** : Génération automatique liasses fiscales
- **Aide contextuelle** : Chatbot assistant comptable (FAQ fiscales)

---

### V6.0 - Système d'Extensions (Marketplace) 🧩
**Cible** : Écosystème modulaire et communautaire  
**Timeline** : T4 2026 (4 mois)

#### Architecture plugin
- **Core Hooks** : Système événements (before_transaction_save, after_budget_exceeded, etc.)
- **API Plugins** : SDK PHP avec classes abstraites `MonBudgetExtension`
- **Sandboxing** : Isolation mémoire et DB par namespace
- **Versioning** : Compatibilité sémantique (v1.2.3), migrations auto
- **Marketplace** : Interface découverte/installation 1-clic

#### Extensions officielles (exemples)
1. **Gestion de Patrimoine** :
   - Biens immobiliers (valeur, revenus locatifs, charges copro)
   - Placements financiers (actions, obligations, SCPI, crypto)
   - Assurances vie (contrats, versements, arbitrages)
   - Calcul impôts fonciers, IFI, plus-values

2. **Facturation Avancée** :
   - Templates personnalisables (Twig templating)
   - Relances automatiques (J+15, J+30, mise en demeure)
   - Pénalités de retard calculées (taux BCE + 10 pts)
   - Signature électronique (DocuSign, Adobe Sign)
   - Prélèvement SEPA (fichiers XML pain.008)

3. **Internationalisation (i18n)** :
   - Support 20+ langues (gettext .po/.mo)
   - Formats locaux (dates, nombres, devises)
   - RTL (right-to-left) pour arabe/hébreu
   - Traductions communautaires (Crowdin integration)

4. **Import Banque Avancé** :
   - 50+ banques françaises (Crédit Agricole, BNP, Société Générale...)
   - Budget Insight / Linxo API
   - Catégorisation ML pré-entraînée par banque
   - Détection fraudes (alertes montants atypiques)

5. **Gestion de Trésorerie** :
   - Prévisions court/moyen/long terme (3/6/12 mois)
   - Scénarios what-if (si augmentation loyer +10%, si crédit anticipé...)
   - Alertes seuils découvert
   - Recommandations placements (livrets, LDDS, PEL optimaux)

#### Développement communautaire
- **GitHub Marketplace** : Dépôt extensions open-source
- **Documentation SDK** : Tutoriels, API reference, exemples annotés
- **Validation qualité** : Code review automatique (PHPStan niveau 8, tests >80%)
- **Monétisation** : Extensions freemium (30% commission marketplace)

---

### V7.0 - IA Analytique & Conseil Budgétaire 🤖
**Cible** : Assistant intelligent proactif  
**Timeline** : T2 2027 (6 mois)

#### Stack technique IA
- **Migration progressive** : PHP/JS reste pour CRUD, Python/FastAPI pour IA
- **Architecture microservices** :
  - Frontend : Vue.js 3 / React 18 (SPA moderne)
  - Backend API : Laravel 11 ou Symfony 7 (REST/GraphQL)
  - IA Service : Python 3.12 + FastAPI + Celery (workers async)
  - Message broker : RabbitMQ / Redis Pub/Sub
  - Data lake : PostgreSQL 16 + Clickhouse (analytics)

#### Modèles ML/IA
1. **Catégorisation automatique** :
   - Transformers (BERT-like) fine-tunés français
   - Apprentissage par transfert sur corpus bancaire
   - Précision >95% après 100 transactions
   - Suggestions temps réel avec confiance score

2. **Détection récurrences** :
   - Algorithmes séries temporelles (ARIMA, Prophet)
   - Identification patterns mensuels/hebdomadaires
   - Proposition création automatique (ex: "Netflix détecté tous les 12 du mois -12.99€")
   - Alertes anomalies (montant inhabituel, date décalée)

3. **Recommandations catégories** :
   - Clustering K-means sur libellés similaires
   - Suggestions sous-catégories pertinentes
   - Analyse hiérarchique (ex: "Alimentation > Courses > Bio" si détecté Biocoop)

4. **Création tiers intelligente** :
   - NER (Named Entity Recognition) pour extraire noms entités
   - Déduplication fuzzy matching (similitude >85%)
   - Enrichissement données (SIRET, adresse, logo via API publiques)

5. **Projections budgétaires ML** :
   - Réseaux neurones LSTM (Long Short-Term Memory)
   - Prédiction 12 mois avec intervalle confiance 90%
   - Facteurs saisonniers (Noël, vacances, rentrée)
   - Alertes proactives ("Risque découvert dans 3 mois si tendance maintenue")

6. **Conseils personnalisés** :
   - Analyse comparative (top 10% utilisateurs profil similaire)
   - Suggestions optimisation ("Vous dépensez 23% de plus en loisirs que la moyenne")
   - Challenges épargne ("Objectif -10% dépenses superflues = +150€/mois")
   - Simulations scénarios ("Crédit auto 15k€ sur 5 ans = -280€/mois, impact sur capacité épargne")

#### Interface conversationnelle
- **Chatbot avancé** : Assistants GPT-4 fine-tuné comptabilité/finance
- **Commandes vocales** : Web Speech API + Whisper transcription
- **Rapports narratifs** : Génération texte explicatif automatique
  - "En novembre, vos dépenses ont augmenté de 18% principalement à cause de 3 achats Amazon (342€). Vos revenus sont stables. Votre taux d'épargne chute à 8% (objectif 15%)."

#### Technologies avancées
- **Ray** (distributed ML) : Entraînement modèles sur cluster
- **MLflow** : Versioning modèles, A/B testing performances
- **TensorFlow Serving** : Inférence haute performance (<50ms)
- **Explainability** : SHAP/LIME pour transparence prédictions
- **Edge ML** : TensorFlow.js pour inférence navigateur (privacy-first)

---

### V8.0 - Applications Natives Multi-Plateformes 📱💻
**Cible** : Expérience 100% offline, sync multi-devices  
**Timeline** : T4 2027 (8 mois)

#### Stack cross-platform
- **Frontend** : Flutter 4.0 (Dart) - UI native iOS/Android/Desktop
  - Alternative : React Native / .NET MAUI / Tauri
- **Backend local** : SQLite + Rust (Tauri) ou Go (haute performance)
- **Sync engine** : CouchDB / Realm / WatermelonDB (conflict resolution CRDT)
- **Cloud sync** : AWS AppSync / Firebase / Supabase (real-time)

#### Applications natives
1. **Windows** : 
   - MSIX package (Microsoft Store)
   - Intégration Cortana, notifications Windows 11
   - Support Continuum (PC/Tablette)

2. **macOS** :
   - .app notarisé Apple
   - Touch Bar MacBook Pro
   - Widgets macOS Sonoma
   - iCloud sync natif

3. **Linux** :
   - AppImage / Flatpak / Snap
   - Support GNOME/KDE
   - Intégration freedesktop.org

4. **Android** :
   - Google Play + APK direct
   - Material Design 3
   - Widgets home screen
   - Wear OS companion

5. **iOS/iPadOS** :
   - App Store
   - SwiftUI adaptive layouts
   - Apple Watch app
   - Siri Shortcuts
   - Live Activities (iOS 16+)

#### Fonctionnalités offline-first
- **Mode déconnecté complet** : Toutes opérations CRUD disponibles
- **Queue synchronisation** : Actions empilées, sync auto à reconnexion
- **Résolution conflits** : Last-write-wins avec versioning + historique
- **Cache intelligent** : Téléchargement sélectif (3 derniers mois par défaut)
- **Delta sync** : Synchronisation différentielle (uniquement changements)
- **Compression** : gzip transfers (-70% bande passante)

#### Sécurité multi-devices
- **Chiffrement E2E** : AES-256-GCM, clés dérivées PBKDF2
- **Biométrie** : Touch ID, Face ID, Windows Hello, empreinte Android
- **Coffre-fort** : Données sensibles (RIB, mots de passe) chiffrées séparément
- **Wipe remote** : Effacement distance en cas vol
- **Audit devices** : Liste appareils connectés, révocation instantanée

#### Synchronisation avancée
- **Modes sync** :
  - Real-time : WebSocket bidirectionnel (< 1s latence)
  - Périodique : Toutes les 15 min en arrière-plan
  - Manuel : Bouton refresh utilisateur
  - Intelligent : Détection WiFi/4G, économie batterie
- **Versionning** : Git-like diff/merge (3-way merge conflicts)
- **Rollback** : Restauration état antérieur (snapshots quotidiens)
- **Multi-comptes** : Sync sélectif par profil (perso/pro séparés)

#### Performances natives
- **Démarrage** : < 2s (vs 5-10s web)
- **Navigation** : 60 FPS animations fluides
- **Mémoire** : Optimisation ressources mobiles (< 100MB RAM)
- **Batterie** : Background tasks optimisés (< 2% drain/jour)
- **Stockage** : Compression DB (SQLite VACUUM, indexes optimisés)

---

## 🎯 Roadmap Synthétique 2025-2028

| Version | Nom | Timeline | Effort | Impact Business |
|---------|-----|----------|--------|----------------|
| **V2.1** | Quick Wins | Nov 2025 | 3j | UX +20% |
| **V2.2** | Notifications & PWA | Nov 2025 | 1sem | Rétention +30% |
| **V2.3** | Performance & Scale | Déc 2025 | 1sem | Perf +50% |
| **V3.0** | API & Extensions | Déc 2025 | 2sem | Écosystème naissant |
| **V4.0** | UX Premium & Analytics | Jan 2026 | 3sem | Premium users +40% |
| **V5.0** | Gestion Pro | T2 2026 | 3mois | B2B market entry |
| **V6.0** | Marketplace Extensions | T4 2026 | 4mois | Revenus récurrents |
| **V7.0** | IA Analytique | T2 2027 | 6mois | Disruption marché |
| **V8.0** | Apps Natives Offline | T4 2027 | 8mois | Global scale ready |

---

## 💡 Innovations Clés par Version

### V2.x - Fondations Modernes
- ✅ Architecture MVC solide
- ✅ PWA installable
- ✅ Performance optimisée

### V3-V4 - Écosystème Ouvert
- 🔐 API publique sécurisée
- 🧩 Système plugins extensible
- 📊 Analytics avancées

### V5-V6 - Professionnalisation
- 💼 Comptabilité complète
- 🏢 Multi-sociétés
- 🛒 Marketplace rentable

### V7 - Intelligence Artificielle
- 🤖 ML/IA intégrée
- 🧠 Conseils proactifs
- 📈 Prédictions précises

### V8 - Omniprésence
- 📱 Apps natives 5 plateformes
- ☁️ Sync temps réel
- 🔒 Offline-first sécurisé

---

**Dernière mise à jour** : 16 novembre 2025  
**Prochaine session** : Implémenter todos #4, #5, #6  
**Version actuelle** : V2.0.0 (20+ bugs corrigés, 3/6 features V2.1)  
**Vision** : De l'application personnelle à la plateforme IA globale (2025-2028)

