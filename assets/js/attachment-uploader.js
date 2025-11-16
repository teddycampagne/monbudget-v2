/**
 * Gestionnaire d'upload de pièces jointes pour transactions
 * 
 * Gère le drag&drop, l'upload AJAX, l'affichage des vignettes
 * et la suppression des fichiers.
 * 
 * @version 2.1.0-dev
 */

class AttachmentUploader {
    constructor(transactionId, compteId) {
        this.transactionId = transactionId;
        this.compteId = compteId;
        this.dropZone = document.getElementById('attachment-dropzone');
        this.fileInput = document.getElementById('attachment-file-input');
        this.fileList = document.getElementById('attachment-file-list');
        this.baseUrl = this.dropZone?.dataset.baseUrl || '';
        
        this.init();
    }

    init() {
        if (!this.dropZone || !this.fileInput) return;

        // Drag & Drop events
        this.dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.dropZone.addEventListener('drop', (e) => this.handleDrop(e));
        
        // Click to select
        this.dropZone.addEventListener('click', () => this.fileInput.click());
        
        // File input change
        this.fileInput.addEventListener('change', (e) => this.handleFiles(e.target.files));
    }

    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.add('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        this.handleFiles(files);
    }

    handleFiles(files) {
        if (!files.length) return;

        // Limiter à 5 fichiers simultanés
        if (files.length > 5) {
            this.showError('Maximum 5 fichiers à la fois');
            return;
        }

        Array.from(files).forEach(file => this.uploadFile(file));
    }

    async uploadFile(file) {
        // Validation côté client
        const maxSize = 5 * 1024 * 1024; // 5 MB
        if (file.size > maxSize) {
            this.showError(`${file.name} : Fichier trop volumineux (max 5 Mo)`);
            return;
        }

        const allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'text/csv'
        ];

        if (!allowedTypes.includes(file.type)) {
            this.showError(`${file.name} : Type de fichier non autorisé`);
            return;
        }

        // Créer un élément de progression
        const progressItem = this.createProgressItem(file.name);
        this.fileList.appendChild(progressItem);

        // Préparer FormData
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(`${this.baseUrl}/comptes/${this.compteId}/transactions/${this.transactionId}/attachments/upload`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Remplacer la progression par le fichier uploadé
                this.fileList.removeChild(progressItem);
                this.addFileItem(data.attachment);
                this.showSuccess(`${file.name} uploadé avec succès`);
            } else {
                throw new Error(data.error || 'Erreur lors de l\'upload');
            }

        } catch (error) {
            this.fileList.removeChild(progressItem);
            this.showError(`${file.name} : ${error.message}`);
        }
    }

    createProgressItem(filename) {
        const div = document.createElement('div');
        div.className = 'attachment-progress-item mb-2 p-2 border rounded bg-light';
        div.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">Uploading...</span>
                </div>
                <span class="text-muted small">${this.escapeHtml(filename)}</span>
            </div>
        `;
        return div;
    }

    addFileItem(attachment) {
        const div = document.createElement('div');
        div.className = 'attachment-item mb-2 p-2 border rounded';
        div.dataset.id = attachment.id;

        const iconClass = attachment.icon || 'bi-file-earmark';
        const isImage = attachment.is_image;

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center flex-grow-1">
                    <i class="bi ${iconClass} fs-4 text-primary me-2"></i>
                    <div class="flex-grow-1">
                        <div class="fw-medium">${this.escapeHtml(attachment.original_name)}</div>
                        <small class="text-muted">${attachment.size} - ${attachment.uploaded_at}</small>
                    </div>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    ${isImage ? `<button type="button" class="btn btn-outline-primary btn-preview" title="Aperçu">
                        <i class="bi bi-eye"></i>
                    </button>` : ''}
                    <a href="${this.baseUrl}/comptes/${this.compteId}/transactions/${this.transactionId}/attachments/${attachment.id}/download" 
                       class="btn btn-outline-secondary" 
                       title="Télécharger">
                        <i class="bi bi-download"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-delete" title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;

        // Event listener pour suppression
        div.querySelector('.btn-delete').addEventListener('click', () => this.deleteFile(attachment.id, div));

        // Event listener pour preview (images uniquement)
        if (isImage) {
            div.querySelector('.btn-preview').addEventListener('click', () => this.previewImage(attachment));
        }

        this.fileList.appendChild(div);
    }

    async deleteFile(attachmentId, element) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette pièce jointe ?')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/comptes/${this.compteId}/transactions/${this.transactionId}/attachments/${attachmentId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Animer la suppression
                element.style.opacity = '0';
                element.style.transition = 'opacity 0.3s';
                setTimeout(() => this.fileList.removeChild(element), 300);
                this.showSuccess('Pièce jointe supprimée');
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }

        } catch (error) {
            this.showError(error.message);
        }
    }

    previewImage(attachment) {
        // Créer modal Bootstrap pour preview
        const modalHtml = `
            <div class="modal fade" id="imagePreviewModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${this.escapeHtml(attachment.original_name)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${this.baseUrl}/uploads/${attachment.path}" 
                                 class="img-fluid" 
                                 alt="${this.escapeHtml(attachment.original_name)}">
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insérer modal dans le DOM
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHtml;
        document.body.appendChild(tempDiv.firstElementChild);

        // Afficher modal
        const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        modal.show();

        // Nettoyer après fermeture
        document.getElementById('imagePreviewModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'danger');
    }

    showToast(message, type = 'info') {
        // Créer toast Bootstrap
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${this.escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        // Container pour toasts
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = toastHtml;
        const toastElement = tempDiv.firstElementChild;
        toastContainer.appendChild(toastElement);

        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

// Auto-initialisation si transaction_id et compte_id sont disponibles
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('attachment-dropzone');
    if (dropzone) {
        const transactionId = dropzone.dataset.transactionId;
        const compteId = dropzone.dataset.compteId;
        
        if (transactionId && compteId) {
            new AttachmentUploader(transactionId, compteId);
        }
    }
});
