<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
            <li class="breadcrumb-item"><a href="<?= url("comptes/{$compte['id']}/transactions") ?>"><?= htmlspecialchars($compte['nom']) ?></a></li>
            <li class="breadcrumb-item active">Transactions récurrentes</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-arrow-repeat"></i> Transactions Récurrentes</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($compte['banque_nom']) ?> - <?= htmlspecialchars($compte['nom']) ?></p>
        </div>
        <div>
            <a href="<?= url("comptes/{$compte['id']}/transactions") ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="<?= url("comptes/{$compte['id']}/transactions/create") ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouvelle récurrence
            </a>
        </div>
    </div>

    <?php if (empty($recurrentes)): ?>
        <!-- Aucune récurrence -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-repeat" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucune transaction récurrente</h4>
                <p class="text-muted">Les transactions récurrentes permettent d'automatiser vos opérations répétitives</p>
                <a href="<?= url("comptes/{$compte['id']}/transactions/create") ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Créer une récurrence
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste des récurrences -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Récurrences actives (<?= count($recurrentes) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Libellé</th>
                                <th>Compte</th>
                                <th>Fréquence</th>
                                <th>Prochaine exécution</th>
                                <th class="text-end">Montant</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recurrentes as $recurrence): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($recurrence['libelle']) ?></strong>
                                        <?php if ($recurrence['description']): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($recurrence['description'], 0, 50)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-bank2 text-muted"></i>
                                        <?= htmlspecialchars($recurrence['compte_nom'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst($recurrence['frequence']) ?>
                                        </span>
                                        <?php if ($recurrence['intervalle'] > 1): ?>
                                            <br>
                                            <small class="text-muted">Tous les <?= $recurrence['intervalle'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($recurrence['prochaine_execution']): ?>
                                            <strong><?= date('d/m/Y', strtotime($recurrence['prochaine_execution'])) ?></strong>
                                            <?php
                                            $diff = (new DateTime($recurrence['prochaine_execution']))->diff(new DateTime());
                                            if ($diff->days == 0): ?>
                                                <br><span class="badge bg-warning">Aujourd'hui</span>
                                            <?php elseif ($diff->invert == 0 && $diff->days > 0): ?>
                                                <br><span class="badge bg-danger">En retard de <?= $diff->days ?> jour(s)</span>
                                            <?php elseif ($diff->days <= 7): ?>
                                                <br><span class="badge bg-info">Dans <?= $diff->days ?> jour(s)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non définie</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?= $recurrence['type_operation'] === 'debit' ? 'text-danger' : 'text-success' ?>">
                                            <?= $recurrence['type_operation'] === 'debit' ? '-' : '+' ?>
                                            <?= number_format($recurrence['montant'], 2, ',', ' ') ?> €
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($recurrence['recurrence_active']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-pause-circle"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $recurrence['nb_executions'] ?? 0 ?> exécution(s)
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($recurrence['recurrence_active']): ?>
                                                <form method="POST" 
                                                      action="<?= url("comptes/{$compte['id']}/transactions/{$recurrence['id']}/executer") ?>" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Exécuter cette récurrence maintenant ?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-success" 
                                                            title="Exécuter maintenant">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="<?= url("comptes/{$compte['id']}/transactions/{$recurrence['id']}/edit") ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger" 
                                                    title="Supprimer"
                                                    onclick="showDeleteRecurrenceModal(<?= $recurrence['id'] ?>, '<?= htmlspecialchars(addslashes($recurrence['libelle'])) ?>', <?= $recurrence['nb_executions'] ?? 0 ?>)">
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

        <!-- Informations -->
        <div class="alert alert-info mt-4">
            <i class="bi bi-info-circle"></i>
            <strong>Comment ça fonctionne ?</strong>
            Les transactions récurrentes sont automatiquement créées à leur date d'échéance. 
            Vous pouvez aussi les exécuter manuellement en cliquant sur <i class="bi bi-play-circle"></i>.
        </div>
    <?php endif; ?>
</div>

<!-- Modal de suppression avancée -->
<div class="modal fade" id="deleteRecurrenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Supprimer la récurrence
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong id="recurrence-libelle"></strong></p>
                <p class="text-muted" id="recurrence-info"></p>
                
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i>
                    <strong>Que souhaitez-vous supprimer ?</strong>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-danger" onclick="deleteRecurrence('modele')">
                        <i class="bi bi-x-circle"></i>
                        <strong>Supprimer uniquement le modèle</strong>
                        <br>
                        <small class="text-muted">Les transactions déjà créées seront conservées (résiliation d'abonnement, fin de crédit)</small>
                    </button>
                    
                    <button type="button" class="btn btn-danger" onclick="deleteRecurrence('tout')">
                        <i class="bi bi-trash"></i>
                        <strong>Supprimer le modèle + toutes les occurrences</strong>
                        <br>
                        <small>Supprime également toutes les transactions générées par cette récurrence (erreur de saisie)</small>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire caché pour suppression -->
<form id="deleteRecurrenceForm" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="mode_suppression" id="mode_suppression">
</form>

<script>
let currentRecurrenceId = null;

function showDeleteRecurrenceModal(id, libelle, nbExecutions) {
    currentRecurrenceId = id;
    document.getElementById('recurrence-libelle').textContent = libelle;
    
    let info = nbExecutions > 0 
        ? `Cette récurrence a généré ${nbExecutions} transaction(s).`
        : 'Aucune transaction générée pour le moment.';
    document.getElementById('recurrence-info').textContent = info;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteRecurrenceModal'));
    modal.show();
}

function deleteRecurrence(mode) {
    if (!currentRecurrenceId) return;
    
    const form = document.getElementById('deleteRecurrenceForm');
    form.action = '<?= url("comptes/{$compte['id']}/transactions/") ?>' + currentRecurrenceId + '/delete';
    document.getElementById('mode_suppression').value = mode;
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteRecurrenceModal'));
    modal.hide();
    
    // Petite pause pour fermer le modal avant soumission
    setTimeout(() => form.submit(), 200);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
