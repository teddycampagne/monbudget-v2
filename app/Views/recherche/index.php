<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-search"></i> Recherche avancée</h1>
            </div>
        </div>
    </div>
    
    <!-- Formulaire de recherche -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Critères de recherche</h5>
        </div>
        <div class="card-body">
            <form id="formRecherche">
                <div class="row">
                    <!-- Compte -->
                    <div class="col-md-4 mb-3">
                        <label for="compte_id" class="form-label">Compte</label>
                        <select class="form-select" id="compte_id" name="compte_id">
                            <option value="">Tous les comptes</option>
                            <?php foreach ($comptes as $compte): ?>
                                <option value="<?= $compte['id'] ?>">
                                    <?= htmlspecialchars($compte['nom']) ?>
                                    <?php if ($compte['banque_nom']): ?>
                                        (<?= htmlspecialchars($compte['banque_nom']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Type d'opération -->
                    <div class="col-md-4 mb-3">
                        <label for="type_operation" class="form-label">Type d'opération</label>
                        <select class="form-select" id="type_operation" name="type_operation">
                            <option value="">Tous les types</option>
                            <option value="debit">Débits (Dépenses)</option>
                            <option value="credit">Crédits (Revenus)</option>
                        </select>
                    </div>
                    
                    <!-- Période de/à -->
                    <div class="col-md-4 mb-3">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" 
                               class="form-control" 
                               id="date_debut" 
                               name="date_debut"
                               data-shortcuts="today,month-start,year-start,month-ago">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" 
                               class="form-control" 
                               id="date_fin" 
                               name="date_fin"
                               data-shortcuts="today,month-end,year-end">
                    </div>
                    
                    <!-- Catégorie -->
                    <div class="col-md-4 mb-3">
                        <label for="categorie_id" class="form-label">Catégorie</label>
                        <select class="form-select" id="categorie_id" name="categorie_id">
                            <option value="">Toutes les catégories</option>
                            <option value="-1">⚠️ Non catégorisé</option>
                            <optgroup label="Dépenses">
                                <?php foreach ($categoriesDepenses as $cat): ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Revenus">
                                <?php foreach ($categoriesRevenus as $cat): ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <!-- Sous-catégorie (chargée dynamiquement) -->
                    <div class="col-md-4 mb-3">
                        <label for="sous_categorie_id" class="form-label">Sous-catégorie</label>
                        <select class="form-select" id="sous_categorie_id" name="sous_categorie_id" disabled>
                            <option value="">Sélectionnez d'abord une catégorie</option>
                        </select>
                    </div>
                    
                    <!-- Tiers -->
                    <div class="col-md-4 mb-3">
                        <label for="tiers_id" class="form-label">Tiers</label>
                        <select class="form-select" id="tiers_id" name="tiers_id">
                            <option value="">Tous les tiers</option>
                            <option value="-1">⚠️ Sans tiers</option>
                            <?php foreach ($tiers as $tier): ?>
                                <option value="<?= $tier['id'] ?>">
                                    <?= htmlspecialchars($tier['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Tags -->
                    <div class="col-md-4 mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <select class="form-select" id="tags" name="tags[]" multiple size="3">
                            <option value="">Tous les tags</option>
                            <?php if (!empty($tags)): ?>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?= $tag['id'] ?>" style="background-color: var(--bs-<?= $tag['color'] ?>);">
                                        🏷️ <?= htmlspecialchars($tag['name']) ?> (<?= $tag['usage_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Maintenez Ctrl/Cmd pour sélectionner plusieurs tags</small>
                    </div>
                    
                    <!-- Montant min/max -->
                    <div class="col-md-4 mb-3">
                        <label for="montant_min" class="form-label">Montant minimum (€)</label>
                        <input type="number" class="form-control" id="montant_min" name="montant_min" step="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="montant_max" class="form-label">Montant maximum (€)</label>
                        <input type="number" class="form-control" id="montant_max" name="montant_max" step="0.01" placeholder="9999.99">
                    </div>
                    
                    <!-- Libellé -->
                    <div class="col-md-4 mb-3">
                        <label for="libelle" class="form-label">Libellé (recherche)</label>
                        <input type="text" class="form-control" id="libelle" name="libelle" placeholder="Texte à rechercher...">
                    </div>
                    
                    <!-- Statut validation -->
                    <div class="col-md-4 mb-3">
                        <label for="est_valide" class="form-label">Validation</label>
                        <select class="form-select" id="est_valide" name="est_valide">
                            <option value="">Tous</option>
                            <option value="1">Validé</option>
                            <option value="0">Non validé</option>
                        </select>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="col-12">
                        <hr>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Rechercher
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnReset">
                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                        </button>
                        <button type="button" class="btn btn-success" id="btnExport" style="display: none;">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Exporter CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div id="statsContainer" style="display: none;">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Débits</h6>
                        <h3 id="statDebits">0.00 €</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Crédits</h6>
                        <h3 id="statCredits">0.00 €</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Balance</h6>
                        <h3 id="statBalance">0.00 €</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Transactions</h6>
                        <h3 id="statNb">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Résultats -->
    <div id="resultsContainer" style="display: none;">
        <div class="card">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Résultats (<span id="totalResults">0</span>)</h5>
                <div>
                    <select class="form-select form-select-sm d-inline-block w-auto" id="orderBy">
                        <option value="date_transaction">Trier par Date</option>
                        <option value="montant">Trier par Montant</option>
                        <option value="libelle">Trier par Libellé</option>
                        <option value="compte_nom">Trier par Compte</option>
                        <option value="categorie_nom">Trier par Catégorie</option>
                    </select>
                    <select class="form-select form-select-sm d-inline-block w-auto ms-2" id="orderDir">
                        <option value="DESC">↓ Décroissant</option>
                        <option value="ASC">↑ Croissant</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Compte</th>
                                <th>Type</th>
                                <th>Libellé</th>
                                <th>Catégorie</th>
                                <th>Tiers</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody">
                            <!-- Rempli dynamiquement -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <nav>
                    <ul class="pagination mb-0" id="pagination">
                        <!-- Rempli dynamiquement -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Message vide -->
    <div id="emptyMessage" class="text-center text-muted py-5">
        <i class="bi bi-search" style="font-size: 4rem;"></i>
        <p class="mt-3">Utilisez les filtres ci-dessus pour rechercher des transactions</p>
    </div>
</div>

<!-- Modal d'édition de transaction -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditTransaction">
                    <input type="hidden" id="edit_transaction_id">
                    <input type="hidden" id="edit_compte_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_date" class="form-label">Date *</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="edit_date" 
                                   data-shortcuts="today,yesterday,week-ago"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_type" class="form-label">Type *</label>
                            <select class="form-select" id="edit_type" required>
                                <option value="debit">Débit (Dépense)</option>
                                <option value="credit">Crédit (Revenu)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_libelle" class="form-label">Libellé *</label>
                        <input type="text" class="form-control" id="edit_libelle" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_montant" class="form-label">Montant (€) *</label>
                            <input type="number" class="form-control" id="edit_montant" step="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_moyen_paiement" class="form-label">Moyen de paiement</label>
                            <select class="form-select" id="edit_moyen_paiement">
                                <option value="">Autre</option>
                                <option value="carte">Carte bancaire</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement</option>
                                <option value="prelevement">Prélèvement</option>
                                <option value="especes">Espèces</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="edit_categorie">
                                <option value="">-- Sélectionner --</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_sous_categorie" class="form-label">Sous-catégorie</label>
                            <select class="form-select" id="edit_sous_categorie" disabled>
                                <option value="">-- Sélectionner d'abord une catégorie --</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_tiers" class="form-label">Tiers</label>
                        <select class="form-select" id="edit_tiers">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($tiers as $tier): ?>
                                <option value="<?= $tier['id'] ?>">
                                    <?= htmlspecialchars($tier['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="edit_valide">
                        <label class="form-check-label" for="edit_valide">
                            Transaction validée
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="sauvegarderTransaction()">
                    <i class="bi bi-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
    // Charger les sous-catégories quand une catégorie est sélectionnée
    document.getElementById('categorie_id').addEventListener('change', async function() {
        const categorieId = this.value;
        const sousCatSelect = document.getElementById('sous_categorie_id');
        
        sousCatSelect.innerHTML = '<option value="">Toutes les sous-catégories</option>';
        
        if (categorieId && categorieId !== '-1') {
            sousCatSelect.disabled = true;
            
            try {
                const response = await fetch(`<?= url('api/recherche/sous-categories') ?>?categorie_id=${categorieId}`);
                const sousCategories = await response.json();
                
                if (sousCategories.length > 0) {
                    sousCategories.forEach(sc => {
                        const option = document.createElement('option');
                        option.value = sc.id;
                        option.textContent = sc.nom;
                        sousCatSelect.appendChild(option);
                    });
                    sousCatSelect.disabled = false;
                } else {
                    sousCatSelect.innerHTML = '<option value="">Aucune sous-catégorie</option>';
                }
            } catch (error) {
                console.error('Erreur chargement sous-catégories:', error);
                sousCatSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            }
        } else {
            sousCatSelect.innerHTML = '<option value="">Sélectionnez d\'abord une catégorie</option>';
            sousCatSelect.disabled = true;
        }
    });
    
    // Soumission du formulaire
    document.getElementById('formRecherche').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        effectuerRecherche();
    });
    
    // Réinitialiser
    document.getElementById('btnReset').addEventListener('click', function() {
        document.getElementById('formRecherche').reset();
        document.getElementById('sous_categorie_id').disabled = true;
        document.getElementById('sous_categorie_id').innerHTML = '<option value="">Sélectionnez d\'abord une catégorie</option>';
        
        document.getElementById('statsContainer').style.display = 'none';
        document.getElementById('resultsContainer').style.display = 'none';
        document.getElementById('emptyMessage').style.display = 'block';
        document.getElementById('btnExport').style.display = 'none';
    });
    
    // Export CSV
    document.getElementById('btnExport').addEventListener('click', function() {
        const params = new URLSearchParams(currentFilters);
        window.location.href = `<?= url('api/recherche/export') ?>?${params.toString()}`;
    });
    
    // Changement de tri
    document.getElementById('orderBy').addEventListener('change', effectuerRecherche);
    document.getElementById('orderDir').addEventListener('change', effectuerRecherche);
});

async function effectuerRecherche() {
    const form = document.getElementById('formRecherche');
    const formData = new FormData(form);
    
    // Construire les paramètres
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    params.append('page', currentPage);
    params.append('order_by', document.getElementById('orderBy').value);
    params.append('order_dir', document.getElementById('orderDir').value);
    
    currentFilters = Object.fromEntries(params.entries());
    
    try {
        const response = await fetch(`<?= url('api/recherche') ?>?${params.toString()}`);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erreur serveur:', errorText);
            showErrorModal('Erreur de recherche', 'Une erreur est survenue lors de la recherche. Veuillez vérifier vos critères.');
            return;
        }
        
        const data = await response.json();
        
        afficherResultats(data);
        afficherStats(data.stats);
        afficherPagination(data);
        
        document.getElementById('statsContainer').style.display = 'block';
        document.getElementById('resultsContainer').style.display = 'block';
        document.getElementById('emptyMessage').style.display = 'none';
        document.getElementById('btnExport').style.display = 'inline-block';
        
    } catch (error) {
        console.error('Erreur recherche:', error);
        showErrorModal('Erreur de recherche', 'Impossible de communiquer avec le serveur.');
    }
}

function afficherResultats(data) {
    const tbody = document.getElementById('resultsBody');
    const totalSpan = document.getElementById('totalResults');
    
    tbody.innerHTML = '';
    totalSpan.textContent = data.total;
    
    // Réinitialiser le cache
    transactionsCache = {};
    
    if (data.transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Aucune transaction trouvée</td></tr>';
        return;
    }
    
    data.transactions.forEach(t => {
        // Mettre en cache
        transactionsCache[t.id] = t;
        
        const row = document.createElement('tr');
        
        const typeClass = t.type_operation === 'credit' ? 'success' : 'danger';
        const typeIcon = t.type_operation === 'credit' ? '↑' : '↓';
        
        row.innerHTML = `
            <td>${new Date(t.date_transaction).toLocaleDateString('fr-FR')}</td>
            <td>
                <small class="text-muted">${escapeHtml(t.banque_nom || '')}</small><br>
                ${escapeHtml(t.compte_nom)}
            </td>
            <td>
                <span class="badge bg-${typeClass}">${typeIcon} ${t.type_operation === 'credit' ? 'Crédit' : 'Débit'}</span>
            </td>
            <td>${escapeHtml(t.libelle)}</td>
            <td>
                ${t.categorie_nom ? `
                    <span class="badge" style="background-color: ${t.categorie_couleur || '#6c757d'}">
                        ${t.categorie_icone ? '<i class="bi bi-' + t.categorie_icone + '"></i>' : ''} 
                        ${escapeHtml(t.categorie_nom)}
                    </span>
                    ${t.sous_categorie_nom ? '<br><small class="text-muted">' + escapeHtml(t.sous_categorie_nom) + '</small>' : ''}
                ` : '<span class="text-muted">Non catégorisé</span>'}
            </td>
            <td>${t.tiers_nom ? escapeHtml(t.tiers_nom) : '<span class="text-muted">-</span>'}</td>
            <td class="text-end fw-bold text-${typeClass}">${formatMontant(t.montant)} €</td>
            <td class="text-center">
                ${t.est_valide ? '<i class="bi bi-check-circle-fill text-success" title="Validé"></i>' : '<i class="bi bi-circle text-muted" title="Non validé"></i>'}
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <a href="${escapeHtml(window.location.origin)}/comptes/${t.compte_id}/transactions/${t.id}/duplicate" 
                       class="btn btn-outline-secondary" 
                       title="Dupliquer">
                        <i class="bi bi-files"></i>
                    </a>
                    <button class="btn btn-outline-primary" onclick="editerTransaction(${t.id})" title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function afficherStats(stats) {
    document.getElementById('statDebits').textContent = formatMontant(stats.total_debits) + ' €';
    document.getElementById('statCredits').textContent = formatMontant(stats.total_credits) + ' €';
    document.getElementById('statBalance').textContent = formatMontant(stats.balance) + ' €';
    document.getElementById('statNb').textContent = stats.nb_transactions;
}

function afficherPagination(data) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    if (data.total_pages <= 1) return;
    
    // Bouton Précédent
    const prevLi = document.createElement('li');
    prevLi.className = 'page-item' + (data.page === 1 ? ' disabled' : '');
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${data.page - 1}">Précédent</a>`;
    pagination.appendChild(prevLi);
    
    // Pages
    const maxPages = 5;
    let startPage = Math.max(1, data.page - Math.floor(maxPages / 2));
    let endPage = Math.min(data.total_pages, startPage + maxPages - 1);
    
    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = 'page-item' + (i === data.page ? ' active' : '');
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        pagination.appendChild(li);
    }
    
    // Bouton Suivant
    const nextLi = document.createElement('li');
    nextLi.className = 'page-item' + (data.page === data.total_pages ? ' disabled' : '');
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${data.page + 1}">Suivant</a>`;
    pagination.appendChild(nextLi);
    
    // Event listeners
    pagination.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (!this.parentElement.classList.contains('disabled')) {
                currentPage = parseInt(this.dataset.page);
                effectuerRecherche();
            }
        });
    });
}

function formatMontant(montant) {
    return parseFloat(montant).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Gestion de l'édition de transaction
let editModal;
let transactionsCache = {};

document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
    
    // Charger les catégories quand le type change
    document.getElementById('edit_type').addEventListener('change', chargerCategoriesEdit);
    
    // Charger les sous-catégories quand une catégorie est sélectionnée
    document.getElementById('edit_categorie').addEventListener('change', async function() {
        const categorieId = this.value;
        const sousCatSelect = document.getElementById('edit_sous_categorie');
        
        sousCatSelect.innerHTML = '<option value="">Aucune</option>';
        
        if (categorieId) {
            try {
                const response = await fetch(`<?= url('api/recherche/sous-categories') ?>?categorie_id=${categorieId}`);
                const sousCategories = await response.json();
                
                if (sousCategories.length > 0) {
                    sousCategories.forEach(sc => {
                        const option = document.createElement('option');
                        option.value = sc.id;
                        option.textContent = sc.nom;
                        sousCatSelect.appendChild(option);
                    });
                    sousCatSelect.disabled = false;
                }
            } catch (error) {
                console.error('Erreur chargement sous-catégories:', error);
            }
        } else {
            sousCatSelect.disabled = true;
        }
    });
}, {once: true});

async function editerTransaction(transactionId) {
    // Chercher la transaction dans le cache
    const transaction = transactionsCache[transactionId];
    
    if (!transaction) {
        alert('Transaction introuvable');
        return;
    }
    
    // Remplir le formulaire
    document.getElementById('edit_transaction_id').value = transaction.id;
    document.getElementById('edit_compte_id').value = transaction.compte_id;
    document.getElementById('edit_date').value = transaction.date_transaction;
    document.getElementById('edit_type').value = transaction.type_operation;
    document.getElementById('edit_libelle').value = transaction.libelle;
    document.getElementById('edit_montant').value = transaction.montant;
    document.getElementById('edit_moyen_paiement').value = transaction.moyen_paiement || '';
    document.getElementById('edit_notes').value = transaction.notes || '';
    document.getElementById('edit_valide').checked = transaction.est_valide == 1;
    
    // Charger les catégories selon le type
    await chargerCategoriesEdit();
    
    // Sélectionner la catégorie
    if (transaction.categorie_id) {
        document.getElementById('edit_categorie').value = transaction.categorie_id;
        
        // Charger les sous-catégories
        const categorieSelect = document.getElementById('edit_categorie');
        categorieSelect.dispatchEvent(new Event('change'));
        
        // Attendre un peu que les sous-catégories se chargent
        setTimeout(() => {
            if (transaction.sous_categorie_id) {
                document.getElementById('edit_sous_categorie').value = transaction.sous_categorie_id;
            }
        }, 300);
    }
    
    // Sélectionner le tiers
    if (transaction.tiers_id) {
        document.getElementById('edit_tiers').value = transaction.tiers_id;
    }
    
    // Afficher le modal
    editModal.show();
}

async function chargerCategoriesEdit() {
    const type = document.getElementById('edit_type').value;
    const categorieSelect = document.getElementById('edit_categorie');
    
    categorieSelect.innerHTML = '<option value="">-- Sélectionner --</option>';
    
    const categoriesType = type === 'debit' ? <?= json_encode($categoriesDepenses) ?> : <?= json_encode($categoriesRevenus) ?>;
    
    categoriesType.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.nom;
        categorieSelect.appendChild(option);
    });
    
    // Réinitialiser les sous-catégories
    document.getElementById('edit_sous_categorie').innerHTML = '<option value="">-- Sélectionner d\'abord une catégorie --</option>';
    document.getElementById('edit_sous_categorie').disabled = true;
}

async function sauvegarderTransaction() {
    const transactionId = document.getElementById('edit_transaction_id').value;
    const compteId = document.getElementById('edit_compte_id').value;
    
    // Créer un FormData pour envoyer en POST classique
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('compte_id', compteId);
    formData.append('date_transaction', document.getElementById('edit_date').value);
    formData.append('type_operation', document.getElementById('edit_type').value);
    formData.append('libelle', document.getElementById('edit_libelle').value);
    formData.append('montant', document.getElementById('edit_montant').value);
    formData.append('moyen_paiement', document.getElementById('edit_moyen_paiement').value);
    formData.append('description', document.getElementById('edit_notes').value);
    formData.append('est_valide', document.getElementById('edit_valide').checked ? 1 : 0);
    
    const categorieId = document.getElementById('edit_categorie').value;
    const sousCategorieId = document.getElementById('edit_sous_categorie').value;
    const tiersId = document.getElementById('edit_tiers').value;
    
    if (categorieId) formData.append('categorie_id', categorieId);
    if (sousCategorieId) formData.append('sous_categorie_id', sousCategorieId);
    if (tiersId) formData.append('tiers_id', tiersId);
    
    // Champs requis pour la validation mais non utilisés dans ce contexte
    formData.append('est_recurrente', 0);
    
    try {
        const response = await fetch(`<?= url('comptes') ?>/${compteId}/transactions/${transactionId}/update`, {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        if (response.ok || response.redirected) {
            editModal.hide();
            // Recharger les résultats
            await effectuerRecherche();
            
            // Afficher un message de succès
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i> Transaction modifiée avec succès
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
        } else {
            console.error('Erreur serveur:', responseText);
            showErrorModal('Erreur de sauvegarde', 'Impossible de modifier la transaction.');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showErrorModal('Erreur de sauvegarde', 'Impossible de communiquer avec le serveur.');
    }
}

function showErrorModal(titre, message) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> ${titre}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    modal.addEventListener('hidden.bs.modal', () => modal.remove());
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>