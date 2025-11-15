<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil"></i> Modifier la Catégorie
                <?php if ($categorie['user_id'] === null): ?>
                    <span class="badge bg-warning text-dark ms-2">
                        <i class="bi bi-shield-exclamation"></i> Catégorie système (Admin)
                    </span>
                <?php endif; ?>
            </h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($categorie['nom']) ?></p>
        </div>
        <a href="<?= url('categories') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <?php if ($categorie['user_id'] === null): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Attention :</strong> Vous modifiez une catégorie système visible par tous les utilisateurs. 
            Les changements affecteront l'ensemble de l'application.
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulaire -->
        <div class="col-lg-8">
            <form method="POST" action="<?= url("categories/{$categorie['id']}/update") ?>">
                <?= csrf_field() ?>

                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $typeOptions = [
                            'depense' => '💸 Dépense',
                            'revenu' => '💰 Revenu',
                            'mixte' => '🔄 Mixte (Dépense et Revenu)'
                        ];
                        echo formSelect('type', 'Type', $typeOptions, $categorie['type'], true, '');
                        ?>
                        <small class="text-muted d-block mb-3">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Mixte :</strong> Pour des opérations pouvant être à la fois des dépenses et des revenus
                        </small>

                        <?= formInput('nom', 'Nom', 'text', $categorie['nom'], true, 'Ex: Alimentation, Salaire, Mutuelle...', ['maxlength' => '100']) ?>

                        <?= formTextarea('description', 'Description', $categorie['description'] ?? '', 3, false, 'Description optionnelle...') ?>

                        <?php if ($isAdmin ?? false): ?>
                            <div class="card border-warning mb-3">
                                <div class="card-body bg-warning bg-opacity-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_system" name="is_system" value="1" 
                                               <?= $categorie['user_id'] === null ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_system">
                                            <strong><i class="bi bi-globe"></i> Catégorie système</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle"></i>
                                        <?php if ($categorie['user_id'] === null): ?>
                                            Cette catégorie est actuellement <strong>visible par tous les utilisateurs</strong>. 
                                            Décocher pour la rendre privée.
                                        <?php else: ?>
                                            Cocher pour rendre cette catégorie <strong>visible par tous les utilisateurs</strong>.
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="couleur" class="form-label">Couleur</label>
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="couleur" 
                                       name="couleur" 
                                       value="<?= htmlspecialchars($categorie['couleur'] ?? '#6c757d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="icone" class="form-label">Icône (Bootstrap Icons)</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i id="icone-preview" class="<?= htmlspecialchars($categorie['icone'] ?? 'bi-tag') ?>"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="icone" 
                                           name="icone" 
                                           value="<?= htmlspecialchars($categorie['icone'] ?? 'bi-tag') ?>" 
                                           placeholder="bi-cart, bi-house...">
                                </div>
                                <small class="text-muted">
                                    <a href="https://icons.getbootstrap.com/" target="_blank">Voir toutes les icônes</a>
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Catégorie parente (optionnel)</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">Catégorie principale</option>
                                <?php foreach ($categoriesPrincipales as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                            <?= $cat['id'] == ($categorie['parent_id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                Laissez vide pour une catégorie principale
                            </small>
                        </div>
                    </div>
                </div>

                <?php if (!empty($categorie['sous_categories'])): ?>
                <!-- Sous-catégories existantes -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Sous-catégories (<?= count($categorie['sous_categories']) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($categorie['sous_categories'] as $sous): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-arrow-return-right text-muted"></i>
                                        <strong><?= htmlspecialchars($sous['nom']) ?></strong>
                                        <?php if ($sous['description']): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($sous['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            Cette catégorie a des sous-catégories. Supprimez-les d'abord si vous souhaitez supprimer cette catégorie.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Boutons -->
                <div class="d-flex gap-2">
                    <?= cancelButton('categories', 'Annuler') ?>
                    <?= submitButton('Enregistrer les modifications') ?>
                </div>
            </form>
        </div>

        <!-- Informations -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Type :</strong>
                        <span class="badge <?= $categorie['type'] === 'depense' ? 'bg-danger' : 'bg-success' ?>">
                            <?= $categorie['type'] === 'depense' ? 'Dépense' : 'Revenu' ?>
                        </span>
                    </p>
                    <p class="small mb-2">
                        <strong>Sous-catégories :</strong> <?= count($categorie['sous_categories'] ?? []) ?>
                    </p>
                    <hr>
                    <p class="small text-muted">
                        <i class="bi bi-exclamation-circle"></i>
                        Les modifications affecteront toutes les transactions futures utilisant cette catégorie.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/icon-picker.js') ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
