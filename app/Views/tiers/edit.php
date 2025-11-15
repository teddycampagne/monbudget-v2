<?php 
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-pencil"></i> Modifier le Tiers</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($tiers['nom']) ?></p>
        </div>
        <a href="<?= url('tiers') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="<?= url("tiers/{$tiers['id']}/update") ?>">
                <?= csrf_field() ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h5>
                    </div>
                    <div class="card-body">
                        <?= formInput(
                            'nom',
                            'Nom',
                            'text',
                            htmlspecialchars($tiers['nom']),
                            true
                        ) ?>

                        <?= formSelect(
                            'type',
                            'Type',
                            [
                                'debiteur' => 'Débiteur (je lui dois de l\'argent)',
                                'crediteur' => 'Créditeur (il me doit de l\'argent)',
                                'mixte' => 'Mixte (les deux)'
                            ],
                            $tiers['type'],
                            true,
                            ''
                        ) ?>
                        <small class="text-muted d-block mt-n2 mb-3">
                            Choisissez "Mixte" pour les tiers qui peuvent être créditeurs ou débiteurs (ex: mutuelle, amis)
                        </small>

                        <?= formInput(
                            'groupe',
                            'Groupe (optionnel)',
                            'text',
                            htmlspecialchars($tiers['groupe'] ?? ''),
                            false
                        ) ?>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($tiers['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

            <div class="d-flex gap-2 mt-3">
                <a href="<?= url('tiers') ?>" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Type :</strong>
                        <span class="badge <?= $tiers['type'] === 'crediteur' ? 'bg-success' : ($tiers['type'] === 'mixte' ? 'bg-info' : 'bg-warning') ?>">
                            <?= $tiers['type'] === 'crediteur' ? 'Créditeur' : ($tiers['type'] === 'mixte' ? 'Mixte' : 'Débiteur') ?>
                        </span>
                    </p>
                    <?php if ($tiers['groupe']): ?>
                        <p class="small mb-2">
                            <strong>Groupe :</strong> <?= htmlspecialchars($tiers['groupe']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
