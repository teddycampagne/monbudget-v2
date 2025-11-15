<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
            <li class="breadcrumb-item"><a href="<?= url("comptes/{$compte['id']}/transactions") ?>"><?= htmlspecialchars($compte['nom']) ?></a></li>
            <li class="breadcrumb-item"><a href="<?= url("comptes/{$compte['id']}/recurrences") ?>">Récurrences</a></li>
            <li class="breadcrumb-item active">Nouvelle récurrence</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-arrow-repeat"></i> 
            Nouvelle Récurrence - <?= htmlspecialchars($compte['nom']) ?>
        </h1>
        <p class="text-muted">Créer une transaction qui se répète automatiquement</p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form method="POST" action="<?= url('recurrences/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="compte_id" value="<?= $compte['id'] ?>">

                <!-- Informations de base -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations de base</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="compte_id_display" class="form-label">
                                    Compte <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($compte['nom']) ?> - <?= htmlspecialchars($compte['banque_nom']) ?>" 
                                       disabled>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="type_operation" class="form-label">
                                    Type d'opération <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="type_operation" name="type_operation" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="credit">Crédit (Entrée d'argent)</option>
                                    <option value="debit" selected>Débit (Sortie d'argent)</option>
                                    <option value="virement">Virement interne</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="montant" class="form-label">
                                    Montant <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant" 
                                           name="montant" 
                                           step="0.01"
                                           min="0"
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="compte_destination_block" style="display: none;">
                                <label for="compte_destination_id" class="form-label">
                                    Compte de destination <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="compte_destination_id" name="compte_destination_id">
                                    <option value="">Sélectionnez un compte</option>
                                    <?php foreach ($comptes as $c): ?>
                                        <?php if ($c['id'] != $compte['id']): ?>
                                            <option value="<?= $c['id'] ?>">
                                                <?= htmlspecialchars($c['nom']) ?> - <?= htmlspecialchars($c['banque_nom']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="libelle" class="form-label">
                                Libellé <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="libelle" 
                                   name="libelle" 
                                   placeholder="Ex: Abonnement Netflix, Salaire mensuel, Loyer..."
                                   required 
                                   maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="categorie_id" class="form-label">Catégorie</label>
                                <select class="form-select" id="categorie_id" name="categorie_id">
                                    <option value="">Sélectionnez une catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sous_categorie_id" class="form-label">Sous-catégorie</label>
                                <select class="form-select" id="sous_categorie_id" name="sous_categorie_id" disabled>
                                    <option value="">Sélectionnez d'abord une catégorie</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tiers_id" class="form-label">Tiers</label>
                            <select class="form-select" id="tiers_id" name="tiers_id">
                                <option value="">Sélectionnez un tiers</option>
                                <?php foreach ($tiers as $t): ?>
                                    <option value="<?= $t['id'] ?>">
                                        <?= htmlspecialchars($t['nom']) ?>
                                        <?php if ($t['groupe']): ?>
                                            (<?= htmlspecialchars($t['groupe']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Paramètres de récurrence -->
                <div class="card mb-3">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Paramètres de récurrence</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="frequence" class="form-label">
                                    Fréquence <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="frequence" name="frequence" required>
                                    <option value="quotidien">Quotidien (chaque jour)</option>
                                    <option value="hebdomadaire">Hebdomadaire (chaque semaine)</option>
                                    <option value="mensuel" selected>Mensuel (chaque mois)</option>
                                    <option value="trimestriel">Trimestriel (tous les 3 mois)</option>
                                    <option value="semestriel">Semestriel (tous les 6 mois)</option>
                                    <option value="annuel">Annuel (chaque année)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="intervalle" class="form-label">
                                    Intervalle <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="intervalle" 
                                       name="intervalle" 
                                       value="1"
                                       min="1"
                                       required>
                                <small class="text-muted">1 = chaque période, 2 = une période sur deux, etc.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label">
                                    Date de début <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_debut" 
                                       name="date_debut" 
                                       value="<?= date('Y-m-d') ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_fin" class="form-label">Date de fin (optionnel)</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_fin" 
                                       name="date_fin">
                                <small class="text-muted">Laisser vide pour récurrence illimitée</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nb_executions_max" class="form-label">Nombre maximum d'exécutions</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="nb_executions_max" 
                                       name="nb_executions_max" 
                                       min="1"
                                       placeholder="Laisser vide pour illimité">
                                <small class="text-muted">La récurrence s'arrêtera après X occurrences</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="tolerance_weekend" class="form-label">Tolérance weekend</label>
                                <select class="form-select" id="tolerance_weekend" name="tolerance_weekend">
                                    <option value="jour_ouvre_suivant" selected>Jour ouvré suivant</option>
                                    <option value="jour_ouvre_precedent">Jour ouvré précédent</option>
                                    <option value="aucune">Aucune (weekend inclus)</option>
                                </select>
                                <small class="text-muted">Si échéance tombe un weekend</small>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="auto_validation" 
                                   name="auto_validation" 
                                   value="1"
                                   checked>
                            <label class="form-check-label" for="auto_validation">
                                Valider automatiquement les transactions générées
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="recurrence_active" 
                                   name="recurrence_active" 
                                   value="1"
                                   checked>
                            <label class="form-check-label" for="recurrence_active">
                                <strong>Activer immédiatement cette récurrence</strong>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="creer_occurrence_immediate" 
                                   name="creer_occurrence_immediate" 
                                   value="1">
                            <label class="form-check-label" for="creer_occurrence_immediate">
                                Créer une occurrence immédiatement (si date de début = aujourd'hui)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-between">
                    <a href="<?= url("comptes/{$compte['id']}/recurrences") ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Créer la récurrence
                    </button>
                </div>
            </form>
        </div>

        <!-- Aide -->
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-question-circle"></i> Aide</h6>
                </div>
                <div class="card-body">
                    <h6>Qu'est-ce qu'une récurrence ?</h6>
                    <p class="small">
                        Une récurrence génère automatiquement des transactions à intervalles réguliers 
                        (abonnements, salaires, loyers, etc.).
                    </p>

                    <h6 class="mt-3">Fréquences disponibles</h6>
                    <ul class="small">
                        <li><strong>Quotidien :</strong> Chaque jour</li>
                        <li><strong>Hebdomadaire :</strong> Chaque semaine</li>
                        <li><strong>Mensuel :</strong> Chaque mois (ex: le 15)</li>
                        <li><strong>Trimestriel :</strong> Tous les 3 mois</li>
                        <li><strong>Semestriel :</strong> Tous les 6 mois</li>
                        <li><strong>Annuel :</strong> Chaque année</li>
                    </ul>

                    <h6 class="mt-3">Exemples</h6>
                    <div class="small">
                        <p class="mb-1"><strong>Netflix 9.99€</strong></p>
                        <p class="text-muted">Fréquence: Mensuel, Intervalle: 1</p>
                        
                        <p class="mb-1 mt-2"><strong>Salaire 2500€</strong></p>
                        <p class="text-muted">Fréquence: Mensuel, Date début: 28</p>
                        
                        <p class="mb-1 mt-2"><strong>Loyer 800€</strong></p>
                        <p class="text-muted">Fréquence: Mensuel, Début: 1er du mois</p>
                    </div>

                    <div class="alert alert-warning mt-3 small">
                        <i class="bi bi-exclamation-triangle"></i>
                        Les occurrences seront créées automatiquement par le système (CRON).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion du compte de destination pour virements
document.getElementById('type_operation').addEventListener('change', function() {
    const compteDestBlock = document.getElementById('compte_destination_block');
    const compteDestSelect = document.getElementById('compte_destination_id');
    
    if (this.value === 'virement') {
        compteDestBlock.style.display = 'block';
        compteDestSelect.required = true;
    } else {
        compteDestBlock.style.display = 'none';
        compteDestSelect.required = false;
        compteDestSelect.value = '';
    }
});

// Chargement dynamique des sous-catégories
document.getElementById('categorie_id').addEventListener('change', function() {
    const categorieId = this.value;
    const sousCategorieSelect = document.getElementById('sous_categorie_id');
    
    sousCategorieSelect.innerHTML = '<option value="">Sélectionnez une sous-catégorie</option>';
    
    if (!categorieId) {
        sousCategorieSelect.disabled = true;
        return;
    }
    
    fetch(`<?= url('api/categories') ?>/${categorieId}/sous-categories`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(sousCategories => {
            if (sousCategories.length > 0) {
                sousCategorieSelect.disabled = false;
                sousCategories.forEach(sc => {
                    const option = document.createElement('option');
                    option.value = sc.id;
                    option.textContent = sc.nom;
                    sousCategorieSelect.appendChild(option);
                });
            } else {
                sousCategorieSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur chargement sous-catégories:', error);
            sousCategorieSelect.disabled = true;
        });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
