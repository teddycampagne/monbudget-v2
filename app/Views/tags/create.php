<?php
/**
 * Vue Création de Tag
 * Formulaire de création d'un nouveau tag avec sélection de couleur
 */

use MonBudget\Models\Tag;

// Récupérer les anciennes valeurs et erreurs
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

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
                    <i class="bi bi-plus-circle text-primary"></i>
                    Créer un Nouveau Tag
                </h1>
            </div>

            <!-- Formulaire -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/tags/store') ?>">
                        
                        <!-- Nom du tag -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Nom du Tag <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($old['name'] ?? '') ?>"
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
                                $selectedColor = $old['color'] ?? 'secondary';
                                foreach (array_keys(Tag::COLORS) as $color): 
                                    $colorLabel = Tag::getColorLabel($color);
                                    $isSelected = ($color === $selectedColor);
                                ?>
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="color" 
                                               id="color_<?= $color ?>" 
                                               value="<?= $color ?>"
                                               <?= $isSelected ? 'checked' : '' ?>
                                               required>
                                        <label class="btn btn-outline-<?= $color ?> w-100 d-flex align-items-center justify-content-center py-2" 
                                               for="color_<?= $color ?>"
                                               style="border-width: 2px;">
                                            <span class="badge bg-<?= $color ?> me-2" style="width: 20px; height: 20px;"></span>
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
                                    <i class="bi bi-tag-fill text-<?= $selectedColor ?> me-2"></i>
                                    <span class="badge bg-<?= $selectedColor ?> fs-6" id="previewBadge">
                                        <span id="previewText"><?= htmlspecialchars($old['name'] ?? 'Exemple Tag') ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('/tags') ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Créer le Tag
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Aide -->
            <div class="card mt-3 bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-question-circle text-info"></i> 
                        Conseils d'utilisation
                    </h6>
                    <ul class="mb-0 small">
                        <li>Choisissez des noms courts et descriptifs pour vos tags</li>
                        <li>Utilisez des couleurs différentes pour distinguer les types de tags (urgent=rouge, perso=bleu, etc.)</li>
                        <li>Les tags permettent d'ajouter plusieurs étiquettes à une même transaction</li>
                        <li>Vous pourrez ensuite filtrer vos transactions par tags</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
