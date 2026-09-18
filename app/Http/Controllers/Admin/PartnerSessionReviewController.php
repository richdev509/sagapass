<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyPartnerSessionWebhook;
use App\Models\PartnerVerificationSession;
use App\Services\FaceVerification\PartnerSessionFinalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Revue manuelle des PartnerVerificationSession : soit l'analyse automatisée a
 * échoué techniquement, soit le contrôle de doublons de visage a signalé un cas
 * suspect (voir AnalyzePartnerSessionJob::handle() et FaceDuplicateService —
 * jumeaux et fausses correspondances possibles, d'où la décision humaine).
 * Les photos sont conservées dans tous les cas. Même squelette que
 * Admin\VerificationController (flux Document/citoyen), très simplifié :
 * pas de compte SagaID rattaché ici.
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

        // Sessions des correspondances suspectes, pour comparer visuellement
        // leurs photos (conservées) avec celles de cette session.
        $matchedSessions = PartnerVerificationSession::query()
            ->whereIn('id', collect($partnerSession->duplicate_check['matches'] ?? [])->pluck('partner_verification_session_id')->filter())
            ->get()
            ->keyBy('id');

        return view('admin.partner-sessions.show', [
            'session' => $partnerSession,
            'matchedSessions' => $matchedSessions,
        ]);
    }

    public function approve(PartnerVerificationSession $partnerSession, PartnerSessionFinalizer $finalizer)
    {
        if (! $partnerSession->isAwaitingManualReview()) {
            return redirect()->route('admin.partner-sessions.index')->with('error', 'Cette session a déjà été traitée.');
        }

        if ($partnerSession->isDuplicateReview()) {
            // L'analyse avait réussi : on termine ce que le job a suspendu
            // (KYC ID, criblage, enregistrement de l'empreinte, webhook).
            $finalizer->complete(
                $partnerSession,
                [
                    'document_number' => $partnerSession->ocr_extracted_document_number,
                    'full_name' => $partnerSession->ocr_extracted_full_name,
                    'date_of_birth' => $partnerSession->ocr_extracted_date_of_birth?->toDateString(),
                ],
                $partnerSession->face_match_score,
                $partnerSession->liveness_passed,
                $partnerSession->pending_face_embedding,
                enrollFace: $partnerSession->liveness_passed !== false,
                attributes: [
                    'reviewed_by' => Auth::guard('admin')->id(),
                    'reviewed_at' => now(),
                    'duplicate_check' => [...$partnerSession->duplicate_check, 'decision' => 'approved'],
                ],
            );

            return redirect()->route('admin.partner-sessions.index')->with('success', 'Vérification approuvée — le partenaire a été notifié.');
        }

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

        $note = (string) $request->string('rejection_reason');

        if ($partnerSession->isDuplicateReview()) {
            // Motif générique côté partenaire : jamais les données d'une autre
            // personne ni d'un autre partenaire. La note reste interne.
            $partnerSession->forceFill([
                'status' => 'failed',
                'rejection_reason' => 'duplicate_identity',
                'duplicate_check' => [...$partnerSession->duplicate_check, 'decision' => 'rejected', 'review_note' => $note],
                'pending_face_embedding' => null,
            ]);
        } else {
            $partnerSession->forceFill([
                'status' => 'failed',
                'rejection_reason' => 'manual_rejection: ' . $note,
            ]);
        }

        $partnerSession->forceFill([
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'completed_at' => now(),
        ])->save();

        NotifyPartnerSessionWebhook::dispatch($partnerSession, 'verification.failed');

        return redirect()->route('admin.partner-sessions.index')->with('success', 'Vérification rejetée — le partenaire a été notifié.');
    }

    public function serveImage(PartnerVerificationSession $partnerSession, string $type)
    {
        // Pas de restriction au statut "en revue" : l'admin compare aussi les
        // photos conservées des correspondances (autres sessions déjà terminées).
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
