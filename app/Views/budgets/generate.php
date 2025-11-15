<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-magic"></i> Générer des budgets automatiquement
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-permanent">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Comment ça fonctionne ?</strong><br>
                        Cette fonctionnalité analyse vos transactions passées pour générer automatiquement des budgets réalistes.
                        Vous pourrez prévisualiser et ajuster les montants avant de les créer.
                    </div>

                    <form method="POST" action="<?= url('budgets/preview') ?>">
                        <?= csrf_field() ?>

                        <div class="row">
                            <!-- Année source -->
                            <div class="col-md-6 mb-3">
                                <label for="annee_source" class="form-label">
                                    Année source (à analyser) <span class="text-danger">*</span>
                                </label>
                                <select name="annee_source" id="annee_source" class="form-select" required>
                                    <?php for($y = $annee_actuelle - 3; $y <= $annee_actuelle; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == ($annee_actuelle - 1) ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="form-text">Année dont les transactions seront analysées</div>
                            </div>

                            <!-- Année cible -->
                            <div class="col-md-6 mb-3">
                                <label for="annee_cible" class="form-label">
                                    Année cible (à budgéter) <span class="text-danger">*</span>
                                </label>
                                <select name="annee_cible" id="annee_cible" class="form-select" required>
                                    <?php for($y = $annee_actuelle; $y <= $annee_actuelle + 2; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == $annee_actuelle ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="form-text">Année pour laquelle créer les budgets</div>
                            </div>
                        </div>

                        <!-- Période d'analyse -->
                        <div class="mb-3">
                            <label for="nb_mois_analyse" class="form-label">
                                Période d'analyse <span class="text-danger">*</span>
                            </label>
                            <select name="nb_mois_analyse" id="nb_mois_analyse" class="form-select" required>
                                <option value="6">6 derniers mois</option>
                                <option value="12" selected>12 derniers mois (recommandé)</option>
                                <option value="18">18 derniers mois</option>
                                <option value="24">24 derniers mois</option>
                            </select>
                            <div class="form-text">Plus la période est longue, plus l'analyse est précise</div>
                        </div>

                        <!-- Ajustement -->
                        <div class="mb-3">
                            <label for="ajustement" class="form-label">
                                Ajustement global (%)
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       name="ajustement" 
                                       id="ajustement" 
                                       class="form-control" 
                                       value="0"
                                       step="1"
                                       min="-50"
                                       max="100">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">
                                Ex: +5 pour anticiper l'inflation, -10 pour réduire vos dépenses
                            </div>
                        </div>

                        <!-- Options avancées -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Options avancées</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="exclude_outliers" 
                                           id="exclude_outliers"
                                           checked>
                                    <label class="form-check-label" for="exclude_outliers">
                                        <strong>Exclure les valeurs exceptionnelles</strong>
                                    </label>
                                    <div class="form-text">
                                        Ignore les pics inhabituels (ex: achat exceptionnel) pour des budgets plus réalistes
                                    </div>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="variations_saisonnieres" 
                                           id="variations_saisonnieres"
                                           checked>
                                    <label class="form-check-label" for="variations_saisonnieres">
                                        <strong>Tenir compte des variations saisonnières</strong>
                                    </label>
                                    <div class="form-text">
                                        Ajuste les budgets mensuels selon les habitudes (ex: plus en décembre pour les fêtes)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= url('budgets') ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-eye"></i> Prévisualiser les budgets
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
