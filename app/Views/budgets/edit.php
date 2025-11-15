<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-pencil"></i> Modifier le budget</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('budgets/' . $budget['id'] . '/update') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="PUT">
                        
                        <!-- Catégorie (lecture seule) -->
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($budget['categorie_nom']) ?>" 
                                   readonly>
                            <div class="form-text">La catégorie ne peut pas être modifiée</div>
                        </div>

                        <!-- Montant -->
                        <div class="mb-3">
                            <label for="montant" class="form-label">Montant prévu (€) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   name="montant" 
                                   id="montant" 
                                   class="form-control" 
                                   step="0.01" 
                                   min="0.01"
                                   value="<?= number_format($budget['montant'], 2, '.', '') ?>"
                                   required>
                            <div class="form-text">Montant maximum à ne pas dépasser</div>
                        </div>

                        <!-- Période -->
                        <div class="mb-3">
                            <label for="annee" class="form-label">Année</label>
                            <select name="annee" id="annee" class="form-select" required>
                                <?php for($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                                    <option value="<?= $y ?>" <?= $budget['annee'] == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="mois" class="form-label">Mois (optionnel)</label>
                            <select name="mois" id="mois" class="form-select">
                                <option value="">Année complète</option>
                                <?php 
                                $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                                for($m = 1; $m <= 12; $m++): 
                                ?>
                                    <option value="<?= $m ?>" <?= $budget['mois'] == $m ? 'selected' : '' ?>>
                                        <?= $moisNoms[$m - 1] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Type de période -->
                        <div class="mb-3">
                            <label class="form-label">Type de période</label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="periode" 
                                       id="periode_mensuel" 
                                       value="mensuel" 
                                       <?= $budget['periode'] === 'mensuel' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="periode_mensuel">
                                    Mensuel
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="periode" 
                                       id="periode_annuel" 
                                       value="annuel"
                                       <?= $budget['periode'] === 'annuel' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="periode_annuel">
                                    Annuel
                                </label>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= url('budgets?' . http_build_query(['annee' => $budget['annee'], 'mois' => $budget['mois'] ?? ''])) ?>" 
                               class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
