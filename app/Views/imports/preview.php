<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-eye"></i> Aperçu de l'import</h1>
            <p class="text-muted mb-0">
                <?= $total_rows ?> lignes à importer dans <?= htmlspecialchars($compte['nom']) ?>
            </p>
        </div>
        <a href="<?= url('imports/cancel') ?>" class="btn btn-secondary">
            <i class="bi bi-x-lg"></i> Annuler
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Configuration des colonnes</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= url('imports/process') ?>">
                <?= csrf_field() ?>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Colonne Date <span class="text-danger">*</span></label>
                        <select class="form-select" name="col_date" required>
                            <?php foreach ($headers as $index => $header): ?>
                                <option value="<?= $index ?>" <?= stripos($header ?? '', 'date') !== false ? 'selected' : '' ?>>
                                    Col <?= $index + 1 ?>: <?= htmlspecialchars($header ?? 'Colonne ' . ($index + 1)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">Colonne Libellé <span class="text-danger">*</span></label>
                        <select class="form-select" name="col_libelle" required>
                            <?php foreach ($headers as $index => $header): ?>
                                <option value="<?= $index ?>" <?= stripos($header ?? '', 'libelle') !== false || stripos($header ?? '', 'description') !== false ? 'selected' : '' ?>>
                                    Col <?= $index + 1 ?>: <?= htmlspecialchars($header ?? 'Colonne ' . ($index + 1)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Colonne Débit</label>
                        <select class="form-select" name="col_debit" id="col_debit">
                            <option value="">-- Non utilisé --</option>
                            <?php foreach ($headers as $index => $header): ?>
                                <option value="<?= $index ?>" <?= stripos($header ?? '', 'debit') !== false || stripos($header ?? '', 'débit') !== false ? 'selected' : '' ?>>
                                    Col <?= $index + 1 ?>: <?= htmlspecialchars($header ?? 'Colonne ' . ($index + 1)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Montants négatifs (sorties)</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Colonne Crédit</label>
                        <select class="form-select" name="col_credit" id="col_credit">
                            <option value="">-- Non utilisé --</option>
                            <?php foreach ($headers as $index => $header): ?>
                                <option value="<?= $index ?>" <?= stripos($header ?? '', 'credit') !== false || stripos($header ?? '', 'crédit') !== false ? 'selected' : '' ?>>
                                    Col <?= $index + 1 ?>: <?= htmlspecialchars($header ?? 'Colonne ' . ($index + 1)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Montants positifs (entrées)</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">OU Colonne Montant unique</label>
                        <select class="form-select" name="col_montant" id="col_montant">
                            <option value="">-- Non utilisé --</option>
                            <?php foreach ($headers as $index => $header): ?>
                                <option value="<?= $index ?>" <?= stripos($header ?? '', 'montant') !== false && stripos($header ?? '', 'debit') === false && stripos($header ?? '', 'credit') === false ? 'selected' : '' ?>>
                                    Col <?= $index + 1 ?>: <?= htmlspecialchars($header ?? 'Colonne ' . ($index + 1)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Si montant signé (+/-)</small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg"></i> Importer <?= $total_rows ?> transaction<?= $total_rows > 1 ? 's' : '' ?>
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Aperçu des données (10 premières lignes)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <?php foreach ($headers as $header): ?>
                                <th><?= htmlspecialchars($header ?? '') ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview_rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= htmlspecialchars($cell ?? '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle"></i> <strong>Note :</strong> 
        Vous devez choisir soit les colonnes <strong>Débit ET Crédit</strong> (pour les fichiers avec montants séparés),
        soit une colonne <strong>Montant unique</strong> (pour les fichiers avec montants signés).
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Confirmer et importer
        </button>
        <a href="index.php?page=imports&action=cancel" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Annuler
        </a>
    </div>
</form>
</div>

<script>
document.getElementById('form-preview').addEventListener('submit', function(e) {
    const debit = document.getElementById('col_debit').value;
    const credit = document.getElementById('col_credit').value;
    const montant = document.getElementById('col_montant').value;
    
    // Vérifier qu'au moins une option est sélectionnée
    if (!montant && (!debit || !credit)) {
        e.preventDefault();
        alert('Vous devez soit sélectionner les colonnes Débit ET Crédit, soit une colonne Montant unique.');
        return false;
    }
    
    // Avertir si les deux options sont remplies
    if (montant && (debit || credit)) {
        if (!confirm('Vous avez sélectionné à la fois un montant unique ET débit/crédit. Le montant unique sera ignoré. Continuer ?')) {
            e.preventDefault();
            return false;
        }
    }
    
    return true;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
