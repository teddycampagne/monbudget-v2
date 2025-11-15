<?php
$title = $title ?? 'Modifier la Banque';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-pencil"></i> Modifier la Banque</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url("banques/{$banque['id']}/update") ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <!-- Informations principales -->
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Informations principales</h5>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nom" class="form-label">Nom de la banque <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required maxlength="100" value="<?= htmlspecialchars($banque['nom']) ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="code_banque" class="form-label">Code Banque</label>
                                <input type="text" class="form-control" id="code_banque" name="code_banque" maxlength="20" value="<?= htmlspecialchars($banque['code_banque'] ?? '') ?>">
                                <div class="form-text">5 chiffres</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bic" class="form-label">BIC / SWIFT</label>
                                <input type="text" class="form-control text-uppercase" id="bic" name="bic" maxlength="11" value="<?= htmlspecialchars($banque['bic'] ?? '') ?>" placeholder="BNPAFRPPXXX">
                                <div class="form-text">Code international (8 ou 11 caractères)</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="20" value="<?= htmlspecialchars($banque['telephone'] ?? '') ?>" placeholder="01 23 45 67 89">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_web" class="form-label">Site Web</label>
                            <input type="url" class="form-control" id="site_web" name="site_web" maxlength="255" value="<?= htmlspecialchars($banque['site_web'] ?? '') ?>" placeholder="https://www.exemple.fr">
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">Email de contact</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" maxlength="255" value="<?= htmlspecialchars($banque['contact_email'] ?? '') ?>" placeholder="contact@banque.fr">
                        </div>

                        <!-- Logo -->
                        <div class="mb-3">
                            <label for="logo_file" class="form-label">Logo de la banque</label>
                            <?php if (!empty($banque['logo_file']) && file_exists(__DIR__ . '/../../../uploads/logos/' . $banque['logo_file'])): ?>
                                <div class="mb-2">
                                    <img src="<?= url('uploads/logos/' . htmlspecialchars($banque['logo_file'])) ?>" alt="Logo actuel" style="max-width: 200px; max-height: 100px;" class="img-thumbnail">
                                    <p class="text-muted small mt-1">Logo actuel</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/jpg,image/gif">
                            <div class="form-text">Taille max: 2 Mo, Recommandé: 200x200px</div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Adresse -->
                        <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Adresse</h5>
                        
                        <div class="mb-3">
                            <label for="adresse_ligne1" class="form-label">Adresse ligne 1</label>
                            <input type="text" class="form-control" id="adresse_ligne1" name="adresse_ligne1" maxlength="255" value="<?= htmlspecialchars($banque['adresse_ligne1'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="adresse_ligne2" class="form-label">Adresse ligne 2</label>
                            <input type="text" class="form-control" id="adresse_ligne2" name="adresse_ligne2" maxlength="255" value="<?= htmlspecialchars($banque['adresse_ligne2'] ?? '') ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code_postal" class="form-label">Code Postal</label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" maxlength="10" value="<?= htmlspecialchars($banque['code_postal'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" maxlength="100" value="<?= htmlspecialchars($banque['ville'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pays" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="pays" name="pays" maxlength="100" value="<?= htmlspecialchars($banque['pays'] ?? 'France') ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Info création/modification -->
                        <div class="alert alert-info">
                            <small>
                                <strong>Créée le :</strong> <?= date('d/m/Y à H:i', strtotime($banque['created_at'])) ?><br>
                                <strong>Dernière modification :</strong> <?= date('d/m/Y à H:i', strtotime($banque['updated_at'])) ?>
                            </small>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= url('banques') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
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

<script>
// Auto-uppercase pour le BIC
document.getElementById('bic').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
