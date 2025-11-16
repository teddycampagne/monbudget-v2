# Scripts de Setup - MonBudget V2

Ce dossier contient les scripts utilitaires pour la configuration et la maintenance de l'application.

## 📁 Scripts disponibles

### `generate-pwa-icons.py`

Génère automatiquement toutes les icônes nécessaires pour la PWA et les favicons.

**Prérequis:**
```bash
pip install pillow
```

**Usage:**
```bash
python setup/generate-pwa-icons.py
```

**Génère:**
- 8 icônes PWA (72px à 512px)
- 2 icônes maskable (192px, 512px) pour Android
- 3 favicons PNG (16px, 32px, 48px)
- 1 favicon.ico multi-résolution
- 1 icône Apple Touch (180px)

**Total:** 15 fichiers d'icônes

**Emplacements:**
- `assets/icons/` - Icônes PWA
- `public/` - Favicons et icône Apple

**Design:**
- Dégradé violet (#667eea → #764ba2)
- Symbole € blanc sur cercle
- Optimisé pour toutes les tailles

## 🔄 Régénération des icônes

Les icônes peuvent être régénérées à tout moment en relançant le script. Utile pour :
- Changer le design
- Mettre à jour les couleurs
- Ajouter de nouvelles tailles

## 📝 Notes

- Les icônes sont versionnées dans Git (elles font partie des assets)
- Le script utilise la police Arial du système Windows
- Fallback sur des formes géométriques si la police n'est pas disponible
