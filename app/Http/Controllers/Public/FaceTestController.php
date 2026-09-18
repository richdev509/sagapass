<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\FaceVerificationUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\FaceVerification\FaceVerificationScriptClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Page de test du moteur facial, SANS connexion (demande explicite : test sur
 * téléphone) : compare une photo de référence à une image de la caméra et montre
 * les scores réels (similarité, verdict, vivacité) — outil de calibration du
 * seuil de doublons.
 *
 * Comme aucune authentification ne protège cette page, elle est temporaire et
 * bornée : désactivée par défaut (FACE_TEST_ENABLED), accessible seulement par
 * un lien secret (FACE_TEST_TOKEN), limitée en débit, et une seule comparaison
 * à la fois (calcul lourd sur un serveur partagé). À DÉSACTIVER après les tests.
 *
 * Ne stocke RIEN : ni photo, ni empreinte, aucun lien avec les sessions ni avec
 * le registre face_embeddings. Les fichiers restent dans le répertoire
 * temporaire de PHP, supprimé en fin de requête.
 */
class FaceTestController extends Controller
{
    private function guard(string $token): void
    {
        $expected = (string) config('faceverification.face_test.token');

        abort_unless(
            config('faceverification.face_test.enabled') && $expected !== '' && hash_equals($expected, $token),
            404,
        );
    }

    public function show(string $token)
    {
        $this->guard($token);

        return view('public.face-test', [
            'token' => $token,
            'threshold' => (float) config('faceverification.duplicate_similarity_threshold'),
        ]);
    }

    public function compare(Request $request, string $token, FaceVerificationScriptClient $client): JsonResponse
    {
        $this->guard($token);

        $request->validate([
            'reference' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            'probe' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
        ]);

        // Une seule comparaison à la fois : DeepFace charge des modèles lourds
        // et le serveur héberge d'autres applications.
        $lock = Cache::lock('face-test-compare', 120);

        if (! $lock->get()) {
            return response()->json(['error' => 'Une autre comparaison est en cours, réessayez dans quelques secondes.'], 429);
        }

        try {
            $result = $client->compareFaces(
                $request->file('reference')->getRealPath(),
                $request->file('probe')->getRealPath(),
            );
        } catch (FaceVerificationUnavailableException $e) {
            Log::warning('FaceTest: moteur indisponible', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Le moteur de reconnaissance est indisponible.'], 503);
        } finally {
            $lock->release();
        }

        $threshold = (float) config('faceverification.duplicate_similarity_threshold');
        $similarity = $result['cosine_similarity'] ?? null;

        // Journal minimal — jamais d'image ni d'empreinte.
        Log::info('FaceTest: comparaison effectuée', [
            'ip' => $request->ip(),
            'similarity' => $similarity,
        ]);

        return response()->json([
            ...$result,
            'threshold' => $threshold,
            'same_face' => $similarity !== null ? $similarity >= $threshold : null,
        ]);
    }
}
