/**
 * Icon Picker - Sélecteur d'icônes Bootstrap Icons
 * Charge la liste des icônes depuis un fichier JSON configurable
 */

let bootstrapIcons = []; // Sera chargé depuis le JSON

class IconPicker {
    constructor(inputElement, previewElement) {
        this.input = inputElement;
        this.preview = previewElement;
        this.modal = null;
        this.selectedIcon = this.input.value || 'bi-tag';
        
        this.loadIcons().then(() => this.init());
    }
    
    async loadIcons() {
        try {
            // Utiliser la route API avec l'URL de base depuis la config
            const baseUrl = window.APP_CONFIG?.baseUrl || '';
            const response = await fetch(`${baseUrl}/api/bootstrap-icons`);
            if (response.ok) {
                bootstrapIcons = await response.json();
                console.log(`${bootstrapIcons.length} icônes chargées depuis l'API`);
            } else {
                console.warn('API bootstrap-icons non disponible, utilisation du fallback');
                // Fallback si le fichier n'existe pas
                bootstrapIcons = ['bi-tag', 'bi-star', 'bi-heart', 'bi-cash-coin'];
            }
        } catch (error) {
            console.error('Erreur chargement icônes:', error);
            bootstrapIcons = ['bi-tag', 'bi-star', 'bi-heart', 'bi-cash-coin'];
        }
    }
    
    init() {
        // Créer le modal
        this.createModal();
        
        // Créer le bouton de sélection
        this.createButton();
        
        // Mettre à jour la prévisualisation initiale
        this.updatePreview();
    }
    
    createButton() {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group';
        
        // Déplacer l'input dans le wrapper
        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);
        
        // Créer le bouton
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary';
        button.innerHTML = '<i class="bi bi-grid-3x3-gap"></i> Choisir';
        button.onclick = () => this.openModal();
        
        wrapper.appendChild(button);
    }
    
    createModal() {
        const modalHTML = `
            <div class="modal fade" id="iconPickerModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-grid-3x3-gap"></i> Choisir une icône
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Recherche -->
                            <div class="mb-3">
                                <input type="text" 
                                       class="form-control" 
                                       id="iconSearch" 
                                       placeholder="🔍 Rechercher une icône... (ex: money, car, house)">
                            </div>
                            
                            <!-- Grille d'icônes -->
                            <div class="icon-grid" id="iconGrid" style="
                                display: grid;
                                grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
                                gap: 10px;
                                max-height: 400px;
                                overflow-y: auto;
                            "></div>
                            
                            <div class="text-muted small mt-3">
                                <i class="bi bi-info-circle"></i> 
                                ${bootstrapIcons.length} icônes disponibles
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <button type="button" class="btn btn-primary" id="iconPickerConfirm">
                                <i class="bi bi-check-lg"></i> Valider
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter au body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        this.modal = new bootstrap.Modal(document.getElementById('iconPickerModal'));
        
        // Remplir la grille
        this.renderIconGrid();
        
        // Événements
        document.getElementById('iconSearch').addEventListener('input', (e) => {
            this.filterIcons(e.target.value);
        });
        
        document.getElementById('iconPickerConfirm').addEventListener('click', () => {
            this.confirmSelection();
        });
    }
    
    renderIconGrid(icons = bootstrapIcons) {
        const grid = document.getElementById('iconGrid');
        grid.innerHTML = '';
        
        icons.forEach(iconClass => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary p-2';
            button.style.cssText = 'aspect-ratio: 1; display: flex; align-items: center; justify-content: center;';
            button.innerHTML = `<i class="${iconClass}" style="font-size: 1.5rem;"></i>`;
            button.title = iconClass;
            
            // Marquer comme sélectionné si c'est l'icône actuelle
            if (iconClass === this.selectedIcon) {
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-primary');
            }
            
            button.addEventListener('click', () => {
                // Désélectionner tous
                grid.querySelectorAll('.btn').forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-secondary');
                });
                
                // Sélectionner celui-ci
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-primary');
                
                this.selectedIcon = iconClass;
            });
            
            grid.appendChild(button);
        });
    }
    
    filterIcons(searchTerm) {
        if (!searchTerm) {
            this.renderIconGrid();
            return;
        }
        
        const filtered = bootstrapIcons.filter(icon => 
            icon.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        this.renderIconGrid(filtered);
    }
    
    openModal() {
        this.modal.show();
    }
    
    confirmSelection() {
        this.input.value = this.selectedIcon;
        this.updatePreview();
        this.modal.hide();
    }
    
    updatePreview() {
        if (this.preview) {
            this.preview.className = this.input.value || 'bi-tag';
        }
    }
}

// Auto-initialisation
document.addEventListener('DOMContentLoaded', () => {
    const iconeInput = document.getElementById('icone');
    const iconePreview = document.getElementById('icone-preview');
    
    if (iconeInput && iconePreview) {
        new IconPicker(iconeInput, iconePreview);
    }
});
