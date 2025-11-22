<?php
/**
 * Page Paramètres de Notifications
 * Phase 3: Budget Alerts - Configuration interface
 */

require_once __DIR__ . '/../includes/header.php';

// Récupérer les paramètres actuels de l'utilisateur
$userId = $_SESSION['user_id'];
$settings = getUserNotificationSettings($userId);

// Valeurs par défaut si pas de paramètres
$defaults = [
    'budget_alert_enabled' => 1,
    'budget_threshold_80' => 1,
    'budget_threshold_90' => 1,
    'budget_exceeded' => 1,
    'weekly_summary' => 0,
    'monthly_summary' => 1,
    'notify_email' => 1,
    'notify_web' => 1,
    'max_emails_per_day' => 5
];

$settings = array_merge($defaults, $settings ?? []);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="sidebarMenu">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('profile') ?>">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= url('profile/notifications') ?>">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('profile/security') ?>">
                            <i class="bi bi-shield"></i> Sécurité
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-bell"></i> Paramètres de notification
                </h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="<?= url('profile/notifications') ?>">
                <?= csrf_field() ?>

                <!-- Alertes budgétaires -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            Alertes budgétaires
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="budget_alert_enabled" name="budget_alert_enabled" value="1"
                                       <?= $settings['budget_alert_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="budget_alert_enabled">
                                    <strong>Activer les alertes budgétaires</strong>
                                </label>
                            </div>
                            <small class="text-muted">Recevoir des notifications quand vos budgets approchent ou dépassent leurs limites.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="budget_threshold_80" name="budget_threshold_80" value="1"
                                           <?= $settings['budget_threshold_80'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="budget_threshold_80">
                                        Alerte à 80% du budget
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="budget_threshold_90" name="budget_threshold_90" value="1"
                                           <?= $settings['budget_threshold_90'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="budget_threshold_90">
                                        Alerte à 90% du budget
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="budget_exceeded" name="budget_exceeded" value="1"
                                           <?= $settings['budget_exceeded'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="budget_exceeded">
                                        Alerte en cas de dépassement
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Récapitulatifs -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart text-info"></i>
                            Récapitulatifs
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="weekly_summary" name="weekly_summary" value="1"
                                           <?= $settings['weekly_summary'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="weekly_summary">
                                        Résumé hebdomadaire
                                    </label>
                                </div>
                                <small class="text-muted">Recevoir un résumé de vos dépenses chaque semaine.</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="monthly_summary" name="monthly_summary" value="1"
                                           <?= $settings['monthly_summary'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="monthly_summary">
                                        Résumé mensuel
                                    </label>
                                </div>
                                <small class="text-muted">Recevoir un résumé de vos dépenses chaque mois.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Méthodes de notification -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-send text-primary"></i>
                            Méthodes de notification
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="notify_email" name="notify_email" value="1"
                                           <?= $settings['notify_email'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notify_email">
                                        <i class="bi bi-envelope"></i> Par email
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="notify_web" name="notify_web" value="1"
                                           <?= $settings['notify_web'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notify_web">
                                        <i class="bi bi-app"></i> Dans l'application
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limites -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear text-secondary"></i>
                            Limites
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="max_emails_per_day" class="form-label">
                                    Maximum d'emails par jour
                                </label>
                                <input type="number" class="form-control" id="max_emails_per_day"
                                       name="max_emails_per_day" min="1" max="20"
                                       value="<?= $settings['max_emails_per_day'] ?>">
                                <small class="text-muted">Limite anti-spam pour éviter la surcharge.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Enregistrer
                    </button>
                    <a href="<?= url('profile') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour au profil
                    </a>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Activer/désactiver les options budgétaires selon le switch principal
document.getElementById('budget_alert_enabled').addEventListener('change', function() {
    const budgetOptions = ['budget_threshold_80', 'budget_threshold_90', 'budget_exceeded'];
    budgetOptions.forEach(id => {
        document.getElementById(id).disabled = !this.checked;
    });
});

// État initial
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('budget_alert_enabled').dispatchEvent(new Event('change'));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>