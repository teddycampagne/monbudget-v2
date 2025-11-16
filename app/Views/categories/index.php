<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../components/breadcrumbs.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumbs -->
    <?php renderBreadcrumbs([
        ['label' => 'Accueil', 'url' => url(''), 'icon' => 'house-door'],
        ['label' => 'Catégories', 'icon' => 'tags']
    ]); ?>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-tags"></i> Catégories</h1>
            <p class="text-muted mb-0">Organisez vos transactions par catégories</p>
        </div>
        <a href="<?= url('categories/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nouvelle Catégorie
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?= url('categories') ?>" 
                   class="btn btn-sm <?= empty($type_filtre) ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="bi bi-grid"></i> Toutes
                </a>
                <a href="<?= url('categories?type=depense') ?>" 
                   class="btn btn-sm <?= $type_filtre === 'depense' ? 'btn-danger' : 'btn-outline-danger' ?>">
                    💸 Dépenses
                </a>
                <a href="<?= url('categories?type=revenu') ?>" 
                   class="btn btn-sm <?= $type_filtre === 'revenu' ? 'btn-success' : 'btn-outline-success' ?>">
                    💰 Revenus
                </a>
                <a href="<?= url('categories?type=mixte') ?>" 
                   class="btn btn-sm <?= $type_filtre === 'mixte' ? 'btn-info' : 'btn-outline-info' ?>">
                    🔄 Mixtes
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($categories)): ?>
        <!-- Aucune catégorie -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-tags" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucune catégorie</h4>
                <p class="text-muted">Créez des catégories pour organiser vos transactions</p>
                <a href="<?= url('categories/create') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Créer ma première catégorie
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste des catégories -->
        <div class="row">
            <?php foreach ($categories as $categorie): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-start border-4" 
                         style="border-left-color: <?= htmlspecialchars($categorie['couleur']) ?> !important;">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <i class="<?= htmlspecialchars($categorie['icone']) ?>" 
                                   style="color: <?= htmlspecialchars($categorie['couleur']) ?>;"></i>
                                <strong><?= htmlspecialchars($categorie['nom']) ?></strong>
                                <?php
                                $badgeClass = match($categorie['type']) {
                                    'depense' => 'bg-danger',
                                    'revenu' => 'bg-success',
                                    'mixte' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                                $badgeText = match($categorie['type']) {
                                    'depense' => '💸 Dépense',
                                    'revenu' => '💰 Revenu',
                                    'mixte' => '🔄 Mixte',
                                    default => $categorie['type']
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> ms-2"><?= $badgeText ?></span>
                                <?php if ($categorie['user_id'] === null): ?>
                                    <span class="badge bg-secondary ms-1" title="Catégorie système partagée">
                                        <i class="bi bi-globe"></i> Système
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($categorie['user_id'] !== null || ($isAdmin ?? false)): ?>
                                <?= actionButtons([
                                    'edit' => "categories/{$categorie['id']}/edit",
                                    'delete' => "categories/{$categorie['id']}/delete"
                                ], $categorie['id']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($categorie['description']): ?>
                                <p class="text-muted mb-3">
                                    <small><?= htmlspecialchars($categorie['description']) ?></small>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($categorie['sous_categories'])): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted small mb-0">Sous-catégories (<?= count($categorie['sous_categories']) ?>)</h6>
                                    <a href="<?= url('categories/create?parent_id=' . $categorie['id']) ?>" 
                                       class="btn btn-sm btn-outline-secondary" 
                                       title="Ajouter une sous-catégorie">
                                        <i class="bi bi-plus-lg"></i>
                                    </a>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($categorie['sous_categories'] as $sous): ?>
                                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-arrow-return-right text-muted"></i>
                                                <?= htmlspecialchars($sous['nom']) ?>
                                                <?php if ($sous['user_id'] === null): ?>
                                                    <span class="badge bg-secondary badge-sm ms-1" title="Catégorie système">
                                                        <i class="bi bi-globe"></i> Système
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($sous['description']): ?>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($sous['description']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($sous['user_id'] !== null || ($isAdmin ?? false)): ?>
                                                <?= actionButtons([
                                                    'edit' => "categories/{$sous['id']}/edit",
                                                    'delete' => "categories/{$sous['id']}/delete"
                                                ], $sous['id'], 'sm') ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="text-muted mb-0">
                                        <small><i class="bi bi-info-circle"></i> Aucune sous-catégorie</small>
                                    </p>
                                    <a href="<?= url('categories/create?parent_id=' . $categorie['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Ajouter une sous-catégorie">
                                        <i class="bi bi-plus-lg"></i> Ajouter
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Informations -->
    <div class="alert alert-info mt-4">
        <i class="bi bi-lightbulb"></i>
        <strong>Astuce :</strong>
        Les catégories vous aident à organiser vos transactions. 
        Créez des catégories principales (ex: "Alimentation", "Transport") et des sous-catégories pour plus de détail (ex: "Restaurants", "Carburant").
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
