@extends('admin.layouts.admin')

@section('title', 'Revue de session partenaire')
@section('page-title', 'Revue de la session')
@section('page-subtitle', "Examinez la pièce et le visage avant de décider — le partenaire sera notifié automatiquement")

@section('styles')
<style>
    :root {
        --ios-blue: #007AFF;
        --ios-green: #34C759;
        --ios-red: #FF3B30;
        --ios-orange: #FF9500;
        --ios-gray: #8E8E93;
        --ios-gray-6: #F2F2F7;
        --ios-border: rgba(60, 60, 67, 0.12);
    }

    .ios-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid var(--ios-border);
        box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.04);
        padding: 24px;
        margin-bottom: 20px;
    }

    .ios-card h6 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ios-gray);
        margin-bottom: 16px;
    }

    .ios-photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 14px;
    }

    .ios-photo {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid var(--ios-border);
        background: var(--ios-gray-6);
    }

    .ios-photo img {
        width: 100%;
        display: block;
        aspect-ratio: 1.4/1;
        object-fit: cover;
    }

    .ios-photo span {
        display: block;
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #1c1c1e;
    }

    .ios-field-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--ios-border);
        font-size: 14px;
    }

    .ios-field-row:last-child { border-bottom: none; }
    .ios-field-label { color: var(--ios-gray); }
    .ios-field-value { font-weight: 600; color: #1c1c1e; text-align: right; }

    .ios-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 100px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: opacity 0.15s ease;
        width: 100%;
    }

    .ios-btn:hover { opacity: 0.85; }
    .ios-btn-approve { background: var(--ios-green); color: #fff; }
    .ios-btn-reject { background: var(--ios-red); color: #fff; }

    .ios-textarea {
        width: 100%;
        border: 1px solid var(--ios-border);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        font-family: inherit;
        resize: vertical;
        min-height: 80px;
        margin-bottom: 12px;
    }

    .ios-textarea:focus {
        outline: none;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 3px rgba(0,122,255,0.15);
    }
</style>
@endsection

@section('content')
<a href="{{ route('admin.partner-sessions.index') }}" style="color: var(--ios-blue); text-decoration: none; font-size: 14px; font-weight: 500; display: inline-block; margin-bottom: 16px;">
    <i class="fas fa-chevron-left" style="font-size: 11px;"></i> Retour à la liste
</a>

<div class="row">
    <div class="col-lg-8">
        <div class="ios-card">
            <h6>Pièce d'identité</h6>
            <div class="ios-photo-grid">
                <div class="ios-photo">
                    <img src="{{ route('admin.partner-sessions.image', [$session, 'front']) }}" alt="Recto">
                    <span>Recto</span>
                </div>
                @if ($session->back_photo_path)
                    <div class="ios-photo">
                        <img src="{{ route('admin.partner-sessions.image', [$session, 'back']) }}" alt="Verso">
                        <span>Verso</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="ios-card">
            <h6>Selfies (vivacité)</h6>
            <div class="ios-photo-grid">
                <div class="ios-photo">
                    <img src="{{ route('admin.partner-sessions.image', [$session, 'selfie_left']) }}" alt="Gauche">
                    <span>Gauche</span>
                </div>
                <div class="ios-photo">
                    <img src="{{ route('admin.partner-sessions.image', [$session, 'selfie_center']) }}" alt="Centre">
                    <span>Centre</span>
                </div>
                <div class="ios-photo">
                    <img src="{{ route('admin.partner-sessions.image', [$session, 'selfie_right']) }}" alt="Droite">
                    <span>Droite</span>
                </div>
            </div>
        </div>

        @if ($session->isDuplicateReview())
            @php
                $duplicate = $session->duplicate_check;
                $strong = ($duplicate['severity'] ?? null) === 'strong';
                $verdictLabels = [
                    'duplicate_same_type' => 'Même visage, même type de pièce, numéro différent',
                    'identity_conflict' => 'Même visage, mais nom ou date de naissance différents',
                    'document_face_mismatch' => 'Même numéro de pièce déjà enregistré, mais visage différent',
                ];
            @endphp
            <div class="ios-card" style="border-color: {{ $strong ? 'var(--ios-red)' : 'var(--ios-orange)' }};">
                <h6 style="color: {{ $strong ? 'var(--ios-red)' : 'var(--ios-orange)' }};">
                    Doublon de visage suspect — {{ $strong ? 'signal fort' : 'à vérifier' }}
                </h6>
                <p style="font-size: 14px; margin-bottom: 16px;">
                    <strong>{{ $verdictLabels[$duplicate['verdict']] ?? $duplicate['verdict'] }}.</strong>
                    Vérifiez visuellement : des jumeaux ou une fausse correspondance sont possibles.
                    Un renouvellement légitime de pièce est aussi possible (même type, nouveau numéro).
                </p>

                @foreach ($duplicate['matches'] as $match)
                    @php
                        $matched = $matchedSessions[$match['partner_verification_session_id'] ?? 0] ?? null;
                        $matchedDocument = $matchedDocuments[$match['document_id'] ?? 0] ?? null;
                    @endphp
                    <div style="border-top: 1px solid var(--ios-border); padding-top: 14px; margin-top: 14px;">
                        <div class="ios-field-row"><span class="ios-field-label">Similarité</span><span class="ios-field-value">{{ number_format($match['similarity'], 3) }}</span></div>
                        <div class="ios-field-row"><span class="ios-field-label">Identité existante</span><span class="ios-field-value">{{ $match['full_name'] ?? '—' }}</span></div>
                        <div class="ios-field-row"><span class="ios-field-label">Date de naissance</span><span class="ios-field-value">{{ $match['date_of_birth'] ?? '—' }}</span></div>
                        <div class="ios-field-row"><span class="ios-field-label">Pièce existante</span><span class="ios-field-value">{{ $match['document_type'] }} · {{ $match['document_number'] ?? '—' }}</span></div>
                        <div class="ios-field-row"><span class="ios-field-label">Origine</span><span class="ios-field-value">{{ $match['partner_name'] ?? ($match['document_id'] ? 'Compte SagaPass' : '—') }}</span></div>
                        <div class="ios-field-row"><span class="ios-field-label">Enregistrée le</span><span class="ios-field-value">{{ $match['enrolled_at'] ?? '—' }}</span></div>

                        @if ($matchedDocument && $matchedDocument->selfie_path)
                            <div class="ios-photo-grid" style="margin-top: 12px;">
                                <div class="ios-photo">
                                    <img src="{{ route('admin.verification.image', [$matchedDocument, 'selfie']) }}" alt="Selfie existant">
                                    <span>Selfie existant</span>
                                </div>
                                @if ($matchedDocument->front_photo_path)
                                    <div class="ios-photo">
                                        <img src="{{ route('admin.verification.image', [$matchedDocument, 'front']) }}" alt="Pièce existante">
                                        <span>Pièce existante</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($matched && $matched->selfie_center_path)
                            <div class="ios-photo-grid" style="margin-top: 12px;">
                                <div class="ios-photo">
                                    <img src="{{ route('admin.partner-sessions.image', [$matched, 'selfie_center']) }}" alt="Selfie existant">
                                    <span>Selfie existant</span>
                                </div>
                                @if ($matched->front_photo_path)
                                    <div class="ios-photo">
                                        <img src="{{ route('admin.partner-sessions.image', [$matched, 'front']) }}" alt="Pièce existante">
                                        <span>Pièce existante</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($session->analysis_raw['error'] ?? null)
            <div class="ios-card">
                <h6>Erreur technique rencontrée</h6>
                <p style="font-size: 13px; color: var(--ios-red); font-family: monospace; margin: 0;">
                    {{ $session->analysis_raw['error'] }}
                </p>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="ios-card">
            <h6>Informations transmises par le partenaire</h6>
            <div class="ios-field-row">
                <span class="ios-field-label">Partenaire</span>
                <span class="ios-field-value">{{ $session->developerApplication->name ?? '—' }}</span>
            </div>
            <div class="ios-field-row">
                <span class="ios-field-label">Type de pièce</span>
                <span class="ios-field-value">{{ $session->document_type }}</span>
            </div>
            @foreach (($session->partner_submitted_data ?? []) as $key => $value)
                <div class="ios-field-row">
                    <span class="ios-field-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                    <span class="ios-field-value">{{ is_scalar($value) ? $value : json_encode($value) }}</span>
                </div>
            @endforeach
        </div>

        @if (session('success'))
            <div class="ios-card" style="color: var(--ios-green); font-weight: 600;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="ios-card" style="color: var(--ios-red); font-weight: 600;">{{ session('error') }}</div>
        @endif

        <div class="ios-card">
            <h6>Décision</h6>

            <form method="POST" action="{{ route('admin.partner-sessions.approve', $session) }}" style="margin-bottom: 14px;">
                @csrf
                <button type="submit" class="ios-btn ios-btn-approve" onclick="return confirm('Approuver cette vérification ?');">
                    <i class="fas fa-check"></i> Approuver
                </button>
            </form>

            <form method="POST" action="{{ route('admin.partner-sessions.reject', $session) }}">
                @csrf
                <textarea name="rejection_reason" class="ios-textarea" placeholder="Motif du rejet (visible en interne uniquement)" required>{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')
                    <div style="color: var(--ios-red); font-size: 12px; margin-bottom: 10px;">{{ $message }}</div>
                @enderror
                <button type="submit" class="ios-btn ios-btn-reject" onclick="return confirm('Rejeter cette vérification ?');">
                    <i class="fas fa-xmark"></i> Rejeter
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
