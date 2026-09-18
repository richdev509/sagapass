<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\FaceVerificationUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\FaceVerification\FaceVerificationScriptClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Page de test du moteur facial, pour un admin sur son téléphone : compare une
 * photo de référence à une image de la caméra et montre les scores réels
 * (similarité, verdict, vivacité) — outil de calibration du seuil de doublons.
 * Ne stocke RIEN : ni photo, ni empreinte, aucun lien avec les sessions ni avec
 * le registre face_embeddings. Les fichiers restent dans le répertoire
 * temporaire de PHP, supprimé en fin de requête.
 */
class FaceTestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'permission:verify-documents,admin']);
    }

    public function index()
    {
        return view('admin.face-test.index', [
            'threshold' => (float) config('faceverification.duplicate_similarity_threshold'),
        ]);
    }

    public function compare(Request $request, FaceVerificationScriptClient $client): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            'probe' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
        ]);

        try {
            $result = $client->compareFaces(
                $request->file('reference')->getRealPath(),
                $request->file('probe')->getRealPath(),
            );
        } catch (FaceVerificationUnavailableException $e) {
            Log::warning('FaceTest: moteur indisponible', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Le moteur de reconnaissance est indisponible.'], 503);
        }

        $threshold = (float) config('faceverification.duplicate_similarity_threshold');
        $similarity = $result['cosine_similarity'] ?? null;

        // Journal minimal (qui a testé, quand) — jamais d'image ni d'empreinte.
        Log::info('FaceTest: comparaison effectuée', [
            'admin_id' => Auth::guard('admin')->id(),
            'similarity' => $similarity,
        ]);

        return response()->json([
            ...$result,
            'threshold' => $threshold,
            'same_face' => $similarity !== null ? $similarity >= $threshold : null,
        ]);
    }
}
