<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-magic"></i> Règles d'automatisation</h1>
            <p class="text-muted mb-0">Automatisez la catégorisation, le tiers et le moyen de paiement</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('automatisation/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouvelle règle
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                <i class="bi bi-play-circle"></i> Tester
            </button>
            <form method="POST" action="<?= url('automatisation/apply-all') ?>" class="d-inline" onsubmit="return confirm('Appliquer toutes les règles actives sur les transactions existantes ?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-arrow-clockwise"></i> Appliquer à tout
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($regles)): ?>
        <!-- Aucune règle -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-magic" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Aucune règle d'automatisation</h4>
                <p class="text-muted">Créez des règles pour automatiser la catégorisation de vos transactions</p>
                <a href="<?= url('automatisation/create') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Créer une règle
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste des règles -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= count($regles) ?> règle(s)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th>Nom</th>
                            <th>Pattern</th>
                            <th>Actions</th>
                            <th>Priorité</th>
                            <th>Applications</th>
                            <th style="width: 100px;">État</th>
                            <th style="width: 150px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regles as $regle): ?>
                            <tr class="<?= $regle['actif'] ? '' : 'table-secondary' ?>">
                                <td>
                                    <span class="badge bg-secondary"><?= $regle['priorite'] ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($regle['nom']) ?></strong>
                                    <?php if ($regle['derniere_application']): ?>
                                        <br>
                                        <small class="text-muted">
                                            Dernière application : <?= date('d/m/Y H:i', strtotime($regle['derniere_application'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($regle['pattern']) ?></code>
                                    <br>
                                    <small class="text-muted">
                                        <?php
                                        $types = [
                                            'contient' => 'Contient',
                                            'commence_par' => 'Commence par',
                                            'termine_par' => 'Termine par',
                                            'regex' => 'Expression régulière'
                                        ];
                                        echo $types[$regle['type_pattern']] ?? $regle['type_pattern'];
                                        ?>
                                        <?php if ($regle['case_sensitive']): ?>
                                            <span class="badge bg-warning text-dark ms-1">Aa</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($regle['action_categorie']): ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-tag"></i> Catégorie
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($regle['action_sous_categorie']): ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-tag-fill"></i> Sous-catégorie
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($regle['action_tiers']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-person"></i> Tiers
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($regle['action_moyen_paiement']): ?>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-credit-card"></i> Paiement
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $regle['priorite'] <= 10 ? 'bg-danger' : ($regle['priorite'] <= 50 ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                        <?= $regle['priorite'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= $regle['nb_applications'] ?> fois
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               <?= $regle['actif'] ? 'checked' : '' ?>
                                               onchange="toggleRule(<?= $regle['id'] ?>, this)">
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url("automatisation/{$regle['id']}/edit") ?>" 
                                           class="btn btn-outline-primary"
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" 
                                              action="<?= url("automatisation/{$regle['id']}/delete") ?>" 
                                              class="d-inline"
                                              onsubmit="return confirm('Supprimer cette règle ?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" 
                                                    class="btn btn-outline-danger"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info -->
        <div class="alert alert-info mt-4">
            <i class="bi bi-info-circle"></i>
            <strong>Comment ça fonctionne ?</strong>
            <ul class="mb-0 mt-2">
                <li>Les règles sont appliquées <strong>par ordre de priorité</strong> (les plus petits chiffres en premier)</li>
                <li>Une fois qu'un champ est rempli par une règle, les règles suivantes ne peuvent plus le modifier</li>
                <li>Les règles ne s'appliquent qu'aux champs <strong>non encore renseignés</strong></li>
                <li>Utilisez "Appliquer à tout" pour appliquer les règles à toutes les transactions existantes</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de test -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-play-circle"></i> Tester les règles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testForm">
                    <div class="mb-3">
                        <label for="test_libelle" class="form-label">Libellé de transaction</label>
                        <input type="text" 
                               class="form-control" 
                               id="test_libelle" 
                               placeholder="Ex: PAIEMENT PAR CARTE X6984 Picnic Paris 02/10"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-play"></i> Tester
                    </button>
                </form>
                
                <div id="testResults" class="mt-4" style="display: none;">
                    <h6>Résultats :</h6>
                    <div id="testResultsContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle actif/inactif
function toggleRule(id, checkbox) {
    fetch('<?= url('automatisation') ?>/' + id + '/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            checkbox.checked = !checkbox.checked;
            alert('Erreur lors de la modification');
        }
    })
    .catch(error => {
        checkbox.checked = !checkbox.checked;
        alert('Erreur réseau');
    });
}

// Tester les règles
document.getElementById('testForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const libelle = document.getElementById('test_libelle').value;
    const formData = new FormData();
    formData.append('libelle', libelle);
    
    fetch('<?= url('automatisation/test') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        let html = '<div class="alert alert-success">';
        
        if (data.rules_applied && data.rules_applied.length > 0) {
            html += '<strong>Règles appliquées :</strong><ul>';
            data.rules_applied.forEach(rule => {
                html += '<li>' + rule + '</li>';
            });
            html += '</ul>';
        } else {
            html += '<p>Aucune règle ne correspond à ce libellé.</p>';
        }
        
        html += '<hr><strong>Résultat :</strong><ul>';
        if (data.categorie_id) html += '<li>Catégorie ID: ' + data.categorie_id + '</li>';
        if (data.sous_categorie_id) html += '<li>Sous-catégorie ID: ' + data.sous_categorie_id + '</li>';
        if (data.tiers_id) html += '<li>Tiers ID: ' + data.tiers_id + '</li>';
        if (data.moyen_paiement) html += '<li>Moyen de paiement: ' + data.moyen_paiement + '</li>';
        html += '</ul></div>';
        
        document.getElementById('testResultsContent').innerHTML = html;
        document.getElementById('testResults').style.display = 'block';
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
