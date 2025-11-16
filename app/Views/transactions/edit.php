<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
            <li class="breadcrumb-item"><a href="<?= url("comptes/{$compte['id']}/transactions") ?>"><?= htmlspecialchars($compte['nom']) ?></a></li>
            <li class="breadcrumb-item active">Modifier transaction</li>
        </ol>
    </nav>
    
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-pencil"></i> Modifier la Transaction</h1>
            <p class="text-muted mb-0">
                <?= $transaction['est_recurrente'] ? 'Transaction récurrente' : 'Transaction simple' ?>
            </p>
        </div>
        <a href="<?= url("comptes/{$compte['id']}/transactions") ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <!-- Formulaire -->
        <div class="col-lg-9">
            <form method="POST" action="<?= url("comptes/{$compte['id']}/transactions/{$transaction['id']}/update") ?>">
                <?= csrf_field() ?>

                <!-- Informations de base -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations de base</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="compte_id" class="form-label">Compte <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($compte['nom']) ?> - <?= htmlspecialchars($compte['banque_nom']) ?>" 
                                       disabled>
                                <input type="hidden" name="compte_id" value="<?= $transaction['compte_id'] ?>">
                                <small class="text-muted">Compte verrouillé pour éviter les erreurs</small>
                            </div>
                            <div class="col-md-6">
                                <label for="date_transaction" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_transaction" 
                                       name="date_transaction" 
                                       value="<?= htmlspecialchars($transaction['date_transaction']) ?>"
                                       data-shortcuts="today,yesterday,week-ago,month-start"
                                       required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type_operation" class="form-label">Type d'opération <span class="text-danger">*</span></label>
                                <select class="form-select" id="type_operation" name="type_operation" required>
                                    <option value="credit" <?= $transaction['type_operation'] === 'credit' ? 'selected' : '' ?>>
                                        Crédit (argent reçu)
                                    </option>
                                    <option value="debit" <?= $transaction['type_operation'] === 'debit' ? 'selected' : '' ?>>
                                        Débit (argent dépensé)
                                    </option>
                                    <option value="virement" <?= $transaction['type_operation'] === 'virement' ? 'selected' : '' ?>>
                                        Virement (entre comptes)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6" id="compte_destination_block" style="display: none;">
                                <label for="compte_destination_id" class="form-label">
                                    Compte de destination <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="compte_destination_id" name="compte_destination_id">
                                    <option value="">Sélectionnez un compte</option>
                                    <?php foreach ($comptes as $c): ?>
                                        <?php if ($c['id'] != $transaction['compte_id']): ?>
                                            <option value="<?= $c['id'] ?>" <?= ($transaction['compte_destination_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['nom']) ?> - <?= htmlspecialchars($c['banque_nom']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Compte vers lequel transférer l'argent</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="montant" class="form-label">Montant <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant" 
                                           name="montant" 
                                           step="0.01" 
                                           min="0" 
                                           value="<?= htmlspecialchars($transaction['montant']) ?>" 
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="libelle" 
                                   name="libelle" 
                                   value="<?= htmlspecialchars($transaction['libelle']) ?>" 
                                   placeholder="Ex: Salaire, Loyer, Courses..." 
                                   maxlength="255" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Détails supplémentaires..."><?= htmlspecialchars($transaction['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Détails de paiement -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Détails de paiement & Catégorisation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="moyen_paiement" class="form-label">Moyen de paiement</label>
                                <select class="form-select" id="moyen_paiement" name="moyen_paiement">
                                    <option value="">-- Détection automatique --</option>
                                    <option value="virement" <?= ($transaction['moyen_paiement'] ?? '') === 'virement' ? 'selected' : '' ?>>Virement</option>
                                    <option value="prelevement" <?= ($transaction['moyen_paiement'] ?? '') === 'prelevement' ? 'selected' : '' ?>>Prélèvement</option>
                                    <option value="carte" <?= ($transaction['moyen_paiement'] ?? '') === 'carte' ? 'selected' : '' ?>>Carte bancaire</option>
                                    <option value="cheque" <?= ($transaction['moyen_paiement'] ?? '') === 'cheque' ? 'selected' : '' ?>>Chèque</option>
                                    <option value="especes" <?= ($transaction['moyen_paiement'] ?? '') === 'especes' ? 'selected' : '' ?>>Espèces</option>
                                    <option value="autre" <?= ($transaction['moyen_paiement'] ?? '') === 'autre' ? 'selected' : '' ?>>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="categorie_id" class="form-label">Catégorie</label>
                                <select class="form-select" id="categorie_id" name="categorie_id">
                                    <option value="">Aucune</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($transaction['categorie_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sous_categorie_id" class="form-label">Sous-catégorie</label>
                                <select class="form-select" id="sous_categorie_id" name="sous_categorie_id">
                                    <option value="">Aucune</option>
                                </select>
                                <small class="text-muted">Sélectionnez d'abord une catégorie</small>
                            </div>
                            <div class="col-md-6">
                                <label for="tiers_id" class="form-label">Tiers</label>
                                <select class="form-select" id="tiers_id" name="tiers_id">
                                    <option value="">Aucun</option>
                                    <?php foreach ($tiers as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= ($transaction['tiers_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nom']) ?>
                                        <?php if ($t['groupe']): ?>
                                            (<?= htmlspecialchars($t['groupe']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Personne ou organisme concerné par la transaction</small>
                        </div>
                    </div>
                </div>

                <!-- Récurrence -->
                <div class="card mb-4 border-<?= $transaction['est_recurrente'] ? 'warning' : 'info' ?>">
                    <div class="card-header bg-<?= $transaction['est_recurrente'] ? 'warning' : 'info' ?>">
                        <div class="form-check mb-0">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="est_recurrente" 
                                   name="est_recurrente" 
                                   value="1" 
                                   <?= $transaction['est_recurrente'] ? 'checked' : '' ?>
                                   onchange="toggleRecurrenceFields()">
                            <label class="form-check-label fw-bold" for="est_recurrente">
                                <i class="bi bi-arrow-repeat"></i> Transaction récurrente
                            </label>
                            <small class="d-block text-muted">
                                Cochez cette case pour transformer cette transaction en modèle de récurrence
                            </small>
                        </div>
                    </div>
                    <div class="card-body" id="recurrence_fields" style="display: <?= $transaction['est_recurrente'] ? 'block' : 'none' ?>;">
                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="recurrence_active" 
                                   name="recurrence_active" 
                                   value="1" 
                                   <?= ($transaction['recurrence_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="recurrence_active">
                                Récurrence active
                            </label>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="frequence" class="form-label">Fréquence <span class="text-danger">*</span></label>
                                <select class="form-select" id="frequence" name="frequence">
                                    <option value="mensuel" <?= ($transaction['frequence'] ?? '') === 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                                    <option value="hebdomadaire" <?= ($transaction['frequence'] ?? '') === 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire</option>
                                    <option value="trimestriel" <?= ($transaction['frequence'] ?? '') === 'trimestriel' ? 'selected' : '' ?>>Trimestriel</option>
                                    <option value="semestriel" <?= ($transaction['frequence'] ?? '') === 'semestriel' ? 'selected' : '' ?>>Semestriel</option>
                                    <option value="annuel" <?= ($transaction['frequence'] ?? '') === 'annuel' ? 'selected' : '' ?>>Annuel</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="intervalle" class="form-label">Intervalle</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="intervalle" 
                                       name="intervalle" 
                                       min="1" 
                                       value="<?= htmlspecialchars($transaction['intervalle'] ?? 1) ?>">
                                <small class="text-muted">1 = chaque période, 2 = une période sur deux</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_debut" 
                                   name="date_debut" 
                                   value="<?= !empty($transaction['date_debut']) && $transaction['date_debut'] !== '0000-00-00' ? htmlspecialchars($transaction['date_debut']) : '' ?>"
                                   data-shortcuts="today,month-start,month-end">
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_fin" 
                                   name="date_fin" 
                                   value="<?= !empty($transaction['date_fin']) && $transaction['date_fin'] !== '0000-00-00' ? htmlspecialchars($transaction['date_fin']) : '' ?>"
                                   data-shortcuts="month-end,year-end">
                                <small class="text-muted">Laisser vide pour récurrence illimitée</small>
                            </div>
                        </div>                        <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="jour_execution" class="form-label">Jour d'exécution</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="jour_execution" 
                                           name="jour_execution" 
                                           min="1" 
                                           max="31" 
                                           value="<?= !empty($transaction['jour_execution']) && $transaction['jour_execution'] > 0 ? htmlspecialchars($transaction['jour_execution']) : '' ?>">
                                    <small class="text-muted">Pour récurrences mensuelles (1-31)</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="tolerance_weekend" class="form-label">Tolérance week-end</label>
                                    <select class="form-select" id="tolerance_weekend" name="tolerance_weekend">
                                        <option value="jour_ouvre_suivant" <?= ($transaction['tolerance_weekend'] ?? '') === 'jour_ouvre_suivant' ? 'selected' : '' ?>>
                                            Jour ouvré suivant
                                    </option>
                                    <option value="jour_ouvre_precedent" <?= ($transaction['tolerance_weekend'] ?? '') === 'jour_ouvre_precedent' ? 'selected' : '' ?>>
                                        Jour ouvré précédent
                                    </option>
                                    <option value="aucune" <?= ($transaction['tolerance_weekend'] ?? '') === 'aucune' ? 'selected' : '' ?>>
                                        Aucune
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="auto_validation" 
                                   name="auto_validation" 
                                   value="1" 
                                   <?= ($transaction['auto_validation'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_validation">
                                Validation automatique des transactions créées
                            </label>
                        </div>

                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Statistiques :</strong>
                            <?= $transaction['nb_executions'] ?? 0 ?> exécution(s) effectuée(s)
                            <?php if ($transaction['nb_executions_max']): ?>
                                sur <?= $transaction['nb_executions_max'] ?> maximum
                            <?php endif; ?>
                            <br>
                            <strong>Prochaine exécution :</strong> 
                            <?= $transaction['prochaine_execution'] ? date('d/m/Y', strtotime($transaction['prochaine_execution'])) : 'Non définie' ?>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2">
                    <a href="<?= url("comptes/{$transaction['compte_id']}/transactions") ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

        <!-- Aide -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-question-circle"></i> Aide</h6>
                </div>
                <div class="card-body">
                    <h6>Types d'opération</h6>
                    <ul class="small">
                        <li><strong>Crédit :</strong> argent reçu</li>
                        <li><strong>Débit :</strong> argent dépensé</li>
                        <li><strong>Virement :</strong> transfert entre comptes</li>
                    </ul>

                    <?php if ($transaction['est_recurrente']): ?>
                    <hr>
                    <h6>Gestion de la récurrence</h6>
                    <p class="small">
                        Décochez "Récurrence active" pour mettre en pause l'exécution automatique 
                        sans supprimer la transaction.
                    </p>
                    <?php endif; ?>
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
    }
});

// Toggle des champs de récurrence
function toggleRecurrenceFields() {
    const isRecurrente = document.getElementById('est_recurrente').checked;
    const recurrenceFields = document.getElementById('recurrence_fields');
    recurrenceFields.style.display = isRecurrente ? 'block' : 'none';
    
    // Désactiver la validation HTML5 sur les champs cachés pour éviter les erreurs "not focusable"
    const fieldsToToggle = recurrenceFields.querySelectorAll('input, select');
    fieldsToToggle.forEach(field => {
        if (isRecurrente) {
            // Réactiver la validation si le champ était required
            if (field.dataset.wasRequired === 'true') {
                field.required = true;
            }
        } else {
            // Sauvegarder l'état required et désactiver temporairement
            field.dataset.wasRequired = field.required;
            field.required = false;
        }
    });
}

// Afficher le compte de destination au chargement si virement est sélectionné
if (document.getElementById('type_operation').value === 'virement') {
    document.getElementById('compte_destination_block').style.display = 'block';
    document.getElementById('compte_destination_id').required = true;
}

// Appeler toggleRecurrenceFields au chargement pour gérer l'état initial
toggleRecurrenceFields();

// Chargement dynamique des sous-catégories
function loadSousCategories(categorieId, selectedSousCategorieId = null) {
    const sousCategorieSelect = document.getElementById('sous_categorie_id');
    
    // Réinitialiser les sous-catégories
    sousCategorieSelect.innerHTML = '<option value="">Aucune</option>';
    
    if (!categorieId) {
        sousCategorieSelect.disabled = true;
        return;
    }
    
    // Charger les sous-catégories
    fetch(`/monbudgetV2/api/categories/${categorieId}/sous-categories`)
        .then(response => response.json())
        .then(sousCategories => {
            if (sousCategories.length > 0) {
                sousCategorieSelect.disabled = false;
                sousCategories.forEach(sc => {
                    const option = document.createElement('option');
                    option.value = sc.id;
                    option.textContent = sc.nom;
                    if (selectedSousCategorieId && sc.id == selectedSousCategorieId) {
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
}

// Event listener pour le changement de catégorie
document.getElementById('categorie_id').addEventListener('change', function() {
    loadSousCategories(this.value);
});

// Charger les sous-catégories au chargement de la page si une catégorie est sélectionnée
document.addEventListener('DOMContentLoaded', function() {
    const categorieId = document.getElementById('categorie_id').value;
    const sousCategorieId = <?= $transaction['sous_categorie_id'] ?? 'null' ?>;
    
    if (categorieId) {
        loadSousCategories(categorieId, sousCategorieId);
    } else {
        document.getElementById('sous_categorie_id').disabled = true;
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
