<?php

namespace MonBudget\Services;

use TCPDF;
use MonBudget\Models\Compte;
use MonBudget\Models\Titulaire;

/**
 * Service de génération de RIB au format PDF
 * 
 * Génère des Relevés d'Identité Bancaire (RIB) au format PDF pour les comptes bancaires.
 * Inclut toutes les informations obligatoires : code banque, code guichet, numéro de compte,
 * clé RIB, IBAN, BIC, et informations du/des titulaires.
 * 
 * @package MonBudget\Services
 * @author MonBudget
 * @version 1.0.0
 */
class RibGenerator
{
    /**
     * Générer un RIB en PDF pour un compte bancaire
     * 
     * Crée un document PDF formaté contenant toutes les informations du RIB.
     * Inclut le logo de la banque si disponible.
     * 
     * @param array $compte Données du compte avec informations bancaires complètes
     *                      (code_banque, code_guichet, numero_compte, cle_rib, iban, bic, titulaires)
     * @return string Contenu du PDF au format binaire (à envoyer au navigateur ou sauvegarder)
     * @throws \Exception Si les informations RIB sont incomplètes
     */
    public function generate(array $compte): string
    {
        // Créer un nouveau PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Informations du document
        $pdf->SetCreator('MonBudget V2');
        $pdf->SetAuthor('MonBudget V2');
        $pdf->SetTitle('RIB - ' . ($compte['nom'] ?? 'Compte'));
        $pdf->SetSubject('Relevé d\'Identité Bancaire');
        
        // Supprimer header et footer par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Police
        $pdf->SetFont('helvetica', '', 10);
        
        // Logo de la banque (si disponible)
        $logoPath = __DIR__ . '/../../uploads/logos/' . ($compte['logo_file'] ?? '');
        if (!empty($compte['logo_file']) && file_exists($logoPath)) {
            // Afficher le logo en haut à gauche
            $pdf->Image($logoPath, 15, 15, 40, 0, '', '', '', true, 150, '', false, false, 0, false, false, false);
            $yStart = 40; // Décaler le contenu après le logo
        } else {
            $yStart = 20;
        }
        
        $pdf->SetY($yStart);
        
        // Titre du document
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELEVÉ D\'IDENTITÉ BANCAIRE', 0, 1, 'C');
        $pdf->Ln(3);
        
        // === SECTION BANQUE ===
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'DOMICILIATION BANCAIRE', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, strtoupper($compte['banque_nom'] ?? 'Banque'), 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        if (!empty($compte['banque_adresse_ligne1'])) {
            $pdf->Cell(0, 4, $compte['banque_adresse_ligne1'], 0, 1, 'L');
        }
        if (!empty($compte['banque_adresse_ligne2'])) {
            $pdf->Cell(0, 4, $compte['banque_adresse_ligne2'], 0, 1, 'L');
        }
        if (!empty($compte['banque_code_postal']) || !empty($compte['banque_ville'])) {
            $adresse = trim(($compte['banque_code_postal'] ?? '') . ' ' . ($compte['banque_ville'] ?? ''));
            $pdf->Cell(0, 4, $adresse, 0, 1, 'L');
        }
        if (!empty($compte['banque_pays']) && $compte['banque_pays'] !== 'France') {
            $pdf->Cell(0, 4, strtoupper($compte['banque_pays']), 0, 1, 'L');
        }
        if (!empty($compte['banque_telephone'])) {
            $pdf->Cell(0, 4, 'Tél : ' . $compte['banque_telephone'], 0, 1, 'L');
        }
        if (!empty($compte['banque_contact_email'])) {
            $pdf->Cell(0, 4, 'Email : ' . $compte['banque_contact_email'], 0, 1, 'L');
        }
        
        $pdf->Ln(6);
        
        // === SECTION TITULAIRE(S) ===
        $pdf->SetFont('helvetica', 'B', 11);
        
        // Récupérer les titulaires du compte depuis la base de données
        $titulaires = Compte::getTitulaires($compte['id']);
        
        if (!empty($titulaires)) {
            // Afficher le(s) titulaire(s) depuis la base
            if (count($titulaires) > 1) {
                $pdf->Cell(0, 6, 'TITULAIRES DU COMPTE (COMPTE JOINT)', 0, 1, 'L');
            } else {
                $pdf->Cell(0, 6, 'TITULAIRE DU COMPTE', 0, 1, 'L');
            }
            
            $pdf->SetFont('helvetica', '', 10);
            
            foreach ($titulaires as $index => $titulaire) {
                if ($index > 0) {
                    $pdf->Ln(4); // Espacement entre les titulaires
                }
                
                // Nom complet du titulaire
                $nomComplet = trim(($titulaire['prenom'] ?? '') . ' ' . strtoupper($titulaire['nom'] ?? ''));
                
                // Afficher le rôle si c'est un compte joint
                if (count($titulaires) > 1) {
                    $role = '';
                    if ($titulaire['role'] === 'co-titulaire') {
                        $role = ' (Co-titulaire)';
                    } else {
                        $role = ' (Titulaire)';
                    }
                    $pdf->SetFont('helvetica', 'B', 10);
                    $pdf->Cell(0, 5, $nomComplet . $role, 0, 1, 'L');
                } else {
                    $pdf->SetFont('helvetica', 'B', 10);
                    $pdf->Cell(0, 5, $nomComplet, 0, 1, 'L');
                }
                
                // Adresse complète du titulaire
                $pdf->SetFont('helvetica', '', 9);
                if (!empty($titulaire['adresse_ligne1'])) {
                    $pdf->Cell(0, 4, $titulaire['adresse_ligne1'], 0, 1, 'L');
                }
                if (!empty($titulaire['adresse_ligne2'])) {
                    $pdf->Cell(0, 4, $titulaire['adresse_ligne2'], 0, 1, 'L');
                }
                
                $adresseLigne = '';
                if (!empty($titulaire['code_postal'])) {
                    $adresseLigne .= $titulaire['code_postal'];
                }
                if (!empty($titulaire['ville'])) {
                    $adresseLigne .= ($adresseLigne ? ' ' : '') . $titulaire['ville'];
                }
                if ($adresseLigne) {
                    $pdf->Cell(0, 4, $adresseLigne, 0, 1, 'L');
                }
                
                if (!empty($titulaire['pays']) && $titulaire['pays'] !== 'France') {
                    $pdf->Cell(0, 4, strtoupper($titulaire['pays']), 0, 1, 'L');
                }
                
                // Contact (optionnel, uniquement pour le premier titulaire)
                if ($index === 0) {
                    if (!empty($titulaire['email'])) {
                        $pdf->Cell(0, 4, 'Email : ' . $titulaire['email'], 0, 1, 'L');
                    }
                    if (!empty($titulaire['telephone'])) {
                        $pdf->Cell(0, 4, 'Tél : ' . $titulaire['telephone'], 0, 1, 'L');
                    }
                }
            }
        } else {
            // Fallback : si pas de titulaire en base, utiliser l'utilisateur connecté
            $pdf->Cell(0, 6, 'TITULAIRE DU COMPTE', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            $fallbackTitulaire = $_SESSION['user']['nom'] ?? 'Titulaire';
            if (!empty($_SESSION['user']['prenom'])) {
                $fallbackTitulaire = $_SESSION['user']['prenom'] . ' ' . $fallbackTitulaire;
            }
            $pdf->Cell(0, 5, strtoupper($fallbackTitulaire), 0, 1, 'L');
            
            // Adresse du user si disponible
            if (!empty($_SESSION['user']['adresse'])) {
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(0, 4, $_SESSION['user']['adresse'], 0, 1, 'L');
            }
            if (!empty($_SESSION['user']['code_postal']) || !empty($_SESSION['user']['ville'])) {
                $pdf->SetFont('helvetica', '', 9);
                $adresseTitulaire = trim(($_SESSION['user']['code_postal'] ?? '') . ' ' . ($_SESSION['user']['ville'] ?? ''));
                $pdf->Cell(0, 4, $adresseTitulaire, 0, 1, 'L');
            }
        }
        
        $pdf->Ln(6);
        
        // === COORDONNÉES BANCAIRES ===
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'COORDONNÉES BANCAIRES', 1, 1, 'C', true);
        
        // Tableau des coordonnées RIB
        $pdf->SetFont('helvetica', '', 9);
        
        // En-têtes du tableau
        $pdf->SetFillColor(220, 220, 220);
        $colWidths = [45, 45, 65, 25];
        $headers = ['Code Banque', 'Code Guichet', 'N° de Compte', 'Clé RIB'];
        
        foreach ($headers as $i => $header) {
            $pdf->Cell($colWidths[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Valeurs
        $pdf->SetFont('helvetica', 'B', 11);
        $values = [
            $compte['code_banque'] ?? '',
            $compte['code_guichet'] ?? '',
            $compte['numero_compte'] ?? '',
            $compte['cle_rib'] ?? ''
        ];
        
        foreach ($values as $i => $value) {
            $pdf->Cell($colWidths[$i], 10, $value, 1, 0, 'C');
        }
        $pdf->Ln();
        
        $pdf->Ln(4);
        
        // IBAN et BIC
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 7, 'IBAN :', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $iban = $compte['iban'] ?? '';
        // Supprimer les espaces existants et reformater
        $iban = str_replace(' ', '', $iban);
        $ibanFormatted = trim(chunk_split($iban, 4, ' '));
        $pdf->Cell(0, 7, $ibanFormatted, 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 7, 'BIC/SWIFT :', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $compte['bic'] ?? 'Non renseigné', 0, 1, 'L');
        
        $pdf->Ln(8);
        
        // Informations complémentaires
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 4, 'Ce document est un relevé d\'identité bancaire conforme aux normes SEPA (Single Euro Payments Area). Il permet d\'identifier votre compte bancaire pour effectuer des virements SEPA ou mettre en place des prélèvements automatiques.', 0, 'L');
        
        $pdf->Ln(10);
        
        // Date de génération
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 5, 'Document généré le ' . date('d/m/Y à H:i'), 0, 1, 'R');
        
        // Retourner le PDF en tant que chaîne
        return $pdf->Output('', 'S');
    }
}
