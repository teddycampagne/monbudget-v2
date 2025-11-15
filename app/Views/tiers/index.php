<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-people"></i> Tiers</h1>
            <p class="text-muted mb-0">Gérez vos créditeurs, débiteurs et tiers mixtes</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('tiers/create?type=crediteur') ?>" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Créditeur
            </a>
            <a href="<?= url('tiers/create?type=debiteur') ?>" class="btn btn-warning">
                <i class="bi bi-plus-lg"></i> Débiteur
            </a>
            <a href="<?= url('tiers/create?type=mixte') ?>" class="btn btn-info">
                <i class="bi bi-plus-lg"></i> Mixte
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?= url('tiers') ?>" 
                   class="btn btn-sm <?= empty($type_filtre) ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Tous
                </a>
                <a href="<?= url('tiers?type=crediteur') ?>" 
                   class="btn btn-sm <?= $type_filtre === 'crediteur' ? 'btn-success' : 'btn-outline-success' ?>">
                    Créditeurs
                </a>
                <a href="<?= url('tiers?type=debiteur') ?>" 
                   class="btn btn-sm <?= $type_filtre === 'debiteur' ? 'btn-warning' : 'btn-outline-warning' ?>">
                    Débiteurs
                </a>
                <a href="<?= url('tiers?type=mixte') ?>" 
                   class="btn btn-sm <?= $type_filtre === 'mixte' ? 'btn-info' : 'btn-outline-info' ?>">
                    Mixtes
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($tiers) && empty($grouped['crediteurs']) && empty($grouped['debiteurs']) && empty($grouped['mixtes'])): ?>
        <!-- Aucun tiers -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucun tiers</h4>
                <p class="text-muted">Créez des tiers pour suivre vos relations financières</p>
                <div class="mt-3">
                    <a href="<?= url('tiers/create?type=crediteur') ?>" class="btn btn-success me-2">
                        <i class="bi bi-plus-lg"></i> Créer un créditeur
                    </a>
                    <a href="<?= url('tiers/create?type=debiteur') ?>" class="btn btn-danger">
                        <i class="bi bi-plus-lg"></i> Créer un débiteur
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste des tiers (par type si filtré, sinon groupés) -->
        <?php if ($tiers): ?>
            <!-- Liste filtrée -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <?= $type_filtre === 'crediteur' ? 'Créditeurs' : 'Débiteurs' ?> 
                        (<?= count($tiers) ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Groupe</th>
                                    <th>Type</th>
                                    <th>Notes</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tiers as $t): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-person-circle"></i>
                                            <strong><?= htmlspecialchars($t['nom']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($t['groupe']): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($t['groupe']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $t['type'] === 'crediteur' ? 'bg-success' : ($t['type'] === 'mixte' ? 'bg-info' : 'bg-warning') ?>">
                                                <?= $t['type'] === 'crediteur' ? 'Créditeur' : ($t['type'] === 'mixte' ? 'Mixte' : 'Débiteur') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($t['notes']): ?>
                                                <small class="text-muted"><?= htmlspecialchars(substr($t['notes'], 0, 50)) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url("tiers/{$t['id']}/edit") ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="<?= url("tiers/{$t['id']}/delete") ?>" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Supprimer ce tiers ?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Vue groupée -->
            <div class="row">
                <!-- Créditeurs -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-down-circle"></i> 
                                Créditeurs (<?= count($grouped['crediteurs']) ?>)
                            </h5>
                            <small>Ils me doivent de l'argent</small>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($grouped['crediteurs'])): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Aucun créditeur</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($grouped['crediteurs'] as $t): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($t['nom']) ?></strong>
                                                <?php if ($t['groupe']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-tag"></i> <?= htmlspecialchars($t['groupe']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url("tiers/{$t['id']}/edit") ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="<?= url("tiers/{$t['id']}/delete") ?>" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Supprimer ?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Débiteurs -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-up-circle"></i> 
                                Débiteurs (<?= count($grouped['debiteurs']) ?>)
                            </h5>
                            <small>Je leur dois de l'argent</small>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($grouped['debiteurs'])): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Aucun débiteur</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($grouped['debiteurs'] as $t): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($t['nom']) ?></strong>
                                                <?php if ($t['groupe']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-tag"></i> <?= htmlspecialchars($t['groupe']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url("tiers/{$t['id']}/edit") ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="<?= url("tiers/{$t['id']}/delete") ?>" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Supprimer ?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Mixtes -->
                <div class="col-lg-12 mb-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-left-right"></i> 
                                Mixtes (<?= count($grouped['mixtes'] ?? []) ?>)
                            </h5>
                            <small>Relations financières bidirectionnelles</small>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($grouped['mixtes'])): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Aucun tiers mixte</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($grouped['mixtes'] as $t): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($t['nom']) ?></strong>
                                                <?php if ($t['groupe']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-tag"></i> <?= htmlspecialchars($t['groupe']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url("tiers/{$t['id']}/edit") ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="<?= url("tiers/{$t['id']}/delete") ?>" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Supprimer ?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Informations -->
    <div class="alert alert-info mt-4">
        <i class="bi bi-lightbulb"></i>
        <strong>Définitions :</strong><br>
        • <strong>Créditeur :</strong> Personne ou organisme qui vous doit de l'argent (prêt accordé, avance, etc.)<br>
        • <strong>Débiteur :</strong> Personne ou organisme à qui vous devez de l'argent (factures, prêts reçus, etc.)<br>
        • <strong>Mixte :</strong> Relation bidirectionnelle (ex: mutuelle qui me rembourse ET me facture, ami avec qui j'échange des prêts)
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
