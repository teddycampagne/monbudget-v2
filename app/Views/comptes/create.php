<?php 
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../components/ui-helpers.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('comptes') ?>">Comptes</a></li>
                <li class="breadcrumb-item active">Nouveau compte</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><i class="bi bi-plus-circle"></i> Nouveau Compte Bancaire</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations du compte</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('comptes/store') ?>">
                        <?= csrf_field() ?>
                        
                        <!-- Banque -->
                        <div class="mb-3">
                            <label for="banque_id" class="form-label">
                                Banque <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="banque_id" name="banque_id" required>
                                <option value="">Sélectionnez une banque</option>
                                <?php foreach ($banques as $banque): ?>
                                    <option value="<?= $banque['id'] ?>" <?= old('banque_id') == $banque['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($banque['nom']) ?>
                                        <?php if ($banque['code_banque']): ?>
                                            (<?= htmlspecialchars($banque['code_banque']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($banques)): ?>
                                <div class="form-text text-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Aucune banque disponible. <a href="<?= url('banques/create') ?>">Créez d'abord une banque</a>.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Nom du compte -->
                        <?= formInput(
                            'nom',
                            'Nom du compte',
                            'text',
                            old('nom'),
                            true,
                            'Ex: Compte Courant Principal',
                            'Nom personnalisé pour identifier facilement ce compte'
                        ) ?>

                        <!-- Titulaires du compte -->
                        <div class="card bg-light mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-people"></i> Titulaire(s) du compte</h6>
                                <a href="<?= url('titulaires/create') ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-plus-circle"></i> Créer un titulaire
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($titulaires)): ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Aucun titulaire disponible. 
                                        <a href="<?= url('titulaires/create') ?>" target="_blank">Créez d'abord un titulaire</a>.
                                    </div>
                                <?php else: ?>
                                    <!-- Premier titulaire (obligatoire) -->
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="titulaire_1_id" class="form-label">
                                                Premier titulaire <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="titulaire_1_id" name="titulaire_1_id" required>
                                                <option value="">Sélectionnez un titulaire</option>
                                                <?php foreach ($titulaires as $titulaire): ?>
                                                    <option value="<?= $titulaire['id'] ?>" <?= old('titulaire_1_id') == $titulaire['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($titulaire['prenom'] . ' ' . strtoupper($titulaire['nom'])) ?>
                                                        <?php if ($titulaire['ville']): ?>
                                                            - <?= htmlspecialchars($titulaire['ville']) ?>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="titulaire_1_role" class="form-label">Rôle</label>
                                            <select class="form-select" id="titulaire_1_role" name="titulaire_1_role">
                                                <option value="titulaire" <?= old('titulaire_1_role', 'titulaire') == 'titulaire' ? 'selected' : '' ?>>Titulaire</option>
                                                <option value="co-titulaire" <?= old('titulaire_1_role') == 'co-titulaire' ? 'selected' : '' ?>>Co-titulaire</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Second titulaire (optionnel pour compte joint) -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="add_second_titulaire" <?= old('titulaire_2_id') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="add_second_titulaire">
                                            <i class="bi bi-people-fill"></i> Compte joint (ajouter un 2ème titulaire)
                                        </label>
                                    </div>

                                    <div id="second_titulaire_section" style="display: <?= old('titulaire_2_id') ? 'block' : 'none' ?>;">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label for="titulaire_2_id" class="form-label">Second titulaire</label>
                                                <select class="form-select" id="titulaire_2_id" name="titulaire_2_id">
                                                    <option value="">Sélectionnez un titulaire</option>
                                                    <?php foreach ($titulaires as $titulaire): ?>
                                                        <option value="<?= $titulaire['id'] ?>" <?= old('titulaire_2_id') == $titulaire['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($titulaire['prenom'] . ' ' . strtoupper($titulaire['nom'])) ?>
                                                            <?php if ($titulaire['ville']): ?>
                                                                - <?= htmlspecialchars($titulaire['ville']) ?>
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="titulaire_2_role" class="form-label">Rôle</label>
                                                <select class="form-select" id="titulaire_2_role" name="titulaire_2_role">
                                                    <option value="titulaire" <?= old('titulaire_2_role', 'titulaire') == 'titulaire' ? 'selected' : '' ?>>Titulaire</option>
                                                    <option value="co-titulaire" <?= old('titulaire_2_role') == 'co-titulaire' ? 'selected' : '' ?>>Co-titulaire</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Type de compte -->
                            <div class="col-md-6">
                                <?= formSelect(
                                    'type_compte',
                                    'Type de compte',
                                    [
                                        'Compte Courant' => 'Compte Courant',
                                        'Compte Épargne' => 'Compte Épargne',
                                        'Livret A' => 'Livret A',
                                        'LDD' => 'LDD',
                                        'PEL' => 'PEL',
                                        'CEL' => 'CEL',
                                        'Assurance Vie' => 'Assurance Vie',
                                        'PEA' => 'PEA',
                                        'Autre' => 'Autre'
                                    ],
                                    old('type_compte'),
                                    false,
                                    'Sélectionnez un type'
                                ) ?>
                            </div>

                            <!-- Devise -->
                            <div class="col-md-6">
                                <?= formSelect(
                                    'devise',
                                    'Devise',
                                    [
                                        'EUR' => 'EUR (€)',
                                        'USD' => 'USD ($)',
                                        'GBP' => 'GBP (£)',
                                        'CHF' => 'CHF'
                                    ],
                                    old('devise', 'EUR'),
                                    false,
                                    ''
                                ) ?>
                            </div>
                        </div>

                        <!-- Coordonnées bancaires -->
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-credit-card"></i> Coordonnées bancaires (RIB/IBAN)</h6>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-3">
                                    <i class="bi bi-info-circle"></i> 
                                    Saisissez soit le RIB complet (code guichet + n° compte + clé), soit l'IBAN seul. 
                                    La clé RIB et l'IBAN seront calculés automatiquement.
                                </p>

                                <div class="row">
                                    <!-- Code guichet -->
                                    <div class="col-md-4 mb-3">
                                        <label for="code_guichet" class="form-label">Code guichet</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="code_guichet" 
                                               name="code_guichet" 
                                               value="<?= old('code_guichet') ?>"
                                               placeholder="12345"
                                               maxlength="5"
                                               pattern="\d{5}">
                                        <div class="form-text">5 chiffres</div>
                                    </div>

                                    <!-- Numéro de compte -->
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_compte" class="form-label">Numéro de compte</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="numero_compte" 
                                               name="numero_compte" 
                                               value="<?= old('numero_compte') ?>"
                                               placeholder="12345678901"
                                               maxlength="11"
                                               pattern="\d{11}">
                                        <div class="form-text">11 chiffres</div>
                                    </div>

                                    <!-- Clé RIB -->
                                    <div class="col-md-4 mb-3">
                                        <label for="cle_rib" class="form-label">
                                            Clé RIB
                                            <span class="badge bg-success" title="Auto-calculée">Auto</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="cle_rib" 
                                               name="cle_rib" 
                                               value="<?= old('cle_rib') ?>"
                                               placeholder="01"
                                               maxlength="2"
                                               pattern="\d{2}">
                                        <div class="form-text">2 chiffres (calculé auto)</div>
                                    </div>
                                </div>

                                <!-- IBAN -->
                                <div class="mb-0">
                                    <label for="iban" class="form-label">
                                        IBAN 
                                        <span class="badge bg-success" title="Auto-généré">Auto</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="iban" 
                                           name="iban" 
                                           value="<?= old('iban') ?>"
                                           placeholder="FR76 1234 5678 9012 3456 7890 123"
                                           maxlength="34">
                                    <div class="form-text">Format: FR + 25 chiffres (généré automatiquement ou saisie manuelle)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Solde initial -->
                        <div class="mb-3">
                            <label for="solde_initial" class="form-label">Solde initial</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="solde_initial" 
                                       name="solde_initial" 
                                       value="<?= old('solde_initial', '0.00') ?>"
                                       step="0.01"
                                       placeholder="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="form-text">Solde du compte au moment de sa création</div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnel)</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Informations complémentaires sur ce compte..."><?= old('description') ?></textarea>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <?= linkButton('Annuler', url('comptes'), 'btn-secondary', 'bi-arrow-left') ?>
                            <?= submitButton('Créer le compte', 'btn-primary', 'bi-save') ?>
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
                    <h6>Informations requises</h6>
                    <ul class="small">
                        <li><strong>Banque :</strong> Sélectionnez la banque associée</li>
                        <li><strong>Nom :</strong> Donnez un nom reconnaissable à votre compte</li>
                    </ul>
                    
                    <h6 class="mt-3">Conseils</h6>
                    <ul class="small">
                        <li>Utilisez des noms clairs (ex: "Compte Courant Perso", "Livret A Épargne")</li>
                        <li>Le solde initial sera automatiquement défini comme solde actuel</li>
                        <li>Vous pourrez modifier ces informations plus tard</li>
                    </ul>
                    
                    <h6 class="mt-3">💡 RIB / IBAN automatique</h6>
                    <ul class="small">
                        <li><strong>Option 1 :</strong> Saisissez uniquement l'IBAN → Les champs RIB seront extraits automatiquement</li>
                        <li><strong>Option 2 :</strong> Saisissez code guichet + n° compte → La clé RIB et l'IBAN seront calculés</li>
                        <li><strong>Option 3 :</strong> Saisie manuelle de tous les champs</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/compte-iban.js') ?>"></script>
<script>
// Gestion de l'affichage du second titulaire
document.getElementById('add_second_titulaire').addEventListener('change', function() {
    const secondSection = document.getElementById('second_titulaire_section');
    const secondSelect = document.getElementById('titulaire_2_id');
    
    if (this.checked) {
        secondSection.style.display = 'block';
    } else {
        secondSection.style.display = 'none';
        secondSelect.value = '';
    }
});

// Validation pour éviter de sélectionner le même titulaire 2 fois
document.getElementById('titulaire_1_id').addEventListener('change', validateTitulaires);
document.getElementById('titulaire_2_id').addEventListener('change', validateTitulaires);

function validateTitulaires() {
    const tit1 = document.getElementById('titulaire_1_id').value;
    const tit2 = document.getElementById('titulaire_2_id').value;
    
    if (tit1 && tit2 && tit1 === tit2) {
        alert('Vous ne pouvez pas sélectionner le même titulaire deux fois !');
        document.getElementById('titulaire_2_id').value = '';
    }
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
