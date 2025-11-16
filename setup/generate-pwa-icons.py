#!/usr/bin/env python3
"""
Générateur d'icônes PWA et Favicon pour MonBudget
Crée toutes les tailles d'icônes nécessaires + favicon

Requirements: pip install pillow
Usage: python generate-pwa-icons.py
"""

from PIL import Image, ImageDraw, ImageFont
import os
import sys

# Configuration
SIZES = {
    'pwa': [72, 96, 128, 144, 152, 192, 384, 512],
    'favicon': [16, 32, 48],
    'apple': [180]  # apple-touch-icon
}

COLORS = {
    'primary': '#0d6efd',      # Bootstrap primary
    'secondary': '#6c757d',
    'success': '#198754',
    'gradient_start': '#667eea',
    'gradient_end': '#764ba2'
}

def create_gradient_background(size, color1, color2):
    """Créer un arrière-plan avec dégradé"""
    image = Image.new('RGB', (size, size))
    draw = ImageDraw.Draw(image)
    
    # Dégradé diagonal
    for y in range(size):
        ratio = y / size
        r = int(int(color1[1:3], 16) * (1 - ratio) + int(color2[1:3], 16) * ratio)
        g = int(int(color1[3:5], 16) * (1 - ratio) + int(color2[3:5], 16) * ratio)
        b = int(int(color1[5:7], 16) * (1 - ratio) + int(color2[5:7], 16) * ratio)
        draw.line([(0, y), (size, y)], fill=(r, g, b))
    
    return image

def create_monbudget_icon(size):
    """Créer l'icône MonBudget avec logo €"""
    # Dégradé de fond
    image = create_gradient_background(size, COLORS['gradient_start'], COLORS['gradient_end'])
    draw = ImageDraw.Draw(image)
    
    # Calculer les dimensions
    padding = size * 0.15
    icon_size = size - (2 * padding)
    
    # Cercle blanc semi-transparent au centre
    circle_center = size // 2
    circle_radius = icon_size // 2
    draw.ellipse(
        [circle_center - circle_radius, circle_center - circle_radius,
         circle_center + circle_radius, circle_center + circle_radius],
        fill=(255, 255, 255, 230)
    )
    
    # Symbole € stylisé
    euro_size = icon_size * 0.6
    euro_x = circle_center
    euro_y = circle_center
    
    # Dessiner le symbole €
    try:
        # Essayer de charger une police
        font_size = int(euro_size)
        try:
            font = ImageFont.truetype("arial.ttf", font_size)
        except:
            try:
                font = ImageFont.truetype("C:\\Windows\\Fonts\\arial.ttf", font_size)
            except:
                font = ImageFont.load_default()
        
        # Dessiner le symbole €
        text = "€"
        
        # Obtenir la taille du texte pour le centrer
        bbox = draw.textbbox((0, 0), text, font=font)
        text_width = bbox[2] - bbox[0]
        text_height = bbox[3] - bbox[1]
        
        text_x = euro_x - text_width // 2
        text_y = euro_y - text_height // 2
        
        # Ombre
        draw.text((text_x + 2, text_y + 2), text, fill=(0, 0, 0, 100), font=font)
        # Texte principal
        draw.text((text_x, text_y), text, fill=COLORS['primary'], font=font)
        
    except Exception as e:
        print(f"  ⚠️  Police non disponible, utilisation du symbole basique: {e}")
        # Fallback: cercle avec barre
        euro_radius = int(euro_size // 2)
        draw.ellipse(
            [euro_x - euro_radius, euro_y - euro_radius,
             euro_x + euro_radius, euro_y + euro_radius],
            outline=COLORS['primary'],
            width=int(size * 0.08)
        )
        # Barres horizontales
        bar_width = int(euro_radius * 1.2)
        bar_height = int(size * 0.06)
        draw.rectangle(
            [euro_x - bar_width, euro_y - bar_height // 2,
             euro_x + bar_width // 3, euro_y + bar_height // 2],
            fill=COLORS['primary']
        )
    
    return image

def create_favicon_icon(size):
    """Créer une version simplifiée pour le favicon"""
    # Fond dégradé
    image = create_gradient_background(size, COLORS['primary'], COLORS['gradient_end'])
    draw = ImageDraw.Draw(image)
    
    # Pour les petites tailles, juste un cercle avec €
    if size <= 48:
        # Cercle blanc
        center = size // 2
        radius = size // 3
        draw.ellipse(
            [center - radius, center - radius, center + radius, center + radius],
            fill='white'
        )
        
        # € simplifié
        try:
            font_size = int(size * 0.6)
            try:
                font = ImageFont.truetype("arial.ttf", font_size)
            except:
                try:
                    font = ImageFont.truetype("C:\\Windows\\Fonts\\arial.ttf", font_size)
                except:
                    font = ImageFont.load_default()
            
            text = "€"
            bbox = draw.textbbox((0, 0), text, font=font)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]
            
            draw.text(
                (center - text_width // 2, center - text_height // 2),
                text,
                fill=COLORS['primary'],
                font=font
            )
        except:
            # Fallback simple
            draw.ellipse(
                [center - radius // 2, center - radius // 2,
                 center + radius // 2, center + radius // 2],
                outline=COLORS['primary'],
                width=max(1, size // 16)
            )
    
    return image

def generate_icons():
    """Générer toutes les icônes"""
    print("🎨 Génération des icônes PWA et Favicon pour MonBudget")
    print("=" * 60)
    
    # Créer les dossiers si nécessaire
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    icons_dir = os.path.join(base_dir, 'assets', 'icons')
    public_dir = os.path.join(base_dir, 'public')
    
    os.makedirs(icons_dir, exist_ok=True)
    
    # Compteurs
    total = sum(len(sizes) for sizes in SIZES.values())
    current = 0
    
    # Générer les icônes PWA
    print(f"\n📱 Génération des icônes PWA...")
    for size in SIZES['pwa']:
        current += 1
        print(f"  [{current}/{total}] Création icon-{size}x{size}.png...")
        
        icon = create_monbudget_icon(size)
        icon.save(os.path.join(icons_dir, f'icon-{size}x{size}.png'), 'PNG')
        
        # Version maskable pour Android (avec plus de padding)
        if size in [192, 512]:
            print(f"  [{current}/{total}] Création icon-{size}x{size}-maskable.png...")
            maskable = Image.new('RGB', (size, size), COLORS['gradient_start'])
            icon_small = create_monbudget_icon(int(size * 0.8))
            offset = (size - icon_small.width) // 2
            maskable.paste(icon_small, (offset, offset))
            maskable.save(os.path.join(icons_dir, f'icon-{size}x{size}-maskable.png'), 'PNG')
    
    # Générer les favicons
    print(f"\n🔖 Génération des favicons...")
    favicon_images = []
    for size in SIZES['favicon']:
        current += 1
        print(f"  [{current}/{total}] Création favicon-{size}x{size}.png...")
        
        icon = create_favicon_icon(size)
        icon_path = os.path.join(public_dir, f'favicon-{size}x{size}.png')
        icon.save(icon_path, 'PNG')
        favicon_images.append(icon)
    
    # Créer le favicon.ico multi-résolution
    print(f"  Création favicon.ico (multi-résolution)...")
    favicon_ico_path = os.path.join(public_dir, 'favicon.ico')
    favicon_images[0].save(
        favicon_ico_path,
        format='ICO',
        sizes=[(s, s) for s in SIZES['favicon']]
    )
    
    # Générer l'icône Apple
    print(f"\n🍎 Génération de l'icône Apple Touch...")
    for size in SIZES['apple']:
        current += 1
        print(f"  [{current}/{total}] Création apple-touch-icon.png ({size}x{size})...")
        
        icon = create_monbudget_icon(size)
        icon.save(os.path.join(public_dir, 'apple-touch-icon.png'), 'PNG')
        icon.save(os.path.join(icons_dir, 'apple-touch-icon-180x180.png'), 'PNG')
    
    print("\n" + "=" * 60)
    print("✅ Génération terminée !")
    print(f"\n📊 Résumé:")
    print(f"  • {len(SIZES['pwa'])} icônes PWA + 2 maskable")
    print(f"  • {len(SIZES['favicon'])} favicons PNG + 1 ICO multi-résolution")
    print(f"  • {len(SIZES['apple'])} icône Apple Touch")
    print(f"  • Total: {len(SIZES['pwa']) + 2 + len(SIZES['favicon']) + 1 + len(SIZES['apple'])} fichiers")
    
    print(f"\n📁 Fichiers créés dans:")
    print(f"  • {icons_dir}")
    print(f"  • {public_dir}")
    
    return True

def check_dependencies():
    """Vérifier les dépendances"""
    try:
        import PIL
        return True
    except ImportError:
        print("❌ Erreur: La bibliothèque Pillow n'est pas installée")
        print("\nInstallez-la avec:")
        print("  pip install pillow")
        print("\nOu:")
        print("  python -m pip install pillow")
        return False

if __name__ == '__main__':
    print("MonBudget - Générateur d'icônes PWA")
    print()
    
    # Vérifier les dépendances
    if not check_dependencies():
        sys.exit(1)
    
    # Générer les icônes
    try:
        success = generate_icons()
        sys.exit(0 if success else 1)
    except Exception as e:
        print(f"\n❌ Erreur lors de la génération: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
