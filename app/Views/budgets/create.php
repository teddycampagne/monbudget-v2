<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Créer un budget</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('budgets/store') ?>">
                        <?= csrf_field() ?>
                        
                        <!-- Catégorie -->
                        <div class="mb-3">
                            <label for="categorie_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie_id" id="categorie_id" class="form-select" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <div class="form-text text-warning">
                                    Toutes les catégories ont déjà un budget pour cette période.
                                </div>
                            <?php endif; ?>
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
                                   required 
                                   placeholder="0.00">
                            <div class="form-text">Montant maximum à ne pas dépasser</div>
                        </div>

                        <!-- Période -->
                        <div class="mb-3">
                            <label class="form-label">Période <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="periode" 
                                       id="periode_mensuel" 
                                       value="mensuel" 
                                       checked
                                       onchange="togglePeriodeOptions('mensuel')">
                                <label class="form-check-label" for="periode_mensuel">
                                    Mensuel (un seul mois)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="periode" 
                                       id="periode_annuel" 
                                       value="annuel"
                                       onchange="togglePeriodeOptions('annuel')">
                                <label class="form-check-label" for="periode_annuel">
                                    Annuel (répartition sur 12 mois)
                                </label>
                            </div>
                        </div>

                        <!-- Mois (pour budget mensuel) -->
                        <div class="mb-3" id="mois_container">
                            <label for="mois" class="form-label">Mois <span class="text-danger">*</span></label>
                            <select name="mois" id="mois" class="form-select" required>
                                <?php 
                                $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                                $moisActuel = $mois ?? (int)date('n');
                                for($m = 1; $m <= 12; $m++): 
                                ?>
                                    <option value="<?= $m ?>" <?= $moisActuel == $m ? 'selected' : '' ?>>
                                        <?= $moisNoms[$m - 1] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Montants mensualisés (pour budget annuel mensualisé) -->
                        <div id="montants_mensuels_container" style="display: none;">
                            <label class="form-label">Montants mensuels (€)</label>
                            
                            <!-- Outil de répartition -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <label for="montant_repartition" class="form-label small">Montant annuel total à répartir</label>
                                            <input type="number" 
                                                   id="montant_repartition" 
                                                   class="form-control" 
                                                   step="0.01" 
                                                   min="0"
                                                   placeholder="Ex: 4800.00">
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-outline-primary" onclick="repartirEquitablement()">
                                                <i class="bi bi-calculator"></i> Répartir équitablement sur 12 mois
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-2">
                                <?php 
                                $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                                for($m = 1; $m <= 12; $m++): 
                                ?>
                                    <div class="col-md-4 col-sm-6">
                                        <label for="montant_mois_<?= $m ?>" class="form-label small"><?= $moisNoms[$m - 1] ?></label>
                                        <input type="number" 
                                               name="montants_mensuels[<?= $m ?>]" 
                                               id="montant_mois_<?= $m ?>"
                                               class="form-control form-control-sm montant-mensuel" 
                                               step="0.01" 
                                               min="0"
                                               placeholder="0.00">
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="mt-2 alert alert-info">
                                <strong>Total annuel : <span id="total_annuel">0.00</span> €</strong>
                            </div>
                        </div>

                        <!-- Année -->
                        <div class="mb-3">
                            <label for="annee" class="form-label">Année <span class="text-danger">*</span></label>
                            <select name="annee" id="annee" class="form-select" required>
                                <?php for($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                                    <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <script>
                        function togglePeriodeOptions(type) {
                            const montantInput = document.getElementById('montant');
                            const moisContainer = document.getElementById('mois_container');
                            const moisSelect = document.getElementById('mois');
                            const montantsMensuelsContainer = document.getElementById('montants_mensuels_container');
                            const montantsMensuels = document.querySelectorAll('.montant-mensuel');
                            
                            // Réinitialiser
                            montantInput.parentElement.style.display = 'none';
                            moisContainer.style.display = 'none';
                            montantsMensuelsContainer.style.display = 'none';
                            moisSelect.required = false;
                            montantInput.required = false;
                            montantsMensuels.forEach(input => input.required = false);
                            
                            if (type === 'mensuel') {
                                // Budget mensuel : afficher montant et mois
                                montantInput.parentElement.style.display = 'block';
                                montantInput.required = true;
                                moisContainer.style.display = 'block';
                                moisSelect.required = true;
                            } else if (type === 'annuel') {
                                // Budget annuel : afficher les 12 champs mensuels
                                montantsMensuelsContainer.style.display = 'block';
                                montantsMensuels.forEach(input => input.required = true);
                            }
                        }
                        
                        function repartirEquitablement() {
                            const montantGlobal = parseFloat(document.getElementById('montant_repartition').value) || 0;
                            
                            if (montantGlobal <= 0) {
                                alert('Veuillez saisir un montant annuel total à répartir');
                                return;
                            }
                            
                            const montantMensuel = (montantGlobal / 12).toFixed(2);
                            
                            document.querySelectorAll('.montant-mensuel').forEach(input => {
                                input.value = montantMensuel;
                            });
                            
                            calculerTotalAnnuel();
                        }
                        
                        function calculerTotalAnnuel() {
                            let total = 0;
                            document.querySelectorAll('.montant-mensuel').forEach(input => {
                                total += parseFloat(input.value) || 0;
                            });
                            document.getElementById('total_annuel').textContent = total.toFixed(2);
                        }
                        
                        // Mettre à jour le total à chaque saisie
                        document.addEventListener('DOMContentLoaded', function() {
                            document.querySelectorAll('.montant-mensuel').forEach(input => {
                                input.addEventListener('input', calculerTotalAnnuel);
                            });
                        });
                        </script>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= url('budgets?' . http_build_query(['annee' => $annee, 'mois' => $mois ?? ''])) ?>" 
                               class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary" <?= empty($categories) ? 'disabled' : '' ?>>
                                <i class="bi bi-check-circle"></i> Créer le budget
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
