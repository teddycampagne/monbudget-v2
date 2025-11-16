<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MonBudget' ?> - MonBudget v2.1</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Application de gestion de budget personnel et suivi des dépenses">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MonBudget">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= url('favicon.ico?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= url('favicon-16x16.png?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('favicon-32x32.png?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= url('favicon-48x48.png?v=2.1') ?>">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?= url('assets/icons/icon-192x192.png?v=2.1') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= url('assets/icons/icon-512x512.png?v=2.1') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('apple-touch-icon.png?v=2.1') ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <!-- Dark Mode CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/dark-mode.css') ?>?v=4">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= url('dashboard') ?>">
                <i class="bi bi-piggy-bank-fill"></i> MonBudget
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('dashboard') ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('banques') ?>">
                            <i class="bi bi-bank"></i> Banques
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('titulaires') ?>">
                            <i class="bi bi-people"></i> Titulaires
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('comptes') ?>">
                            <i class="bi bi-wallet2"></i> Comptes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('categories') ?>">
                            <i class="bi bi-tags"></i> Catégories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('tiers') ?>">
                            <i class="bi bi-people"></i> Tiers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('automatisation') ?>">
                            <i class="bi bi-magic"></i> Automatisation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('imports') ?>">
                            <i class="bi bi-upload"></i> Imports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('budgets') ?>">
                            <i class="bi bi-cash-stack"></i> Budgets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('projections') ?>">
                            <i class="bi bi-graph-up-arrow"></i> Projections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('rapports') ?>">
                            <i class="bi bi-graph-up"></i> Rapports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('recherche') ?>">
                            <i class="bi bi-search"></i> Recherche
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Toggle Dark Mode -->
                    <li class="nav-item d-flex align-items-center me-3">
                        <label class="theme-toggle">
                            <input type="checkbox" id="themeToggle">
                            <span class="theme-toggle-slider"></span>
                        </label>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('documentation') ?>">
                            <i class="bi bi-question-circle"></i> Aide
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Utilisateur') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= url('profile') ?>"><i class="bi bi-person"></i> Profil</a></li>
                            <?php if ((isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') || 
                                      (isset($_SESSION['user']['username']) && $_SESSION['user']['username'] === 'UserFirst')): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= url('admin') ?>">
                                        <i class="bi bi-shield-lock"></i> Administration
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Messages flash -->
    <?php if ($successMsg = flash('success')): ?>
        <div class="container-fluid px-4 pt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg = flash('error')): ?>
        <div class="container-fluid px-4 pt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($warningMsg = flash('warning')): ?>
        <div class="container-fluid px-4 pt-3">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($warningMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($infoMsg = flash('info')): ?>
        <div class="container-fluid px-4 pt-3">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle"></i> <?= htmlspecialchars($infoMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal de confirmation personnalisé -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> MonBudget - Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    <!-- Message injecté dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmModalOk">
                        <i class="bi bi-check-lg"></i> Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <main>
