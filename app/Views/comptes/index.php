<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../components/breadcrumbs.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumbs -->
    <?php renderBreadcrumbs([
        ['label' => 'Accueil', 'url' => url(''), 'icon' => 'house-door'],
        ['label' => 'Comptes', 'icon' => 'wallet2']
    ]); ?>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-bank"></i> Mes Comptes Bancaires</h1>
            <p class="text-muted mb-0">Gérez vos comptes bancaires</p>
        </div>
        <div>
            <a href="<?= url('comptes/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouveau Compte
            </a>
        </div>
    </div>

    <?php if (empty($comptes)): ?>
        <!-- Aucun compte -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-bank" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucun compte bancaire</h4>
                <p class="text-muted">Commencez par créer votre premier compte bancaire</p>
                <a href="<?= url('comptes/create') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Créer un compte
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Statistiques rapides -->
        <div class="row mb-4">
            <div class="col-md-4">
                <?= statsCard(
                    'Nombre de comptes',
                    count($comptes),
                    'bi-bank',
                    'primary',
                    null
                ) ?>
            </div>
            <div class="col-md-4">
                <?php
                $total = array_sum(array_column($comptes, 'solde_actuel'));
                echo statsCard(
                    'Solde total',
                    number_format($total, 2, ',', ' ') . ' €',
                    'bi-wallet2',
                    'success',
                    null
                );
                ?>
            </div>
            <div class="col-md-4">
                <?= statsCard(
                    'Comptes actifs',
                    count(array_filter($comptes, fn($c) => $c['actif'] == 1)),
                    'bi-check-circle',
                    'info',
                    null
                ) ?>
            </div>
        </div>

        <!-- Liste des comptes -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Liste des comptes (<?= count($comptes) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom du compte</th>
                                <th>Banque</th>
                                <th>Type</th>
                                <th>N° Compte / IBAN</th>
                                <th class="text-end">Solde actuel</th>
                                <th>Devise</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comptes as $compte): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($compte['nom']) ?></strong>
                                    </td>
                                    <td>
                                        <i class="bi bi-bank2 text-muted"></i>
                                        <?= htmlspecialchars($compte['banque_nom'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?php if ($compte['type_compte']): ?>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($compte['type_compte']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if ($compte['iban']): ?>
                                            <i class="bi bi-credit-card"></i>
                                            <?= htmlspecialchars($compte['iban']) ?>
                                        <?php elseif ($compte['numero_compte']): ?>
                                            <?= htmlspecialchars($compte['numero_compte']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?= $compte['solde_actuel'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($compte['solde_actuel'], 2, ',', ' ') ?> €
                                        </strong>
                                    </td>
                                    <td><?= htmlspecialchars($compte['devise']) ?></td>
                                    <td>
                                        <?php if ($compte['actif']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Actif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-x-circle"></i> Inactif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php
                                        $actions = [
                                            'view' => "comptes/{$compte['id']}/transactions",
                                            'edit' => "comptes/{$compte['id']}/edit",
                                            'delete' => "comptes/{$compte['id']}/delete"
                                        ];
                                        
                                        // Ajouter le téléchargement RIB si les infos sont complètes
                                        if (!empty($compte['code_banque']) && !empty($compte['code_guichet']) && 
                                            !empty($compte['numero_compte']) && !empty($compte['cle_rib']) && 
                                            !empty($compte['iban'])) {
                                            $actions['download'] = "comptes/{$compte['id']}/rib/download";
                                        }
                                        
                                        echo actionButtons($actions, $compte['id']);
                                        ?>
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