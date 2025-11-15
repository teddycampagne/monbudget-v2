<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Restaurer une sauvegarde') ?> - MonBudget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php require __DIR__ . '/../layouts/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-arrow-clockwise"></i> Restaurer une sauvegarde
                </h1>
            </div>
        </div>

        <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= url('admin/restore/upload') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">
                                <i class="bi bi-exclamation-triangle"></i> Attention !
                            </h5>
                            <p class="mb-0">
                                La restauration d'une sauvegarde va <strong>REMPLACER TOUTES LES DONNÉES ACTUELLES</strong> 
                                de la base de données par celles du fichier SQL uploadé.
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="sql_file" class="form-label">
                                Fichier de sauvegarde (.sql) <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="sql_file" 
                                   name="sql_file" 
                                   accept=".sql" 
                                   required>
                            <div class="form-text">
                                Sélectionnez un fichier de sauvegarde .sql généré par la fonction "Télécharger une sauvegarde"
                            </div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="confirm_restore" 
                                   required>
                            <label class="form-check-label text-danger fw-bold" for="confirm_restore">
                                Je comprends que cette action est IRRÉVERSIBLE et remplacera toutes les données actuelles
                            </label>
                        </div>
                        
                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-arrow-clockwise"></i> Restaurer la sauvegarde
                            </button>
                            <a href="<?= url('admin') ?>" class="btn btn-secondary ms-2">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Instructions
                    </h5>
                </div>
                <div class="card-body">
                    <ol class="small mb-0">
                        <li class="mb-2">Assurez-vous d'avoir une sauvegarde récente de vos données actuelles</li>
                        <li class="mb-2">Sélectionnez le fichier .sql à restaurer</li>
                        <li class="mb-2">Cochez la case de confirmation</li>
                        <li class="mb-2">Cliquez sur "Restaurer la sauvegarde"</li>
                        <li class="mb-0">Vous serez déconnecté et devrez vous reconnecter</li>
                    </ol>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-exclamation"></i> Recommandations
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li class="mb-2"><strong>Créez une sauvegarde avant de restaurer</strong> pour pouvoir revenir en arrière si nécessaire</li>
                        <li class="mb-2">Prévenez les autres utilisateurs que le système sera momentanément indisponible</li>
                        <li class="mb-0">Vérifiez que le fichier SQL provient bien d'une source fiable</li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
    </div>

    <?php require __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
