<?php

namespace MonBudget\Services;

use MonBudget\Models\Attachment;
use Exception;

/**
 * Service de gestion des uploads de fichiers
 * 
 * Gère le téléchargement sécurisé, la validation et le stockage des pièces jointes
 * 
 * @package App\Services
 * @version 2.1.0-dev
 */
class FileUploadService
{
    /**
     * Répertoire de base pour les uploads
     */
    private const UPLOAD_BASE_DIR = 'uploads/attachments';

    /**
     * Upload un fichier et retourne les informations
     *
     * @param array $file Tableau $_FILES['name']
     * @param int $userId ID de l'utilisateur (pour organisation des dossiers)
     * @return array Informations du fichier uploadé
     * @throws Exception
     */
    public function uploadFile(array $file, int $userId): array
    {
        // Validation du fichier
        $this->validateFile($file);

        // Génération du chemin de stockage
        $storagePath = $this->generateStoragePath($userId);
        
        // Création du répertoire si nécessaire
        $this->ensureDirectoryExists($storagePath);

        // Génération nom unique
        $filename = $this->generateUniqueFilename($file['name']);
        
        // Chemin complet
        $fullPath = $storagePath . '/' . $filename;

        // Upload du fichier
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception("Erreur lors du déplacement du fichier");
        }

        // Détection du MIME type réel (sécurité)
        $realMimeType = $this->detectMimeType($fullPath);
        
        // Validation du MIME type réel
        if (!Attachment::isAllowedMimeType($realMimeType)) {
            // Suppression du fichier uploadé
            unlink($fullPath);
            throw new Exception("Type de fichier non autorisé (détecté: {$realMimeType})");
        }

        // Chemin relatif depuis uploads/ (sans le chemin absolu du projet)
        $baseDir = dirname(__DIR__, 2); // Racine du projet
        $relativePath = str_replace($baseDir . '/', '', $storagePath) . '/' . $filename;
        // Supprimer "uploads/" du début pour avoir : attachments/2/2025/11/xxx.jpg
        $relativePath = str_replace('uploads/', '', $relativePath);

        return [
            'filename' => $filename,
            'original_name' => $this->sanitizeFilename($file['name']),
            'path' => $relativePath,
            'mimetype' => $realMimeType,
            'size' => $file['size']
        ];
    }

    /**
     * Valider le fichier uploadé
     *
     * @param array $file
     * @throws Exception
     */
    private function validateFile(array $file): void
    {
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        // Vérifier la taille
        if ($file['size'] > Attachment::MAX_FILE_SIZE) {
            $maxSizeMB = Attachment::MAX_FILE_SIZE / (1024 * 1024);
            throw new Exception("Le fichier dépasse la taille maximale autorisée ({$maxSizeMB} Mo)");
        }

        // Vérifier que le fichier a bien été uploadé
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Fichier non valide");
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!Attachment::isAllowedExtension($extension)) {
            throw new Exception("Extension de fichier non autorisée (.{$extension})");
        }
    }

    /**
     * Générer le chemin de stockage (uploads/attachments/{user_id}/{year}/{month})
     *
     * @param int $userId
     * @return string
     */
    private function generateStoragePath(int $userId): string
    {
        $year = date('Y');
        $month = date('m');
        
        $baseDir = dirname(__DIR__, 2); // Racine du projet
        return $baseDir . '/' . self::UPLOAD_BASE_DIR . '/' . $userId . '/' . $year . '/' . $month;
    }

    /**
     * S'assurer que le répertoire existe
     *
     * @param string $path
     * @throws Exception
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new Exception("Impossible de créer le répertoire de stockage");
            }

            // Créer un .htaccess pour sécuriser
            $htaccessPath = dirname($path, 3) . '/.htaccess'; // Dans uploads/attachments/
            if (!file_exists($htaccessPath)) {
                $htaccessContent = "# Protection des fichiers uploadés\n";
                $htaccessContent .= "# Autorise uniquement les types de fichiers sûrs\n\n";
                $htaccessContent .= "<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\n";
                $htaccessContent .= "    Require all denied\n";
                $htaccessContent .= "</FilesMatch>\n";
                
                file_put_contents($htaccessPath, $htaccessContent);
            }
        }
    }

    /**
     * Générer un nom de fichier unique
     *
     * @param string $originalName
     * @return string
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $hash = bin2hex(random_bytes(16)); // 32 caractères
        
        return $hash . '.' . $extension;
    }

    /**
     * Sanitize le nom de fichier original pour stockage BDD
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // Supprimer caractères dangereux
        $filename = preg_replace('/[^\w\s\-\.]/', '', $filename);
        
        // Limiter la longueur
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($basename, 0, 250) . '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Détecter le type MIME réel du fichier
     *
     * @param string $filepath
     * @return string
     */
    private function detectMimeType(string $filepath): string
    {
        // Utiliser finfo (plus fiable que mime_content_type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * Supprimer un fichier physique
     *
     * @param array $attachment
     * @return bool
     */
    public function deleteFile(array $attachment): bool
    {
        $fullPath = Attachment::getFullPath($attachment);
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return true; // Fichier déjà supprimé
    }

    /**
     * Obtenir le message d'erreur d'upload
     *
     * @param int $errorCode
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la limite configurée du serveur",
            UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la limite du formulaire",
            UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement téléchargé",
            UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été téléchargé",
            UPLOAD_ERR_NO_TMP_DIR => "Répertoire temporaire manquant",
            UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture sur le disque",
            UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté le téléchargement",
            default => "Erreur inconnue lors du téléchargement"
        };
    }

    /**
     * Valider plusieurs fichiers simultanément
     *
     * @param array $files Tableau de fichiers
     * @return array Erreurs par fichier
     */
    public function validateMultipleFiles(array $files): array
    {
        $errors = [];
        
        foreach ($files as $index => $file) {
            try {
                $this->validateFile($file);
            } catch (Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }
        
        return $errors;
    }
}
