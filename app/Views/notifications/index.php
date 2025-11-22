<?php
/**
 * Page Centre de Notifications
 * Phase 4: Notification Center - Interface principale
 */

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="sidebarMenu">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('dashboard') ?>">
                            <i class="bi bi-house"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= url('notifications') ?>">
                            <i class="bi bi-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('transactions') ?>">
                            <i class="bi bi-receipt"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('budgets') ?>">
                            <i class="bi bi-pie-chart"></i> Budgets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('profile') ?>">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-bell"></i> Centre de Notifications
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllRead">
                            <i class="bi bi-check-all"></i> Tout marquer comme lu
                        </button>
                        <a href="?all=1" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i> Voir tout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?= $unreadCount ?></h5>
                            <p class="card-text">Non lues</p>
                        </div>
                    </div>
                </div>
                <?php foreach ($stats as $type => $stat): ?>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-<?= $this->getTypeColor($type) ?>">
                                <?= $stat['unread_count'] ?>/<?= $stat['count'] ?>
                            </h5>
                            <p class="card-text"><?= $this->getTypeLabel($type) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Notifications List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($notifications)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <?php if ($includeRead): ?>
                                Aucune notification trouvée.
                            <?php else: ?>
                                Aucune notification non lue. <a href="?all=1">Voir toutes les notifications</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action <?= $notification['is_read'] ? '' : 'bg-light' ?>" data-notification-id="<?= $notification['id'] ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-<?= $this->getTypeColor($notification['type']) ?> me-2">
                                                    <i class="bi bi-<?= $this->getTypeIcon($notification['type']) ?>"></i>
                                                    <?= $this->getTypeLabel($notification['type']) ?>
                                                </span>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                                                </small>
                                            </div>
                                            <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <p class="mb-1 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                            <?php if ($notification['data'] && isset($notification['data']['budget_id'])): ?>
                                                <small class="text-muted">
                                                    <a href="<?= url('budgets/view/' . $notification['data']['budget_id']) ?>" class="text-decoration-none">
                                                        Voir le budget
                                                    </a>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column align-items-end">
                                            <?php if (!$notification['is_read']): ?>
                                                <button class="btn btn-sm btn-outline-primary mb-1 mark-read" data-id="<?= $notification['id'] ?>">
                                                    <i class="bi bi-check"></i> Marquer comme lu
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger delete-notification" data-id="<?= $notification['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Pagination des notifications" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $includeRead ? '&all=1' : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark single notification as read
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            markAsRead(notificationId);
        });
    });

    // Delete notification
    document.querySelectorAll('.delete-notification').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            deleteNotification(notificationId);
        });
    });

    // Mark all as read
    document.getElementById('markAllRead').addEventListener('click', function() {
        markAllAsRead();
    });

    function markAsRead(notificationId) {
        fetch('<?= url('notifications/mark-read') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors du marquage de la notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors du marquage de la notification');
        });
    }

    function deleteNotification(notificationId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
            return;
        }

        fetch('<?= url('notifications/delete') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression de la notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la suppression de la notification');
        });
    }

    function markAllAsRead() {
        if (!confirm('Marquer toutes les notifications comme lues ?')) {
            return;
        }

        fetch('<?= url('notifications/mark-all-read') ?>', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors du marquage des notifications');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors du marquage des notifications');
        });
    }
});
</script>

<?php
// Helper methods for the view
class NotificationViewHelper {
    public function getTypeColor($type) {
        return match($type) {
            'budget_alert' => 'warning',
            'system' => 'info',
            'info' => 'primary',
            'warning' => 'warning',
            'error' => 'danger',
            default => 'secondary'
        };
    }

    public function getTypeIcon($type) {
        return match($type) {
            'budget_alert' => 'exclamation-triangle',
            'system' => 'gear',
            'info' => 'info-circle',
            'warning' => 'exclamation-circle',
            'error' => 'x-circle',
            default => 'bell'
        };
    }

    public function getTypeLabel($type) {
        return match($type) {
            'budget_alert' => 'Alerte Budget',
            'system' => 'Système',
            'info' => 'Information',
            'warning' => 'Avertissement',
            'error' => 'Erreur',
            default => 'Notification'
        };
    }
}

// Create helper instance
$helper = new NotificationViewHelper();
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>