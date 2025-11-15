<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
            <li class="breadcrumb-item"><a href="<?= url("comptes/{$recurrence['compte_id']}/recurrences") ?>">Récurrences</a></li>
            <li class="breadcrumb-item active">Modifier</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-pencil"></i> 
            Modifier la récurrence : <?= htmlspecialchars($recurrence['libelle']) ?>
        </h1>
        <p class="text-muted">
            Compte : <?= htmlspecialchars($recurrence['compte_nom'] ?? '') ?>
            <span class="mx-2">|</span>
            Occurrences créées : <strong id="nb-occurrences">...</strong>
        </p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form method="POST" action="<?= url("recurrences/{$recurrence['id']}/update") ?>">
                <?= csrf_field() ?>

                <!-- Informations de base -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations de base</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Compte</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($compte['nom']) ?> - <?= htmlspecialchars($compte['banque_nom']) ?>" 
                                       disabled>
                                <small class="text-muted">Le compte ne peut pas être modifié</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="type_operation" class="form-label">
                                    Type d'opération <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="type_operation" name="type_operation" required>
                                    <option value="credit" <?= $recurrence['type_operation'] === 'credit' ? 'selected' : '' ?>>Crédit</option>
                                    <option value="debit" <?= $recurrence['type_operation'] === 'debit' ? 'selected' : '' ?>>Débit</option>
                                    <option value="virement" <?= $recurrence['type_operation'] === 'virement' ? 'selected' : '' ?>>Virement</option>
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
                                           value="<?= abs($recurrence['montant']) ?>"
                                           step="0.01"
                                           min="0"
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="compte_destination_block" style="display: <?= $recurrence['type_operation'] === 'virement' ? 'block' : 'none' ?>;">
                                <label for="compte_destination_id" class="form-label">
                                    Compte de destination <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="compte_destination_id" name="compte_destination_id">
                                    <option value="">Sélectionnez un compte</option>
                                    <?php foreach ($comptes as $c): ?>
                                        <?php if ($c['id'] != $recurrence['compte_id']): ?>
                                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $recurrence['compte_destination_id'] ? 'selected' : '' ?>>
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
                                   value="<?= htmlspecialchars($recurrence['libelle']) ?>"
                                   required 
                                   maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="categorie_id" class="form-label">Catégorie</label>
                                <select class="form-select" id="categorie_id" name="categorie_id">
                                    <option value="">Aucune catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $recurrence['categorie_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sous_categorie_id" class="form-label">Sous-catégorie</label>
                                <select class="form-select" id="sous_categorie_id" name="sous_categorie_id">
                                    <option value="">Aucune sous-catégorie</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tiers_id" class="form-label">Tiers</label>
                            <select class="form-select" id="tiers_id" name="tiers_id">
                                <option value="">Aucun tiers</option>
                                <?php foreach ($tiers as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $recurrence['tiers_id'] ? 'selected' : '' ?>>
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
                                    <option value="quotidien" <?= $recurrence['frequence'] === 'quotidien' ? 'selected' : '' ?>>Quotidien</option>
                                    <option value="hebdomadaire" <?= $recurrence['frequence'] === 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire</option>
                                    <option value="mensuel" <?= $recurrence['frequence'] === 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                                    <option value="trimestriel" <?= $recurrence['frequence'] === 'trimestriel' ? 'selected' : '' ?>>Trimestriel</option>
                                    <option value="semestriel" <?= $recurrence['frequence'] === 'semestriel' ? 'selected' : '' ?>>Semestriel</option>
                                    <option value="annuel" <?= $recurrence['frequence'] === 'annuel' ? 'selected' : '' ?>>Annuel</option>
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
                                       value="<?= $recurrence['intervalle'] ?>"
                                       min="1"
                                       required>
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
                                       value="<?= $recurrence['date_debut'] ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_fin" class="form-label">Date de fin (optionnel)</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_fin" 
                                       name="date_fin" 
                                       value="<?= $recurrence['date_fin'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nb_executions_max" class="form-label">Nombre maximum d'exécutions</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="nb_executions_max" 
                                       name="nb_executions_max" 
                                       value="<?= $recurrence['nb_executions_max'] ?? '' ?>"
                                       min="1">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="tolerance_weekend" class="form-label">Tolérance weekend</label>
                                <select class="form-select" id="tolerance_weekend" name="tolerance_weekend">
                                    <option value="jour_ouvre_suivant" <?= ($recurrence['tolerance_weekend'] ?? 'jour_ouvre_suivant') === 'jour_ouvre_suivant' ? 'selected' : '' ?>>Jour ouvré suivant</option>
                                    <option value="jour_ouvre_precedent" <?= ($recurrence['tolerance_weekend'] ?? '') === 'jour_ouvre_precedent' ? 'selected' : '' ?>>Jour ouvré précédent</option>
                                    <option value="aucune" <?= ($recurrence['tolerance_weekend'] ?? '') === 'aucune' ? 'selected' : '' ?>>Aucune</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="auto_validation" 
                                   name="auto_validation" 
                                   value="1"
                                   <?= $recurrence['auto_validation'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_validation">
                                Valider automatiquement les transactions générées
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="recurrence_active" 
                                   name="recurrence_active" 
                                   value="1"
                                   <?= $recurrence['recurrence_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="recurrence_active">
                                <strong>Récurrence active</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-between">
                    <a href="<?= url("comptes/{$recurrence['compte_id']}/recurrences") ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

        <!-- Informations -->
        <div class="col-md-4">
            <div class="card bg-light mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Statistiques</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Créée le :</strong><br>
                        <?= (new DateTime($recurrence['created_at']))->format('d/m/Y à H:i') ?>
                    </p>
                    <p class="mb-2">
                        <strong>Prochaine exécution :</strong><br>
                        <?php if ($recurrence['prochaine_execution']): ?>
                            <?= (new DateTime($recurrence['prochaine_execution']))->format('d/m/Y') ?>
                        <?php else: ?>
                            <em class="text-muted">Non planifiée</em>
                        <?php endif; ?>
                    </p>
                    <p class="mb-2">
                        <strong>Dernière exécution :</strong><br>
                        <?php if ($recurrence['derniere_execution']): ?>
                            <?= (new DateTime($recurrence['derniere_execution']))->format('d/m/Y') ?>
                        <?php else: ?>
                            <em class="text-muted">Jamais exécutée</em>
                        <?php endif; ?>
                    </p>
                    <p class="mb-0">
                        <strong>Exécutions :</strong><br>
                        <?= $recurrence['nb_executions'] ?? 0 ?> 
                        <?php if ($recurrence['nb_executions_max']): ?>
                            / <?= $recurrence['nb_executions_max'] ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Attention :</strong> Les modifications ne s'appliquent qu'aux futures occurrences. Les transactions déjà créées ne sont pas modifiées.
            </div>
        </div>
    </div>
</div>

<script>
// Charger le nombre d'occurrences
fetch('<?= url("api/recurrences/{$recurrence['id']}/count-occurrences") ?>')
    .then(response => response.json())
    .then(data => {
        document.getElementById('nb-occurrences').textContent = data.count;
    })
    .catch(err => {
        document.getElementById('nb-occurrences').textContent = '?';
    });

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
    }
});

// Chargement des sous-catégories
document.getElementById('categorie_id').addEventListener('change', function() {
    const categorieId = this.value;
    const sousCategorieSelect = document.getElementById('sous_categorie_id');
    
    sousCategorieSelect.innerHTML = '<option value="">Aucune sous-catégorie</option>';
    
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
                    // Sélectionner la sous-catégorie actuelle
                    if (sc.id == <?= $recurrence['sous_categorie_id'] ?? 'null' ?>) {
                        option.selected = true;
                    }
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

// Charger les sous-catégories au chargement si catégorie sélectionnée
document.addEventListener('DOMContentLoaded', function() {
    const categorieId = document.getElementById('categorie_id').value;
    if (categorieId) {
        document.getElementById('categorie_id').dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
