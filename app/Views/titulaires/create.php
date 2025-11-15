<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus"></i> Nouveau Titulaire</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('titulaires/store') ?>">
                        <?= csrf_field() ?>

                        <!-- Informations personnelles -->
                        <h5 class="mb-3"><i class="bi bi-person"></i> Informations personnelles</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nom" 
                                       name="nom" 
                                       value="<?= old('nom') ?>"
                                       placeholder="DUPONT"
                                       required 
                                       maxlength="100">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="prenom" 
                                       name="prenom" 
                                       value="<?= old('prenom') ?>"
                                       placeholder="Jean"
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_naissance" 
                                       name="date_naissance" 
                                       value="<?= old('date_naissance') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lieu_naissance" class="form-label">Lieu de naissance</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="lieu_naissance" 
                                       name="lieu_naissance" 
                                       value="<?= old('lieu_naissance') ?>"
                                       placeholder="Paris"
                                       maxlength="100">
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Adresse -->
                        <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Adresse</h5>

                        <div class="mb-3">
                            <label for="adresse_ligne1" class="form-label">Adresse ligne 1</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="adresse_ligne1" 
                                   name="adresse_ligne1" 
                                   value="<?= old('adresse_ligne1') ?>"
                                   placeholder="10 rue de la République"
                                   maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="adresse_ligne2" class="form-label">Adresse ligne 2</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="adresse_ligne2" 
                                   name="adresse_ligne2" 
                                   value="<?= old('adresse_ligne2') ?>"
                                   placeholder="Appartement 5B"
                                   maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code_postal" class="form-label">Code postal</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="code_postal" 
                                       name="code_postal" 
                                       value="<?= old('code_postal') ?>"
                                       placeholder="75001"
                                       maxlength="10">
                            </div>

                            <div class="col-md-8 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ville" 
                                       name="ville" 
                                       value="<?= old('ville') ?>"
                                       placeholder="Paris"
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pays" class="form-label">Pays</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pays" 
                                   name="pays" 
                                   value="<?= old('pays', 'France') ?>"
                                   maxlength="100">
                        </div>

                        <hr class="my-4">

                        <!-- Contact -->
                        <h5 class="mb-3"><i class="bi bi-telephone"></i> Contact</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telephone" 
                                       name="telephone" 
                                       value="<?= old('telephone') ?>"
                                       placeholder="01 23 45 67 89"
                                       maxlength="20">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= old('email') ?>"
                                       placeholder="jean.dupont@example.com"
                                       maxlength="255">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('titulaires') ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Créer le titulaire
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
