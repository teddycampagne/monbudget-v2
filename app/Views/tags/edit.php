<?php
/**
 * Vue Édition de Tag
 * Formulaire de modification d'un tag existant
 */

use MonBudget\Models\Tag;

// Récupérer les données du tag
$tag = $tag ?? null;
if (!$tag) {
    header('Location: ' . url('/tags'));
    exit;
}

// Récupérer les anciennes valeurs et erreurs
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

// Utiliser les anciennes valeurs si disponibles, sinon les valeurs du tag
$name = $old['name'] ?? $tag['name'];
$color = $old['color'] ?? $tag['color'];

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- En-tête -->
            <div class="d-flex align-items-center mb-4">
                <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil text-primary"></i>
                    Modifier le Tag
                </h1>
            </div>

            <!-- Formulaire -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/tags/' . $tag['id'] . '/update') ?>">
                        
                        <!-- Nom du tag -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Nom du Tag <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($name) ?>"
                                   placeholder="Ex: Urgent, Personnel, Professionnel..."
                                   maxlength="50"
                                   required
                                   autofocus>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> 
                                Maximum 50 caractères. Lettres, chiffres, espaces et tirets autorisés.
                            </div>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Couleur -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                Couleur <span class="text-danger">*</span>
                            </label>
                            <div class="form-text mb-3">
                                <i class="bi bi-palette"></i> 
                                Sélectionnez une couleur pour votre tag. Cela facilitera l'identification visuelle.
                            </div>
                            
                            <div class="row g-2">
                                <?php 
                                foreach (array_keys(Tag::COLORS) as $colorOption): 
                                    $colorLabel = Tag::getColorLabel($colorOption);
                                    $isSelected = ($colorOption === $color);
                                ?>
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="color" 
                                               id="color_<?= $colorOption ?>" 
                                               value="<?= $colorOption ?>"
                                               <?= $isSelected ? 'checked' : '' ?>
                                               required>
                                        <label class="btn btn-outline-<?= $colorOption ?> w-100 d-flex align-items-center justify-content-center py-2" 
                                               for="color_<?= $colorOption ?>"
                                               style="border-width: 2px;">
                                            <span class="badge bg-<?= $colorOption ?> me-2" style="width: 20px; height: 20px;"></span>
                                            <span><?= $colorLabel ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (isset($errors['color'])): ?>
                                <div class="text-danger mt-2">
                                    <small><?= $errors['color'] ?></small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Aperçu -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Aperçu</label>
                            <div class="p-3 bg-light rounded border">
                                <div id="tagPreview" class="d-inline-flex align-items-center">
                                    <i class="bi bi-tag-fill text-<?= $color ?> me-2"></i>
                                    <span class="badge bg-<?= $color ?> fs-6" id="previewBadge">
                                        <span id="previewText"><?= htmlspecialchars($name) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Statistiques d'utilisation -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Utilisation</label>
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-graph-up text-info fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">
                                            <?= $tag['usage_count'] ?? 0 ?> 
                                            transaction<?= ($tag['usage_count'] ?? 0) > 1 ? 's' : '' ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php if ($tag['usage_count'] > 0): ?>
                                                Ce tag est utilisé. Les modifications seront visibles sur toutes les transactions associées.
                                            <?php else: ?>
                                                Ce tag n'est pas encore utilisé.
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <button type="button" 
                                    class="btn btn-danger" 
                                    onclick="confirmDelete()">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                            <div class="d-flex gap-2">
                                <a href="<?= url('/tags') ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Aide -->
            <div class="card mt-3 bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-info-circle text-info"></i> 
                        Informations
                    </h6>
                    <ul class="mb-0 small">
                        <li><strong>Nom du tag :</strong> Le changement de nom sera appliqué à toutes les transactions utilisant ce tag</li>
                        <li><strong>Couleur :</strong> Changez la couleur pour mieux organiser visuellement vos tags</li>
                        <li><strong>Suppression :</strong> La suppression d'un tag le retirera de toutes les transactions associées</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de suppression caché -->
<form id="deleteForm" method="POST" action="<?= url('/tags/' . $tag['id'] . '/delete') ?>" style="display: none;">
    <input type="hidden" name="confirm" value="1">
</form>

<script>
// Confirmation de suppression
function confirmDelete() {
    const usageCount = <?= $tag['usage_count'] ?? 0 ?>;
    let message = `Voulez-vous vraiment supprimer le tag "<?= htmlspecialchars($tag['name'], ENT_QUOTES) ?>" ?\n\n`;
    
    if (usageCount > 0) {
        message += `Ce tag est utilisé sur ${usageCount} transaction${usageCount > 1 ? 's' : ''}.\nToutes ces associations seront supprimées.`;
    } else {
        message += `Ce tag n'est pas utilisé et sera définitivement supprimé.`;
    }
    
    if (confirm(message)) {
        document.getElementById('deleteForm').submit();
    }
}

// Mise à jour de l'aperçu en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const colorInputs = document.querySelectorAll('input[name="color"]');
    const previewText = document.getElementById('previewText');
    const previewBadge = document.getElementById('previewBadge');
    const previewIcon = document.querySelector('#tagPreview i');

    // Mise à jour du texte
    nameInput.addEventListener('input', function() {
        const text = this.value.trim() || 'Exemple Tag';
        previewText.textContent = text;
    });

    // Mise à jour de la couleur
    colorInputs.forEach(input => {
        input.addEventListener('change', function() {
            const color = this.value;
            
            // Retirer toutes les classes de couleur
            previewBadge.className = 'badge fs-6';
            previewIcon.className = 'bi bi-tag-fill me-2';
            
            // Ajouter la nouvelle couleur
            previewBadge.classList.add('bg-' + color);
            previewIcon.classList.add('text-' + color);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
