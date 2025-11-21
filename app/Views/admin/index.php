<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <!-- En-tête Administration avec badge demandes admin -->
    <?php
    // Compter les demandes d'aide admin non traitées
    try {
        $pdo = MonBudget\Core\Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_password_requests WHERE status = 'pending' OR status IS NULL OR status = ''");
        $pendingAdminRequests = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        $pendingAdminRequests = 0;
    }
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-shield-lock text-danger"></i> 
                <?= $_SESSION['user']['username'] === 'UserFirst' ? 'Administration Super-Admin' : 'Administration' ?>
                <?php if ($pendingAdminRequests > 0): ?>
                    <span class="badge bg-danger ms-2" title="Demandes d'aide en attente">
                        <a href="<?= url('admin/users/reset-passwords') ?>" class="text-white text-decoration-none">
                            <i class="bi bi-exclamation-circle"></i> <?= $pendingAdminRequests ?> demande<?= $pendingAdminRequests > 1 ? 's' : '' ?> d'aide
                        </a>
                        <br>
                        <a href="<?= url('admin/admin-requests') ?>" class="text-white-50 text-decoration-underline small ms-1">Voir toutes les demandes</a>
                    </span>
                <?php endif; ?>
            </h2>
            <p class="text-muted mb-0">
                <i class="bi bi-person-badge"></i> Connecté en tant que : <strong><?= htmlspecialchars($user['username']) ?></strong>
            </p>
        </div>
    </div>

    <!-- Statistiques système -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="bi bi-graph-up"></i> Statistiques système</h5>
        </div>
        
        <!-- Utilisateurs -->
        <div class="col-lg-3 col-md-6 mb-3">
            <?= statsCard('UTILISATEURS', $stats['users_total'] ?? 0, 'bi-people', 'primary', 'Comptes actifs') ?>
        </div>
        
        <!-- Comptes bancaires -->
        <div class="col-lg-3 col-md-6 mb-3">
            <?= statsCard('COMPTES BANCAIRES', $stats['comptes_total'] ?? 0, 'bi-bank', 'info', 'Total') ?>
        </div>
        
        <!-- Transactions -->
        <div class="col-lg-3 col-md-6 mb-3">
            <?= statsCard(
                'TRANSACTIONS',
                number_format($stats['transactions_total'] ?? 0, 0, ',', ' '),
                'bi-arrow-down-up',
                'success',
                'dont ' . number_format($stats['transactions_importees'] ?? 0, 0, ',', ' ') . ' importées'
            ) ?>
        </div>
        
        <!-- Base de données -->
        <div class="col-lg-3 col-md-6 mb-3">
            <?= statsCard(
                'BASE DE DONNÉES',
                ($stats['db_size_mb'] ?? 0) . ' Mo',
                'bi-database',
                'warning',
                'Taille totale'
            ) ?>
        </div>
    </div>

    <!-- Statistiques détaillées -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Statistiques détaillées</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><i class="bi bi-folder"></i> Catégories</td>
                                <td class="text-end"><strong><?= $stats['categories_total'] ?? 0 ?></strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-person-circle"></i> Tiers</td>
                                <td class="text-end"><strong><?= $stats['tiers_total'] ?? 0 ?></strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-arrow-clockwise"></i> Règles d'automatisation</td>
                                <td class="text-end"><strong><?= $stats['regles_total'] ?? 0 ?></strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-piggy-bank"></i> Budgets</td>
                                <td class="text-end"><strong><?= $stats['budgets_total'] ?? 0 ?></strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-download"></i> Imports réalisés</td>
                                <td class="text-end"><strong><?= $stats['imports_total'] ?? 0 ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informations système</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><i class="bi bi-code-slash"></i> Version PHP</td>
                                <td class="text-end"><strong><?= $stats['php_version'] ?? 'N/A' ?></strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-server"></i> Version MySQL</td>
                                <td class="text-end"><strong><?= $stats['mysql_version'] ?? 'N/A' ?></strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-hdd"></i> Espace disque BDD</td>
                                <td class="text-end"><strong><?= $stats['db_size_mb'] ?? 0 ?> Mo</strong></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-calendar3"></i> Date</td>
                                <td class="text-end"><strong><?= date('d/m/Y H:i') ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Documentation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-book"></i> Documentation technique</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Documentation API générée automatiquement depuis les commentaires PHPDoc du code source.
                        <br>
                        <small class="text-muted">
                            Inclut : Classes, méthodes, propriétés, paramètres, types de retour et exemples d'utilisation.
                        </small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <a href="<?= url('.phpdoc/output/index.html') ?>" class="btn btn-primary w-100 text-start" target="_blank">
                                <i class="bi bi-file-earmark-code"></i> Consulter la documentation API
                                <i class="bi bi-box-arrow-up-right float-end"></i>
                            </a>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-check-circle text-success"></i> 13 modèles documentés
                                • <i class="bi bi-check-circle text-success"></i> Navigation par namespace
                                • <i class="bi bi-check-circle text-success"></i> Index des classes et méthodes
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestion des utilisateurs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Gestion des utilisateurs</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Gérez les comptes utilisateurs, les rôles et les permissions.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="<?= url('admin/users') ?>" class="btn btn-outline-primary w-100 text-start">
                                <i class="bi bi-person-lines-fill"></i> Liste des utilisateurs
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= url('admin/users/create') ?>" class="btn btn-outline-success w-100 text-start">
                                <i class="bi bi-person-plus"></i> Ajouter un utilisateur
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= url('admin/users/roles') ?>" class="btn btn-outline-info w-100 text-start">
                                <i class="bi bi-shield-check"></i> Gérer les rôles
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= url('admin/locked-users') ?>" class="btn btn-outline-danger w-100 text-start">
                                <i class="bi bi-shield-lock-fill"></i> Comptes verrouillés
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sécurité PCI DSS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-shield-fill-exclamation"></i> Sécurité & Conformité PCI DSS</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Opérations critiques</strong> - Ces actions affectent la sécurité des comptes utilisateurs.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="<?= url('admin/users/reset-passwords') ?>" class="btn btn-outline-warning w-100 text-start">
                                <i class="bi bi-key-fill"></i> Réinitialiser des mots de passe
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?= url('admin/locked-users') ?>" class="btn btn-outline-danger w-100 text-start">
                                <i class="bi bi-unlock-fill"></i> Déverrouiller des comptes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personnalisation de l'interface -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-palette"></i> Personnalisation de l'interface</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Personnalisez l'apparence et les éléments visuels de l'application.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="<?= url('admin/icons') ?>" class="btn btn-outline-primary w-100 text-start">
                                <i class="bi bi-grid-3x3-gap"></i> Gestion des icônes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance de la base de données -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-database"></i> Maintenance de la base de données</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Attention :</strong> Ces opérations peuvent affecter les performances de l'application.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <button type="button" class="btn btn-outline-warning w-100 text-start" onclick="confirmMaintenance('recalcul-soldes')">
                                <i class="bi bi-calculator"></i> Recalculer les soldes
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <button type="button" class="btn btn-outline-warning w-100 text-start" onclick="confirmMaintenance('clean-logs')">
                                <i class="bi bi-file-earmark-text"></i> Nettoyer les logs
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <button type="button" class="btn btn-outline-warning w-100 text-start" onclick="confirmMaintenance('clean-sessions')">
                                <i class="bi bi-hourglass-split"></i> Nettoyer les sessions
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <button type="button" class="btn btn-outline-danger w-100 text-start" onclick="confirmMaintenance('optimize-db')">
                                <i class="bi bi-gear"></i> Optimiser les tables
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <button type="button" class="btn btn-outline-success w-100 text-start" onclick="confirmMaintenance('apply-indexes')">
                                <i class="bi bi-lightning-charge"></i> Appliquer index performance
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Récupération et sécurité -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-shield-exclamation"></i> Récupération et sécurité</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Sauvegardez régulièrement vos données pour éviter toute perte.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="<?= url('admin/backup') ?>" class="btn btn-outline-primary w-100 text-start">
                                <i class="bi bi-cloud-download"></i> Sauvegarder la BDD
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= url('admin/restore') ?>" class="btn btn-outline-info w-100 text-start">
                                <i class="bi bi-cloud-upload"></i> Restaurer une sauvegarde
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= url('admin/users/reset-passwords') ?>" class="btn btn-outline-warning w-100 text-start">
                                <i class="bi bi-key"></i> Réinitialiser les mots de passe
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($_SESSION['user']['username'] === 'UserFirst'): ?>
    <!-- ZONE DANGER - Réinitialisation complète (UserFirst uniquement) -->
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i> ZONE DANGER - Réinitialisation complète
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger mb-3">
                        <h6 class="alert-heading">
                            <i class="bi bi-exclamation-octagon"></i> ATTENTION : Action irréversible !
                        </h6>
                        <p class="mb-0">
                            Cette opération va <strong>SUPPRIMER TOUTES LES DONNÉES</strong> de la base de données :
                        </p>
                        <ul class="mb-0 mt-2">
                            <li>Tous les utilisateurs (sauf UserFirst)</li>
                            <li>Tous les comptes bancaires</li>
                            <li>Toutes les transactions</li>
                            <li>Tous les imports</li>
                            <li>Toutes les catégories, tiers, règles</li>
                            <li>Tous les budgets</li>
                            <li>Tous les logs et sessions</li>
                        </ul>
                        <p class="mt-2 mb-0">
                            <strong>Seul le compte super-admin "UserFirst" sera préservé.</strong>
                        </p>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#confirmResetModal">
                            <i class="bi bi-trash3"></i> Réinitialiser la base de données
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation RAZ -->
    <div class="modal fade" id="confirmResetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill"></i> Confirmation requise
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">⚠️ Dernière chance !</h6>
                        <p class="mb-0">
                            Vous êtes sur le point de <strong>SUPPRIMER TOUTES LES DONNÉES</strong> de l'application.
                            Cette action est <strong>IRRÉVERSIBLE</strong>.
                        </p>
                    </div>

                    <p class="mb-3">
                        Pour confirmer cette opération, veuillez taper le code suivant :
                    </p>

                    <form method="POST" action="<?= url('admin/reset-database') ?>" id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Code de confirmation :</label>
                            <div class="text-center mb-2">
                                <code class="fs-5 bg-dark text-white p-2 rounded">RESET-ALL-DATA</code>
                            </div>
                            <input 
                                type="text" 
                                class="form-control form-control-lg text-center" 
                                id="confirmCode" 
                                name="confirm_code"
                                placeholder="Tapez le code ici"
                                autocomplete="off"
                                required
                            >
                            <small class="text-muted">Le code doit être saisi exactement comme affiché ci-dessus</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg" id="submitResetBtn" disabled>
                                <i class="bi bi-trash3"></i> Confirmer la réinitialisation
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Confirmation pour les actions de maintenance
function confirmMaintenance(action) {
    let message = '';
    let url = '';
    
    switch(action) {
        case 'recalcul-soldes':
            message = 'Recalculer tous les soldes des comptes ?\nCette opération peut prendre quelques minutes.';
            url = '<?= url('admin/maintenance/recalcul-soldes') ?>';
            break;
        case 'clean-logs':
            message = 'Supprimer tous les logs de plus de 90 jours ?\nCette action est irréversible.';
            url = '<?= url('admin/maintenance/clean-logs') ?>';
            break;
        case 'clean-sessions':
            message = 'Nettoyer toutes les sessions expirées ?\nLes utilisateurs inactifs devront se reconnecter.';
            url = '<?= url('admin/maintenance/clean-sessions') ?>';
            break;
        case 'optimize-db':
            message = 'Optimiser toutes les tables de la base de données ?\nL\'application sera brièvement indisponible.';
            url = '<?= url('admin/maintenance/optimize-db') ?>';
            break;
        case 'apply-indexes':
            message = 'Appliquer les index de performance ?\nCette opération améliore les performances des requêtes.\nTemps estimé: 30-60 secondes.';
            url = '<?= url('admin/maintenance/apply-indexes') ?>';
            break;
    }
    
    if (confirm(message)) {
        // Créer un formulaire POST dynamique
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        
        // Ajouter le token CSRF
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_csrf_token';
        csrfInput.value = '<?= $_SESSION['_csrf_token'] ?? '' ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

<?php if ($_SESSION['user']['username'] === 'UserFirst'): ?>
// Vérification du code de confirmation pour RAZ
document.getElementById('confirmCode')?.addEventListener('input', function(e) {
    const submitBtn = document.getElementById('submitResetBtn');
    const code = e.target.value.trim();
    
    if (code === 'RESET-ALL-DATA') {
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn-secondary');
        submitBtn.classList.add('btn-danger');
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.remove('btn-danger');
        submitBtn.classList.add('btn-secondary');
    }
});

// Confirmation finale avant soumission
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const code = document.getElementById('confirmCode').value.trim();
    
    if (code !== 'RESET-ALL-DATA') {
        e.preventDefault();
        alert('Code de confirmation incorrect !');
        return false;
    }
    
    if (!confirm('DERNIÈRE CONFIRMATION : Êtes-vous ABSOLUMENT SÛR de vouloir supprimer toutes les données ?')) {
        e.preventDefault();
        return false;
    }
    
    return true;
});

// Réinitialiser le formulaire à la fermeture du modal
document.getElementById('confirmResetModal')?.addEventListener('hidden.bs.modal', function() {
    document.getElementById('resetForm').reset();
    document.getElementById('submitResetBtn').disabled = true;
    document.getElementById('submitResetBtn').classList.remove('btn-danger');
    document.getElementById('submitResetBtn').classList.add('btn-secondary');
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
