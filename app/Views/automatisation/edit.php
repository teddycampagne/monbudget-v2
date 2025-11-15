<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('automatisation') ?>">Automatisation</a></li>
            <li class="breadcrumb-item active">Modifier la règle</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-pencil"></i> Modifier la règle : <?= htmlspecialchars($regle['nom']) ?></h1>
        <p class="text-muted mb-0">Modifiez les critères et les actions automatiques</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="<?= url("automatisation/{$regle['id']}/update") ?>">
                <?= csrf_field() ?>
                
                <!-- Informations générales -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Informations générales</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                Nom de la règle <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nom" 
                                   name="nom"
                                   value="<?= htmlspecialchars($regle['nom']) ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priorite" class="form-label">
                                Priorité <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="priorite" 
                                   name="priorite"
                                   value="<?= $regle['priorite'] ?>"
                                   min="0"
                                   max="999"
                                   required>
                            <small class="text-muted">Les règles avec une priorité plus petite sont appliquées en premier</small>
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="actif" 
                                   name="actif"
                                   <?= $regle['actif'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="actif">
                                Règle active
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Critères de détection -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Critères de détection</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="pattern" class="form-label">
                                Motif à rechercher <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pattern" 
                                   name="pattern"
                                   value="<?= htmlspecialchars($regle['pattern']) ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type_pattern" class="form-label">
                                Type de recherche <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="type_pattern" name="type_pattern" required>
                                <option value="contient" <?= $regle['type_pattern'] === 'contient' ? 'selected' : '' ?>>Contient le texte</option>
                                <option value="commence_par" <?= $regle['type_pattern'] === 'commence_par' ? 'selected' : '' ?>>Commence par le texte</option>
                                <option value="termine_par" <?= $regle['type_pattern'] === 'termine_par' ? 'selected' : '' ?>>Termine par le texte</option>
                                <option value="regex" <?= $regle['type_pattern'] === 'regex' ? 'selected' : '' ?>>Expression régulière (avancé)</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="case_sensitive" 
                                   name="case_sensitive"
                                   <?= $regle['case_sensitive'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="case_sensitive">
                                Sensible à la casse (distinguer majuscules/minuscules)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions automatiques -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Actions automatiques</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="action_categorie" class="form-label">
                                <i class="bi bi-tag"></i> Catégorie
                            </label>
                            <select class="form-select" id="action_categorie" name="action_categorie" onchange="loadSousCategories(this.value)">
                                <option value="">-- Ne pas modifier --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $regle['action_categorie'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="action_sous_categorie" class="form-label">
                                <i class="bi bi-tag-fill"></i> Sous-catégorie
                            </label>
                            <select class="form-select" id="action_sous_categorie" name="action_sous_categorie">
                                <option value="">-- Ne pas modifier --</option>
                                <?php foreach ($sousCategories as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" <?= $regle['action_sous_categorie'] == $sc['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sc['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="action_tiers" class="form-label">
                                <i class="bi bi-person"></i> Tiers
                            </label>
                            <select class="form-select" id="action_tiers" name="action_tiers">
                                <option value="">-- Ne pas modifier --</option>
                                <?php foreach ($tiers as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $regle['action_tiers'] == $t['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="action_moyen_paiement" class="form-label">
                                <i class="bi bi-credit-card"></i> Moyen de paiement
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="action_moyen_paiement" 
                                   name="action_moyen_paiement"
                                   value="<?= htmlspecialchars($regle['action_moyen_paiement'] ?? '') ?>"
                                   list="moyens_paiement">
                            <datalist id="moyens_paiement">
                                <option value="Carte bancaire">
                                <option value="Virement">
                                <option value="Prélèvement">
                                <option value="Chèque">
                                <option value="Espèces">
                                <option value="Prélèvement automatique">
                                <option value="Virement instantané">
                            </datalist>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Statistiques</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Nombre d'applications :</dt>
                            <dd class="col-sm-6"><?= $regle['nb_applications'] ?> fois</dd>
                            
                            <dt class="col-sm-6">Dernière application :</dt>
                            <dd class="col-sm-6">
                                <?php if ($regle['derniere_application']): ?>
                                    <?= date('d/m/Y H:i', strtotime($regle['derniere_application'])) ?>
                                <?php else: ?>
                                    Jamais appliquée
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Créée le :</dt>
                            <dd class="col-sm-6"><?= date('d/m/Y H:i', strtotime($regle['created_at'])) ?></dd>
                            
                            <dt class="col-sm-6">Modifiée le :</dt>
                            <dd class="col-sm-6"><?= date('d/m/Y H:i', strtotime($regle['updated_at'])) ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('automatisation') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Aide -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h5>
                </div>
                <div class="card-body">
                    <h6>Priorité :</h6>
                    <p class="small">
                        Les règles sont appliquées par ordre de priorité croissante (0 = priorité maximale).
                        Une fois qu'un champ est rempli, les règles suivantes ne peuvent plus le modifier.
                    </p>
                    
                    <hr>
                    
                    <h6>Types de recherche :</h6>
                    <ul class="small">
                        <li><strong>Contient :</strong> Le libellé contient le texte</li>
                        <li><strong>Commence par :</strong> Le libellé commence par le texte</li>
                        <li><strong>Termine par :</strong> Le libellé se termine par le texte</li>
                        <li><strong>Regex :</strong> Expression régulière avancée</li>
                    </ul>
                    
                    <hr>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Attention :</strong> La modification d'une règle n'affecte pas les transactions déjà traitées.
                        Utilisez "Appliquer à tout" pour appliquer les nouvelles règles.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Charger les sous-catégories quand on change la catégorie
function loadSousCategories(categorieId) {
    const select = document.getElementById('action_sous_categorie');
    select.innerHTML = '<option value="">Chargement...</option>';
    
    if (!categorieId) {
        select.innerHTML = '<option value="">-- Sélectionnez d\'abord une catégorie --</option>';
        return;
    }
    
    fetch('<?= url('api/categories') ?>/' + categorieId + '/sous-categories')
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Ne pas modifier --</option>';
            data.forEach(sc => {
                const option = document.createElement('option');
                option.value = sc.id;
                option.textContent = sc.nom;
                // Sélectionner la sous-catégorie actuelle si elle existe
                if (sc.id == <?= $regle['action_sous_categorie'] ?? 0 ?>) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        })
        .catch(error => {
            select.innerHTML = '<option value="">-- Erreur de chargement --</option>';
            console.error(error);
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
