<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modèle Attachment - Gestion des pièces jointes attachées aux transactions
 * 
 * @package App\Models
 * @version 2.1.0-dev
 */
class Attachment
{
    /**
     * Types MIME autorisés pour les uploads
     */
    public const ALLOWED_MIMETYPES = [
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        
        // Documents
        'application/pdf',
        
        // Excel
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        
        // Word
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        
        // Texte
        'text/plain',
        'text/csv',
    ];

    /**
     * Extensions autorisées (sécurité additionnelle)
     */
    public const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf',
        'xls', 'xlsx',
        'doc', 'docx',
        'txt', 'csv'
    ];

    /**
     * Taille maximale par fichier (5 Mo)
     */
    public const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    /**
     * Récupérer une pièce jointe par ID
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM attachments 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Récupérer toutes les pièces jointes d'une transaction
     *
     * @param int $transactionId
     * @return array
     */
    public static function findByTransaction(int $transactionId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM attachments 
            WHERE transaction_id = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$transactionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les pièces jointes d'une transaction
     *
     * @param int $transactionId
     * @return int
     */
    public static function countByTransaction(int $transactionId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM attachments 
            WHERE transaction_id = ?
        ");
        $stmt->execute([$transactionId]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Créer une nouvelle pièce jointe
     *
     * @param array $data
     * @return int|false ID de l'attachment créé ou false
     */
    public static function create(array $data): int|false
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO attachments (
                transaction_id, 
                filename, 
                original_name, 
                path, 
                mimetype, 
                size
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $data['transaction_id'],
            $data['filename'],
            $data['original_name'],
            $data['path'],
            $data['mimetype'],
            $data['size']
        ]);
        
        return $success ? (int) $db->lastInsertId() : false;
    }

    /**
     * Supprimer une pièce jointe
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM attachments WHERE id = ?");
        
        return $stmt->execute([$id]);
    }

    /**
     * Supprimer toutes les pièces jointes d'une transaction
     *
     * @param int $transactionId
     * @return bool
     */
    public static function deleteByTransaction(int $transactionId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM attachments WHERE transaction_id = ?");
        
        return $stmt->execute([$transactionId]);
    }

    /**
     * Vérifier si un type MIME est autorisé
     *
     * @param string $mimetype
     * @return bool
     */
    public static function isAllowedMimeType(string $mimetype): bool
    {
        return in_array($mimetype, self::ALLOWED_MIMETYPES, true);
    }

    /**
     * Vérifier si une extension est autorisée
     *
     * @param string $extension
     * @return bool
     */
    public static function isAllowedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Obtenir le chemin complet du fichier
     *
     * @param array $attachment
     * @return string
     */
    public static function getFullPath(array $attachment): string
    {
        $baseDir = dirname(__DIR__, 2); // Remonte à la racine du projet
        return $baseDir . '/uploads/' . $attachment['path'];
    }

    /**
     * Vérifier si le fichier est une image
     *
     * @param array $attachment
     * @return bool
     */
    public static function isImage(array $attachment): bool
    {
        return str_starts_with($attachment['mimetype'], 'image/');
    }

    /**
     * Formater la taille du fichier pour affichage
     *
     * @param int $bytes
     * @return string
     */
    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' Mo';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' Ko';
        }
        return $bytes . ' o';
    }

    /**
     * Obtenir l'icône Bootstrap appropriée selon le type MIME
     *
     * @param string $mimetype
     * @return string Classe d'icône Bootstrap Icons
     */
    public static function getIcon(string $mimetype): string
    {
        return match (true) {
            str_starts_with($mimetype, 'image/') => 'bi-file-image',
            $mimetype === 'application/pdf' => 'bi-file-pdf',
            str_contains($mimetype, 'excel') || str_contains($mimetype, 'spreadsheet') => 'bi-file-excel',
            str_contains($mimetype, 'word') || str_contains($mimetype, 'document') => 'bi-file-word',
            str_starts_with($mimetype, 'text/') => 'bi-file-text',
            default => 'bi-file-earmark'
        };
    }
}
