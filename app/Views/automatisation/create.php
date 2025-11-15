<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('automatisation') ?>">Automatisation</a></li>
            <li class="breadcrumb-item active">Créer une règle</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-plus-circle"></i> Créer une règle d'automatisation</h1>
        <p class="text-muted mb-0">Définissez les critères et les actions automatiques</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="<?= url('automatisation/store') ?>">
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
                                   placeholder="Ex: Picnic - Livraisons alimentaires"
                                   required>
                            <small class="text-muted">Nom descriptif pour identifier facilement la règle</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priorite" class="form-label">
                                Priorité <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="priorite" 
                                   name="priorite"
                                   value="50"
                                   min="0"
                                   max="999"
                                   required>
                            <small class="text-muted">Les règles avec une priorité plus petite sont appliquées en premier (0 = priorité maximale)</small>
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="actif" 
                                   name="actif"
                                   checked>
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
                                   placeholder="Ex: Picnic Paris"
                                   required>
                            <small class="text-muted">Texte ou expression régulière à rechercher dans le libellé</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type_pattern" class="form-label">
                                Type de recherche <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="type_pattern" name="type_pattern" required>
                                <option value="contient" selected>Contient le texte</option>
                                <option value="commence_par">Commence par le texte</option>
                                <option value="termine_par">Termine par le texte</option>
                                <option value="regex">Expression régulière (avancé)</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="case_sensitive" 
                                   name="case_sensitive">
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
                        <p class="text-muted">Sélectionnez une ou plusieurs actions à appliquer automatiquement :</p>
                        
                        <div class="mb-3">
                            <label for="action_categorie" class="form-label">
                                <i class="bi bi-tag"></i> Catégorie
                            </label>
                            <select class="form-select" id="action_categorie" name="action_categorie" onchange="loadSousCategories(this.value)">
                                <option value="">-- Ne pas modifier --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="action_sous_categorie" class="form-label">
                                <i class="bi bi-tag-fill"></i> Sous-catégorie
                            </label>
                            <select class="form-select" id="action_sous_categorie" name="action_sous_categorie">
                                <option value="">-- Sélectionnez d'abord une catégorie --</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="action_tiers" class="form-label">
                                <i class="bi bi-person"></i> Tiers
                            </label>
                            <select class="form-select" id="action_tiers" name="action_tiers">
                                <option value="">-- Ne pas modifier --</option>
                                <?php foreach ($tiers as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nom']) ?></option>
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
                                   list="moyens_paiement"
                                   placeholder="Ex: Carte bancaire, Virement, Prélèvement...">
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

                <!-- Boutons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('automatisation') ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Créer la règle
                    </button>
                </div>
            </form>
        </div>

        <!-- Aide -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> Aide</h5>
                </div>
                <div class="card-body">
                    <h6>Exemples de règles :</h6>
                    
                    <div class="mb-3">
                        <strong>Paiement par carte</strong>
                        <ul class="small mb-0">
                            <li>Pattern : <code>PAIEMENT PAR CARTE</code></li>
                            <li>Type : Contient</li>
                            <li>Action : Moyen de paiement = "Carte bancaire"</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Livraison Picnic</strong>
                        <ul class="small mb-0">
                            <li>Pattern : <code>Picnic</code></li>
                            <li>Type : Contient</li>
                            <li>Action : Catégorie = "Alimentation", Tiers = "Picnic"</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Salaire</strong>
                        <ul class="small mb-0">
                            <li>Pattern : <code>FOUNDEVER FRANCE</code></li>
                            <li>Type : Contient</li>
                            <li>Action : Catégorie = "Salaire", Tiers = "Foundever"</li>
                        </ul>
                    </div>
                    
                    <hr>
                    
                    <h6>Types de recherche :</h6>
                    <ul class="small">
                        <li><strong>Contient :</strong> Le libellé contient le texte</li>
                        <li><strong>Commence par :</strong> Le libellé commence par le texte</li>
                        <li><strong>Termine par :</strong> Le libellé se termine par le texte</li>
                        <li><strong>Regex :</strong> Utilise une expression régulière</li>
                    </ul>
                    
                    <hr>
                    
                    <h6>Priorité :</h6>
                    <p class="small mb-0">
                        Plus le chiffre est <strong>petit</strong>, plus la règle est prioritaire.
                        Les règles prioritaires s'appliquent en premier.
                    </p>
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
