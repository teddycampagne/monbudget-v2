<?php 
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../components/ui-helpers.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-plus-lg"></i> Nouveau Tiers</h1>
            <p class="text-muted mb-0">Ajouter un créditeur, débiteur ou mixte</p>
        </div>
        <?= linkButton('Retour', url('tiers'), 'btn-secondary', 'bi-arrow-left') ?>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="<?= url('tiers/store') ?>">
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
                            '',
                            true,
                            'Ex: EDF, Netflix, Jean Dupont...'
                        ) ?>

                        <?= formSelect(
                            'type',
                            'Type',
                            [
                                'debiteur' => 'Débiteur (je lui dois de l\'argent)',
                                'crediteur' => 'Créditeur (il me doit de l\'argent)',
                                'mixte' => 'Mixte (les deux)'
                            ],
                            $type,
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
                            '',
                            false,
                            'Ex: Fournisseurs, Abonnements, Amis...',
                            'Permet de regrouper plusieurs tiers'
                        ) ?>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Informations complémentaires..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <?= linkButton('Annuler', url('tiers'), 'btn-secondary', 'bi-x-lg') ?>
                    <?= submitButton('Créer le tiers', 'btn-primary', 'bi-save') ?>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-question-circle"></i> Aide</h6>
                </div>
                <div class="card-body">
                    <?php if ($type === 'crediteur'): ?>
                        <h6>Créditeur</h6>
                        <p class="small">Un créditeur est une personne ou un organisme qui <strong>vous doit de l'argent</strong>.</p>
                        <p class="small"><strong>Exemples :</strong></p>
                        <ul class="small">
                            <li>Un ami à qui vous avez prêté de l'argent</li>
                            <li>Un remboursement en attente</li>
                            <li>Un client (si activité professionnelle)</li>
                        </ul>
                    <?php else: ?>
                        <h6>Débiteur</h6>
                        <p class="small">Un débiteur est une personne ou un organisme à qui <strong>vous devez de l'argent</strong>.</p>
                        <p class="small"><strong>Exemples :</strong></p>
                        <ul class="small">
                            <li>EDF, Bouygues Telecom (fournisseurs)</li>
                            <li>Netflix, Spotify (abonnements)</li>
                            <li>Banque (prêts, crédits)</li>
                            <li>Propriétaire (loyer)</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
