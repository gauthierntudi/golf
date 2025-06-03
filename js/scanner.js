document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des éléments UI avec vérification
    const reader = document.getElementById('reader');
    const resultContainer = document.getElementById('result');
    
    if (!reader || !resultContainer) {
        console.error('Éléments DOM requis non trouvés');
        return;
    }

    const scanningIcon = resultContainer.querySelector('.scanning-icon');
    const successIcon = resultContainer.querySelector('.success-icon');
    const errorIcon = resultContainer.querySelector('.error-icon');
    const resultTitle = resultContainer.querySelector('.result-title');
    const resultMessage = resultContainer.querySelector('.result-message');
    const guestDetails = resultContainer.querySelector('.guest-details');
    
    // Vérifier que tous les éléments nécessaires sont présents
    if (!scanningIcon || !successIcon || !errorIcon || !resultTitle || !resultMessage || !guestDetails) {
        console.error('Éléments UI requis non trouvés');
        return;
    }

    let html5QrcodeScanner = null;
    let isScanning = false;
    let scannerPaused = false;

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };

    // Fonction pour afficher/masquer les résultats
    async function toggleResultContainer(show) {
        if (!resultContainer) return;

        if (show) {
            resultContainer.classList.remove('hidden');
            resultContainer.classList.add('visible');
            scannerPaused = true;
            
            if (isScanning && html5QrcodeScanner) {
                try {
                    await html5QrcodeScanner.pause();
                } catch (err) {
                    console.error("Erreur lors de la pause du scanner:", err);
                }
            }
        } else {
            resultContainer.classList.remove('visible');
            resultContainer.classList.add('hidden');
            scanningIcon.classList.remove('hidden');
            successIcon.classList.add('hidden');
            errorIcon.classList.add('hidden');
            guestDetails.classList.add('hidden');
            resultTitle.textContent = 'Vérification en cours...';
            resultMessage.textContent = '';
            
            // Supprimer l'ancien bouton de fermeture s'il existe
            const oldCloseButton = resultContainer.querySelector('.btn-primary');
            if (oldCloseButton) {
                oldCloseButton.remove();
            }
            
            // Redémarrer le scanner
            scannerPaused = false;
            if (isScanning && html5QrcodeScanner) {
                try {
                    await html5QrcodeScanner.resume();
                } catch (err) {
                    console.error("Erreur lors de la reprise du scanner:", err);
                }
            }
        }
    }

    // Fonction de vérification de l'invitation
    async function verifyInvitation(code) {
        if (scannerPaused || !resultContainer) return;
        
        try {
            await toggleResultContainer(true);
            
            const response = await fetch(`php/api/verify-invitation.php?code=${encodeURIComponent(code)}`);
            const data = await response.json();
            
            if (!scanningIcon || !successIcon || !errorIcon || !resultTitle || !resultMessage || !guestDetails) {
                throw new Error('Éléments UI manquants');
            }

            scanningIcon.classList.add('hidden');
            
            if (data.success) {
                successIcon.classList.remove('hidden');
                resultTitle.textContent = 'Invitation valide';
                resultMessage.textContent = data.scan_info?.is_first_scan 
                    ? 'Première vérification réussie' 
                    : `Vérifiée ${data.scan_info?.count || 1} fois`;
                
                // Afficher les détails
                const guestName = document.getElementById('guest-name');
                const guestFunction = document.getElementById('guest-function');
                const guestCompany = document.getElementById('guest-company');

                if (guestName) guestName.textContent = data.guest.nom || 'Non renseigné';
                if (guestFunction) guestFunction.textContent = data.guest.fonction || 'Non renseigné';
                if (guestCompany) guestCompany.textContent = data.guest.entreprise || 'Non renseigné';
                
                guestDetails.classList.remove('hidden');
            } else {
                errorIcon.classList.remove('hidden');
                resultTitle.textContent = 'Erreur';
                resultMessage.textContent = data.message || 'Erreur de vérification';
                guestDetails.classList.add('hidden');
            }
            
            addCloseButton();
        } catch (error) {
            console.error('Erreur:', error);
            if (scanningIcon) scanningIcon.classList.add('hidden');
            if (errorIcon) errorIcon.classList.remove('hidden');
            if (resultTitle) resultTitle.textContent = 'Erreur';
            if (resultMessage) resultMessage.textContent = 'Une erreur est survenue lors de la vérification.';
            if (guestDetails) guestDetails.classList.add('hidden');
            
            addCloseButton();
        }
    }

    function addCloseButton() {
        if (!resultContainer) return;

        const resultContent = resultContainer.querySelector('.result-content');
        if (!resultContent) return;

        const closeButton = document.createElement('button');
        closeButton.className = 'btn btn-primary mt-4';
        closeButton.textContent = 'Fermer';
        closeButton.onclick = async () => {
            await toggleResultContainer(false);
        };
        resultContent.appendChild(closeButton);
    }

    // Fonction pour démarrer le scanner
    async function startScanner() {
        if (isScanning || scannerPaused || !reader) return;
        
        try {
            html5QrcodeScanner = new Html5Qrcode("reader");
            
            await html5QrcodeScanner.start(
                { facingMode: "environment" }, 
                config,
                async (decodedText) => {
                    if (!scannerPaused) {
                        await verifyInvitation(decodedText);
                    }
                },
                (errorMessage) => {
                    if (!errorMessage.includes("No QR code found")) {
                        console.debug(`Erreur de scan: ${errorMessage}`);
                    }
                }
            );
            
            isScanning = true;
        } catch (err) {
            console.error(`Impossible de démarrer le scanner: ${err}`);
            if (errorIcon) errorIcon.classList.remove('hidden');
            if (resultTitle) resultTitle.textContent = 'Erreur du scanner';
            if (resultMessage) resultMessage.textContent = 'Impossible de démarrer la caméra. Veuillez vérifier les permissions.';
            await toggleResultContainer(true);
            addCloseButton();
        }
    }

    // Fonction pour arrêter le scanner
    async function stopScanner() {
        if (!isScanning || !html5QrcodeScanner) return;
        
        try {
            await html5QrcodeScanner.stop();
            isScanning = false;
            html5QrcodeScanner = null;
        } catch (err) {
            console.error("Erreur lors de l'arrêt du scanner:", err);
        }
    }

    // Démarrer le scanner au chargement
    startScanner();

    // Gérer la visibilité de la page
    document.addEventListener('visibilitychange', async () => {
        if (!html5QrcodeScanner || !isScanning) return;
        
        if (document.hidden) {
            if (!scannerPaused) {
                try {
                    await html5QrcodeScanner.pause();
                } catch (err) {
                    console.error("Erreur lors de la pause du scanner:", err);
                }
            }
        } else if (!scannerPaused) {
            try {
                await html5QrcodeScanner.resume();
            } catch (err) {
                console.error("Erreur lors de la reprise du scanner:", err);
            }
        }
    });

    // Nettoyer à la fermeture
    window.addEventListener('beforeunload', async () => {
        await stopScanner();
    });
});