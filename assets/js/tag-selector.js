/**
 * Tag Selector - Composant multi-select avec autocomplete pour les tags
 * 
 * Fonctionnalités :
 * - Multi-sélection de tags existants
 * - Autocomplete lors de la saisie
 * - Création rapide de nouveaux tags via modal
 * - Badges interactifs (suppression au clic)
 * 
 * @version 2.2.0
 */

class TagSelector {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Tag selector container #${containerId} not found`);
            return;
        }
        
        this.options = {
            apiBaseUrl: options.apiBaseUrl || '/api/tags',
            maxTags: options.maxTags || 10,
            placeholder: options.placeholder || 'Rechercher ou créer un tag...',
            selectedTags: options.selectedTags || [],
            ...options
        };
        
        this.selectedTags = new Map(); // Map<tagId, tagObject>
        this.allTags = [];
        
        this.init();
    }
    
    async init() {
        await this.loadAllTags();
        this.render();
        this.setupEventListeners();
        
        // Pré-sélectionner les tags si fournis
        if (this.options.selectedTags.length > 0) {
            this.options.selectedTags.forEach(tagId => {
                const tag = this.allTags.find(t => t.id === tagId);
                if (tag) {
                    this.addTag(tag);
                }
            });
        }
    }
    
    async loadAllTags() {
        try {
            const response = await fetch(this.options.apiBaseUrl + '/all');
            if (!response.ok) throw new Error('Failed to load tags');
            const data = await response.json();
            this.allTags = data.tags || [];
        } catch (error) {
            console.error('Error loading tags:', error);
            this.allTags = [];
        }
    }
    
    render() {
        this.container.innerHTML = `
            <div class="tag-selector">
                <div class="tag-selected-list mb-2" id="${this.container.id}-selected"></div>
                <div class="input-group">
                    <input type="text" 
                           class="form-control tag-search-input" 
                           id="${this.container.id}-search"
                           placeholder="${this.options.placeholder}"
                           autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="${this.container.id}-create-btn">
                        <i class="bi bi-plus-circle"></i> Nouveau
                    </button>
                </div>
                <div class="tag-autocomplete-list" id="${this.container.id}-autocomplete"></div>
            </div>
        `;
    }
    
    setupEventListeners() {
        const searchInput = document.getElementById(`${this.container.id}-search`);
        const createBtn = document.getElementById(`${this.container.id}-create-btn`);
        const autocompleteList = document.getElementById(`${this.container.id}-autocomplete`);
        
        // Autocomplete lors de la saisie
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.showAutocomplete(e.target.value);
            }, 300);
        });
        
        // Fermer l'autocomplete si clic en dehors
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                autocompleteList.innerHTML = '';
            }
        });
        
        // Bouton créer nouveau tag
        createBtn.addEventListener('click', () => this.showCreateModal());
        
        // Enter pour sélectionner le premier résultat
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstResult = autocompleteList.querySelector('.autocomplete-item');
                if (firstResult) {
                    firstResult.click();
                }
            }
        });
    }
    
    showAutocomplete(query) {
        const autocompleteList = document.getElementById(`${this.container.id}-autocomplete`);
        
        if (!query.trim()) {
            autocompleteList.innerHTML = '';
            return;
        }
        
        // Filtrer les tags disponibles (non déjà sélectionnés)
        const filtered = this.allTags.filter(tag => {
            return !this.selectedTags.has(tag.id) && 
                   tag.name.toLowerCase().includes(query.toLowerCase());
        }).slice(0, 10);
        
        if (filtered.length === 0) {
            autocompleteList.innerHTML = `
                <div class="autocomplete-empty p-2 text-muted">
                    <small>Aucun tag trouvé. Cliquez sur "Nouveau" pour en créer un.</small>
                </div>
            `;
            return;
        }
        
        autocompleteList.innerHTML = filtered.map(tag => `
            <div class="autocomplete-item p-2" data-tag-id="${tag.id}">
                <i class="bi bi-tag-fill text-${tag.color} me-2"></i>
                <span class="badge bg-${tag.color} me-2">${this.escapeHtml(tag.name)}</span>
                ${tag.usage_count > 0 ? `<small class="text-muted">(${tag.usage_count})</small>` : ''}
            </div>
        `).join('');
        
        // Événements de clic sur les résultats
        autocompleteList.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                const tagId = parseInt(item.dataset.tagId);
                const tag = this.allTags.find(t => t.id === tagId);
                if (tag) {
                    this.addTag(tag);
                    document.getElementById(`${this.container.id}-search`).value = '';
                    autocompleteList.innerHTML = '';
                }
            });
        });
    }
    
    addTag(tag) {
        if (this.selectedTags.has(tag.id)) return;
        if (this.selectedTags.size >= this.options.maxTags) {
            alert(`Maximum ${this.options.maxTags} tags autorisés`);
            return;
        }
        
        this.selectedTags.set(tag.id, tag);
        this.updateSelectedDisplay();
        this.updateHiddenInputs();
    }
    
    removeTag(tagId) {
        this.selectedTags.delete(tagId);
        this.updateSelectedDisplay();
        this.updateHiddenInputs();
    }
    
    updateSelectedDisplay() {
        const selectedList = document.getElementById(`${this.container.id}-selected`);
        
        if (this.selectedTags.size === 0) {
            selectedList.innerHTML = '<small class="text-muted">Aucun tag sélectionné</small>';
            return;
        }
        
        selectedList.innerHTML = Array.from(this.selectedTags.values()).map(tag => `
            <span class="badge bg-${tag.color} me-1 mb-1" style="font-size: 0.9rem;">
                <i class="bi bi-tag-fill me-1"></i>
                ${this.escapeHtml(tag.name)}
                <i class="bi bi-x-circle ms-1" 
                   style="cursor: pointer;" 
                   onclick="tagSelector.removeTag(${tag.id})"></i>
            </span>
        `).join('');
    }
    
    updateHiddenInputs() {
        // Supprimer les anciens inputs cachés
        this.container.querySelectorAll('input[name="tags[]"]').forEach(input => input.remove());
        
        // Créer de nouveaux inputs pour chaque tag sélectionné
        Array.from(this.selectedTags.keys()).forEach(tagId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tagId;
            this.container.appendChild(input);
        });
    }
    
    showCreateModal() {
        const modalHtml = `
            <div class="modal fade" id="quickCreateTagModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-plus-circle text-primary"></i> Créer un Tag
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nom du tag</label>
                                <input type="text" class="form-control" id="quickTagName" 
                                       placeholder="Ex: Urgent, Personnel..." maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Couleur</label>
                                <div class="d-flex flex-wrap gap-2" id="quickTagColors"></div>
                            </div>
                            <div id="quickTagError" class="alert alert-danger d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="quickTagSubmit">
                                <i class="bi bi-check-circle"></i> Créer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Supprimer ancien modal si existe
        document.getElementById('quickCreateTagModal')?.remove();
        
        // Ajouter le nouveau modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Charger les couleurs
        this.renderColorPicker();
        
        // Ouvrir le modal
        const modal = new bootstrap.Modal(document.getElementById('quickCreateTagModal'));
        modal.show();
        
        // Focus sur le champ nom
        setTimeout(() => document.getElementById('quickTagName').focus(), 500);
        
        // Événement submit
        document.getElementById('quickTagSubmit').addEventListener('click', () => {
            this.submitQuickCreate(modal);
        });
        
        // Enter pour soumettre
        document.getElementById('quickTagName').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.submitQuickCreate(modal);
            }
        });
    }
    
    async renderColorPicker() {
        const colorsContainer = document.getElementById('quickTagColors');
        const colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
        const labels = ['Bleu', 'Gris', 'Vert', 'Rouge', 'Jaune', 'Cyan', 'Noir'];
        
        colorsContainer.innerHTML = colors.map((color, index) => `
            <div>
                <input type="radio" class="btn-check" name="quickTagColor" 
                       id="quickColor-${color}" value="${color}" ${index === 0 ? 'checked' : ''}>
                <label class="btn btn-outline-${color}" for="quickColor-${color}" style="min-width: 80px;">
                    <span class="badge bg-${color}" style="width: 15px; height: 15px; display: inline-block;"></span>
                    ${labels[index]}
                </label>
            </div>
        `).join('');
    }
    
    async submitQuickCreate(modal) {
        const name = document.getElementById('quickTagName').value.trim();
        const color = document.querySelector('input[name="quickTagColor"]:checked')?.value || 'secondary';
        const errorDiv = document.getElementById('quickTagError');
        
        if (!name) {
            errorDiv.textContent = 'Le nom du tag est obligatoire';
            errorDiv.classList.remove('d-none');
            return;
        }
        
        try {
            const response = await fetch(this.options.apiBaseUrl + '/quick-create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, color })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                errorDiv.textContent = data.error || 'Erreur lors de la création';
                errorDiv.classList.remove('d-none');
                return;
            }
            
            // Ajouter le nouveau tag à la liste complète
            this.allTags.push(data.tag);
            
            // Sélectionner automatiquement le nouveau tag
            this.addTag(data.tag);
            
            // Fermer le modal
            modal.hide();
            
            // Nettoyer le DOM
            setTimeout(() => {
                document.getElementById('quickCreateTagModal')?.remove();
            }, 500);
            
        } catch (error) {
            console.error('Error creating tag:', error);
            errorDiv.textContent = 'Erreur réseau lors de la création';
            errorDiv.classList.remove('d-none');
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Instance globale pour faciliter l'accès depuis onclick
let tagSelector = null;
