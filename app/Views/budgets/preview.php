<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">
                <i class="bi bi-eye"></i> Prévisualisation des budgets générés
            </h4>
        </div>
        <div class="card-body">
            <?php if (empty($projections)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Aucune donnée disponible pour générer des budgets. 
                    Assurez-vous d'avoir des transactions sur la période sélectionnée.
                </div>
                <a href="<?= url('budgets/generate') ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            <?php else: ?>
                <!-- Résumé de l'analyse -->
                <div class="alert alert-info mb-4">
                    <strong>Paramètres d'analyse :</strong><br>
                    - Transactions de l'année <?= $annee_source ?> (sur <?= $nb_mois_analyse ?> mois)<br>
                    - Budgets pour l'année <?= $annee_cible ?><br>
                    - Ajustement : <?= $ajustement > 0 ? '+' : '' ?><?= $ajustement ?>%<br>
                    - Valeurs exceptionnelles : <?= $exclude_outliers ? 'Exclues' : 'Incluses' ?><br>
                    - Variations saisonnières : <?= $variations_saisonnieres ? 'Activées' : 'Désactivées' ?>
                </div>

                <!-- Statistiques globales -->
                <?php 
                $totalAnnuel = 0;
                foreach ($projections as $proj) {
                    $totalAnnuel += $proj['total_annuel'];
                }
                ?>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h6>Catégories</h6>
                                <h3><?= count($projections) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6>Total annuel prévu</h6>
                                <h3><?= number_format($totalAnnuel, 2, ',', ' ') ?> €</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h6>Budgets mensuels</h6>
                                <h3><?= count($projections) * 12 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des projections -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <?php 
                                $moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                                foreach ($moisNoms as $moisNom): 
                                ?>
                                    <th class="text-end small"><?= $moisNom ?></th>
                                <?php endforeach; ?>
                                <th class="text-end"><strong>Total</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projections as $proj): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($proj['categorie_icone'])): ?>
                                            <i class="bi bi-<?= htmlspecialchars($proj['categorie_icone']) ?>"></i>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($proj['categorie_nom']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= $proj['base_calcul'] ?></small>
                                    </td>
                                    <?php foreach ($proj['budgets_mensuels'] as $mois => $montant): ?>
                                        <td class="text-end small"><?= number_format($montant, 0) ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-end">
                                        <strong><?= number_format($proj['total_annuel'], 2, ',', ' ') ?> €</strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th>TOTAL</th>
                                <?php 
                                for ($m = 1; $m <= 12; $m++) {
                                    $totalMois = 0;
                                    foreach ($projections as $proj) {
                                        $totalMois += $proj['budgets_mensuels'][$m];
                                    }
                                    echo '<th class="text-end">' . number_format($totalMois, 0) . '</th>';
                                }
                                ?>
                                <th class="text-end"><strong><?= number_format($totalAnnuel, 2, ',', ' ') ?> €</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Formulaire de validation -->
                <form method="POST" action="<?= url('budgets/create-from-projection') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="annee_cible" value="<?= $annee_cible ?>">
                    <input type="hidden" name="projections" value='<?= htmlspecialchars(json_encode($projections), ENT_QUOTES) ?>'>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Attention :</strong> La création va générer <?= count($projections) * 12 ?> budgets mensuels.
                        Les budgets existants ne seront pas écrasés.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= url('budgets/generate') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Modifier les paramètres
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Créer ces budgets
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
