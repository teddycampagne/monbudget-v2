<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-arrow-up"></i> <?= $title ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= url('/') ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('imports') ?>">Imports</a></li>
                    <li class="breadcrumb-item active">Aperçu OFX</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-info-circle"></i> Informations du fichier
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Compte :</strong> <?= htmlspecialchars($compte['nom']) ?></p>
                    <p class="mb-0"><strong>Banque :</strong> <?= htmlspecialchars($compte['banque_nom']) ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Format :</strong> <span class="badge bg-success">OFX</span></p>
                    <p class="mb-0"><strong>Transactions trouvées :</strong> <?= $total_transactions ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Aperçu des transactions (20 premières)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 50%;">Libellé</th>
                            <th style="width: 12%;" class="text-end">Montant</th>
                            <th style="width: 10%;" class="text-center">Type</th>
                            <th style="width: 18%;">Référence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trn): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($trn['date'])) ?></td>
                                <td><?= htmlspecialchars($trn['libelle']) ?></td>
                                <td class="text-end <?= $trn['montant'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <strong><?= number_format(abs($trn['montant']), 2, ',', ' ') ?> €</strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($trn['type'] === 'credit'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-arrow-down-circle"></i> Crédit
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-up-circle"></i> Débit
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted font-monospace">
                                        <?= htmlspecialchars(substr($trn['reference'], 0, 20)) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_transactions > 20): ?>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i>
                    Seules les 20 premières transactions sont affichées. 
                    <strong><?= $total_transactions - 20 ?> transaction(s) supplémentaire(s)</strong> seront également importées.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="alert alert-success">
                <h6><i class="bi bi-check-circle"></i> <strong>Import automatisé</strong></h6>
                <p class="mb-0">
                    Les transactions seront automatiquement catégorisées selon vos règles d'automatisation.
                    Les doublons seront détectés et ignorés.
                </p>
            </div>
            
            <form method="POST" action="<?= url('imports/process') ?>" id="form-import-ofx">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Confirmer et importer
                    </button>
                    <a href="<?= url('imports/cancel') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
