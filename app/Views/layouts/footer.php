    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container text-center text-muted">
            <p class="mb-0">
                <i class="bi bi-piggy-bank-fill"></i> MonBudget <?= get_app_version() ?> - 
                Gestion financière personnelle
            </p>
            <small>&copy; <?= date('Y') ?> - Tous droits réservés</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= url('assets/js/app.js') ?>"></script>
    <!-- Date Picker Shortcuts -->
    <script src="<?= url('assets/js/date-picker-shortcuts.js') ?>"></script>
    
    <!-- Script de confirmation personnalisé -->
    <script>
    // Système de confirmation personnalisé avec modal Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        const confirmModalElement = document.getElementById('confirmModal');
        const confirmModal = new bootstrap.Modal(confirmModalElement);
        const confirmModalBody = document.getElementById('confirmModalBody');
        const confirmModalOk = document.getElementById('confirmModalOk');
        let formToSubmit = null;
        
        // Intercepter tous les formulaires avec onsubmit="return confirm(...)"
        const forms = document.querySelectorAll('form[onsubmit*="confirm"]');
        
        forms.forEach(function(form) {
            const originalOnsubmit = form.getAttribute('onsubmit');
            
            // Extraire le message de confirmation
            const match = originalOnsubmit.match(/confirm\(['"](.+?)['"]\)/);
            if (match) {
                const confirmMessage = match[1];
                
                // Supprimer l'ancien onsubmit
                form.removeAttribute('onsubmit');
                
                // Ajouter nouvel événement
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Afficher le message dans le modal
                    confirmModalBody.innerHTML = '<p class="mb-0"><i class="bi bi-question-circle text-warning"></i> ' + confirmMessage + '</p>';
                    
                    // Stocker le formulaire à soumettre
                    formToSubmit = this;
                    
                    // Afficher le modal
                    confirmModal.show();
                    
                    return false;
                });
            }
        });
        
        // Gérer le clic sur OK
        confirmModalOk.addEventListener('click', function() {
            if (formToSubmit) {
                // Stocker la référence dans une variable locale AVANT de masquer le modal
                const formToSubmitNow = formToSubmit;
                formToSubmit = null; // Réinitialiser immédiatement
                
                // Masquer le modal
                confirmModal.hide();
                
                // Attendre un court instant que le modal se cache puis soumettre
                setTimeout(function() {
                    // Utiliser HTMLFormElement.prototype.submit pour bypasser les événements
                    HTMLFormElement.prototype.submit.call(formToSubmitNow);
                }, 150);
            }
        });
        
        // Réinitialiser quand le modal se ferme
        confirmModalElement.addEventListener('hidden.bs.modal', function() {
            if (formToSubmit !== null) {
                formToSubmit = null;
            }
        });
    });
    
    // =====================================================
    // DARK MODE - GESTION DU THEME
    // =====================================================
    (function() {
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        
        // Charger le thème depuis localStorage au chargement de la page
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            htmlElement.setAttribute('data-theme', 'dark');
            if (themeToggle) themeToggle.checked = true;
        }
        
        // Gérer le changement de thème
        if (themeToggle) {
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    htmlElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    htmlElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                }
                
                // Déclencher un événement personnalisé pour les graphiques
                window.dispatchEvent(new CustomEvent('themeChanged', {
                    detail: { theme: this.checked ? 'dark' : 'light' }
                }));
            });
        }
    })();
    </script>
    
    <!-- PWA Service Worker -->
    <script src="<?= url('assets/js/pwa-install.js') ?>"></script>
    
    <!-- Version Manager -->
    <script src="<?= url('assets/js/version-manager.js') ?>"></script>
    
</body>
</html>
