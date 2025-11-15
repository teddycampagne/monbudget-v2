<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MonBudget v2.0' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body>
    <?php
    // Charger les helpers UI pour tous les templates
    require_once BASE_PATH . '/app/Views/components/ui-helpers.php';
    ?>
    
    <!-- Navigation -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include BASE_PATH . '/app/Views/components/navbar.php'; ?>
    <?php endif; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="col-md-3 col-lg-2 px-0">
                    <?php include BASE_PATH . '/app/Views/components/sidebar.php'; ?>
                </div>
            <?php endif; ?>
            
            <!-- Main Content -->
            <main class="<?= isset($_SESSION['user_id']) ? 'col-md-9 col-lg-10' : 'col-12' ?> px-md-4">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash'])): ?>
                    <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show mt-3" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>
                
                <!-- Page Content -->
                <div class="content">
                    <?php
                    // Le contenu sera inséré ici par les vues enfants
                    ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
