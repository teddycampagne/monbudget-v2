<?php
/**
 * Composant : Zone d'upload de pièces jointes pour transactions
 * 
 * Usage : require __DIR__ . '/attachment-uploader.php';
 * 
 * Variables requises :
 * - $transaction['id'] : ID de la transaction
 * - $compte['id'] : ID du compte
 * 
 * @version 2.1.0-dev
 */

use App\Models\Attachment;

// Récupérer les pièces jointes existantes si la transaction existe
$attachments = [];
if (isset($transaction['id']) && $transaction['id']) {
    $attachments = Attachment::findByTransaction($transaction['id']);
}
?>

<!-- Section Pièces jointes -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="bi bi-paperclip"></i> Pièces jointes
            <?php if (count($attachments) > 0): ?>
                <span class="badge bg-light text-dark"><?= count($attachments) ?></span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($transaction['id']) && $transaction['id']): ?>
            <!-- Zone de drag & drop -->
            <div id="attachment-dropzone" 
                 class="border border-2 border-dashed rounded p-4 text-center mb-3"
                 data-transaction-id="<?= $transaction['id'] ?>"
                 data-compte-id="<?= $compte['id'] ?>"
                 style="cursor: pointer; transition: all 0.3s;">
                <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                <p class="mb-1"><strong>Cliquez ou glissez-déposez vos fichiers ici</strong></p>
                <p class="text-muted small mb-0">
                    PDF, Images, Excel, Word, TXT, CSV (max 5 Mo par fichier)
                </p>
                <input type="file" 
                       id="attachment-file-input" 
                       multiple 
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.xls,.xlsx,.doc,.docx,.txt,.csv"
                       style="display: none;">
            </div>

            <!-- Liste des fichiers -->
            <div id="attachment-file-list">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="attachment-item mb-2 p-2 border rounded" data-id="<?= $attachment['id'] ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center flex-grow-1">
                                <i class="bi <?= Attachment::getIcon($attachment['mimetype']) ?> fs-4 text-primary me-2"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-medium"><?= htmlspecialchars($attachment['original_name']) ?></div>
                                    <small class="text-muted">
                                        <?= Attachment::formatFileSize($attachment['size']) ?> - 
                                        <?= date('d/m/Y H:i', strtotime($attachment['uploaded_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <?php if (Attachment::isImage($attachment)): ?>
                                    <button type="button" class="btn btn-outline-primary btn-preview" title="Aperçu">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="<?= url("comptes/{$compte['id']}/transactions/{$transaction['id']}/attachments/{$attachment['id']}/download") ?>" 
                                   class="btn btn-outline-secondary" 
                                   title="Télécharger">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-delete" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($attachments)): ?>
                <p class="text-muted text-center mb-0 small">
                    <i class="bi bi-info-circle"></i> Aucune pièce jointe pour cette transaction
                </p>
            <?php endif; ?>

        <?php else: ?>
            <!-- Transaction non encore créée -->
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i> 
                <strong>Information :</strong> Vous pourrez ajouter des pièces jointes après la création de la transaction.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Inclure le JavaScript -->
<?php if (isset($transaction['id']) && $transaction['id']): ?>
    <link rel="stylesheet" href="<?= url('assets/css/attachment-uploader.css') ?>">
    <script src="<?= url('assets/js/attachment-uploader.js') ?>"></script>
    <style>
        #attachment-dropzone:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd !important;
        }
        #attachment-dropzone.dragover {
            background-color: #e7f1ff;
            border-color: #0d6efd !important;
        }
    </style>
<?php endif; ?>
