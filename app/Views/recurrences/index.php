<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <?php if (isset($compte)): ?>
                <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
                <li class="breadcrumb-item"><a href="<?= url("comptes/{$compte['id']}/transactions") ?>"><?= htmlspecialchars($compte['nom']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Récurrences</li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Toutes les récurrences</li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-arrow-repeat"></i> 
                Transactions Récurrentes
                <?php if (isset($compte)): ?>
                    - <?= htmlspecialchars($compte['nom']) ?>
                <?php endif; ?>
            </h1>
            <?php if (isset($compte) && isset($compte['banque_nom'])): ?>
                <p class="text-muted mb-0">
                    <i class="bi bi-bank2"></i> 
                    <?= htmlspecialchars($compte['banque_nom']) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php if (isset($compte)): ?>
            <div class="btn-group">
                <a href="<?= url("comptes/{$compte['id']}/recurrences/create") ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nouvelle Récurrence
                </a>
                <a href="<?= url('recurrences/admin') ?>" class="btn btn-outline-info">
                    <i class="bi bi-graph-up"></i> Administration
                </a>
                <a href="<?= url("comptes/{$compte['id']}/transactions") ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($recurrences)): ?>
        <!-- Aucune récurrence -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-repeat" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucune transaction récurrente</h4>
                <p class="text-muted">Les transactions récurrentes permettent de générer automatiquement des transactions périodiques</p>
                <?php if (isset($compte)): ?>
                    <a href="<?= url("comptes/{$compte['id']}/recurrences/create") ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-lg"></i> Créer une récurrence
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste des récurrences -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Statut</th>
                                <th>Libellé</th>
                                <th>Compte</th>
                                <th>Montant</th>
                                <th>Fréquence</th>
                                <th>Prochaine exécution</th>
                                <th>Occurrences</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recurrences as $rec): ?>
                                <tr>
                                    <!-- Statut -->
                                    <td>
                                        <?php if ($rec['recurrence_active']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-pause-circle"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Libellé -->
                                    <td>
                                        <strong><?= htmlspecialchars($rec['libelle']) ?></strong>
                                        <?php if (!empty($rec['categorie_nom'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-tag"></i> 
                                                <?= htmlspecialchars($rec['categorie_nom']) ?>
                                                <?php if (!empty($rec['sous_categorie_nom'])): ?>
                                                    > <?= htmlspecialchars($rec['sous_categorie_nom']) ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Compte -->
                                    <td>
                                        <?= htmlspecialchars($rec['compte_nom']) ?>
                                        <?php if (!empty($rec['banque_nom'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($rec['banque_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Montant -->
                                    <td>
                                        <?php if ($rec['type_operation'] === 'debit'): ?>
                                            <span class="text-danger fw-bold">
                                                -<?= number_format(abs($rec['montant']), 2, ',', ' ') ?> €
                                            </span>
                                        <?php else: ?>
                                            <span class="text-success fw-bold">
                                                +<?= number_format(abs($rec['montant']), 2, ',', ' ') ?> €
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Fréquence -->
                                    <td>
                                        <?php
                                        $frequences = [
                                            'quotidien' => 'Tous les jours',
                                            'hebdomadaire' => 'Hebdomadaire',
                                            'mensuel' => 'Mensuel',
                                            'trimestriel' => 'Trimestriel',
                                            'semestriel' => 'Semestriel',
                                            'annuel' => 'Annuel'
                                        ];
                                        echo $frequences[$rec['frequence']] ?? ucfirst($rec['frequence']);
                                        ?>
                                        <?php if ($rec['intervalle'] > 1): ?>
                                            <br><small class="text-muted">Tous les <?= $rec['intervalle'] ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Prochaine exécution -->
                                    <td>
                                        <?php if ($rec['recurrence_active'] && $rec['prochaine_execution']): ?>
                                            <?php
                                            $prochaine = new DateTime($rec['prochaine_execution']);
                                            $aujourd_hui = new DateTime();
                                            $diff = $aujourd_hui->diff($prochaine);
                                            ?>
                                            <strong><?= $prochaine->format('d/m/Y') ?></strong>
                                            <br>
                                            <?php if ($prochaine < $aujourd_hui): ?>
                                                <small class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> En retard
                                                </small>
                                            <?php elseif ($diff->days <= 7): ?>
                                                <small class="text-warning">
                                                    Dans <?= $diff->days ?> jour(s)
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    Dans <?= $diff->days ?> jours
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Occurrences -->
                                    <td>
                                        <span class="badge bg-info" id="count-<?= $rec['id'] ?>">
                                            <i class="bi bi-clock-history"></i> ...
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($rec['recurrence_active']): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-execute"
                                                        data-id="<?= $rec['id'] ?>"
                                                        data-libelle="<?= htmlspecialchars($rec['libelle']) ?>"
                                                        title="Exécuter maintenant">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= url("recurrences/{$rec['id']}/edit") ?>" 
                                               class="btn btn-outline-secondary"
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-delete"
                                                    data-id="<?= $rec['id'] ?>"
                                                    data-libelle="<?= htmlspecialchars($rec['libelle']) ?>"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<!-- Modal Exécution -->
<div class="modal fade" id="executeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exécuter la récurrence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Voulez-vous créer une occurrence maintenant pour :</p>
                <p class="fw-bold" id="execute-libelle"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" id="execute-form" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-play-circle"></i> Exécuter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supprimer la récurrence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Que voulez-vous supprimer pour :</p>
                <p class="fw-bold" id="delete-libelle"></p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Cette récurrence a généré <strong id="delete-count">...</strong> occurrence(s).
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="delete_mode" id="delete_modele" value="modele" checked>
                    <label class="form-check-label" for="delete_modele">
                        <strong>Uniquement le modèle</strong>
                        <br><small class="text-muted">Les occurrences déjà créées seront conservées</small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="delete_mode" id="delete_tout" value="tout">
                    <label class="form-check-label" for="delete_tout">
                        <strong>Modèle + toutes les occurrences</strong>
                        <br><small class="text-danger">⚠️ Suppression définitive de toutes les transactions générées</small>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" id="delete-form" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" id="delete-mode-input" value="modele">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Charger le nombre d'occurrences pour chaque récurrence
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($recurrences as $rec): ?>
        fetch('<?= url("api/recurrences/{$rec['id']}/count-occurrences") ?>')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('count-<?= $rec['id'] ?>');
                if (badge) {
                    badge.innerHTML = `<i class="bi bi-clock-history"></i> ${data.count}`;
                }
            })
            .catch(err => {
                console.error('Erreur chargement count:', err);
                const badge = document.getElementById('count-<?= $rec['id'] ?>');
                if (badge) {
                    badge.innerHTML = `<i class="bi bi-clock-history"></i> 0`;
                }
            });
    <?php endforeach; ?>
});

// Modal Exécution
document.querySelectorAll('.btn-execute').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const libelle = this.dataset.libelle;
        document.getElementById('execute-libelle').textContent = libelle;
        document.getElementById('execute-form').action = '<?= url('recurrences') ?>/' + id + '/execute';
        
        // Utiliser l'API Bootstrap 5 data-bs-toggle
        const executeModal = document.getElementById('executeModal');
        const modal = new bootstrap.Modal(executeModal);
        modal.show();
    });
});

// Modal Suppression
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const libelle = this.dataset.libelle;
        
        document.getElementById('delete-libelle').textContent = libelle;
        document.getElementById('delete-form').action = '<?= url('recurrences') ?>/' + id + '/delete';
        
        // Charger le nombre d'occurrences
        fetch('<?= url('api/recurrences') ?>/' + id + '/count-occurrences')
            .then(response => response.json())
            .then(data => {
                document.getElementById('delete-count').textContent = data.count;
            })
            .catch(err => {
                console.error('Erreur chargement count occurrences:', err);
                document.getElementById('delete-count').textContent = '?';
            });
        
        // Utiliser l'API Bootstrap 5 data-bs-toggle
        const deleteModal = document.getElementById('deleteModal');
        const modal = new bootstrap.Modal(deleteModal);
        modal.show();
    });
});

// Synchroniser le mode de suppression avec le radio
document.querySelectorAll('input[name="delete_mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('delete-mode-input').value = this.value;
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
