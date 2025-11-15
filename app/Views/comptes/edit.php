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
                <li class="breadcrumb-item active">Modifier</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">
            <i class="bi bi-pencil"></i> Modifier le Compte : <?= htmlspecialchars($compte['nom']) ?>
        </h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations du compte</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url("comptes/{$compte['id']}/update") ?>">
                        <?= csrf_field() ?>
                        
                        
                        <!-- Banque -->
                        <div class="mb-3">
                            <label for="banque_id" class="form-label">
                                Banque <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="banque_id" name="banque_id" required>
                                <option value="">Sélectionnez une banque</option>
                                <?php foreach ($banques as $banque): ?>
                                    <option value="<?= $banque['id'] ?>" <?= $compte['banque_id'] == $banque['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($banque['nom']) ?>
                                        <?php if ($banque['code_banque']): ?>
                                            (<?= htmlspecialchars($banque['code_banque']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nom du compte -->
                        <?= formInput(
                            'nom',
                            'Nom du compte',
                            'text',
                            htmlspecialchars($compte['nom']),
                            true
                        ) ?>

                        <!-- Titulaires du compte -->
                        <?php
                        // Préparer les titulaires actuels
                        $tit1 = $compte['titulaires'][0] ?? null;
                        $tit2 = $compte['titulaires'][1] ?? null;
                        ?>
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
                                                    <option value="<?= $titulaire['id'] ?>" <?= ($tit1 && $tit1['id'] == $titulaire['id']) ? 'selected' : '' ?>>
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
                                                <option value="titulaire" <?= ($tit1 && $tit1['role'] == 'titulaire') ? 'selected' : '' ?>>Titulaire</option>
                                                <option value="co-titulaire" <?= ($tit1 && $tit1['role'] == 'co-titulaire') ? 'selected' : '' ?>>Co-titulaire</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Second titulaire (optionnel pour compte joint) -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="add_second_titulaire" <?= $tit2 ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="add_second_titulaire">
                                            <i class="bi bi-people-fill"></i> Compte joint (ajouter un 2ème titulaire)
                                        </label>
                                    </div>

                                    <div id="second_titulaire_section" style="display: <?= $tit2 ? 'block' : 'none' ?>;">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label for="titulaire_2_id" class="form-label">Second titulaire</label>
                                                <select class="form-select" id="titulaire_2_id" name="titulaire_2_id">
                                                    <option value="">Sélectionnez un titulaire</option>
                                                    <?php foreach ($titulaires as $titulaire): ?>
                                                        <option value="<?= $titulaire['id'] ?>" <?= ($tit2 && $tit2['id'] == $titulaire['id']) ? 'selected' : '' ?>>
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
                                                    <option value="titulaire" <?= ($tit2 && $tit2['role'] == 'titulaire') ? 'selected' : '' ?>>Titulaire</option>
                                                    <option value="co-titulaire" <?= ($tit2 && $tit2['role'] == 'co-titulaire') ? 'selected' : '' ?>>Co-titulaire</option>
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
                                    $compte['type_compte'],
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
                                    $compte['devise'],
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
                                               value="<?= htmlspecialchars($compte['code_guichet'] ?? '') ?>"
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
                                               value="<?= htmlspecialchars($compte['numero_compte'] ?? '') ?>"
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
                                               value="<?= htmlspecialchars($compte['cle_rib'] ?? '') ?>"
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
                                           value="<?= htmlspecialchars($compte['iban'] ?? '') ?>"
                                           maxlength="34">
                                    <div class="form-text">Format: FR + 25 chiffres (généré automatiquement ou saisie manuelle)</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Solde initial -->
                            <div class="col-md-6 mb-3">
                                <label for="solde_initial" class="form-label">Solde initial</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="solde_initial" 
                                           name="solde_initial" 
                                           value="<?= $compte['solde_initial'] ?>"
                                           step="0.01">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>

                            <!-- Solde actuel -->
                            <div class="col-md-6 mb-3">
                                <label for="solde_actuel" class="form-label">Solde actuel</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="solde_actuel" 
                                           name="solde_actuel" 
                                           value="<?= $compte['solde_actuel'] ?>"
                                           step="0.01">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                    Attention : modifiez uniquement en cas de correction manuelle
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= htmlspecialchars($compte['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Compte actif -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="actif" 
                                       name="actif" 
                                       value="1"
                                       <?= $compte['actif'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="actif">
                                    Compte actif
                                </label>
                                <div class="form-text">Les comptes inactifs ne sont pas pris en compte dans les calculs</div>
                            </div>
                        </div>

                        <!-- Métadonnées -->
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i>
                            <strong>Créé le :</strong> <?= date('d/m/Y à H:i', strtotime($compte['created_at'])) ?>
                            <br>
                            <strong>Dernière modification :</strong> <?= date('d/m/Y à H:i', strtotime($compte['updated_at'])) ?>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <?= linkButton('Retour', url('comptes'), 'btn-secondary', 'bi-arrow-left') ?>
                            <?= submitButton('Enregistrer les modifications', 'btn-primary', 'bi-save') ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Informations -->
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h6>
                </div>
                <div class="card-body">
                    <h6>Banque associée</h6>
                    <p class="small">
                        <i class="bi bi-bank2"></i>
                        <strong><?= htmlspecialchars($compte['banque_nom']) ?></strong>
                    </p>
                    
                    <h6 class="mt-3">Avertissement</h6>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        Le solde actuel est normalement calculé automatiquement en fonction des transactions.
                        Ne modifiez cette valeur que pour corriger une erreur.
                    </div>
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
