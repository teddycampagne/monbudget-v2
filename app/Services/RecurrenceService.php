<?php

namespace MonBudget\Services;

use MonBudget\Core\Database;
use MonBudget\Models\Transaction;
use MonBudget\Models\Compte;
use PDO;

/**
 * Service de gestion automatique des récurrences
 * 
 * Responsabilités :
 * - Exécuter les récurrences échues pour tous les utilisateurs
 * - Détecter et éviter les doublons (même date + même récurrence)
 * - Calculer les prochaines dates d'exécution
 * - Gérer la tolérance des weekends
 * - Logger les opérations
 * 
 * @package MonBudget\Services
 * @version 2.2.0
 */
class RecurrenceService
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    
    /**
     * Exécuter toutes les récurrences échues (tous utilisateurs confondus)
     * Appelé automatiquement au login de n'importe quel utilisateur
     * 
     * @return array Statistiques d'exécution
     */
    public function executeAllPendingRecurrences(): array
    {
        $stats = [
            'total_checked' => 0,
            'total_executed' => 0,
            'total_skipped' => 0,
            'errors' => [],
            'details' => []
        ];
        
        try {
            // Récupérer toutes les récurrences actives échues
            $recurrences = $this->getPendingRecurrences();
            $stats['total_checked'] = count($recurrences);
            
            foreach ($recurrences as $recurrence) {
                try {
                    $result = $this->executeRecurrence($recurrence);
                    
                    if ($result['executed']) {
                        $stats['total_executed']++;
                        $stats['details'][] = [
                            'recurrence_id' => $recurrence['id'],
                            'libelle' => $recurrence['libelle'],
                            'user_id' => $recurrence['user_id'],
                            'date' => $result['date_executed'],
                            'status' => 'executed'
                        ];
                    } else {
                        $stats['total_skipped']++;
                        $stats['details'][] = [
                            'recurrence_id' => $recurrence['id'],
                            'libelle' => $recurrence['libelle'],
                            'reason' => $result['reason'] ?? 'duplicate',
                            'status' => 'skipped'
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $stats['errors'][] = [
                        'recurrence_id' => $recurrence['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Logger les statistiques
            $this->logExecution($stats);
            
        } catch (\Exception $e) {
            $stats['errors'][] = [
                'global' => $e->getMessage()
            ];
        }
        
        return $stats;
    }
    
    /**
     * Récupérer toutes les récurrences actives dont la prochaine exécution est échue
     * 
     * @return array
     */
    private function getPendingRecurrences(): array
    {
        $sql = "SELECT r.* 
                FROM recurrences r
                WHERE r.recurrence_active = 1
                  AND r.prochaine_execution IS NOT NULL
                  AND r.prochaine_execution <= CURDATE()
                  AND (r.date_fin IS NULL OR r.date_fin >= CURDATE())
                ORDER BY r.prochaine_execution ASC, r.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Exécuter une récurrence spécifique (avec protection anti-doublon)
     * 
     * @param array $recurrence Données de la récurrence
     * @return array Résultat de l'exécution
     */
    private function executeRecurrence(array $recurrence): array
    {
        $dateExecution = $recurrence['prochaine_execution'];
        
        // 1. Vérifier si une transaction existe déjà pour cette date + récurrence
        if ($this->isDuplicate($recurrence['id'], $dateExecution)) {
            // Doublon détecté : mettre à jour la prochaine exécution sans créer de transaction
            $this->updateNextExecution($recurrence);
            
            return [
                'executed' => false,
                'reason' => 'duplicate',
                'date_checked' => $dateExecution
            ];
        }
        
        // 2. Appliquer la tolérance des weekends si configurée
        $dateAjustee = $this->applyWeekendTolerance($dateExecution, $recurrence['tolerance_weekend'] ?? 'aucune');
        
        // 3. Créer la transaction
        $transactionData = [
            'user_id' => $recurrence['user_id'],
            'compte_id' => $recurrence['compte_id'],
            'date_transaction' => $dateAjustee,
            'date_valeur' => $dateAjustee,
            'montant' => $recurrence['montant'],
            'libelle' => $recurrence['libelle'],
            'description' => $recurrence['description'],
            'categorie_id' => $recurrence['categorie_id'],
            'sous_categorie_id' => $recurrence['sous_categorie_id'],
            'tiers_id' => $recurrence['tiers_id'],
            'type_operation' => $recurrence['type_operation'],
            'moyen_paiement' => $recurrence['moyen_paiement'],
            'beneficiaire' => $recurrence['beneficiaire'],
            'validee' => $recurrence['auto_validation'] ? 1 : 0,
            'recurrence_id' => $recurrence['id'] // Lien vers la récurrence parente
        ];
        
        $transactionId = Transaction::create($transactionData);
        
        if (!$transactionId) {
            throw new \Exception("Erreur lors de la création de la transaction récurrente #{$recurrence['id']}");
        }
        
        // 4. Mettre à jour la récurrence (nb_executions, derniere_execution, prochaine_execution)
        $this->updateRecurrenceAfterExecution($recurrence, $dateExecution);
        
        // 5. Recalculer le solde du compte
        Compte::recalculerSolde($recurrence['compte_id']);
        
        return [
            'executed' => true,
            'transaction_id' => $transactionId,
            'date_executed' => $dateAjustee
        ];
    }
    
    /**
     * Vérifier si une transaction existe déjà pour cette récurrence à cette date
     * Protection anti-doublon robuste
     * 
     * @param int $recurrenceId
     * @param string $date Date au format YYYY-MM-DD
     * @return bool
     */
    private function isDuplicate(int $recurrenceId, string $date): bool
    {
        $sql = "SELECT COUNT(*) as count
                FROM transactions
                WHERE recurrence_id = ?
                  AND DATE(date_transaction) = ?
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$recurrenceId, $date]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] > 0);
    }
    
    /**
     * Appliquer la tolérance des weekends
     * 
     * @param string $date Date au format YYYY-MM-DD
     * @param string $tolerance 'aucune', 'jour_ouvre_suivant', 'jour_ouvre_precedent'
     * @return string Date ajustée
     */
    private function applyWeekendTolerance(string $date, string $tolerance): string
    {
        if ($tolerance === 'aucune' || empty($tolerance)) {
            return $date;
        }
        
        $timestamp = strtotime($date);
        $dayOfWeek = (int) date('N', $timestamp); // 1 (lundi) à 7 (dimanche)
        
        // Si c'est un jour ouvré (lundi à vendredi), pas d'ajustement
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            return $date;
        }
        
        // Samedi (6) ou Dimanche (7)
        if ($tolerance === 'jour_ouvre_suivant') {
            // Passer au lundi suivant
            if ($dayOfWeek == 6) { // Samedi
                return date('Y-m-d', strtotime($date . ' +2 days'));
            } else { // Dimanche
                return date('Y-m-d', strtotime($date . ' +1 day'));
            }
        } elseif ($tolerance === 'jour_ouvre_precedent') {
            // Revenir au vendredi précédent
            if ($dayOfWeek == 6) { // Samedi
                return date('Y-m-d', strtotime($date . ' -1 day'));
            } else { // Dimanche
                return date('Y-m-d', strtotime($date . ' -2 days'));
            }
        }
        
        return $date;
    }
    
    /**
     * Mettre à jour la récurrence après exécution
     * 
     * @param array $recurrence
     * @param string $dateExecuted
     * @return void
     */
    private function updateRecurrenceAfterExecution(array $recurrence, string $dateExecuted): void
    {
        $nbExecutions = ((int) ($recurrence['nb_executions'] ?? 0)) + 1;
        $prochaineExecution = $this->calculateNextExecution($recurrence, $dateExecuted);
        
        // Si nb_executions_max atteint, désactiver
        $recurrenceActive = 1;
        if ($recurrence['nb_executions_max'] && $nbExecutions >= $recurrence['nb_executions_max']) {
            $recurrenceActive = 0;
            $prochaineExecution = null;
        }
        
        // Si date_fin dépassée, désactiver
        if ($recurrence['date_fin'] && $prochaineExecution && strtotime($prochaineExecution) > strtotime($recurrence['date_fin'])) {
            $recurrenceActive = 0;
            $prochaineExecution = null;
        }
        
        $sql = "UPDATE recurrences 
                SET nb_executions = ?,
                    derniere_execution = ?,
                    prochaine_execution = ?,
                    recurrence_active = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $nbExecutions,
            $dateExecuted,
            $prochaineExecution,
            $recurrenceActive,
            $recurrence['id']
        ]);
    }
    
    /**
     * Mettre à jour uniquement la prochaine exécution (cas doublon)
     * 
     * @param array $recurrence
     * @return void
     */
    private function updateNextExecution(array $recurrence): void
    {
        $prochaineExecution = $this->calculateNextExecution($recurrence, $recurrence['prochaine_execution']);
        
        $sql = "UPDATE recurrences 
                SET prochaine_execution = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prochaineExecution, $recurrence['id']]);
    }
    
    /**
     * Calculer la prochaine date d'exécution selon la fréquence
     * 
     * @param array $recurrence
     * @param string $currentDate Date de référence
     * @return string|null Date au format YYYY-MM-DD
     */
    private function calculateNextExecution(array $recurrence, string $currentDate): ?string
    {
        $frequence = $recurrence['frequence'];
        $intervalle = (int) ($recurrence['intervalle'] ?? 1);
        $jourExecution = (int) ($recurrence['jour_execution'] ?? 0);
        $jourSemaine = (int) ($recurrence['jour_semaine'] ?? 0);
        
        $date = new \DateTime($currentDate);
        
        switch ($frequence) {
            case 'quotidien':
                $date->modify("+{$intervalle} days");
                break;
                
            case 'hebdomadaire':
                $date->modify("+{$intervalle} weeks");
                break;
                
            case 'mensuel':
                $date->modify("+{$intervalle} months");
                
                // Ajuster au jour spécifique si défini
                if ($jourExecution > 0) {
                    $lastDayOfMonth = (int) $date->format('t');
                    $day = min($jourExecution, $lastDayOfMonth);
                    $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $day);
                }
                break;
                
            case 'trimestriel':
                $date->modify("+{$intervalle} months");
                if ($intervalle == 1) $date->modify("+2 months"); // Si intervalle=1, ajouter 3 mois au total
                break;
                
            case 'semestriel':
                $date->modify("+{$intervalle} months");
                if ($intervalle == 1) $date->modify("+5 months"); // Si intervalle=1, ajouter 6 mois au total
                break;
                
            case 'annuel':
                $date->modify("+{$intervalle} years");
                break;
                
            default:
                return null;
        }
        
        return $date->format('Y-m-d');
    }
    
    /**
     * Logger l'exécution dans un fichier
     * 
     * @param array $stats
     * @return void
     */
    private function logExecution(array $stats): void
    {
        $logFile = BASE_PATH . '/storage/logs/recurrence_auto_' . date('Y-m') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] AUTO-EXECUTION: Checked=%d, Executed=%d, Skipped=%d, Errors=%d\n",
            $timestamp,
            $stats['total_checked'],
            $stats['total_executed'],
            $stats['total_skipped'],
            count($stats['errors'])
        );
        
        // Ajouter les détails si exécutions
        if ($stats['total_executed'] > 0) {
            foreach ($stats['details'] as $detail) {
                if ($detail['status'] === 'executed') {
                    $message .= sprintf(
                        "  ✓ Récurrence #%d (user:%d) exécutée le %s: %s\n",
                        $detail['recurrence_id'],
                        $detail['user_id'],
                        $detail['date'],
                        $detail['libelle']
                    );
                }
            }
        }
        
        // Ajouter les erreurs si présentes
        if (!empty($stats['errors'])) {
            $message .= "  ERRORS:\n";
            foreach ($stats['errors'] as $error) {
                $message .= "    - " . json_encode($error) . "\n";
            }
        }
        
        file_put_contents($logFile, $message, FILE_APPEND);
    }
    
    /**
     * Obtenir les statistiques du dernier run (utile pour dashboard admin)
     * 
     * @return array|null
     */
    public function getLastExecutionStats(): ?array
    {
        $logFile = BASE_PATH . '/storage/logs/recurrence_auto_' . date('Y-m') . '.log';
        
        if (!file_exists($logFile)) {
            return null;
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return null;
        }
        
        // Récupérer la dernière ligne d'exécution
        $lastLine = end($lines);
        
        if (preg_match('/Checked=(\d+), Executed=(\d+), Skipped=(\d+), Errors=(\d+)/', $lastLine, $matches)) {
            return [
                'checked' => (int) $matches[1],
                'executed' => (int) $matches[2],
                'skipped' => (int) $matches[3],
                'errors' => (int) $matches[4],
                'timestamp' => substr($lastLine, 1, 19) // Extraire le timestamp
            ];
        }
        
        return null;
    }
}
