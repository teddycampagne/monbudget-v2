<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">
    <h1 class="h3 mb-4">
        <i class="bi bi-ticket-detailed"></i> Demandes d'aide à un administrateur
    </h1>
    <?php
    try {
        $pdo = MonBudget\Core\Database::getConnection();
        $stmt = $pdo->query("SELECT r.*, u.username FROM admin_password_requests r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 50");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $requests = [];
        echo '<div class="alert alert-danger">Erreur lors de la récupération des demandes.</div>';
    }
    ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Email demandeur</th>
                        <th>Raison</th>
                        <th>Statut</th>
                        <th>IP</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Aucune demande en attente</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($req['created_at']))) ?></td>
                            <td><?= htmlspecialchars($req['username'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($req['requester_email']) ?></td>
                            <td><?= nl2br(htmlspecialchars($req['reason'])) ?></td>
                            <td>
                                <?php if ($req['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Traité</span>
                                <?php elseif ($req['status'] === 'rejected'): ?>
                                    <span class="badge bg-secondary">Rejeté</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">En attente</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($req['ip_address']) ?></td>
                            <td>
                                <?php if (!$req['status'] || $req['status'] === 'pending' || $req['status'] === ''): ?>
                                    <a href="<?= url('admin/users/reset-passwords?request_id=' . $req['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-key"></i> Traiter
                                    </a>
                                    <form method="post" action="<?= url('admin/admin-requests/' . $req['id'] . '/close') ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success ms-1" onclick="return confirm('Clore cette demande ?');">
                                            <i class="bi bi-check2-circle"></i> Clore
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
