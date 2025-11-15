<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../components/breadcrumbs.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumbs -->
    <?php renderBreadcrumbs([
        ['label' => 'Accueil', 'url' => url(''), 'icon' => 'house-door'],
        ['label' => 'Banques', 'url' => url('banques'), 'icon' => 'building'],
        ['label' => htmlspecialchars($banque['nom']), 'icon' => 'bank']
    ]); ?>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-bank"></i> 
                <?= htmlspecialchars($banque['nom']) ?>
            </h1>
            <p class="text-muted mb-0">
                <?php if ($banque['bic']): ?>
                    BIC: <?= htmlspecialchars($banque['bic']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="btn-group">
            <a href="<?= url("banques/{$banque['id']}/edit") ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <a href="<?= url('banques') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <?= statsCard(
                'Nombre de comptes',
                $totalComptes,
                'bi-wallet',
                'primary',
                null
            ) ?>
        </div>
        <div class="col-md-4">
            <?= statsCard(
                'Solde total',
                number_format($totalSolde, 2, ',', ' ') . ' €',
                'bi-cash-stack',
                $totalSolde >= 0 ? 'success' : 'danger',
                null
            ) ?>
        </div>
        <div class="col-md-4">
            <?= statsCard(
                'Total transactions',
                $totalTransactions,
                'bi-arrow-left-right',
                'info',
                null
            ) ?>
        </div>
    </div>

    <!-- Informations de la banque -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php if ($banque['code_banque']): ?>
                            <tr>
                                <th width="40%">Code banque</th>
                                <td><?= htmlspecialchars($banque['code_banque']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($banque['bic']): ?>
                            <tr>
                                <th>BIC</th>
                                <td><?= htmlspecialchars($banque['bic']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($banque['site_web']): ?>
                            <tr>
                                <th>Site web</th>
                                <td><a href="<?= htmlspecialchars($banque['site_web']) ?>" target="_blank" class="text-decoration-none">
                                    <?= htmlspecialchars($banque['site_web']) ?> <i class="bi bi-box-arrow-up-right"></i>
                                </a></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($banque['telephone']): ?>
                            <tr>
                                <th>Téléphone</th>
                                <td><?= htmlspecialchars($banque['telephone']) ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($banque['contact_email']): ?>
                            <tr>
                                <th>Email</th>
                                <td><a href="mailto:<?= htmlspecialchars($banque['contact_email']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($banque['contact_email']) ?>
                                </a></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($banque['adresse_ligne1'] || $banque['ville']): ?>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Adresse</h5>
                </div>
                <div class="card-body">
                    <?php if ($banque['adresse_ligne1']): ?>
                        <p class="mb-1"><?= htmlspecialchars($banque['adresse_ligne1']) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($banque['adresse_ligne2']): ?>
                        <p class="mb-1"><?= htmlspecialchars($banque['adresse_ligne2']) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($banque['code_postal'] || $banque['ville']): ?>
                        <p class="mb-1">
                            <?= htmlspecialchars($banque['code_postal']) ?> 
                            <?= htmlspecialchars($banque['ville']) ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($banque['pays']): ?>
                        <p class="mb-0"><strong><?= htmlspecialchars($banque['pays']) ?></strong></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Liste des comptes -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-wallet"></i> Comptes associés (<?= count($comptes) ?>)</h5>
            <a href="<?= url('comptes/create') ?>?banque_id=<?= $banque['id'] ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Nouveau compte
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($comptes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-wallet" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Aucun compte associé à cette banque</p>
                    <a href="<?= url('comptes/create') ?>?banque_id=<?= $banque['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Créer un compte
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom du compte</th>
                                <th>Type</th>
                                <th>N° Compte / IBAN</th>
                                <th class="text-end">Solde actuel</th>
                                <th class="text-center">Transactions</th>
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
                                        <?php if (isset($compte['type']) && $compte['type']): ?>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($compte['type']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="font-monospace">
                                            <?= htmlspecialchars($compte['numero_compte'] ?? $compte['iban'] ?? 'N/A') ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?= $compte['solde_actuel'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($compte['solde_actuel'], 2, ',', ' ') ?> €
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?= $compte['nb_transactions'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($compte['actif']): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url("comptes/{$compte['id']}/transactions") ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Voir les transactions">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </a>
                                            <a href="<?= url("comptes/{$compte['id']}/edit") ?>" 
                                               class="btn btn-outline-secondary" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total :</strong></td>
                                <td class="text-end">
                                    <strong class="<?= $totalSolde >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($totalSolde, 2, ',', ' ') ?> €
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <strong><?= $totalTransactions ?></strong>
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
