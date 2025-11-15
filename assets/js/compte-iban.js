/**
 * Gestion automatique IBAN / RIB pour les comptes bancaires
 * 
 * Fonctionnalités:
 * - Calcul automatique de la clé RIB
 * - Génération d'IBAN depuis RIB
 * - Extraction des composants RIB depuis IBAN
 * - Validation en temps réel
 * 
 * @version 2.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    const codeGuichet = document.getElementById('code_guichet');
    const numeroCompte = document.getElementById('numero_compte');
    const cleRib = document.getElementById('cle_rib');
    const iban = document.getElementById('iban');
    const codeBanque = document.getElementById('banque_id');

    // Si les champs n'existent pas sur cette page, on sort
    if (!codeGuichet || !numeroCompte || !cleRib || !iban) {
        return;
    }

    /**
     * Calcule la clé RIB à partir du code banque, code guichet et numéro de compte
     * Algorithme officiel français : la clé est le nombre qui, ajouté au RIB, donne modulo 97 = 0
     * 
     * @param {string} codeBanque - Code banque (5 chiffres)
     * @param {string} codeGuichet - Code guichet (5 chiffres)
     * @param {string} numeroCompte - Numéro de compte (11 caractères alphanumériques)
     * @returns {string} Clé RIB (2 chiffres) ou chaîne vide si invalide
     * @example
     * calculerCleRib('30004', '00123', '12345678901'); // "76"
     */
    function calculerCleRib(codeBanque, codeGuichet, numeroCompte) {
        if (!codeBanque || !codeGuichet || !numeroCompte) {
            return '';
        }

        // Nettoyer les valeurs (retirer espaces et mettre en majuscules)
        codeBanque = codeBanque.replace(/\s/g, '').toUpperCase().padStart(5, '0');
        codeGuichet = codeGuichet.replace(/\s/g, '').toUpperCase().padStart(5, '0');
        numeroCompte = numeroCompte.replace(/\s/g, '').toUpperCase().padStart(11, '0');

        // Vérifier les longueurs
        if (codeBanque.length !== 5 || codeGuichet.length !== 5 || numeroCompte.length !== 11) {
            return '';
        }

        // Algorithme officiel de calcul de la clé RIB
        // On calcule le modulo 97 du RIB (sans la clé)
        const rib = codeBanque + codeGuichet + numeroCompte;
        
        // Table de conversion des lettres (norme bancaire française)
        const tableConversion = {
            '0': 0, '1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8, '9': 9,
            'A': 1, 'B': 2, 'C': 3, 'D': 4, 'E': 5, 'F': 6, 'G': 7, 'H': 8, 'I': 9,
            'J': 1, 'K': 2, 'L': 3, 'M': 4, 'N': 5, 'O': 6, 'P': 7, 'Q': 8, 'R': 9,
            'S': 2, 'T': 3, 'U': 4, 'V': 5, 'W': 6, 'X': 7, 'Y': 8, 'Z': 9
        };

        // Calculer le modulo 97 du RIB multiplié par 100 (pour faire de la place aux 2 chiffres de la clé)
        let valeur = 0;
        
        for (let i = 0; i < rib.length; i++) {
            const char = rib[i];
            const chiffre = tableConversion[char];
            
            if (chiffre === undefined) {
                console.error('Caractère invalide dans le RIB:', char);
                return '';
            }
            
            // Multiplier par 10 et ajouter le chiffre, puis modulo 97
            valeur = ((valeur * 10) + chiffre) % 97;
        }
        
        // Multiplier par 100 pour faire de la place aux 2 chiffres de la clé
        valeur = (valeur * 100) % 97;
        
        // La clé est le complément à 97 (ce qui donnera 0 quand on l'ajoute)
        const cle = (97 - valeur) % 97;
        
        return cle.toString().padStart(2, '0');
    }

    /**
     * Génère l'IBAN français à partir du RIB complet
     * Format: FR + clé IBAN (2) + code banque (5) + code guichet (5) + numéro compte (11) + clé RIB (2)
     * 
     * @param {string} codeBanque - Code banque (5 chiffres)
     * @param {string} codeGuichet - Code guichet (5 chiffres)
     * @param {string} numeroCompte - Numéro de compte (11 caractères)
     * @param {string} cleRib - Clé RIB (2 chiffres)
     * @returns {string} IBAN formaté avec espaces (ex: "FR76 3000 4001 2312 3456 7890 176") ou vide si invalide
     * @example
     * genererIban('30004', '00123', '12345678901', '76'); // "FR76 3000 4001 2312 3456 7890 176"
     */
    function genererIban(codeBanque, codeGuichet, numeroCompte, cleRib) {
        if (!codeBanque || !codeGuichet || !numeroCompte || !cleRib) {
            return '';
        }

        // Nettoyer les valeurs
        codeBanque = codeBanque.replace(/\s/g, '').padStart(5, '0');
        codeGuichet = codeGuichet.replace(/\s/g, '').padStart(5, '0');
        numeroCompte = numeroCompte.replace(/\s/g, '').padStart(11, '0');
        cleRib = cleRib.replace(/\s/g, '').padStart(2, '0');

        // Format: FR + clé IBAN (2 chiffres) + code banque (5) + code guichet (5) + numéro compte (11) + clé RIB (2)
        const bban = codeBanque + codeGuichet + numeroCompte + cleRib;
        
        // Calculer la clé IBAN
        // On ajoute FR00 à la fin du BBAN, puis on convertit
        const temp = bban + '1527' + '00'; // FR = 1527 en numérique
        
        // Calcul modulo 97
        let reste = BigInt(temp) % 97n;
        const cleIban = 98n - reste;
        
        const ibanComplet = 'FR' + cleIban.toString().padStart(2, '0') + bban;
        
        // Formater avec espaces tous les 4 caractères
        return ibanComplet.match(/.{1,4}/g).join(' ');
    }

    /**
     * Extrait les composants RIB d'un IBAN français
     * 
     * @param {string} ibanValue - IBAN français (27 caractères)
     * @returns {Object|null} Objet contenant {codeBanque, codeGuichet, numeroCompte, cleRib} ou null si invalide
     * @example
     * extraireIban('FR76 3000 4001 2312 3456 7890 176');
     * // { codeBanque: '30004', codeGuichet: '00123', numeroCompte: '12345678901', cleRib: '76' }
     */
    function extraireIban(ibanValue) {
        // Nettoyer l'IBAN (retirer espaces et mettre en majuscules)
        ibanValue = ibanValue.replace(/\s/g, '').toUpperCase();
        
        // Vérifier que c'est un IBAN français (27 caractères : FR + 2 chiffres + 23 chiffres)
        if (!ibanValue.match(/^FR\d{25}$/)) {
            return null;
        }

        // Extraire les composants
        // FR XX YYYYY ZZZZZ CCCCCCCCCCC KK
        // XX = clé IBAN (2)
        // YYYYY = code banque (5)
        // ZZZZZ = code guichet (5)
        // CCCCCCCCCCC = numéro de compte (11)
        // KK = clé RIB (2)
        
        const codeBanque = ibanValue.substring(4, 9);
        const codeGuichet = ibanValue.substring(9, 14);
        const numeroCompte = ibanValue.substring(14, 25);
        const cleRib = ibanValue.substring(25, 27);

        return {
            codeBanque,
            codeGuichet,
            numeroCompte,
            cleRib
        };
    }

    /**
     * Obtient le code banque depuis le select de banque
     * Extrait le code entre parenthèses dans le texte de l'option (format: "Nom (12345)")
     * 
     * @returns {string} Code banque (5 chiffres) ou chaîne vide si non trouvé
     */
    function getCodeBanque() {
        const banqueSelect = document.getElementById('banque_id');
        if (!banqueSelect || !banqueSelect.value) {
            return '';
        }

        // Le code banque doit être récupéré depuis les options
        // On va l'extraire du texte de l'option sélectionnée qui contient (XXXXX)
        const selectedOption = banqueSelect.options[banqueSelect.selectedIndex];
        const match = selectedOption.text.match(/\((\d+)\)/);
        
        return match ? match[1] : '';
    }

    /**
     * Gestionnaire: Code guichet, N° compte, Clé RIB => Générer IBAN
     * Génère automatiquement l'IBAN lorsque tous les champs RIB sont remplis
     */
    function onRibChange() {
        const codeBanqueValue = getCodeBanque();
        const codeGuichetValue = codeGuichet.value.trim();
        const numeroCompteValue = numeroCompte.value.trim();
        const cleRibValue = cleRib.value.trim();

        // Si tous les champs RIB sont remplis, générer l'IBAN
        if (codeBanqueValue && codeGuichetValue && numeroCompteValue && cleRibValue) {
            const ibanGenere = genererIban(codeBanqueValue, codeGuichetValue, numeroCompteValue, cleRibValue);
            if (ibanGenere) {
                iban.value = ibanGenere;
                iban.classList.add('is-valid');
            }
        }
    }

    /**
     * Gestionnaire: IBAN => Extraire code guichet, N° compte, Clé RIB
     * Remplit automatiquement les champs RIB lorsqu'un IBAN valide est saisi
     */
    function onIbanChange() {
        const ibanValue = iban.value.trim();
        
        if (ibanValue.length >= 27) {
            const composants = extraireIban(ibanValue);
            
            if (composants) {
                codeGuichet.value = composants.codeGuichet;
                numeroCompte.value = composants.numeroCompte;
                cleRib.value = composants.cleRib;
                
                // Marquer les champs comme valides
                codeGuichet.classList.add('is-valid');
                numeroCompte.classList.add('is-valid');
                cleRib.classList.add('is-valid');
                iban.classList.add('is-valid');
            } else {
                iban.classList.remove('is-valid');
                iban.classList.add('is-invalid');
            }
        }
    }

    /**
     * Calcul automatique de la clé RIB
     * Déclenché lorsque le code guichet et le numéro de compte sont complets
     * Génère ensuite automatiquement l'IBAN
     */
    function autoCalculerCleRib() {
        const codeBanqueValue = getCodeBanque();
        const codeGuichetValue = codeGuichet.value.trim();
        const numeroCompteValue = numeroCompte.value.trim();

        // Si code guichet et numéro de compte sont remplis, calculer la clé
        if (codeBanqueValue && codeGuichetValue.length === 5 && numeroCompteValue.length === 11) {
            const cleCalculee = calculerCleRib(codeBanqueValue, codeGuichetValue, numeroCompteValue);
            if (cleCalculee && !cleRib.value) {
                cleRib.value = cleCalculee;
                cleRib.classList.add('is-valid');
                
                // Déclencher la génération de l'IBAN
                onRibChange();
            }
        }
    }

    // Écouter les changements
    codeGuichet.addEventListener('input', function() {
        this.classList.remove('is-valid', 'is-invalid');
        autoCalculerCleRib();
    });

    numeroCompte.addEventListener('input', function() {
        this.classList.remove('is-valid', 'is-invalid');
        autoCalculerCleRib();
    });

    cleRib.addEventListener('input', function() {
        this.classList.remove('is-valid', 'is-invalid');
        onRibChange();
    });

    iban.addEventListener('input', function() {
        this.classList.remove('is-valid', 'is-invalid');
        onIbanChange();
    });

    // Écouter aussi le changement de banque
    if (codeBanque) {
        codeBanque.addEventListener('change', function() {
            autoCalculerCleRib();
        });
    }
});
