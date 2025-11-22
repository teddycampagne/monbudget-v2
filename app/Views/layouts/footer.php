    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container text-center text-muted">
            <p class="mb-0">
                <i class="bi bi-piggy-bank-fill"></i> MonBudget v2.0 - 
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
    
    // Gestionnaire de notifications
    (function() {
        let notificationCheckInterval;
        
        function loadNotifications() {
            fetch('<?= url('notifications/latest') ?>')
                .then(response => response.json())
                .then(data => {
                    updateNotificationDropdown(data.notifications || []);
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des notifications:', error);
                });
        }
        
        function updateNotificationDropdown(notifications) {
            const badge = document.getElementById('notificationBadge');
            const list = document.getElementById('notificationList');
            const noNotifications = document.getElementById('noNotifications');
            
            // Compter les non lues
            const unreadCount = notifications.filter(n => !n.is_read).length;
            
            // Mettre à jour le badge
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
            
            // Nettoyer les anciennes notifications
            const existingItems = list.querySelectorAll('.notification-item');
            existingItems.forEach(item => item.remove());
            
            if (notifications.length === 0) {
                noNotifications.style.display = 'block';
                return;
            }
            
            noNotifications.style.display = 'none';
            
            // Ajouter les nouvelles notifications
            notifications.slice(0, 5).forEach(notification => {
                const li = document.createElement('li');
                li.className = 'notification-item';
                
                const a = document.createElement('a');
                a.className = 'dropdown-item d-flex align-items-start';
                a.href = '#';
                a.onclick = () => markAsRead(notification.id);
                
                const icon = document.createElement('i');
                icon.className = `bi bi-${getNotificationIcon(notification.type)} me-2 mt-1`;
                a.appendChild(icon);
                
                const content = document.createElement('div');
                content.className = 'flex-grow-1';
                
                const title = document.createElement('div');
                title.className = 'fw-bold small';
                title.textContent = notification.title;
                content.appendChild(title);
                
                const message = document.createElement('div');
                message.className = 'small text-muted';
                message.textContent = notification.message.length > 50 ? 
                    notification.message.substring(0, 50) + '...' : 
                    notification.message;
                content.appendChild(message);
                
                const time = document.createElement('small');
                time.className = 'text-muted';
                time.textContent = formatTimeAgo(notification.created_at);
                content.appendChild(time);
                
                a.appendChild(content);
                
                if (!notification.is_read) {
                    const unreadDot = document.createElement('span');
                    unreadDot.className = 'badge bg-primary ms-2';
                    unreadDot.textContent = 'Nouveau';
                    a.appendChild(unreadDot);
                }
                
                li.appendChild(a);
                list.insertBefore(li, list.lastElementChild);
            });
        }
        
        function markAsRead(notificationId) {
            fetch('<?= url('notifications/mark-read') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            })
            .catch(error => {
                console.error('Erreur lors du marquage:', error);
            });
        }
        
        function getNotificationIcon(type) {
            switch(type) {
                case 'budget_alert': return 'exclamation-triangle';
                case 'system': return 'gear';
                case 'info': return 'info-circle';
                case 'warning': return 'exclamation-circle';
                case 'error': return 'x-circle';
                default: return 'bell';
            }
        }
        
        function formatTimeAgo(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);
            
            if (diffMins < 1) return 'À l\'instant';
            if (diffMins < 60) return `Il y a ${diffMins} min`;
            if (diffHours < 24) return `Il y a ${diffHours} h`;
            return `Il y a ${diffDays} j`;
        }
        
        // Charger les notifications au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            
            // Vérifier les nouvelles notifications toutes les 30 secondes
            notificationCheckInterval = setInterval(loadNotifications, 30000);
        });
        
        // Nettoyer l'intervalle quand la page se ferme
        window.addEventListener('beforeunload', function() {
            if (notificationCheckInterval) {
                clearInterval(notificationCheckInterval);
            }
        });
    })();
    </script>
    
    <!-- PWA Service Worker -->
    <script src="<?= url('assets/js/pwa-install.js') ?>"></script>
    
    <!-- Version Manager -->
    <script src="<?= url('assets/js/version-manager.js') ?>"></script>
    
</body>
</html>
