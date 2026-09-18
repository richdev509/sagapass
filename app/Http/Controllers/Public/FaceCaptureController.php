<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzePartnerSessionJob;
use App\Models\PartnerVerificationSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Page publique de capture faciale — AUCUNE authentification, le jeton dans
 * l'URL en tient lieu (comme un lien signé à usage unique). Destinée à
 * l'utilisateur final d'un partenaire (ex. client SwapLajan), qui n'a pas de
 * compte SagaID. Voir le plan associé.
 */
class FaceCaptureController extends Controller
{
    /**
     * Routeur selon l'état de la session — permet de reprendre proprement au
     * bon écran si l'utilisateur recharge la page en cours de route (ex.
     * pièce déjà capturée, mais pas encore le selfie).
     */
    public function show(string $token): View
    {
        $session = PartnerVerificationSession::where('token', $token)->first();

        if (! $session) {
            return view('public.face-capture-unavailable', ['reason' => 'not_found']);
        }

        if ($session->isAwaitingIdCapture()) {
            return view('public.id-capture', [
                'token' => $token,
                'documentType' => $session->document_type,
            ]);
        }

        if ($session->isAwaitingSelfieCapture()) {
            return view('public.face-capture', ['token' => $token]);
        }

        return view('public.face-capture-unavailable', [
            'reason' => $session->isExpired() ? 'expired' : 'already_used',
        ]);
    }

    /**
     * POST /capture/{token}/id — photo(s) de la pièce (recto seul, ou
     * recto+verso pour une carte nationale). Jamais de fichier arbitraire
     * transmis par un tiers : ces images viennent uniquement de la capture
     * caméra en direct sur cette page (voir id-capture.blade.php).
     */
    public function submitId(Request $request, string $token): View
    {
        $session = PartnerVerificationSession::where('token', $token)->first();

        if (! $session || ! $session->isAwaitingIdCapture()) {
            return view('public.face-capture-unavailable', [
                'reason' => $session === null ? 'not_found' : ($session->isExpired() ? 'expired' : 'already_used'),
            ]);
        }

        $requiresBack = $session->document_type === 'national_id';

        $validator = Validator::make($request->all(), [
            'id_front' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            'id_back' => [$requiresBack ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            // Consentement affiché avant la capture (conditions d'utilisation,
            // données biométriques) — vérifié aussi côté serveur, pas seulement
            // par le bouton désactivé de la page.
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => "Vous devez accepter les conditions d'utilisation pour continuer.",
        ]);

        if ($validator->fails()) {
            return view('public.id-capture', [
                'token' => $token,
                'documentType' => $session->document_type,
                'errors' => $validator->errors(),
            ]);
        }

        // Même garde atomique que submitSelfie() : empêche un double-tap ou
        // un replay de repasser deux fois par cette étape.
        $affected = PartnerVerificationSession::where('token', $token)
            ->where('status', 'awaiting_id_capture')
            ->update(['status' => 'awaiting_selfie_capture']);

        if ($affected === 0) {
            return view('public.face-capture-unavailable', ['reason' => 'already_used']);
        }

        $folder = "partner-sessions/{$token}";
        $session->update([
            'front_photo_path' => $request->file('id_front')->store($folder, 'private'),
            'back_photo_path' => $request->hasFile('id_back')
                ? $request->file('id_back')->store($folder, 'private')
                : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consent_accepted_at' => now(),
            'consent_terms_version' => config('faceverification.consent_terms_version'),
        ]);

        return view('public.face-capture', ['token' => $token]);
    }

    /**
     * POST /capture/{token}/selfie — les 3 frames de vivacité active
     * (gauche/centre/droite).
     */
    public function submitSelfie(Request $request, string $token): RedirectResponse|View
    {
        $validator = Validator::make($request->all(), [
            'selfie_left' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            'selfie_center' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            'selfie_right' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
        ]);

        if ($validator->fails()) {
            return view('public.face-capture', [
                'token' => $token,
                'errors' => $validator->errors(),
            ]);
        }

        // Mise à jour conditionnelle atomique : empêche qu'un double-tap ou
        // un replay ne fasse tourner l'analyse deux fois sur la même session
        // (usage unique). Le nombre de lignes affectées fait foi, pas une
        // lecture préalable suivie d'une écriture séparée.
        $affected = PartnerVerificationSession::where('token', $token)
            ->where('status', 'awaiting_selfie_capture')
            ->update(['status' => 'processing']);

        if ($affected === 0) {
            return view('public.face-capture-unavailable', ['reason' => 'already_used']);
        }

        $session = PartnerVerificationSession::where('token', $token)->firstOrFail();

        $folder = "partner-sessions/{$token}";
        $session->update([
            'selfie_left_path' => $request->file('selfie_left')->store($folder, 'private'),
            'selfie_center_path' => $request->file('selfie_center')->store($folder, 'private'),
            'selfie_right_path' => $request->file('selfie_right')->store($folder, 'private'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        AnalyzePartnerSessionJob::dispatch($session->id);

        return view('public.face-capture-complete');
    }
}
