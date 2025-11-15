<?php
$title = $title ?? 'Nouvelle Banque';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-bank"></i> Nouvelle Banque</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('banques/store') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <!-- Informations principales -->
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Informations principales</h5>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nom" class="form-label">Nom de la banque <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required maxlength="100" value="<?= old('nom') ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="code_banque" class="form-label">Code Banque</label>
                                <input type="text" class="form-control" id="code_banque" name="code_banque" maxlength="20" value="<?= old('code_banque') ?>">
                                <div class="form-text">5 chiffres</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bic" class="form-label">BIC / SWIFT</label>
                                <input type="text" class="form-control text-uppercase" id="bic" name="bic" maxlength="11" value="<?= old('bic') ?>" placeholder="BNPAFRPPXXX">
                                <div class="form-text">Code international (8 ou 11 caractères)</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="20" value="<?= old('telephone') ?>" placeholder="01 23 45 67 89">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_web" class="form-label">Site Web</label>
                            <input type="url" class="form-control" id="site_web" name="site_web" maxlength="255" value="<?= old('site_web') ?>" placeholder="https://www.exemple.fr">
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">Email de contact</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" maxlength="255" value="<?= old('contact_email') ?>" placeholder="contact@banque.fr">
                        </div>
                        
                        <!-- Logo -->
                        <div class="mb-3">
                            <label for="logo_file" class="form-label">
                                <i class="bi bi-image"></i> Logo de la banque
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="logo_file" 
                                   name="logo_file" 
                                   accept="image/png,image/jpeg,image/jpg,image/gif">
                            <div class="form-text">
                                Formats acceptés : PNG, JPG, GIF. Taille max : 2 Mo. Recommandé : 200x200px
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Adresse -->
                        <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Adresse</h5>
                        
                        <div class="mb-3">
                            <label for="adresse_ligne1" class="form-label">Adresse ligne 1</label>
                            <input type="text" class="form-control" id="adresse_ligne1" name="adresse_ligne1" maxlength="255" value="<?= old('adresse_ligne1') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="adresse_ligne2" class="form-label">Adresse ligne 2</label>
                            <input type="text" class="form-control" id="adresse_ligne2" name="adresse_ligne2" maxlength="255" value="<?= old('adresse_ligne2') ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code_postal" class="form-label">Code Postal</label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" maxlength="10" value="<?= old('code_postal') ?>">
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" maxlength="100" value="<?= old('ville') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pays" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="pays" name="pays" maxlength="100" value="<?= old('pays') ?: 'France' ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= url('banques') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Créer la banque
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-uppercase pour le BIC
document.getElementById('bic').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
