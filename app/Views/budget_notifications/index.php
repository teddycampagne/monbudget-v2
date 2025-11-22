<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i>
                        Notifications de Budget
                    </h3>
                    <div class="card-tools">
                        <a href="<?= url('budget-notifications/settings') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-cog"></i>
                            Paramètres
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Aucune notification de budget pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <?php
                                                $iconClass = match($notification['type']) {
                                                    'warning' => 'fas fa-exclamation-triangle text-warning',
                                                    'alert' => 'fas fa-exclamation-circle text-danger',
                                                    'critical' => 'fas fa-times-circle text-dark',
                                                    default => 'fas fa-bell text-info'
                                                };
                                                ?>
                                                <i class="<?= $iconClass ?> me-2"></i>
                                                <h6 class="mb-0">
                                                    <?php
                                                    $typeLabel = match($notification['type']) {
                                                        'warning' => 'Avertissement',
                                                        'alert' => 'Alerte',
                                                        'critical' => 'Dépassement',
                                                        default => 'Notification'
                                                    };
                                                    echo htmlspecialchars($typeLabel);
                                                    ?>
                                                </h6>
                                                <small class="text-muted ms-2">
                                                    <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                                                </small>
                                            </div>
                                            <p class="mb-1">
                                                <?= htmlspecialchars($notification['message']) ?>
                                            </p>
                                            <div class="text-muted small mb-2">
                                                <span class="badge bg-secondary">
                                                    <?= number_format($notification['pourcentage_depasse'], 1) ?>% utilisé
                                                </span>
                                                <span class="badge bg-danger ms-1">
                                                    +<?= number_format($notification['montant_depasse'], 2, ',', ' ') ?> €
                                                </span>
                                            </div>
                                            <?php if (!empty($notification['budget_nom'])): ?>
                                                <div class="small">
                                                    <strong>Budget :</strong>
                                                    <a href="<?= url('budgets') ?>" class="text-decoration-none">
                                                        <i class="bi bi-piggy-bank"></i>
                                                        <?= htmlspecialchars($notification['budget_nom']) ?>
                                                        <?php if (!empty($notification['categorie_nom'])): ?>
                                                            (<?= htmlspecialchars($notification['categorie_nom']) ?>)
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success mark-as-read"
                                                    data-notification-id="<?= $notification['id'] ?>">
                                                <i class="fas fa-check"></i>
                                                Marquer comme lu
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour marquer comme lu -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            const listItem = this.closest('.list-group-item');

            fetch('<?= url("budget-notifications/mark-as-read") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Masquer la notification
                    listItem.style.opacity = '0.5';
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-check"></i> Lu';

                    // Afficher un message de succès
                    showToast('Notification marquée comme lue', 'success');
                } else {
                    showToast('Erreur lors de la mise à jour', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Erreur réseau', 'error');
            });
        });
    });
});

function showToast(message, type) {
    // Créer un toast simple
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(toast);

    // Auto-suppression après 3 secondes
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>