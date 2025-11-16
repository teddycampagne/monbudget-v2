<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
                <li class="breadcrumb-item"><a href="<?= url("comptes/{$compte['id']}/transactions") ?>"><?= htmlspecialchars($compte['nom']) ?></a></li>
                <li class="breadcrumb-item active">Nouvelle transaction</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><i class="bi bi-plus-circle"></i> Nouvelle Transaction - <?= htmlspecialchars($compte['nom']) ?></h1>
        
        <?php if (isset($isDuplicate) && $isDuplicate): ?>
            <div class="alert alert-info mt-3" role="alert">
                <i class="bi bi-files"></i> <strong>Mode duplication :</strong> 
                Les informations de la transaction d'origine ont été pré-remplies. 
                La date a été définie à aujourd'hui.
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations de la transaction</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url("comptes/{$compte['id']}/transactions/store") ?>" id="transactionForm">
                        <?= csrf_field() ?>
                        
                        <!-- Informations de base -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="compte_id" class="form-label">
                                    Compte <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($compte['nom']) ?> - <?= htmlspecialchars($compte['banque_nom']) ?>" 
                                       disabled>
                                <input type="hidden" name="compte_id" value="<?= $compte['id'] ?>">
                                <small class="text-muted">Compte verrouillé pour éviter les erreurs</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_transaction" class="form-label">
                                    Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_transaction" 
                                       name="date_transaction" 
                                       value="<?= old('date_transaction', $transaction['date_transaction'] ?? date('Y-m-d')) ?>"
                                       data-shortcuts="today,yesterday,week-ago,month-start"
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type_operation" class="form-label">
                                    Type d'opération <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="type_operation" name="type_operation" required>
                                    <option value="">Sélectionnez un type</option>
                                    <?php $selectedType = old('type_operation', $transaction['type_operation'] ?? ''); ?>
                                    <option value="credit" <?= $selectedType == 'credit' ? 'selected' : '' ?>>Crédit (Entrée d'argent)</option>
                                    <option value="debit" <?= $selectedType == 'debit' ? 'selected' : '' ?>>Débit (Sortie d'argent)</option>
                                    <option value="virement" <?= $selectedType == 'virement' ? 'selected' : '' ?>>Virement interne</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="compte_destination_block" style="display: none;">
                                <label for="compte_destination_id" class="form-label">
                                    Compte de destination <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="compte_destination_id" name="compte_destination_id">
                                    <option value="">Sélectionnez un compte</option>
                                    <?php $selectedDest = old('compte_destination_id', $transaction['compte_destination_id'] ?? ''); ?>
                                    <?php foreach ($comptes as $c): ?>
                                        <?php if ($c['id'] != $compte['id']): ?>
                                            <option value="<?= $c['id'] ?>" <?= $selectedDest == $c['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['nom']) ?> - <?= htmlspecialchars($c['banque_nom']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Compte vers lequel transférer l'argent</small>
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
                                           value="<?= old('montant', $transaction['montant'] ?? '') ?>"
                                           step="0.01"
                                           min="0"
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
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
                                   value="<?= old('libelle', $transaction['libelle'] ?? '') ?>"
                                   placeholder="Ex: Salaire, Courses, Loyer..."
                                   required 
                                   maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnel)</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Informations complémentaires..."><?= old('description', $transaction['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="moyen_paiement" class="form-label">Moyen de paiement</label>
                                <select class="form-select" id="moyen_paiement" name="moyen_paiement">
                                    <option value="">-- Détection automatique --</option>
                                    <option value="virement" <?= old('moyen_paiement') == 'virement' ? 'selected' : '' ?>>Virement</option>
                                    <option value="prelevement" <?= old('moyen_paiement') == 'prelevement' ? 'selected' : '' ?>>Prélèvement</option>
                                    <option value="carte" <?= old('moyen_paiement') == 'carte' ? 'selected' : '' ?>>Carte bancaire</option>
                                    <option value="cheque" <?= old('moyen_paiement') == 'cheque' ? 'selected' : '' ?>>Chèque</option>
                                    <option value="especes" <?= old('moyen_paiement') == 'especes' ? 'selected' : '' ?>>Espèces</option>
                                    <option value="autre" <?= old('moyen_paiement') == 'autre' ? 'selected' : '' ?>>Autre</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="categorie_id" class="form-label">Catégorie</label>
                                <div class="input-group">
                                    <select class="form-select" id="categorie_id" name="categorie_id">
                                        <option value="">-- Détection automatique --</option>
                                        <?php $selectedCategorie = old('categorie_id', $transaction['categorie_id'] ?? ''); ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $selectedCategorie == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCreateCategorie" title="Créer une catégorie">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sous_categorie_id" class="form-label">Sous-catégorie</label>
                                <select class="form-select" id="sous_categorie_id" name="sous_categorie_id" data-selected="<?= old('sous_categorie_id', $transaction['sous_categorie_id'] ?? '') ?>">
                                    <option value="">-- Détection automatique --</option>
                                </select>
                                <small class="text-muted">Sélectionnez d'abord une catégorie</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tiers_id" class="form-label">Tiers</label>
                            <div class="input-group">
                                <select class="form-select" id="tiers_id" name="tiers_id">
                                    <option value="">-- Détection automatique --</option>
                                    <?php $selectedTiers = old('tiers_id', $transaction['tiers_id'] ?? ''); ?>
                                    <?php foreach ($tiers as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $selectedTiers == $t['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['nom']) ?>
                                            <?php if ($t['groupe']): ?>
                                                (<?= htmlspecialchars($t['groupe']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCreateTiers" title="Créer un tiers">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <small class="text-muted">Personne ou organisme concerné par la transaction</small>
                        </div>

                        <!-- Section Récurrence -->
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="est_recurrente" 
                                           name="est_recurrente" 
                                           value="1"
                                           <?= old('est_recurrente') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="est_recurrente">
                                        <i class="bi bi-arrow-repeat"></i> <strong>Transaction récurrente</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="card-body" id="recurrence_section" style="display: <?= old('est_recurrente') ? 'block' : 'none' ?>;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="frequence" class="form-label">Fréquence</label>
                                        <select class="form-select" id="frequence" name="frequence">
                                            <option value="mensuel" <?= old('frequence', 'mensuel') == 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                                            <option value="hebdomadaire" <?= old('frequence') == 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire</option>
                                            <option value="trimestriel" <?= old('frequence') == 'trimestriel' ? 'selected' : '' ?>>Trimestriel</option>
                                            <option value="semestriel" <?= old('frequence') == 'semestriel' ? 'selected' : '' ?>>Semestriel</option>
                                            <option value="annuel" <?= old('frequence') == 'annuel' ? 'selected' : '' ?>>Annuel</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="intervalle" class="form-label">Intervalle</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="intervalle" 
                                               name="intervalle" 
                                               value="<?= old('intervalle', '1') ?>"
                                               min="1"
                                               placeholder="Ex: 2 pour tous les 2 mois">
                                        <div class="form-text">1 = chaque période, 2 = une période sur deux, etc.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_debut" class="form-label">Date de début</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="date_debut" 
                                               name="date_debut" 
                                               value="<?= old('date_debut', date('Y-m-d')) ?>"
                                               data-shortcuts="today,month-start,month-end">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="date_fin" class="form-label">Date de fin (optionnel)</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="date_fin" 
                                               name="date_fin" 
                                               value="<?= old('date_fin') ?>"
                                               data-shortcuts="month-end,year-end">
                                        <div class="form-text">Laisser vide pour une récurrence illimitée</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="jour_execution" class="form-label">Jour d'exécution</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="jour_execution" 
                                               name="jour_execution" 
                                               value="<?= old('jour_execution') ?>"
                                               min="1"
                                               max="31"
                                               placeholder="Ex: 15 pour le 15 du mois">
                                        <div class="form-text">Pour les récurrences mensuelles</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="tolerance_weekend" class="form-label">Tolérance weekend</label>
                                        <select class="form-select" id="tolerance_weekend" name="tolerance_weekend">
                                            <option value="jour_ouvre_suivant" <?= old('tolerance_weekend', 'jour_ouvre_suivant') == 'jour_ouvre_suivant' ? 'selected' : '' ?>>Jour ouvré suivant</option>
                                            <option value="jour_ouvre_precedent" <?= old('tolerance_weekend') == 'jour_ouvre_precedent' ? 'selected' : '' ?>>Jour ouvré précédent</option>
                                            <option value="aucune" <?= old('tolerance_weekend') == 'aucune' ? 'selected' : '' ?>>Aucune</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="auto_validation" 
                                           name="auto_validation" 
                                           value="1"
                                           <?= old('auto_validation', '1') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="auto_validation">
                                        Validation automatique des transactions générées
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= url("comptes/{$compte['id']}/transactions") ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Créer la transaction
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Aide -->
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Aide</h6>
                </div>
                <div class="card-body">
                    <h6>Types d'opération</h6>
                    <ul class="small">
                        <li><strong>Crédit :</strong> Entrée d'argent (salaire, virement reçu...)</li>
                        <li><strong>Débit :</strong> Sortie d'argent (achat, prélèvement...)</li>
                        <li><strong>Virement :</strong> Transfert entre vos comptes</li>
                    </ul>
                    
                    <h6 class="mt-3">Transactions récurrentes</h6>
                    <p class="small">
                        Cochez "Transaction récurrente" pour les opérations qui se répètent 
                        régulièrement (abonnements, salaire, loyer...).
                    </p>
                    <p class="small">
                        La transaction sera automatiquement créée à chaque échéance.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion de l'affichage du compte de destination pour les virements internes
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

// Afficher le compte de destination au chargement si virement est sélectionné
document.addEventListener('DOMContentLoaded', function() {
    const typeOperation = document.getElementById('type_operation').value;
    if (typeOperation === 'virement') {
        document.getElementById('compte_destination_block').style.display = 'block';
        document.getElementById('compte_destination_id').required = true;
    }
});

// Gestion de l'affichage de la section récurrence
document.getElementById('est_recurrente').addEventListener('change', function() {
    const section = document.getElementById('recurrence_section');
    section.style.display = this.checked ? 'block' : 'none';
});

// Chargement dynamique des sous-catégories
document.getElementById('categorie_id').addEventListener('change', function() {
    const categorieId = this.value;
    const sousCategorieSelect = document.getElementById('sous_categorie_id');
    
    // Réinitialiser les sous-catégories
    sousCategorieSelect.innerHTML = '<option value="">-- Détection automatique --</option>';
    
    if (!categorieId) {
        sousCategorieSelect.disabled = true;
        return;
    }
    
    // ✅ FIX : Utiliser URL relative au lieu de hardcodé
    fetch(`<?= url('api/categories') ?>/${categorieId}/sous-categories`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(sousCategories => {
            if (sousCategories.length > 0) {
                sousCategorieSelect.disabled = false;
                sousCategories.forEach(sc => {
                    const option = document.createElement('option');
                    option.value = sc.id;
                    option.textContent = sc.nom;
                    
                    // Pré-sélectionner la sous-catégorie si data-selected est défini
                    const selectedId = sousCategorieSelect.dataset.selected;
                    if (selectedId && sc.id == selectedId) {
                        option.selected = true;
                    }
                    
                    sousCategorieSelect.appendChild(option);
                });
            } else {
                sousCategorieSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des sous-catégories:', error);
            sousCategorieSelect.disabled = true;
        });
});

// Initialiser les sous-catégories au chargement si une catégorie est pré-sélectionnée (duplication)
document.addEventListener('DOMContentLoaded', function() {
    const categorieSelect = document.getElementById('categorie_id');
    if (categorieSelect.value) {
        // Déclencher le chargement des sous-catégories
        categorieSelect.dispatchEvent(new Event('change'));
    }
    
    // Afficher le bloc compte destination si virement est sélectionné
    const typeOperationSelect = document.getElementById('type_operation');
    if (typeOperationSelect.value === 'virement') {
        document.getElementById('compte_destination_block').style.display = 'block';
    }
});

// Initialiser l'état du select des sous-catégories
</script>

<!-- Modal Création Catégorie -->
<div class="modal fade" id="modalCreateCategorie" tabindex="-1" aria-labelledby="modalCreateCategorieLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateCategorieLabel">
                    <i class="bi bi-tags"></i> Nouvelle Catégorie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCreateCategorie">
                    <div class="mb-3">
                        <label for="quick_cat_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_cat_nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="quick_cat_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="quick_cat_type" required>
                            <option value="">Sélectionner...</option>
                            <option value="depense">Dépense</option>
                            <option value="revenu">Revenu</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quick_cat_couleur" class="form-label">Couleur</label>
                        <input type="color" class="form-control form-control-color" id="quick_cat_couleur" value="#0d6efd">
                    </div>
                    <div id="quick_cat_error" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnSaveCategorie">
                    <i class="bi bi-check-lg"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Création Tiers -->
<div class="modal fade" id="modalCreateTiers" tabindex="-1" aria-labelledby="modalCreateTiersLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateTiersLabel">
                    <i class="bi bi-person-plus"></i> Nouveau Tiers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCreateTiers">
                    <div class="mb-3">
                        <label for="quick_tiers_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_tiers_nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="quick_tiers_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="quick_tiers_type" required>
                            <option value="">Sélectionner...</option>
                            <option value="debiteur">Débiteur (vous verse de l'argent)</option>
                            <option value="crediteur">Créditeur (vous lui versez)</option>
                            <option value="mixte">Mixte</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quick_tiers_groupe" class="form-label">Groupe</label>
                        <input type="text" class="form-control" id="quick_tiers_groupe" placeholder="Ex: Fournisseurs, Clients...">
                    </div>
                    <div id="quick_tiers_error" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnSaveTiers">
                    <i class="bi bi-check-lg"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion création rapide Catégorie
document.getElementById('btnSaveCategorie').addEventListener('click', async function() {
    const nom = document.getElementById('quick_cat_nom').value;
    const type = document.getElementById('quick_cat_type').value;
    const couleur = document.getElementById('quick_cat_couleur').value;
    const errorDiv = document.getElementById('quick_cat_error');
    
    if (!nom || !type) {
        errorDiv.textContent = 'Veuillez remplir tous les champs obligatoires';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    try {
        const response = await fetch('<?= url("api/categories") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ nom, type, couleur })
        });
        
        if (!response.ok) throw new Error('Erreur serveur');
        
        const data = await response.json();
        
        // Ajouter la nouvelle catégorie au select
        const select = document.getElementById('categorie_id');
        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = data.nom;
        option.selected = true;
        select.appendChild(option);
        
        // Fermer le modal
        bootstrap.Modal.getInstance(document.getElementById('modalCreateCategorie')).hide();
        
        // Réinitialiser le formulaire
        document.getElementById('formCreateCategorie').reset();
        errorDiv.classList.add('d-none');
        
        // Toast de succès
        alert('Catégorie créée avec succès !');
    } catch (error) {
        errorDiv.textContent = 'Erreur lors de la création: ' + error.message;
        errorDiv.classList.remove('d-none');
    }
});

// Gestion création rapide Tiers
document.getElementById('btnSaveTiers').addEventListener('click', async function() {
    const nom = document.getElementById('quick_tiers_nom').value;
    const type = document.getElementById('quick_tiers_type').value;
    const groupe = document.getElementById('quick_tiers_groupe').value;
    const errorDiv = document.getElementById('quick_tiers_error');
    
    if (!nom || !type) {
        errorDiv.textContent = 'Veuillez remplir tous les champs obligatoires';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    try {
        const response = await fetch('<?= url("api/tiers") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ nom, type, groupe })
        });
        
        if (!response.ok) throw new Error('Erreur serveur');
        
        const data = await response.json();
        
        // Ajouter le nouveau tiers au select
        const select = document.getElementById('tiers_id');
        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = data.nom + (data.groupe ? ' (' + data.groupe + ')' : '');
        option.selected = true;
        select.appendChild(option);
        
        // Fermer le modal
        bootstrap.Modal.getInstance(document.getElementById('modalCreateTiers')).hide();
        
        // Réinitialiser le formulaire
        document.getElementById('formCreateTiers').reset();
        errorDiv.classList.add('d-none');
        
        // Toast de succès
        alert('Tiers créé avec succès !');
    } catch (error) {
        errorDiv.textContent = 'Erreur lors de la création: ' + error.message;
        errorDiv.classList.remove('d-none');
    }
});
</script>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
