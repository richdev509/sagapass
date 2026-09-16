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
    public function show(string $token): View
    {
        $session = PartnerVerificationSession::where('token', $token)->first();

        if (! $session || ! $session->isAwaitingCapture()) {
            return view('public.face-capture-unavailable', [
                'reason' => $session === null ? 'not_found' : ($session->isExpired() ? 'expired' : 'already_used'),
            ]);
        }

        return view('public.face-capture', ['token' => $token]);
    }

    public function submit(Request $request, string $token): RedirectResponse|View
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
            ->where('status', 'awaiting_capture')
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
