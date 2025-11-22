<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i>
                        Paramètres des Notifications de Budget
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Configurez comment vous souhaitez être notifié des dépassements de budget et personnalisez les seuils d'alerte.
                    </p>

                    <form action="<?= url('budget-notifications/update-settings') ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notification_type">Type de notification :</label>
                                    <select name="notification_type" id="notification_type" class="form-control" required>
                                        <?php foreach ($notification_types as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $current_preference === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Choisissez comment recevoir les notifications de dépassement de budget.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">Seuils d'Alerte Personnalisés</h5>
                        <p class="text-muted">Définissez les pourcentages d'utilisation du budget qui déclenchent les différents niveaux d'alerte.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="warning_threshold">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                        Seuil d'avertissement (%)
                                    </label>
                                    <input type="number" name="warning_threshold" id="warning_threshold"
                                           class="form-control" min="0" max="99" step="1"
                                           value="<?= $current_thresholds['warning'] ?>" required>
                                    <small class="form-text text-muted">
                                        Pourcentage d'utilisation déclenchant un avertissement.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="alert_threshold">
                                        <i class="fas fa-exclamation-circle text-danger"></i>
                                        Seuil d'alerte (%)
                                    </label>
                                    <input type="number" name="alert_threshold" id="alert_threshold"
                                           class="form-control" min="1" max="99" step="1"
                                           value="<?= $current_thresholds['alert'] ?>" required>
                                    <small class="form-text text-muted">
                                        Pourcentage d'utilisation déclenchant une alerte.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="critical_threshold">
                                        <i class="fas fa-times-circle text-dark"></i>
                                        Seuil critique (%)
                                    </label>
                                    <input type="number" name="critical_threshold" id="critical_threshold"
                                           class="form-control" min="1" max="100" step="1"
                                           value="<?= $current_thresholds['critical'] ?>" required>
                                    <small class="form-text text-muted">
                                        Pourcentage d'utilisation déclenchant une alerte critique.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Enregistrer les préférences
                            </button>
                            <a href="<?= url('dashboard') ?>" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left"></i>
                                Retour au tableau de bord
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations sur les seuils -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Aperçu des Seuils d'Alerte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                    <h6 class="card-title text-warning">Avertissement (<?= $current_thresholds['warning'] ?>%)</h6>
                                    <p class="card-text">Notification quand le budget atteint <?= $current_thresholds['warning'] ?>% d'utilisation.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-circle fa-2x text-danger mb-3"></i>
                                    <h6 class="card-title text-danger">Alerte (<?= $current_thresholds['alert'] ?>%)</h6>
                                    <p class="card-text">Notification quand le budget atteint <?= $current_thresholds['alert'] ?>% d'utilisation.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-dark">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x text-dark mb-3"></i>
                                    <h6 class="card-title text-dark">Dépassement (<?= $current_thresholds['critical'] ?>%+)</h6>
                                    <p class="card-text">Notification urgente en cas de dépassement du budget.</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const warningInput = document.getElementById('warning_threshold');
    const alertInput = document.getElementById('alert_threshold');
    const criticalInput = document.getElementById('critical_threshold');
    const form = document.querySelector('form');

    function validateThresholds() {
        const warning = parseFloat(warningInput.value) || 0;
        const alert = parseFloat(alertInput.value) || 0;
        const critical = parseFloat(criticalInput.value) || 0;

        let isValid = true;

        // Reset validation states
        [warningInput, alertInput, criticalInput].forEach(input => {
            input.classList.remove('is-invalid');
        });

        // Validate order: warning < alert < critical
        if (warning >= alert) {
            warningInput.classList.add('is-invalid');
            alertInput.classList.add('is-invalid');
            isValid = false;
        }

        if (alert >= critical) {
            alertInput.classList.add('is-invalid');
            criticalInput.classList.add('is-invalid');
            isValid = false;
        }

        return isValid;
    }

    // Add validation on input change
    [warningInput, alertInput, criticalInput].forEach(input => {
        input.addEventListener('input', validateThresholds);
    });

    // Validate on form submit
    form.addEventListener('submit', function(e) {
        if (!validateThresholds()) {
            e.preventDefault();
            alert('Les seuils doivent être dans l\'ordre croissant : avertissement < alerte < critique');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>