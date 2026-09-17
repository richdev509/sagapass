<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyPartnerSessionWebhook;
use App\Models\PartnerVerificationSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Revue manuelle des PartnerVerificationSession dont l'analyse automatisée a
 * échoué techniquement (voir AnalyzePartnerSessionJob::handle() — photos
 * conservées exprès, jamais purgées, pour ce statut). Même squelette que
 * Admin\VerificationController (flux Document/citoyen), très simplifié :
 * pas de compte SagaID rattaché ici, rien d'autre à mettre à jour.
 */
class PartnerSessionReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'permission:verify-documents,admin']);
    }

    public function index()
    {
        $sessions = PartnerVerificationSession::query()
            ->where('status', 'awaiting_manual_review')
            ->with('developerApplication')
            ->latest()
            ->paginate(15);

        return view('admin.partner-sessions.index', compact('sessions'));
    }

    public function show(PartnerVerificationSession $partnerSession)
    {
        abort_unless($partnerSession->isAwaitingManualReview(), 404);

        return view('admin.partner-sessions.show', ['session' => $partnerSession]);
    }

    public function approve(PartnerVerificationSession $partnerSession)
    {
        if (! $partnerSession->isAwaitingManualReview()) {
            return redirect()->route('admin.partner-sessions.index')->with('error', 'Cette session a déjà été traitée.');
        }

        $partnerSession->purgePhotos();

        $partnerSession->forceFill([
            'status' => 'completed',
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'completed_at' => now(),
        ])->save();

        NotifyPartnerSessionWebhook::dispatch($partnerSession, 'verification.completed');

        return redirect()->route('admin.partner-sessions.index')->with('success', 'Vérification approuvée — le partenaire a été notifié.');
    }

    public function reject(Request $request, PartnerVerificationSession $partnerSession)
    {
        if (! $partnerSession->isAwaitingManualReview()) {
            return redirect()->route('admin.partner-sessions.index')->with('error', 'Cette session a déjà été traitée.');
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'rejection_reason.required' => 'Veuillez indiquer la raison du rejet.',
        ]);

        $partnerSession->purgePhotos();

        $partnerSession->forceFill([
            'status' => 'failed',
            'rejection_reason' => 'manual_rejection: ' . $request->string('rejection_reason'),
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'completed_at' => now(),
        ])->save();

        NotifyPartnerSessionWebhook::dispatch($partnerSession, 'verification.failed');

        return redirect()->route('admin.partner-sessions.index')->with('success', 'Vérification rejetée — le partenaire a été notifié.');
    }

    public function serveImage(PartnerVerificationSession $partnerSession, string $type)
    {
        abort_unless($partnerSession->isAwaitingManualReview(), 404);

        $path = match ($type) {
            'front' => $partnerSession->front_photo_path,
            'back' => $partnerSession->back_photo_path,
            'selfie_left' => $partnerSession->selfie_left_path,
            'selfie_center' => $partnerSession->selfie_center_path,
            'selfie_right' => $partnerSession->selfie_right_path,
            default => null,
        };

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('private')->path($path));
    }
}
