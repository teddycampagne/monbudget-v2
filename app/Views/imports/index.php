<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-upload"></i> Imports de transactions</h1>
        <a href="<?= url('imports/upload') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nouvel import
        </a>
    </div>

    <?php if (empty($imports)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Aucun import effectué. 
            <a href="<?= url('imports/upload') ?>" class="alert-link">Importer votre premier fichier CSV</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fichier</th>
                                <th>Compte</th>
                                <th>Lignes traitées</th>
                                <th>Importées</th>
                                <th>Ignorées</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($imports as $import): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($import['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($import['nom_fichier']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($import['compte_nom']) ?>
                                        <small class="text-muted">(<?= htmlspecialchars($import['banque_nom']) ?>)</small>
                                    </td>
                                    <td><?= $import['nb_lignes_total'] ?></td>
                                    <td><span class="badge bg-success"><?= $import['nb_lignes_importees'] ?></span></td>
                                    <td><span class="badge bg-warning"><?= $import['nb_lignes_ignorees'] ?></span></td>
                                    <td>
                                        <?php if ($import['statut'] === 'termine'): ?>
                                            <span class="badge bg-success">Terminé</span>
                                        <?php elseif ($import['statut'] === 'en_cours'): ?>
                                            <span class="badge bg-info">En cours</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Erreur</span>
                                        <?php endif; ?>
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
