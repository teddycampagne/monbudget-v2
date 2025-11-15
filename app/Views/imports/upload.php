<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-upload"></i> Importer des transactions</h1>
        <a href="<?= url('imports') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sélectionner le fichier CSV</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('imports/preview') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="compte_id" class="form-label">
                                Compte <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="compte_id" name="compte_id" required>
                                <option value="">Sélectionnez un compte</option>
                                <?php foreach ($comptes as $compte): ?>
                                    <option value="<?= $compte['id'] ?>">
                                        <?= htmlspecialchars($compte['nom']) ?> - <?= htmlspecialchars($compte['banque_nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="fichier" class="form-label">
                                Fichier CSV <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="fichier" 
                                   name="fichier" 
                                   accept=".csv,.ofx,.qfx"
                                   required>
                            <small class="text-muted">Formats acceptés : CSV, OFX, QFX</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Prévisualiser
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instructions</h5>
                </div>
                <div class="card-body">
                    <h6>Formats supportés</h6>
                    <ul class="small">
                        <li><strong>OFX/QFX</strong> : Format standard des banques (recommandé)</li>
                        <li><strong>CSV</strong> : Fichier texte avec délimiteurs (; , | tab)</li>
                    </ul>
                    
                    <h6 class="mt-3">Format CSV</h6>
                    <p class="small">
                        Colonnes requises :
                    </p>
                    <ul class="small">
                        <li><strong>Date</strong> : DD/MM/YYYY ou YYYY-MM-DD</li>
                        <li><strong>Libellé</strong> : Description de la transaction</li>
                        <li><strong>Montant</strong> : Positif/négatif OU colonnes Débit/Crédit séparées</li>
                    </ul>
                    
                    <h6 class="mt-3">Automatisation</h6>
                    <p class="small">
                        Les règles d'automatisation seront appliquées automatiquement pour 
                        remplir catégories, tiers et moyens de paiement.
                    </p>
                    
                    <h6 class="mt-3">Doublons</h6>
                    <p class="small">
                        Les transactions déjà existantes (même date, montant et libellé) 
                        seront ignorées.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
