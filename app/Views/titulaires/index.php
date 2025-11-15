<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-people"></i> Gestion des Titulaires</h2>
            <p class="text-muted">Gérez les titulaires de vos comptes bancaires</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= url('titulaires/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouveau Titulaire
            </a>
        </div>
    </div>

    <?php if (empty($titulaires)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Aucun titulaire enregistré. 
            <a href="<?= url('titulaires/create') ?>" class="alert-link">Créez votre premier titulaire</a> 
            pour pouvoir l'associer à vos comptes bancaires.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Liste des titulaires (<?= count($titulaires) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom complet</th>
                                <th>Adresse</th>
                                <th>Contact</th>
                                <th>Naissance</th>
                                <th class="text-center">Comptes</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($titulaires as $titulaire): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($titulaire['prenom'] ?? '') ?> <?= htmlspecialchars($titulaire['nom']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($titulaire['ville'])): ?>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($titulaire['code_postal'] ?? '') ?> 
                                                <?= htmlspecialchars($titulaire['ville']) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($titulaire['email'])): ?>
                                            <small><i class="bi bi-envelope"></i> <?= htmlspecialchars($titulaire['email']) ?></small><br>
                                        <?php endif; ?>
                                        <?php if (!empty($titulaire['telephone'])): ?>
                                            <small><i class="bi bi-telephone"></i> <?= htmlspecialchars($titulaire['telephone']) ?></small>
                                        <?php endif; ?>
                                        <?php if (empty($titulaire['email']) && empty($titulaire['telephone'])): ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($titulaire['date_naissance'])): ?>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($titulaire['date_naissance'])) ?>
                                                <?php if (!empty($titulaire['lieu_naissance'])): ?>
                                                    <br><?= htmlspecialchars($titulaire['lieu_naissance']) ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $comptes = MonBudget\Models\Titulaire::getComptes($titulaire['id']);
                                        $nbComptes = count($comptes);
                                        ?>
                                        <?php if ($nbComptes > 0): ?>
                                            <span class="badge bg-primary"><?= $nbComptes ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url("titulaires/{$titulaire['id']}/edit") ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" 
                                                  action="<?= url("titulaires/{$titulaire['id']}/delete") ?>" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce titulaire ?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" 
                                                        class="btn btn-outline-danger" 
                                                        title="Supprimer"
                                                        <?= $nbComptes > 0 ? 'disabled' : '' ?>>
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
