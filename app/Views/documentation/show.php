<?php
$pageTitle = $title ?? 'Documentation';
include base_path('app/Views/layouts/header.php');
?>

<style>
/* Sidebar navigation */
.doc-sidebar {
    position: sticky;
    top: 80px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}

.doc-sidebar .nav-link {
    padding: 0.5rem 1rem;
    color: #6c757d;
    border-left: 3px solid transparent;
}

.doc-sidebar .nav-link:hover {
    color: #0d6efd;
    background-color: #f8f9fa;
}

.doc-sidebar .nav-link.active {
    color: #0d6efd;
    border-left-color: #0d6efd;
    background-color: #f8f9fa;
    font-weight: 600;
}

.doc-sidebar .nav-link.sub-section {
    padding-left: 2rem;
    font-size: 0.9rem;
}

/* Contenu markdown */
.doc-content {
    font-size: 1rem;
    line-height: 1.7;
}

.doc-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
}

.doc-content h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-top: 3rem;
    margin-bottom: 1rem;
    padding-top: 1rem;
    color: #0d6efd;
}

.doc-content h3 {
    font-size: 1.5rem;
    font-weight: 500;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #495057;
}

.doc-content h4 {
    font-size: 1.25rem;
    font-weight: 500;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.doc-content code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
    color: #d63384;
}

.doc-content pre {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    overflow-x: auto;
    margin: 1rem 0;
}

.doc-content pre code {
    background-color: transparent;
    padding: 0;
    color: #212529;
    font-size: 0.875rem;
}

.doc-content table {
    width: 100%;
    margin: 1.5rem 0;
    border-collapse: collapse;
}

.doc-content table th,
.doc-content table td {
    padding: 0.75rem;
    border: 1px solid #dee2e6;
}

.doc-content table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.doc-content table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.doc-content blockquote {
    border-left: 4px solid #0d6efd;
    padding-left: 1rem;
    margin: 1.5rem 0;
    color: #6c757d;
    font-style: italic;
}

.doc-content ul,
.doc-content ol {
    margin: 1rem 0;
    padding-left: 2rem;
}

.doc-content li {
    margin: 0.5rem 0;
}

.doc-content img {
    max-width: 100%;
    height: auto;
    margin: 1.5rem 0;
    border-radius: 0.375rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
}

.doc-content a {
    color: #0d6efd;
    text-decoration: underline;
}

.doc-content a:hover {
    color: #0a58ca;
}

/* Contrôles de police */
.font-controls {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.font-controls .btn-group {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Print */
@media print {
    .doc-sidebar,
    .font-controls,
    .btn,
    .navbar,
    .card-header {
        display: none !important;
    }
    
    .doc-content {
        font-size: 12pt;
    }
    
    .doc-content h2 {
        page-break-before: always;
    }
    
    .doc-content pre,
    .doc-content table {
        page-break-inside: avoid;
    }
}

/* Code block actions */
.code-block-wrapper {
    position: relative;
}

.code-block-wrapper .copy-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.code-block-wrapper:hover .copy-btn {
    opacity: 1;
}

/* Feedback */
.feedback-section {
    border-top: 2px solid #dee2e6;
    margin-top: 3rem;
    padding-top: 2rem;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="doc-sidebar">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Sommaire</h6>
                    </div>
                    <div class="card-body p-0">
                        <nav class="nav flex-column" id="docNav">
                            <!-- Généré dynamiquement par JavaScript -->
                        </nav>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="<?= url('documentation') ?>" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <a href="<?= url('documentation/' . htmlspecialchars($documentId) . '/pdf') ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="bi bi-file-pdf"></i> Télécharger PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-book-half"></i> <?= htmlspecialchars($title) ?>
                    </h4>
                    <div class="d-lg-none">
                        <a href="<?= url('documentation') ?>" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="doc-content" id="docContent">
                        <?= $content ?>
                    </div>

                    <!-- Feedback -->
                    <div class="feedback-section">
                        <h5 class="mb-3"><i class="bi bi-chat-square-text"></i> Cette page vous a-t-elle été utile ?</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-success" onclick="submitFeedback(true)">
                                <i class="bi bi-hand-thumbs-up"></i> Oui
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="submitFeedback(false)">
                                <i class="bi bi-hand-thumbs-down"></i> Non
                            </button>
                        </div>
                        <div id="feedbackMessage" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contrôles de taille de police -->
<div class="font-controls">
    <div class="btn-group-vertical" role="group">
        <button type="button" class="btn btn-light" onclick="changeFontSize(1)" title="Agrandir le texte">
            <i class="bi bi-plus-lg"></i>
        </button>
        <button type="button" class="btn btn-light" onclick="changeFontSize(0)" title="Taille normale">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
        <button type="button" class="btn btn-light" onclick="changeFontSize(-1)" title="Réduire le texte">
            <i class="bi bi-dash-lg"></i>
        </button>
    </div>
</div>

<script>
// Génération du sommaire
document.addEventListener('DOMContentLoaded', function() {
    const content = document.getElementById('docContent');
    const nav = document.getElementById('docNav');
    
    if (!content || !nav) return;

    const headings = content.querySelectorAll('h2, h3');
    headings.forEach((heading, index) => {
        const id = 'section-' + index;
        heading.id = id;

        const link = document.createElement('a');
        link.href = '#' + id;
        link.className = 'nav-link';
        if (heading.tagName === 'H3') {
            link.classList.add('sub-section');
        }
        link.textContent = heading.textContent;
        
        nav.appendChild(link);
    });

    // Scroll spy
    let observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                document.querySelectorAll('.doc-sidebar .nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                const activeLink = document.querySelector(`.doc-sidebar .nav-link[href="#${entry.target.id}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
        });
    }, {
        rootMargin: '-100px 0px -66%'
    });

    headings.forEach(heading => observer.observe(heading));

    // Copie de code
    document.querySelectorAll('pre').forEach(pre => {
        const wrapper = document.createElement('div');
        wrapper.className = 'code-block-wrapper';
        pre.parentNode.insertBefore(wrapper, pre);
        wrapper.appendChild(pre);

        const copyBtn = document.createElement('button');
        copyBtn.className = 'btn btn-sm btn-light copy-btn';
        copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
        copyBtn.title = 'Copier le code';
        copyBtn.onclick = function() {
            const code = pre.textContent;
            navigator.clipboard.writeText(code).then(() => {
                copyBtn.innerHTML = '<i class="bi bi-check"></i>';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
                }, 2000);
            });
        };
        wrapper.appendChild(copyBtn);
    });
});

// Gestion de la taille de police
let currentFontSize = 1;
function changeFontSize(delta) {
    const content = document.getElementById('docContent');
    if (!content) return;

    if (delta === 0) {
        currentFontSize = 1;
    } else {
        currentFontSize += delta * 0.1;
        currentFontSize = Math.max(0.8, Math.min(1.4, currentFontSize));
    }
    
    content.style.fontSize = currentFontSize + 'rem';
    localStorage.setItem('docFontSize', currentFontSize);
}

// Restaurer la taille de police
const savedFontSize = localStorage.getItem('docFontSize');
if (savedFontSize) {
    currentFontSize = parseFloat(savedFontSize);
    document.getElementById('docContent').style.fontSize = currentFontSize + 'rem';
}

// Feedback
function submitFeedback(helpful) {
    const documentId = '<?= htmlspecialchars($documentId) ?>';
    const messageDiv = document.getElementById('feedbackMessage');
    
    fetch('<?= url('documentation/feedback') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            document: documentId,
            helpful: helpful
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> Merci pour votre retour !</div>';
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 3000);
        }
    })
    .catch(err => {
        messageDiv.innerHTML = '<div class="alert alert-danger">Erreur lors de l\'envoi du feedback.</div>';
    });
}
</script>

<?php include base_path('app/Views/layouts/footer.php'); ?>
