<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relevé de Compte - <?= htmlspecialchars($compte['nom']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .info-compte {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .info-compte p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .credit {
            color: #28a745;
            font-weight: bold;
        }
        .debit {
            color: #dc3545;
            font-weight: bold;
        }
        .totaux {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border: 2px solid #333;
        }
        .totaux table {
            margin: 0;
        }
        .totaux th {
            background-color: #6c757d;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10pt;
            color: #6c757d;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .solde-final {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
        }
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELEVÉ DE COMPTE</h1>
        <p style="margin: 0; color: #6c757d;">
            <?php 
            $moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                         'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            echo $moisNoms[$mois] . ' ' . $annee;
            ?>
        </p>
    </div>
    
    <div class="info-compte">
        <p><strong>Compte :</strong> <?= htmlspecialchars($compte['nom']) ?></p>
        <?php if (!empty($compte['numero'])): ?>
            <p><strong>Numéro :</strong> <?= htmlspecialchars($compte['numero']) ?></p>
        <?php endif; ?>
        <?php if (!empty($compte['banque_nom'])): ?>
            <p><strong>Banque :</strong> <?= htmlspecialchars($compte['banque_nom']) ?></p>
        <?php endif; ?>
        <p><strong>Date d'édition :</strong> <?= date('d/m/Y H:i') ?></p>
    </div>
    
    <?php if (empty($transactions)): ?>
        <p style="text-align: center; padding: 40px; color: #6c757d;">
            Aucune transaction pour cette période.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 43%;">Libellé</th>
                    <th style="width: 20%;">Catégorie</th>
                    <th style="width: 15%; text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalCredit = 0;
                $totalDebit = 0;
                
                foreach ($transactions as $trans): 
                    if ($trans['type_operation'] === 'credit') {
                        $totalCredit += (float) $trans['montant'];
                    } else {
                        $totalDebit += (float) $trans['montant'];
                    }
                ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($trans['date_transaction'])) ?></td>
                        <td>
                            <?php if ($trans['type_operation'] === 'credit'): ?>
                                <span style="color: #28a745;">●</span> Crédit
                            <?php else: ?>
                                <span style="color: #dc3545;">●</span> Débit
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($trans['libelle'] ?: 'Sans libellé') ?>
                            <?php if (!empty($trans['tiers'])): ?>
                                <br><small style="color: #6c757d;"><?= htmlspecialchars($trans['tiers']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($trans['categorie_nom'] ?? 'Non catégorisé') ?>
                            <?php if (!empty($trans['sous_categorie_nom'])): ?>
                                <br><small style="color: #6c757d;"><?= htmlspecialchars($trans['sous_categorie_nom']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;" class="<?= $trans['type_operation'] ?>">
                            <?= $trans['type_operation'] === 'credit' ? '+' : '-' ?>
                            <?= number_format($trans['montant'], 2, ',', ' ') ?> €
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totaux">
            <h3 style="margin-top: 0;">Récapitulatif</h3>
            <table>
                <tr>
                    <th style="width: 70%;">Total Crédits</th>
                    <td style="text-align: right;" class="credit">
                        + <?= number_format($totalCredit, 2, ',', ' ') ?> €
                    </td>
                </tr>
                <tr>
                    <th>Total Débits</th>
                    <td style="text-align: right;" class="debit">
                        - <?= number_format($totalDebit, 2, ',', ' ') ?> €
                    </td>
                </tr>
                <tr style="background-color: #e9ecef;">
                    <th>Balance du mois</th>
                    <td style="text-align: right;" class="solde-final">
                        <?= number_format($totalCredit - $totalDebit, 2, ',', ' ') ?> €
                    </td>
                </tr>
                <tr>
                    <th>Nombre de transactions</th>
                    <td style="text-align: right;">
                        <?= count($transactions) ?>
                    </td>
                </tr>
                <tr style="background-color: #d1ecf1; border-top: 2px solid #0c5460;">
                    <th style="color: #0c5460;">Solde au <?= date('d/m/Y', strtotime("$annee-$mois-" . date('t', strtotime("$annee-$mois-01")))) ?></th>
                    <td style="text-align: right; color: #0c5460;" class="solde-final">
                        <?= number_format($solde_a_date, 2, ',', ' ') ?> €
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="footer">
        <p>Document généré automatiquement le <?= date('d/m/Y à H:i:s') ?></p>
        <p>MonBudget - Gestion de finances personnelles</p>
    </div>
    
    <div class="no-print" style="position: fixed; bottom: 20px; right: 20px;">
        <button onclick="window.print()" style="padding: 12px 24px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14pt;">
            <span style="margin-right: 8px;">🖨️</span> Imprimer / Sauvegarder en PDF
        </button>
        <button onclick="fermerEtRetour()" style="padding: 12px 24px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14pt; margin-left: 10px;">
            Retour
        </button>
    </div>
    
    <script>
        function fermerEtRetour() {
            // Si la page a été ouverte depuis un autre onglet (target="_blank")
            if (window.opener && !window.opener.closed) {
                // Remettre le focus sur la fenêtre parent
                window.opener.focus();
                // Fermer cet onglet
                window.close();
            } else {
                // Sinon, utiliser l'historique pour revenir en arrière
                window.location.href = '<?= url('rapports') ?>';
            }
        }
    </script>
</body>
</html>
