<?php 
use MonBudget\Models\Attachment;
require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../components/breadcrumbs.php'; 
?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <?php renderBreadcrumbs([
        ['label' => 'Accueil', 'url' => url(''), 'icon' => 'house-door'],
        ['label' => 'Comptes', 'url' => url('comptes'), 'icon' => 'bank'],
        ['label' => htmlspecialchars($compte['nom']), 'icon' => 'arrow-left-right']
    ]); ?>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-arrow-left-right"></i> 
                Transactions - <?= htmlspecialchars($compte['nom']) ?>
            </h1>
            <p class="text-muted mb-0">
                <i class="bi bi-bank2"></i> 
                <?= htmlspecialchars($compte['banque_nom'] ?? 'Aucune banque') ?>
            </p>
        </div>
        <div class="btn-group">
            <a href="<?= url("comptes/{$compte['id']}/transactions/create") ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouvelle Transaction
            </a>
            <a href="<?= url("comptes/{$compte['id']}/recurrences") ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-repeat"></i> Récurrentes
            </a>
        </div>
    </div>

    <?php if (empty($transactions)): ?>
        <!-- Aucune transaction -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-left-right" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucune transaction</h4>
                <p class="text-muted">Commencez par créer votre première transaction pour ce compte</p>
                <a href="<?= url("comptes/{$compte['id']}/transactions/create") ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Créer une transaction
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Statistiques rapides -->
        <?php
        // Approche sécurisée : calcul séparé avec abs() 
        // Fonctionne que les débits soient en positif ou négatif
        $totalDebits = 0;
        $totalCredits = 0;
        
        foreach ($transactions as $t) {
            if ($t['type_operation'] === 'debit') {
                $totalDebits += abs($t['montant']);
            } elseif ($t['type_operation'] === 'credit') {
                $totalCredits += abs($t['montant']);
            }
        }
        
        // La balance des transactions seules (sans solde initial)
        $solde = $totalCredits - $totalDebits;
        ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Nombre de transactions</p>
                                <h3 class="mb-0"><?= count($transactions) ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-list-ul text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Crédits</p>
                                <h3 class="mb-0 text-success">+<?= number_format($totalCredits, 2, ',', ' ') ?> €</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-arrow-down-circle text-success" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Débits</p>
                                <h3 class="mb-0 text-danger">-<?= number_format($totalDebits, 2, ',', ' ') ?> €</h3>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="bi bi-arrow-up-circle text-danger" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1 small">Solde</p>
                                <h3 class="mb-1 <?= $compte['solde_calcule'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($compte['solde_calcule'], 2, ',', ' ') ?> €
                                </h3>
                                <small class="text-muted">
                                    Balance : <?= number_format($solde, 2, ',', ' ') ?> €
                                </small>
                                <?php if (abs($compte['solde_calcule'] - $compte['solde_actuel']) > 0.01): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-warning text-dark" title="Différence entre le solde enregistré (<?= number_format($compte['solde_actuel'], 2, ',', ' ') ?> €) et le solde calculé">
                                            <i class="bi bi-exclamation-triangle"></i> Écart
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-wallet2 text-info" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des transactions -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Liste des transactions (<?= count($transactions) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Libellé</th>
                                <th>Compte</th>
                                <th>Catégorie</th>
                                <th>Tiers</th>
                                <th>Tags</th>
                                <th>Type</th>
                                <th class="text-end">Montant</th>
                                <th>Statut</th>
                                <th class="text-center">PJ</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($transaction['date_transaction'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($transaction['libelle']) ?></strong>
                                        <?php if ($transaction['est_recurrente']): ?>
                                            <span class="badge bg-info ms-1" title="Transaction récurrente">
                                                <i class="bi bi-arrow-repeat"></i> Récurrent
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($transaction['description']): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($transaction['description'], 0, 50)) ?><?= strlen($transaction['description']) > 50 ? '...' : '' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-bank2 text-muted"></i>
                                        <?= htmlspecialchars($transaction['compte_nom'] ?? 'N/A') ?>
                                        <?php if ($transaction['type_operation'] === 'virement' && $transaction['compte_destination_nom']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-arrow-right"></i> <?= htmlspecialchars($transaction['compte_destination_nom']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($transaction['categorie_nom'])): ?>
                                            <span class="badge" style="background-color: <?= htmlspecialchars($transaction['categorie_couleur'] ?? '#6c757d') ?>;">
                                                <?php if (!empty($transaction['categorie_icone'])): ?>
                                                    <i class="bi bi-<?= htmlspecialchars($transaction['categorie_icone']) ?>"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($transaction['categorie_nom']) ?>
                                            </span>
                                            <?php if (!empty($transaction['sous_categorie_nom'])): ?>
                                                <br>
                                                <small class="text-muted">→ <?= htmlspecialchars($transaction['sous_categorie_nom']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($transaction['tiers_nom'])): ?>
                                            <span class="badge <?= $transaction['tiers_type'] === 'crediteur' ? 'bg-success' : ($transaction['tiers_type'] === 'mixte' ? 'bg-info' : 'bg-warning') ?>">
                                                <i class="bi bi-person"></i>
                                                <?= htmlspecialchars($transaction['tiers_nom']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($transaction['tags'])): ?>
                                            <?php foreach ($transaction['tags'] as $tag): ?>
                                                <span class="badge bg-<?= htmlspecialchars($tag['color']) ?> me-1 mb-1" style="font-size: 0.75rem;">
                                                    <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($tag['name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= typeBadge($transaction['type_operation']) ?>
                                        <?php if ($transaction['moyen_paiement'] && $transaction['moyen_paiement'] !== 'autre'): ?>
                                            <br>
                                            <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $transaction['moyen_paiement'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php
                                        // Le montant des débits est déjà négatif dans la BDD
                                        $montantAffichage = abs($transaction['montant']);
                                        $signe = $transaction['type_operation'] === 'debit' ? '-' : '+';
                                        $couleur = $transaction['type_operation'] === 'debit' ? 'text-danger' : 'text-success';
                                        ?>
                                        <strong class="<?= $couleur ?>">
                                            <?= $signe ?><?= number_format($montantAffichage, 2, ',', ' ') ?> €
                                        </strong>
                                    </td>
                                    <td>
                                        <?= statusBadge($transaction['validee'], 'Validée', 'En attente') ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $attachmentCount = Attachment::countByTransaction($transaction['id']);
                                        if ($attachmentCount > 0):
                                        ?>
                                            <span class="badge bg-info" title="<?= $attachmentCount ?> pièce(s) jointe(s)">
                                                <i class="bi bi-paperclip"></i> <?= $attachmentCount ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url("comptes/{$compte['id']}/transactions/{$transaction['id']}/duplicate") ?>" 
                                               class="btn btn-outline-secondary" 
                                               title="Dupliquer">
                                                <i class="bi bi-files"></i>
                                            </a>
                                            <a href="<?= url("comptes/{$compte['id']}/transactions/{$transaction['id']}/edit") ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($transaction['est_recurrente']): ?>
                                                <button type="button"
                                                        class="btn btn-outline-danger" 
                                                        title="Supprimer récurrence"
                                                        onclick="showDeleteRecurrenceModal(<?= $transaction['id'] ?>, '<?= htmlspecialchars(addslashes($transaction['libelle'])) ?>', <?= $transaction['nb_executions'] ?? 0 ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" 
                                                      action="<?= url("comptes/{$compte['id']}/transactions/{$transaction['id']}/delete") ?>" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette transaction ?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

<!-- Modal de suppression récurrence (même que dans recurrentes.php) -->
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
