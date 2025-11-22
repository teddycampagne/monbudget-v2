<?php require __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">
                <i class="fas fa-ticket-alt"></i> Gestion des tickets
            </h1>
        </div>
    </div>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="card-title">
                                <i class="fas fa-exclamation-triangle"></i> Urgent
                            </div>
                            <h3 class="card-text"><?php echo $stats['urgent_count'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="card-title">
                                <i class="fas fa-clock"></i> En attente
                            </div>
                            <h3 class="card-text"><?php echo $stats['open_count'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="card-title">
                                <i class="fas fa-cogs"></i> En cours
                            </div>
                            <h3 class="card-text"><?php echo $stats['in_progress_count'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <div class="card-title">
                                <i class="fas fa-question-circle"></i> Attente user
                            </div>
                            <h3 class="card-text"><?php echo $stats['waiting_count'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="card-title">
                                <i class="fas fa-check-circle"></i> Résolus
                            </div>
                            <h3 class="card-text"><?php echo $stats['resolved_count'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="mb-3">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'open' ? 'active' : ''; ?>" href="<?= url('admin/tickets?status=open') ?>">
                            Ouverts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'in_progress' ? 'active' : ''; ?>" href="<?= url('admin/tickets?status=in_progress') ?>">
                            En cours
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'waiting_user' ? 'active' : ''; ?>" href="<?= url('admin/tickets?status=waiting_user') ?>">
                            Attente utilisateur
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'resolved' ? 'active' : ''; ?>" href="<?= url('admin/tickets?status=resolved') ?>">
                            Résolus
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'closed' ? 'active' : ''; ?>" href="<?= url('admin/tickets?status=closed') ?>">
                            Fermés
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Liste des tickets -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun ticket <?php echo $status === 'open' ? 'ouvert' : $status; ?></h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Utilisateur</th>
                                        <th>Sujet</th>
                                        <th>Priorité</th>
                                        <th>Statut</th>
                                        <th>Créé le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>#<?php echo $ticket['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ticket['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($ticket['user_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                            <td>
                                                <?php
                                                $priorityClass = [
                                                    'low' => 'secondary',
                                                    'normal' => 'info',
                                                    'high' => 'warning',
                                                    'urgent' => 'danger'
                                                ];
                                                $priorityText = [
                                                    'low' => 'Faible',
                                                    'normal' => 'Normal',
                                                    'high' => 'Élevé',
                                                    'urgent' => 'Urgent'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $priorityClass[$ticket['priority']]; ?>">
                                                    <?php echo $priorityText[$ticket['priority']]; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'open' => 'warning',
                                                    'in_progress' => 'info',
                                                    'waiting_user' => 'secondary',
                                                    'resolved' => 'success',
                                                    'closed' => 'dark'
                                                ];
                                                $statusText = [
                                                    'open' => 'Ouvert',
                                                    'in_progress' => 'En cours',
                                                    'waiting_user' => 'Attente user',
                                                    'resolved' => 'Résolu',
                                                    'closed' => 'Fermé'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass[$ticket['status']]; ?>">
                                                    <?php echo $statusText[$ticket['status']]; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></td>
                                            <td>
                                                <a href="<?= url('admin/tickets/' . $ticket['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Voir
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Navigation des tickets">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?= url('admin/tickets?status=' . $status . '&page=' . $i) ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

    <div class="mt-3">
        <a href="<?= url('admin') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>