@if(\App\Models\SystemSetting::isBetaMode())
    <div class="alert alert-info alert-dismissible fade show m-0 rounded-0" role="alert" id="beta-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <i class="fas fa-flask fa-2x"></i>
                </div>
                <div class="col">
                    <h5 class="alert-heading mb-1">
                        <strong>{{ config('app.name') }} est actuellement en phase Early Access</strong>
                    </h5>
                    <p class="mb-2">
                        Vous faites partie des utilisateurs de la phase test ! Certaines fonctionnalités peuvent présenter des erreurs mineures.
                        Merci de contribuer à l'amélioration du système en signalant tout problème rencontré.
                    </p>
                    <a href="{{ \App\Models\SystemSetting::getWhatsAppLink() }}"
                       target="_blank"
                       class="btn btn-light btn-sm">
                        <i class="fab fa-whatsapp me-1"></i>
                        Rejoindre la communauté
                    </a>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #beta-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 0;
            padding-top: 70px;
            margin-top: 56px; /* Height of navbar to prevent it being hidden */
            position: relative;
            z-index: 1000;
        }

        #beta-banner .alert-heading {
            color: white;
        }

        #beta-banner .btn-close {
            filter: brightness(0) invert(1);
        }

        #beta-banner i {
            opacity: 0.8;
        }

        #beta-banner .btn-light {
            background: white;
            border: none;
            font-weight: 600;
            padding: 0.4rem 1rem;
            transition: all 0.3s;
        }

        #beta-banner .btn-light:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Adjust body to account for fixed navbar + banner */
        body {
            padding-top: 0 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const banner = document.getElementById('beta-banner');
            const dismissed = sessionStorage.getItem('beta-banner-dismissed');

            if (dismissed === 'true') {
                banner.style.display = 'none';
            }

            banner.querySelector('.btn-close').addEventListener('click', function() {
                sessionStorage.setItem('beta-banner-dismissed', 'true');
            });
        });
    </script>
@endif
