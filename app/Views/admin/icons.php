<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-grid-3x3-gap"></i> Gestion des Icônes Bootstrap
            </h1>
            <p class="text-muted mb-0">Personnalisez la liste des icônes disponibles dans le sélecteur</p>
        </div>
        <a href="<?= url('admin') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour Admin
        </a>
    </div>

    <div class="row">
        <!-- Formulaire d'ajout -->
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-lg"></i> Ajouter une Icône</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('admin/icons/add') ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="icon_class" class="form-label">Nom de l'icône</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="icon_class" 
                                   name="icon_class" 
                                   placeholder="bi-nom-icone"
                                   pattern="bi-[a-z0-9-]+"
                                   required>
                            <small class="text-muted">Format : bi-nom-icone (ex: bi-rocket)</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg"></i> Ajouter
                        </button>
                    </form>

                    <hr>

                    <div class="alert alert-info mb-0">
                        <strong><i class="bi bi-lightbulb"></i> Astuce :</strong>
                        <p class="small mb-2">
                            Trouvez des icônes sur 
                            <a href="https://icons.getbootstrap.com/" target="_blank" class="alert-link">
                                Bootstrap Icons <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </p>
                        <p class="small mb-0">
                            Copiez le nom de l'icône (ex: <code>bi-rocket</code>) et ajoutez-le ici.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des icônes -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        Icônes disponibles (<?= count($icons) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($icons)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                            <p class="mt-3">Aucune icône configurée</p>
                        </div>
                    <?php else: ?>
                        <div class="icon-grid" style="
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                            gap: 15px;
                        ">
                            <?php foreach ($icons as $icon): ?>
                                <div class="icon-item card text-center p-3">
                                    <i class="<?= htmlspecialchars($icon) ?>" style="font-size: 2rem;"></i>
                                    <small class="d-block mt-2 text-muted" style="font-size: 0.7rem; word-break: break-all;">
                                        <?= htmlspecialchars($icon) ?>
                                    </small>
                                    <form method="POST" action="<?= url('admin/icons/delete') ?>" class="mt-2"
                                          onsubmit="return confirm('Supprimer l\'icône <?= htmlspecialchars($icon) ?> ?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="icon_class" value="<?= htmlspecialchars($icon) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
