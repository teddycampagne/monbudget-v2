<?php

namespace MonBudget\Controllers;

use MonBudget\Models\Projection;
use MonBudget\Models\Compte;
use MonBudget\Models\Categorie;

/**
 * Class ProjectionController
 * 
 * Contrôleur de gestion des projections budgétaires.
 * 
 * @package MonBudget\Controllers
 */
class ProjectionController extends BaseController
{
    /**
     * Page principale des projections budgétaires
     * 
     * Affiche :
     * - Graphique historique + projections futures
     * - Filtres (période, compte, catégorie)
     * - Tableau détaillé des projections
     * - Récurrences actives contributives
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $userId = $_SESSION['user']['id'];
        
        // Récupérer les paramètres de filtrage
        $nbMoisFutur = (int) ($_GET['periode'] ?? 6); // 3, 6 ou 12
        $compteId = !empty($_GET['compte']) ? (int) $_GET['compte'] : null;
        $categorieId = !empty($_GET['categorie']) ? (int) $_GET['categorie'] : null;
        $nbMoisHistorique = 12; // Toujours 12 mois d'historique pour le graphique
        
        // Calculer les projections
        $data = Projection::calculerProjections($userId, $nbMoisFutur, $compteId, $categorieId);
        
        // Récupérer l'historique pour le graphique
        $historique = Projection::getHistoriqueMensuel($userId, $nbMoisHistorique, $compteId, $categorieId);
        
        // Récupérer les comptes et catégories pour les filtres
        $comptes = Compte::getByUser($userId);
        $categories = Categorie::getCategoriesPrincipales($userId);
        
        // Préparer les données pour la vue
        $viewData = [
            'projections' => $data['projections'],
            'recurrences' => $data['recurrences'],
            'tendances' => $data['tendances'],
            'resume' => $data['resume'],
            'historique' => $historique,
            'comptes' => $comptes,
            'categories' => $categories,
            'filtres' => [
                'periode' => $nbMoisFutur,
                'compte_id' => $compteId,
                'categorie_id' => $categorieId
            ]
        ];
        
        $this->view('projections.index', $viewData);
    }
    
    /**
     * Export PDF des projections
     * 
     * @return void
     */
    public function exportPdf(): void
    {
        $this->requireAuth();
        
        $userId = $_SESSION['user']['id'];
        
        // Récupérer les mêmes paramètres que l'index
        $nbMoisFutur = (int) ($_GET['periode'] ?? 6);
        $compteId = !empty($_GET['compte']) ? (int) $_GET['compte'] : null;
        $categorieId = !empty($_GET['categorie']) ? (int) $_GET['categorie'] : null;
        
        // Calculer les projections
        $data = Projection::calculerProjections($userId, $nbMoisFutur, $compteId, $categorieId);
        $historique = Projection::getHistoriqueMensuel($userId, 12, $compteId, $categorieId);
        
        // Générer le PDF avec TCPDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Informations du document
        $pdf->SetCreator('MonBudget v2.0');
        $pdf->SetAuthor($_SESSION['user']['username']);
        $pdf->SetTitle('Projections Budgétaires');
        $pdf->SetSubject('Projections et prévisions financières');
        
        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Page
        $pdf->AddPage();
        
        // Titre
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'Projections Budgétaires', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Date génération
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Généré le ' . date('d/m/Y à H:i'), 0, 1, 'R');
        $pdf->Ln(5);
        
        // Résumé
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Résumé sur ' . $nbMoisFutur . ' mois', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $html = '<table border="1" cellpadding="5">
            <tr style="background-color: #f0f0f0;">
                <th><b>Total Crédits</b></th>
                <th><b>Total Débits</b></th>
                <th><b>Solde Cumulé</b></th>
                <th><b>Moyenne Mensuelle</b></th>
            </tr>
            <tr>
                <td style="color: green;">' . number_format($data['resume']['total_credits'], 2, ',', ' ') . ' €</td>
                <td style="color: red;">' . number_format($data['resume']['total_debits'], 2, ',', ' ') . ' €</td>
                <td style="' . ($data['resume']['solde_cumule'] >= 0 ? 'color: green;' : 'color: red;') . '">' . number_format($data['resume']['solde_cumule'], 2, ',', ' ') . ' €</td>
                <td>' . number_format($data['resume']['moyenne_mensuelle'], 2, ',', ' ') . ' €</td>
            </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(10);
        
        // Projections détaillées
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Projections Mensuelles', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        
        $html = '<table border="1" cellpadding="4">
            <tr style="background-color: #f0f0f0;">
                <th><b>Mois</b></th>
                <th><b>Crédits</b></th>
                <th><b>Débits</b></th>
                <th><b>Solde</b></th>
                <th><b>Confiance Min</b></th>
                <th><b>Confiance Max</b></th>
            </tr>';
        
        foreach ($data['projections'] as $proj) {
            $html .= '<tr>
                <td>' . htmlspecialchars($proj['mois']) . '</td>
                <td style="color: green;">' . number_format($proj['credits_prevus'], 2, ',', ' ') . ' €</td>
                <td style="color: red;">' . number_format($proj['debits_prevus'], 2, ',', ' ') . ' €</td>
                <td style="' . ($proj['solde_previsionnel'] >= 0 ? 'color: green;' : 'color: red;') . '">' . number_format($proj['solde_previsionnel'], 2, ',', ' ') . ' €</td>
                <td>' . number_format($proj['confiance_min'], 2, ',', ' ') . ' €</td>
                <td>' . number_format($proj['confiance_max'], 2, ',', ' ') . ' €</td>
            </tr>';
        }
        
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output PDF
        $filename = 'projections_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D'); // D = download
        exit;
    }
}
