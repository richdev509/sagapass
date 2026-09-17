@extends('admin.layouts.admin')

@section('title', 'Sessions Partenaire — Revue')
@section('page-title', 'Sessions partenaire en revue manuelle')
@section('page-subtitle', "L'analyse automatisée a échoué techniquement pour ces sessions — les photos sont conservées pour votre décision")

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
        overflow: hidden;
    }

    .ios-empty {
        padding: 64px 24px;
        text-align: center;
        color: var(--ios-gray);
    }

    .ios-empty i {
        font-size: 40px;
        color: var(--ios-green);
        margin-bottom: 12px;
        display: block;
    }

    .ios-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 24px;
        border-bottom: 1px solid var(--ios-border);
        transition: background 0.15s ease;
    }

    .ios-row:last-child { border-bottom: none; }
    .ios-row:hover { background: var(--ios-gray-6); }

    .ios-row-title {
        font-weight: 600;
        font-size: 15px;
        color: #1c1c1e;
        margin-bottom: 3px;
    }

    .ios-row-sub {
        font-size: 13px;
        color: var(--ios-gray);
    }

    .ios-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(255, 149, 0, 0.12);
        color: var(--ios-orange);
        white-space: nowrap;
    }

    .ios-badge i { font-size: 6px; }

    .ios-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border-radius: 100px;
        background: var(--ios-blue);
        color: #fff !important;
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        white-space: nowrap;
        transition: opacity 0.15s ease;
    }

    .ios-btn:hover { opacity: 0.85; }
</style>
@endsection

@section('content')
<div class="ios-card">
    @forelse ($sessions as $session)
        <div class="ios-row">
            <div>
                <div class="ios-row-title">
                    {{ $session->developerApplication->name ?? 'Partenaire inconnu' }}
                    <span class="ios-badge"><i class="fas fa-circle"></i> En attente</span>
                </div>
                <div class="ios-row-sub">
                    {{ $session->document_type === 'national_id' ? "Carte d'identification nationale" : ucfirst($session->document_type) }}
                    · Soumis {{ $session->updated_at->diffForHumans() }}
                    · Réf. {{ $session->partner_reference ?? '—' }}
                </div>
            </div>
            <a href="{{ route('admin.partner-sessions.show', $session) }}" class="ios-btn">
                Examiner <i class="fas fa-chevron-right" style="font-size:11px;"></i>
            </a>
        </div>
    @empty
        <div class="ios-empty">
            <i class="fas fa-check-circle"></i>
            Aucune session en attente de revue manuelle.
        </div>
    @endforelse
</div>

@if ($sessions->hasPages())
    <div class="mt-3">{{ $sessions->links() }}</div>
@endif
@endsection
