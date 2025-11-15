<?php
$title = $title ?? 'Banques';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="bi bi-bank"></i> Gestion des Banques</h1>
            <p class="text-muted">Gérez vos établissements bancaires</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('banques/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouvelle Banque
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <?php if (isset($_SESSION['flash']['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['flash']['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash']['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['flash']['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']['error']); ?>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($banques)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-bank" style="font-size: 4rem; color: #ccc;"></i>
                    <h4 class="mt-3">Aucune banque enregistrée</h4>
                    <p class="text-muted">Commencez par ajouter votre première banque</p>
                    <a href="<?= url('banques/create') ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle"></i> Ajouter une banque
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Code Banque</th>
                                <th>BIC</th>
                                <th>Ville</th>
                                <th>Téléphone</th>
                                <th>Comptes</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banques as $banque): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url("banques/{$banque['id']}") ?>" class="text-decoration-none">
                                            <strong><?= htmlspecialchars($banque['nom']) ?></strong>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($banque['code_banque'] ?? '-') ?></td>
                                    <td><code><?= htmlspecialchars($banque['bic'] ?? '-') ?></code></td>
                                    <td><?= htmlspecialchars($banque['ville'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($banque['telephone'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($banque['nb_comptes'] > 0): ?>
                                            <a href="<?= url("banques/{$banque['id']}") ?>" class="badge bg-info text-decoration-none">
                                                <?= $banque['nb_comptes'] ?> compte(s)
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0 compte</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url("banques/{$banque['id']}") ?>" 
                                               class="btn btn-outline-info" 
                                               title="Voir les détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= url("banques/{$banque['id']}/edit") ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?= $banque['id'] ?>, '<?= htmlspecialchars($banque['nom'], ENT_QUOTES) ?>')"
                                                    title="Supprimer"
                                                    <?= $banque['nb_comptes'] > 0 ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Formulaire de suppression caché -->
<form id="deleteForm" method="POST" style="display: none;">
    <?= csrf_field() ?>
</form>

<script>
function confirmDelete(id, nom) {
    if (confirm(`Voulez-vous vraiment supprimer la banque "${nom}" ?\n\nCette action est irréversible.`)) {
        const form = document.getElementById('deleteForm');
        form.action = '<?= url('banques') ?>/' + id + '/delete';
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
