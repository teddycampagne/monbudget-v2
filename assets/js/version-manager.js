/**
 * Gestionnaire de mises à jour de version
 * 
 * Vérifie automatiquement les nouvelles versions et propose déploiement
 * 
 * @version 2.2.0
 */

class VersionManager {
    constructor() {
        this.checkInterval = 3600000; // 1 heure
        this.updateData = null;
        this.init();
    }
    
    init() {
        // Vérifier au chargement de la page
        this.checkForUpdates();
        
        // Vérifier périodiquement
        setInterval(() => this.checkForUpdates(), this.checkInterval);
        
        // Écouter les clics sur le badge de notification
        document.addEventListener('click', (e) => {
            if (e.target.closest('#version-update-badge')) {
                e.preventDefault();
                this.showUpdateModal();
            }
        });
    }
    
    /**
     * Vérifier les mises à jour disponibles
     */
    async checkForUpdates() {
        try {
            const baseUrl = window.APP_CONFIG?.baseUrl || '';
            const response = await fetch(`${baseUrl}/api/version/check-update`);
            const data = await response.json();
            
            if (data.success && data.update_available) {
                this.updateData = data.update;
                this.showUpdateNotification();
            } else {
                this.hideUpdateNotification();
            }
            
        } catch (error) {
            console.error('Erreur vérification version:', error);
        }
    }
    
    /**
     * Afficher la notification de mise à jour dans le header
     */
    showUpdateNotification() {
        // Vérifier si le badge existe déjà
        let badge = document.getElementById('version-update-badge');
        
        if (!badge) {
            // Créer le badge
            badge = document.createElement('a');
            badge.id = 'version-update-badge';
            badge.href = '#';
            badge.className = 'nav-link position-relative';
            badge.innerHTML = `
                <i class="bi bi-cloud-arrow-down-fill text-warning"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    Nouveau
                    <span class="visually-hidden">Nouvelle version disponible</span>
                </span>
            `;
            
            // Ajouter dans le nav (avant le lien profil)
            const nav = document.querySelector('.navbar-nav.ms-auto');
            if (nav) {
                const profileLink = nav.querySelector('[href*="profile"]');
                if (profileLink) {
                    const li = document.createElement('li');
                    li.className = 'nav-item';
                    li.appendChild(badge);
                    profileLink.parentElement.before(li);
                } else {
                    const li = document.createElement('li');
                    li.className = 'nav-item';
                    li.appendChild(badge);
                    nav.appendChild(li);
                }
            }
        }
        
        // Afficher un toast (si pas déjà vu)
        const lastNotification = localStorage.getItem('last_version_notification');
        if (lastNotification !== this.updateData.version) {
            this.showToast(
                'Mise à jour disponible',
                `La version ${this.updateData.version} est disponible !`,
                'warning'
            );
            localStorage.setItem('last_version_notification', this.updateData.version);
        }
    }
    
    /**
     * Masquer la notification
     */
    hideUpdateNotification() {
        const badge = document.getElementById('version-update-badge');
        if (badge) {
            badge.parentElement.remove();
        }
    }
    
    /**
     * Afficher la modal de mise à jour
     */
    showUpdateModal() {
        if (!this.updateData) {
            return;
        }
        
        // Créer ou récupérer la modal
        let modal = document.getElementById('versionUpdateModal');
        
        if (!modal) {
            modal = this.createUpdateModal();
            document.body.appendChild(modal);
        }
        
        // Remplir avec les données
        this.populateModal(modal);
        
        // Afficher
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    /**
     * Créer la modal de mise à jour
     */
    createUpdateModal() {
        const modal = document.createElement('div');
        modal.id = 'versionUpdateModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-cloud-arrow-down-fill"></i>
                            Mise à jour disponible
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Version actuelle:</strong> <span id="current-version"></span>
                                </div>
                                <div>
                                    <i class="bi bi-arrow-right mx-3"></i>
                                </div>
                                <div>
                                    <strong>Nouvelle version:</strong> <span id="new-version" class="text-success fw-bold"></span>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">📝 Notes de version</h6>
                        <div id="changelog-content" class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;">
                            <!-- Changelog ici -->
                        </div>
                        
                        <div id="deployment-output" class="mt-3" style="display: none;">
                            <h6>📤 Sortie du déploiement</h6>
                            <div class="border rounded p-3 bg-dark text-light" style="max-height: 200px; overflow-y: auto;">
                                <pre id="deployment-log" class="text-light mb-0" style="font-size: 0.85rem;"></pre>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Important:</strong> Assurez-vous d'avoir sauvegardé votre base de données avant de déployer.
                            Le déploiement peut nécessiter l'exécution manuelle de migrations SQL.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Annuler
                        </button>
                        <button type="button" id="btn-deploy" class="btn btn-primary">
                            <i class="bi bi-download"></i> Déployer maintenant
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter l'événement de déploiement
        modal.querySelector('#btn-deploy').addEventListener('click', () => {
            this.deployUpdate();
        });
        
        return modal;
    }
    
    /**
     * Remplir la modal avec les données
     */
    populateModal(modal) {
        modal.querySelector('#current-version').textContent = this.updateData.current_version;
        modal.querySelector('#new-version').textContent = this.updateData.version;
        modal.querySelector('#changelog-content').textContent = this.updateData.changelog;
    }
    
    /**
     * Déployer la mise à jour
     */
    async deployUpdate() {
        const btn = document.getElementById('btn-deploy');
        const outputDiv = document.getElementById('deployment-output');
        const logPre = document.getElementById('deployment-log');
        
        // Désactiver le bouton
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Déploiement en cours...';
        
        // Afficher la zone de sortie
        outputDiv.style.display = 'block';
        logPre.textContent = '🚀 Démarrage du déploiement...\n';
        
        try {
            const formData = new FormData();
            formData.append('version', this.updateData.version);
            
            const baseUrl = window.APP_CONFIG?.baseUrl || '';
            const response = await fetch(`${baseUrl}/api/version/deploy`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            // Afficher la sortie
            if (result.output && Array.isArray(result.output)) {
                logPre.textContent += '\n' + result.output.join('\n');
            }
            
            if (result.success) {
                // Succès
                btn.className = 'btn btn-success';
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Déploiement réussi !';
                
                this.showToast(
                    'Mise à jour réussie',
                    `La version ${this.updateData.version} a été déployée avec succès. La page va se recharger dans 3 secondes.`,
                    'success'
                );
                
                // Recharger la page après 3 secondes
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
                
            } else {
                // Erreur
                btn.className = 'btn btn-danger';
                btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> Échec';
                
                this.showToast(
                    'Erreur de déploiement',
                    result.message || 'Une erreur est survenue',
                    'danger'
                );
                
                // Réactiver après 3 secondes
                setTimeout(() => {
                    btn.disabled = false;
                    btn.className = 'btn btn-primary';
                    btn.innerHTML = '<i class="bi bi-download"></i> Réessayer';
                }, 3000);
            }
            
        } catch (error) {
            logPre.textContent += '\n\n❌ ERREUR: ' + error.message;
            
            btn.className = 'btn btn-danger';
            btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> Erreur';
            
            this.showToast(
                'Erreur réseau',
                'Impossible de communiquer avec le serveur',
                'danger'
            );
            
            setTimeout(() => {
                btn.disabled = false;
                btn.className = 'btn btn-primary';
                btn.innerHTML = '<i class="bi bi-download"></i> Réessayer';
            }, 3000);
        }
    }
    
    /**
     * Afficher un toast de notification
     */
    showToast(title, message, type = 'info') {
        // Créer le conteneur de toasts s'il n'existe pas
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        // Créer le toast
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        container.appendChild(toastEl);
        
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Supprimer après fermeture
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }
}

// Initialiser au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    window.versionManager = new VersionManager();
});
