<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">
                <i class="fas fa-ticket-alt"></i> Ticket #<?php echo $ticket['id']; ?>
            </h1>
        </div>
        <div class="col-auto">
            <a href="<?= url('admin/tickets') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

            <!-- Informations du ticket -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                        </div>
                        <div class="col-md-6 text-end">
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
                                'waiting_user' => 'Attente utilisateur',
                                'resolved' => 'Résolu',
                                'closed' => 'Fermé'
                            ];
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
                            <span class="badge bg-<?php echo $statusClass[$ticket['status']]; ?> me-2">
                                <?php echo $statusText[$ticket['status']]; ?>
                            </span>
                            <span class="badge bg-<?php echo $priorityClass[$ticket['priority']]; ?>">
                                Priorité: <?php echo $priorityText[$ticket['priority']]; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Utilisateur:</strong> <?php echo htmlspecialchars($ticket['user_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($ticket['user_email']); ?><br>
                            <strong>Créé le:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Administrateur assigné:</strong> <?php echo $ticket['admin_name'] ? htmlspecialchars($ticket['admin_name']) : 'Non assigné'; ?><br>
                            <strong>Catégorie:</strong> <?php echo htmlspecialchars($ticket['category'] ?? 'Général'); ?><br>
                            <strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-light">
                        <strong>Description:</strong><br>
                        <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                    </div>
                </div>
            </div>

            <!-- Formulaire de mise à jour du statut -->
            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Mettre à jour le ticket</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('admin/tickets/' . $ticket['id'] . '/status') ?>">
                            <?php echo csrf_field(); ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Ouvert</option>
                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="waiting_user" <?php echo $ticket['status'] === 'waiting_user' ? 'selected' : ''; ?>>Attente utilisateur</option>
                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Résolu</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Fermé</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="priority" class="form-label">Priorité</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?php echo $ticket['priority'] === 'low' ? 'selected' : ''; ?>>Faible</option>
                                        <option value="normal" <?php echo $ticket['priority'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="high" <?php echo $ticket['priority'] === 'high' ? 'selected' : ''; ?>>Élevé</option>
                                        <option value="urgent" <?php echo $ticket['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="admin_id" class="form-label">Assigner à</label>
                                    <select class="form-select" id="admin_id" name="admin_id">
                                        <option value="">Non assigné</option>
                                        <!-- Ici on pourrait lister tous les admins -->
                                        <option value="<?php echo $_SESSION['user']['id']; ?>" selected>
                                            <?php echo htmlspecialchars($_SESSION['user']['username']); ?> (moi)
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Mettre à jour
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Réponses -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Conversation</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($replies)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p>Aucune réponse pour le moment</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($replies as $reply): ?>
                            <div class="border rounded p-3 mb-3 <?php echo $reply['is_internal'] ? 'bg-warning bg-opacity-10' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong><?php echo htmlspecialchars($reply['username']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?>
                                        <?php if ($reply['is_internal']): ?>
                                            <span class="badge bg-warning ms-2">Interne</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Formulaire de réponse -->
                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <div class="mt-4">
                            <h6>Ajouter une réponse</h6>
                            <form method="POST" action="<?= url('admin/tickets/' . $ticket['id'] . '/reply') ?>">
                                <?php echo csrf_field(); ?>
                                <div class="mb-3">
                                    <textarea class="form-control" id="message" name="message" rows="4" placeholder="Votre réponse..." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                                        <label class="form-check-label" for="is_internal">
                                            Réponse interne (visible seulement par les administrateurs)
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-reply"></i> Répondre
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>