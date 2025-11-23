@extends('admin.layouts.admin')

@section('title', 'Codes de Récupération')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header bg-gradient-success">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        @if(isset($regenerated) && $regenerated)
                            Nouveaux Codes de Récupération
                        @else
                            2FA Activé avec Succès !
                        @endif
                    </h3>
                </div>

                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-shield-alt fa-2x float-start me-3"></i>
                        <h5 class="alert-heading">Félicitations !</h5>
                        <p class="mb-0">
                            @if(isset($regenerated) && $regenerated)
                                Vos nouveaux codes de récupération ont été générés.
                            @else
                                Votre compte est maintenant protégé par l'authentification à deux facteurs.
                            @endif
                        </p>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important !</strong> Conservez ces codes de récupération en lieu sûr.
                        Ils vous permettront de vous connecter si vous perdez l'accès à votre application d'authentification.
                    </div>

                    <h5 class="mb-3">Vos codes de récupération :</h5>

                    <div class="recovery-codes-container mb-4">
                        @foreach($recoveryCodes as $code)
                            <div class="recovery-code">
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>

                    <div class="d-grid gap-2">
                        <button onclick="printCodes()" class="btn btn-outline-primary">
                            <i class="fas fa-print me-2"></i>
                            Imprimer les codes
                        </button>
                        <button onclick="copyCodes()" class="btn btn-outline-secondary">
                            <i class="fas fa-copy me-2"></i>
                            Copier les codes
                        </button>
                        <a href="{{ route('admin.two-factor.index') }}" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>
                            J'ai sauvegardé mes codes
                        </a>
                    </div>

                    <div class="mt-4">
                        <h6>Conseils de sécurité :</h6>
                        <ul class="text-muted small">
                            <li>Imprimez ces codes et conservez-les dans un endroit sûr</li>
                            <li>Ne partagez jamais ces codes avec personne</li>
                            <li>Chaque code ne peut être utilisé qu'une seule fois</li>
                            <li>Vous pouvez régénérer de nouveaux codes à tout moment</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Version imprimable (cachée) -->
<div id="printable-codes" style="display: none;">
    <div style="text-align: center; padding: 20px;">
        <h2>{{ config('app.name') }} - Codes de Récupération 2FA</h2>
        <p>Compte: {{ auth('admin')->user()->email }}</p>
        <p>Généré le: {{ now()->format('d/m/Y H:i') }}</p>
        <hr>
        <div style="margin: 20px 0;">
            @foreach($recoveryCodes as $code)
                <div style="font-family: monospace; font-size: 18px; padding: 5px; border: 1px solid #ddd; margin: 5px 0;">
                    {{ $code }}
                </div>
            @endforeach
        </div>
        <hr>
        <p style="color: red; font-weight: bold;">⚠️ CONSERVEZ CES CODES EN LIEU SÛR</p>
        <p style="font-size: 12px;">Chaque code ne peut être utilisé qu'une seule fois</p>
    </div>
</div>

<style>
    .bg-gradient-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .card {
        border: none;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        border-radius: 10px;
    }

    .recovery-codes-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .recovery-code {
        font-family: 'Courier New', monospace;
        font-size: 16px;
        font-weight: bold;
        padding: 15px;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 5px;
        text-align: center;
        letter-spacing: 2px;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #printable-codes, #printable-codes * {
            visibility: visible;
        }
        #printable-codes {
            position: absolute;
            left: 0;
            top: 0;
            display: block !important;
        }
    }
</style>

<script>
    function copyCodes() {
        const codes = @json($recoveryCodes);
        const text = codes.join('\n');

        navigator.clipboard.writeText(text).then(() => {
            alert('Codes copiés dans le presse-papier !');
        });
    }

    function printCodes() {
        window.print();
    }
</script>
@endsection
