<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-plus-lg"></i> Nouvelle Catégorie</h1>
            <?php if (isset($parentCategorie)): ?>
                <p class="text-muted mb-0">
                    Créer une sous-catégorie de 
                    <span class="badge" style="background-color: <?= htmlspecialchars($parentCategorie['couleur']) ?>">
                        <i class="<?= htmlspecialchars($parentCategorie['icone']) ?>"></i>
                        <?= htmlspecialchars($parentCategorie['nom']) ?>
                    </span>
                </p>
            <?php else: ?>
                <p class="text-muted mb-0">Créer une catégorie de <?= $type === 'revenu' ? 'revenu' : 'dépense' ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= url('categories') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <!-- Formulaire -->
        <div class="col-lg-8">
            <form method="POST" action="<?= url('categories/store') ?>">
                <?= csrf_field() ?>

                <div class="card">
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
                        echo formSelect('type', 'Type de catégorie', $typeOptions, $type, true, '');
                        ?>
                        <small class="text-muted d-block mb-3">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Mixte :</strong> Pour des opérations pouvant être à la fois des dépenses et des revenus 
                            (ex: Mutuelle, Impôts, Assurance)
                        </small>

                        <?= formInput('nom', 'Nom', 'text', '', true, 'Ex: Alimentation, Salaire, Mutuelle...', ['maxlength' => '100']) ?>

                        <?= formTextarea('description', 'Description', '', 3, false, 'Description optionnelle...') ?>

                        <?php if ($isAdmin ?? false): ?>
                            <div class="card border-warning mb-3">
                                <div class="card-body bg-warning bg-opacity-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_system" name="is_system" value="1">
                                        <label class="form-check-label" for="is_system">
                                            <strong><i class="bi bi-globe"></i> Catégorie système</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle"></i>
                                        Si cochée, cette catégorie sera visible et utilisable par tous les utilisateurs de l'application
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
                                       value="<?= $type === 'revenu' ? '#28a745' : '#dc3545' ?>">
                                <small class="text-muted">Couleur pour identifier la catégorie</small>
                            </div>
                            <div class="col-md-6">
                                <label for="icone" class="form-label">Icône (Bootstrap Icons)</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i id="icone-preview" class="bi-tag"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="icone" 
                                           name="icone" 
                                           value="bi-tag" 
                                           placeholder="bi-cart, bi-house...">
                                </div>
                                <small class="text-muted">
                                    <a href="https://icons.getbootstrap.com/" target="_blank">Voir toutes les icônes</a>
                                </small>
                            </div>
                        </div>

                        <?php if (isset($parentCategorie)): ?>
                            <!-- Parent fixé (sous-catégorie) -->
                            <div class="mb-3">
                                <label class="form-label">Catégorie parente</label>
                                <div class="card border-primary">
                                    <div class="card-body bg-primary bg-opacity-10">
                                        <i class="bi bi-info-circle text-primary"></i>
                                        Cette sous-catégorie sera rattachée à :
                                        <strong>
                                            <i class="<?= htmlspecialchars($parentCategorie['icone']) ?>" 
                                               style="color: <?= htmlspecialchars($parentCategorie['couleur']) ?>;"></i>
                                            <?= htmlspecialchars($parentCategorie['nom']) ?>
                                        </strong>
                                    </div>
                                </div>
                                <input type="hidden" name="parent_id" value="<?= $parentCategorie['id'] ?>">
                            </div>
                        <?php else: ?>
                            <!-- Aucun parent = catégorie principale -->
                            <input type="hidden" name="parent_id" value="">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2 mt-3">
                    <a href="<?= url('categories') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Créer la catégorie
                    </button>
                </div>
            </form>
        </div>

        <!-- Aide -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-question-circle"></i> Aide</h6>
                </div>
                <div class="card-body">
                    <h6>Exemples de catégories</h6>
                    
                    <?php if ($type === 'depense'): ?>
                        <p class="small"><strong>Dépenses courantes :</strong></p>
                        <ul class="small">
                            <li>Alimentation (Restaurants, Courses)</li>
                            <li>Transport (Carburant, Transports publics)</li>
                            <li>Logement (Loyer, Électricité, Eau)</li>
                            <li>Loisirs (Cinéma, Sport, Voyages)</li>
                            <li>Santé (Médecin, Pharmacie)</li>
                        </ul>
                    <?php else: ?>
                        <p class="small"><strong>Revenus courants :</strong></p>
                        <ul class="small">
                            <li>Salaire (Salaire principal, Primes)</li>
                            <li>Investissements (Dividendes, Intérêts)</li>
                            <li>Prestations (Allocations, Remboursements)</li>
                            <li>Autres (Ventes, Cadeaux)</li>
                        </ul>
                    <?php endif; ?>

                    <hr>
                    <h6>Structure hiérarchique</h6>
                    <p class="small">
                        Créez d'abord des catégories principales générales, 
                        puis ajoutez des sous-catégories pour plus de précision dans vos analyses.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/icon-picker.js') ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
