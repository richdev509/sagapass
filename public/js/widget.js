/**
 * SAGAPASS Partner Widget
 * Embeddable JavaScript for partner integrations
 *
 * Usage:
 * <script src="https://sagapass.com/js/widget.js"></script>
 * <script>
 *   SagaPass.verify({
 *     partnerId: 'your-oauth-client-id',
 *     email: 'client@example.com',
 *     firstName: 'John',
 *     lastName: 'Doe',
 *     callbackUrl: 'https://yoursite.com/success',
 *     onSuccess: function(data) {
 *       console.log('Citizen created:', data.citizenId);
 *     },
 *     onError: function(error) {
 *       console.error('Verification failed:', error);
 *     }
 *   });
 * </script>
 */

(function(window) {
    'use strict';

    // Configuration
    // Détecter l'URL de base en fonction de l'origine du script
    const WIDGET_BASE_URL = (function() {
        // Essayer de détecter l'URL depuis la balise script
        const scripts = document.getElementsByTagName('script');
        for (let i = 0; i < scripts.length; i++) {
            const src = scripts[i].src;
            if (src && src.indexOf('widget.js') !== -1) {
                // Extraire l'origine du script (ex: http://127.0.0.1:8000)
                const url = new URL(src);
                return url.origin;
            }
        }
        // Fallback: utiliser l'origine actuelle
        return window.location.origin;
    })();

    const WIDGET_ENDPOINT = '/partner/widget/verify';
    const POPUP_WIDTH = 650;
    const POPUP_HEIGHT = 750;

    // Main SAGAPASS object
    window.SagaPass = window.SagaPass || {};

    /**
     * Open verification widget popup
     * @param {Object} options Configuration options
     */
    window.SagaPass.verify = function(options) {
        // Validate required parameters
        if (!options || typeof options !== 'object') {
            console.error('[SAGAPASS] Options object is required');
            return;
        }

        const required = ['partnerId', 'email', 'firstName', 'lastName'];
        for (const field of required) {
            if (!options[field]) {
                console.error(`[SAGAPASS] Missing required field: ${field}`);
                return;
            }
        }

        // Build widget URL with parameters
        const params = new URLSearchParams({
            partner_id: options.partnerId,
            email: options.email,
            first_name: options.firstName,
            last_name: options.lastName,
            callback_url: options.callbackUrl || window.location.href
        });

        const widgetUrl = `${WIDGET_BASE_URL}${WIDGET_ENDPOINT}?${params.toString()}`;

        // Calculate popup position (centered)
        const left = (window.screen.width - POPUP_WIDTH) / 2;
        const top = (window.screen.height - POPUP_HEIGHT) / 2;

        // Open popup
        const popup = window.open(
            widgetUrl,
            'SagaPassVerification',
            `width=${POPUP_WIDTH},height=${POPUP_HEIGHT},left=${left},top=${top},scrollbars=yes,resizable=yes`
        );

        if (!popup) {
            console.error('[SAGAPASS] Popup blocked. Please allow popups for this site.');
            if (options.onError) {
                options.onError('Popup blocked by browser');
            }
            return;
        }

        // Listen for messages from popup
        const messageHandler = function(event) {
            // Security: Verify origin
            if (event.origin !== WIDGET_BASE_URL) {
                return;
            }

            if (event.data && event.data.type === 'SAGAPASS_VERIFICATION_SUCCESS') {
                console.log('[SAGAPASS] Verification successful:', event.data);

                if (options.onSuccess) {
                    options.onSuccess({
                        citizenId: event.data.citizenId,
                        email: event.data.email
                    });
                }

                // Cleanup
                window.removeEventListener('message', messageHandler);

            } else if (event.data && event.data.type === 'SAGAPASS_VERIFICATION_ERROR') {
                console.error('[SAGAPASS] Verification error:', event.data.error);

                if (options.onError) {
                    options.onError(event.data.error);
                }

                // Cleanup
                window.removeEventListener('message', messageHandler);
            }
        };

        window.addEventListener('message', messageHandler);

        // Check if popup was closed manually
        const popupCheckInterval = setInterval(function() {
            if (popup.closed) {
                clearInterval(popupCheckInterval);
                window.removeEventListener('message', messageHandler);
                console.log('[SAGAPASS] Popup closed by user');

                if (options.onCancel) {
                    options.onCancel();
                }
            }
        }, 500);
    };

    /**
     * Generate a widget token (server-side API call)
     * This is an alternative method for partners who want to generate widget URLs server-side
     *
     * @param {Object} options Configuration options
     * @param {string} options.partnerId OAuth Client ID
     * @param {string} options.accessToken Partner API access token
     * @param {string} options.email Client email
     * @param {string} options.firstName Client first name
     * @param {string} options.lastName Client last name
     * @param {Function} options.onSuccess Success callback
     * @param {Function} options.onError Error callback
     */
    window.SagaPass.generateWidgetToken = function(options) {
        if (!options || !options.partnerId || !options.accessToken || !options.email) {
            console.error('[SAGAPASS] generateWidgetToken requires: partnerId, accessToken, email, firstName, lastName');
            return;
        }

        fetch(`${WIDGET_BASE_URL}/api/partner/v1/widget/generate-token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${options.accessToken}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                partner_id: options.partnerId,
                email: options.email,
                first_name: options.firstName,
                last_name: options.lastName,
                callback_url: options.callbackUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.widget_url) {
                if (options.onSuccess) {
                    options.onSuccess(data);
                }
            } else {
                throw new Error(data.error || 'Failed to generate widget token');
            }
        })
        .catch(error => {
            console.error('[SAGAPASS] Token generation error:', error);
            if (options.onError) {
                options.onError(error.message);
            }
        });
    };

    /**
     * Verify a citizen's status after creation
     *
     * @param {Object} options Configuration options
     * @param {string} options.accessToken Partner API access token
     * @param {string} options.verificationId Verification reference ID
     * @param {Function} options.onSuccess Success callback
     * @param {Function} options.onError Error callback
     */
    window.SagaPass.checkVerification = function(options) {
        if (!options || !options.accessToken || !options.verificationId) {
            console.error('[SAGAPASS] checkVerification requires: accessToken, verificationId');
            return;
        }

        fetch(`${WIDGET_BASE_URL}/api/partner/v1/check-verification?verification_id=${options.verificationId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${options.accessToken}`,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (options.onSuccess) {
                    options.onSuccess(data);
                }
            } else {
                throw new Error(data.error || 'Verification check failed');
            }
        })
        .catch(error => {
            console.error('[SAGAPASS] Verification check error:', error);
            if (options.onError) {
                options.onError(error.message);
            }
        });
    };

    // Log initialization
    console.log('[SAGAPASS] Widget loaded successfully. Use SagaPass.verify() to start verification.');

})(window);
