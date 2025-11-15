<?php

namespace MonBudget\Controllers;

/**
 * Contrôleur de documentation utilisateur
 * 
 * Gère l'affichage de la documentation en ligne (guide, FAQ, installation)
 * et la génération de versions PDF téléchargeables.
 * 
 * @package MonBudget\Controllers
 * @author MonBudget
 * @version 1.0.0
 */
class DocumentationController extends BaseController
{
    /**
     * Chemin vers les fichiers de documentation
     * @var string
     */
    private string $docsPath;

    /**
     * Parser Markdown
     * @var \Parsedown
     */
    private $parser;

    public function __construct()
    {
        parent::__construct();
        $this->docsPath = dirname(__DIR__, 2) . '/docs/user/';
        
        // Charger le parser Markdown (Parsedown)
        if (class_exists('\Parsedown')) {
            $this->parser = new \Parsedown();
        }
    }

    /**
     * Page d'accueil de la documentation
     * Affiche la table des matières et les liens vers les différentes sections
     * 
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();

        // Liste des documents disponibles
        $documents = [
            [
                'id' => 'guide',
                'title' => 'Guide utilisateur',
                'icon' => 'book',
                'description' => 'Manuel complet d\'utilisation de l\'application',
                'file' => 'GUIDE.md'
            ],
            [
                'id' => 'faq',
                'title' => 'FAQ - Questions fréquentes',
                'icon' => 'question-circle',
                'description' => 'Réponses aux questions les plus courantes',
                'file' => 'FAQ.md'
            ],
            [
                'id' => 'install',
                'title' => 'Guide d\'installation',
                'icon' => 'gear',
                'description' => 'Instructions pour installer et configurer l\'application',
                'file' => 'INSTALL.md'
            ]
        ];

        $this->view('documentation/index', [
            'title' => 'Documentation',
            'documents' => $documents
        ]);
    }

    /**
     * Afficher un document de documentation
     * Convertit le Markdown en HTML et l'affiche
     * 
     * @param string $document Nom du document (guide, faq, install)
     * @return void
     */
    public function show(string $document): void
    {
        $this->requireAuth();

        // Mapper les noms de documents aux fichiers
        $files = [
            'guide' => 'GUIDE.md',
            'faq' => 'FAQ.md',
            'install' => 'INSTALL.md',
            'readme' => 'README.md'
        ];

        if (!isset($files[$document])) {
            $this->flashAndRedirect('error', 'Document non trouvé', '/documentation');
        }

        $filePath = $this->docsPath . $files[$document];

        if (!file_exists($filePath)) {
            $this->flashAndRedirect('error', 'Le fichier de documentation n\'existe pas', '/documentation');
        }

        // Lire le contenu Markdown
        $markdown = file_get_contents($filePath);

        // Convertir en HTML
        $html = $this->parser ? $this->parser->text($markdown) : nl2br(htmlspecialchars($markdown));

        // Extraire le titre (première ligne H1)
        $title = 'Documentation';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            $title = $matches[1];
        }

        $this->view('documentation/show', [
            'title' => $title,
            'document' => $document,
            'documentId' => $document,
            'content' => $html,
            'hasParser' => $this->parser !== null
        ]);
    }

    /**
     * Télécharger la documentation en PDF
     * Génère un PDF à partir du document Markdown
     * 
     * @param string $document Nom du document
     * @return void
     */
    public function downloadPdf(string $document): void
    {
        $this->requireAuth();

        // Mapper les noms de documents aux fichiers
        $files = [
            'guide' => 'GUIDE.md',
            'faq' => 'FAQ.md',
            'install' => 'INSTALL.md'
        ];

        if (!isset($files[$document])) {
            $this->flashAndRedirect('error', 'Document non trouvé', '/documentation');
        }

        $filePath = $this->docsPath . $files[$document];

        if (!file_exists($filePath)) {
            $this->flashAndRedirect('error', 'Le fichier de documentation n\'existe pas', '/documentation');
        }

        // Vérifier que TCPDF est disponible
        if (!class_exists('TCPDF')) {
            $this->flashAndRedirect('error', 'La génération PDF n\'est pas disponible (TCPDF manquant)', '/documentation');
        }

        // Lire et convertir le Markdown
        $markdown = file_get_contents($filePath);
        $html = $this->parser ? $this->parser->text($markdown) : nl2br(htmlspecialchars($markdown));

        // Extraire le titre
        $title = 'Documentation';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            $title = $matches[1];
        }

        // Créer le PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Métadonnées
        $pdf->SetCreator('MonBudget');
        $pdf->SetAuthor('MonBudget');
        $pdf->SetTitle($title);
        $pdf->SetSubject('Documentation');

        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // En-tête et pied de page
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // Ajouter une page
        $pdf->AddPage();

        // Titre
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->Ln(10);

        // Contenu
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Nom du fichier
        $filename = strtolower($document) . '_' . date('Y-m-d') . '.pdf';

        // Téléchargement
        $pdf->Output($filename, 'D');
    }

    /**
     * Rechercher dans la documentation
     * 
     * @return void
     */
    public function search(): void
    {
        $this->requireAuth();

        $query = $_GET['q'] ?? '';

        if (strlen($query) < 3) {
            $this->json(['results' => [], 'message' => 'Requête trop courte (minimum 3 caractères)']);
        }

        $results = [];

        // Fichiers à rechercher
        $files = [
            'guide' => 'GUIDE.md',
            'faq' => 'FAQ.md',
            'install' => 'INSTALL.md'
        ];

        foreach ($files as $key => $file) {
            $filePath = $this->docsPath . $file;
            if (!file_exists($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                // Recherche insensible à la casse
                if (stripos($line, $query) !== false) {
                    // Contexte : ligne précédente et suivante
                    $context = [];
                    if ($lineNum > 0) {
                        $context[] = $lines[$lineNum - 1];
                    }
                    $context[] = $line;
                    if ($lineNum < count($lines) - 1) {
                        $context[] = $lines[$lineNum + 1];
                    }

                    $results[] = [
                        'document' => $key,
                        'documentTitle' => ucfirst($key),
                        'line' => $lineNum + 1,
                        'excerpt' => implode(' ', $context),
                        'url' => '/documentation/' . $key . '#L' . ($lineNum + 1)
                    ];

                    // Limiter à 5 résultats par document
                    if (count(array_filter($results, fn($r) => $r['document'] === $key)) >= 5) {
                        break;
                    }
                }
            }
        }

        $this->json([
            'query' => $query,
            'count' => count($results),
            'results' => $results
        ]);
    }

    /**
     * Afficher l'aide contextuelle
     * Popup d'aide selon la page courante
     * 
     * @param string $context Contexte de la page (comptes, transactions, budgets, etc.)
     * @return void
     */
    public function contextHelp(string $context): void
    {
        $this->requireAuth();

        // Mapper les contextes aux sections du guide
        $helpSections = [
            'comptes' => [
                'title' => 'Aide - Gestion des comptes',
                'content' => $this->getHelpSection('GUIDE.md', 'Gestion des comptes')
            ],
            'transactions' => [
                'title' => 'Aide - Transactions',
                'content' => $this->getHelpSection('GUIDE.md', 'Transactions')
            ],
            'budgets' => [
                'title' => 'Aide - Budgets',
                'content' => $this->getHelpSection('GUIDE.md', 'Gestion des budgets')
            ],
            'rapports' => [
                'title' => 'Aide - Rapports',
                'content' => $this->getHelpSection('GUIDE.md', 'Rapports et statistiques')
            ],
            'import' => [
                'title' => 'Aide - Import de fichiers',
                'content' => $this->getHelpSection('GUIDE.md', 'Import de transactions')
            ],
            'automatisation' => [
                'title' => 'Aide - Automatisation',
                'content' => $this->getHelpSection('GUIDE.md', 'Automatisation')
            ]
        ];

        $help = $helpSections[$context] ?? [
            'title' => 'Aide',
            'content' => 'Aucune aide disponible pour cette section.'
        ];

        // Convertir le Markdown en HTML
        if ($this->parser && is_string($help['content'])) {
            $help['content'] = $this->parser->text($help['content']);
        }

        $this->json($help);
    }

    /**
     * Extraire une section spécifique du guide
     * 
     * @param string $file Nom du fichier
     * @param string $sectionTitle Titre de la section (H2)
     * @return string|null Contenu de la section
     */
    private function getHelpSection(string $file, string $sectionTitle): ?string
    {
        $filePath = $this->docsPath . $file;
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        
        // Trouver la section
        $pattern = '/^##\s+' . preg_quote($sectionTitle, '/') . '\s*\n(.*?)(?=\n##|\Z)/ms';
        
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Enregistrer le feedback utilisateur sur une page de documentation
     * 
     * @return void
     */
    public function feedback(): void
    {
        $this->requireAuth();

        $data = json_decode(file_get_contents('php://input'), true);
        $document = $data['document'] ?? '';
        $helpful = $data['helpful'] ?? false;

        // Log du feedback (optionnel)
        $logFile = dirname(__DIR__, 2) . '/storage/logs/documentation_feedback.log';
        $logEntry = date('Y-m-d H:i:s') . " | Document: {$document} | Helpful: " . ($helpful ? 'Yes' : 'No') . " | User: " . ($_SESSION['user_id'] ?? 'Unknown') . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        $this->json([
            'success' => true,
            'message' => 'Merci pour votre retour !'
        ]);
    }
}
