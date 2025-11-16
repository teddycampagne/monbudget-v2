/**
 * PWA Installation Manager
 * Gère l'enregistrement du Service Worker et le prompt d'installation
 * 
 * @version 2.1.0
 */

(function() {
    'use strict';

    let deferredPrompt = null;
    const installButton = document.getElementById('pwa-install-btn');

    // Enregistrer le Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/monbudgetV2/service-worker.js')
                .then((registration) => {
                    console.log('[PWA] Service Worker enregistré:', registration.scope);
                    
                    // Vérifier les mises à jour toutes les heures
                    setInterval(() => {
                        registration.update();
                    }, 60 * 60 * 1000);
                })
                .catch((error) => {
                    console.error('[PWA] Échec enregistrement Service Worker:', error);
                });
        });

        // Écouter les messages du Service Worker
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'CACHE_CLEARED') {
                console.log('[PWA] Cache vidé');
                showToast('Cache mis à jour', 'success');
            }
        });
    }

    // Vérifier si l'app peut être installée
    function checkInstallability() {
        // Vérifier si déjà installé
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            console.log('[PWA] App déjà installée');
            return;
        }

        // Vérifier le support PWA
        const isHttps = window.location.protocol === 'https:' || window.location.hostname === 'localhost';
        const hasServiceWorker = 'serviceWorker' in navigator;
        const hasManifest = document.querySelector('link[rel="manifest"]');
        
        console.log('[PWA] Vérification installabilité:', {
            isHttps,
            hasServiceWorker,
            hasManifest,
            protocol: window.location.protocol,
            hostname: window.location.hostname
        });

        // Créer le bouton si les conditions sont remplies
        if (isHttps && hasServiceWorker && hasManifest) {
            console.log('[PWA] Conditions remplies, création du bouton...');
            createInstallButton();
        } else {
            console.warn('[PWA] Conditions non remplies pour PWA:', {
                needsHttps: !isHttps,
                needsServiceWorker: !hasServiceWorker,
                needsManifest: !hasManifest
            });
        }
    }

    // Capturer l'événement beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('[PWA] ✅ beforeinstallprompt déclenché');
        
        // Empêcher le prompt automatique
        e.preventDefault();
        
        // Sauvegarder l'événement pour l'utiliser plus tard
        deferredPrompt = e;
        
        // Afficher le bouton d'installation personnalisé
        const button = document.getElementById('pwa-install-btn');
        if (button) {
            button.style.display = 'block';
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-download me-2"></i>Installer l\'application';
            console.log('[PWA] Bouton d\'installation activé');
        }
    });

    // Créer le bouton d'installation
    function createInstallButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('pwa-install-btn')) {
            console.log('[PWA] Bouton déjà créé');
            return;
        }

        const button = document.createElement('button');
        button.id = 'pwa-install-btn';
        button.className = 'btn btn-primary btn-sm position-fixed shadow-lg';
        button.style.cssText = 'bottom: 20px; right: 20px; z-index: 1050; display: block;';
        
        // Si pas de prompt, afficher un message info
        if (!deferredPrompt) {
            button.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                Info PWA
            `;
            button.title = 'Cliquez pour voir comment installer';
            button.addEventListener('click', showInstallInstructions);
        } else {
            button.innerHTML = `
                <i class="bi bi-download me-2"></i>
                Installer l'application
            `;
            button.addEventListener('click', installPWA);
        }
        
        document.body.appendChild(button);
        console.log('[PWA] Bouton créé et ajouté au DOM');
    }

    // Afficher les instructions d'installation
    function showInstallInstructions() {
        const isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
        const isEdge = /Edg/.test(navigator.userAgent);
        const isOpera = /OPR/.test(navigator.userAgent);

        let instructions = '';
        
        if (window.matchMedia('(display-mode: standalone)').matches) {
            instructions = 'L\'application est déjà installée ! 🎉';
        } else if (isEdge) {
            instructions = `
                <strong>Pour installer sur Edge :</strong><br>
                1. Cliquez sur l'icône ⚙️ dans la barre d'adresse<br>
                2. Sélectionnez "Installer MonBudget"<br>
                3. Confirmez l'installation
            `;
        } else if (isChrome) {
            instructions = `
                <strong>Pour installer sur Chrome :</strong><br>
                1. Cliquez sur l'icône ⊕ dans la barre d'adresse<br>
                2. Sélectionnez "Installer MonBudget"<br>
                3. Confirmez l'installation
            `;
        } else if (isOpera) {
            instructions = `
                <strong>Pour installer sur Opera :</strong><br>
                1. Menu → Extensions → Installer MonBudget<br>
                2. Ou icône dans la barre d'adresse
            `;
        } else {
            instructions = `
                <strong>Installation PWA :</strong><br>
                Votre navigateur supporte l'installation PWA.<br>
                Cherchez l'icône d'installation dans la barre d'adresse.
            `;
        }

        // Créer un toast personnalisé plus grand
        const toastHtml = `
            <div class="modal fade" id="pwaInfoModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-app-indicator me-2"></i>
                                Installer MonBudget
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${instructions}
                            <hr>
                            <small class="text-muted">
                                <strong>Avantages :</strong> Accès rapide, mode hors ligne, notifications
                            </small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Supprimer le modal précédent si existe
        const oldModal = document.getElementById('pwaInfoModal');
        if (oldModal) oldModal.remove();

        document.body.insertAdjacentHTML('beforeend', toastHtml);
        const modal = new bootstrap.Modal(document.getElementById('pwaInfoModal'));
        modal.show();
    }

    // Fonction d'installation PWA
    async function installPWA() {
        if (!deferredPrompt) {
            console.log('[PWA] ⚠️ Prompt d\'installation non disponible');
            showInstallInstructions();
            return;
        }

        console.log('[PWA] Affichage du prompt d\'installation...');
        
        // Afficher le prompt d'installation
        deferredPrompt.prompt();

        // Attendre la réponse de l'utilisateur
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`[PWA] Choix utilisateur: ${outcome}`);

        if (outcome === 'accepted') {
            console.log('[PWA] ✅ Installation acceptée');
            showToast('Application installée avec succès !', 'success');
        } else {
            console.log('[PWA] ❌ Installation refusée');
            showToast('Installation annulée', 'info');
        }

        // Réinitialiser le prompt
        deferredPrompt = null;

        // Masquer le bouton
        const button = document.getElementById('pwa-install-btn');
        if (button) {
            button.style.display = 'none';
        }
    }

    // Détecter si l'app est installée
    window.addEventListener('appinstalled', () => {
        console.log('[PWA] Application installée');
        showToast('MonBudget est maintenant installé sur votre appareil !', 'success');
        
        // Masquer le bouton
        const button = document.getElementById('pwa-install-btn');
        if (button) {
            button.style.display = 'none';
        }
    });

    // Détecter le mode standalone (app lancée depuis l'icône)
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        console.log('[PWA] Application lancée en mode standalone');
        document.body.classList.add('pwa-standalone');
        
        // Afficher un badge dans la navbar
        const navbar = document.querySelector('.navbar-brand');
        if (navbar) {
            const badge = document.createElement('span');
            badge.className = 'badge bg-success ms-2';
            badge.innerHTML = '<i class="bi bi-phone"></i> App';
            navbar.appendChild(badge);
        }
    }

    // Gérer l'état de connexion
    function updateOnlineStatus() {
        const status = navigator.onLine ? 'online' : 'offline';
        console.log('[PWA] État connexion:', status);
        
        if (!navigator.onLine) {
            showToast('Vous êtes hors ligne. Certaines fonctionnalités peuvent être limitées.', 'warning');
        } else {
            // Vérifier si on revient en ligne
            if (document.body.classList.contains('offline')) {
                showToast('Connexion rétablie !', 'success');
                // Recharger les données si nécessaire
                if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({ type: 'SYNC' });
                }
            }
        }
        
        document.body.classList.toggle('offline', !navigator.onLine);
        document.body.classList.toggle('online', navigator.onLine);
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Vérifier l'état initial
    updateOnlineStatus();
    
    // Lancer la vérification après chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkInstallability);
    } else {
        // DOM déjà chargé, vérifier immédiatement
        checkInstallability();
    }

    // Fonction utilitaire pour afficher des toasts
    function showToast(message, type = 'info') {
        // Créer le container de toasts si il n'existe pas
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1100';
            document.body.appendChild(toastContainer);
        }

        const toastId = 'toast-' + Date.now();
        const bgClass = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info'
        }[type] || 'bg-info';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();

        // Supprimer le toast après qu'il soit caché
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    // Exposer certaines fonctions globalement
    window.PWA = {
        install: installPWA,
        isInstalled: () => window.matchMedia('(display-mode: standalone)').matches,
        isOnline: () => navigator.onLine,
        clearCache: () => {
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHE' });
            }
        }
    };

    console.log('[PWA] Installation manager chargé');
})();
