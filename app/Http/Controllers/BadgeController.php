<?php

namespace App\Http\Controllers;

use App\Models\DigitalBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class BadgeController extends Controller
{
    /**
     * Générer ou récupérer le badge numérique actif
     */
    public function generate(Request $request)
    {
        $user = Auth::user();

        // Vérifier si un badge actif existe
        $activeBadge = DigitalBadge::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        // Générer un nouveau badge si nécessaire
        if (!$activeBadge) {
            $activeBadge = DigitalBadge::generateForUser(
                $user,
                $request->ip(),
                $request->userAgent()
            );
        }

        // Générer le QR code
        $validationUrl = $activeBadge->getValidationUrl();

        $writer = new SvgWriter();
        $qrCode = new QrCode($validationUrl);
        $result = $writer->write($qrCode);
        $qrCodeSvg = $result->getString();

        return view('components.digital-badge', [
            'badge' => $activeBadge,
            'qrCode' => $qrCodeSvg,
            'user' => $user,
        ]);
    }

    /**
     * Rafraîchir le badge (AJAX)
     */
    public function refresh(Request $request)
    {
        $user = Auth::user();

        // Générer un nouveau badge
        $newBadge = DigitalBadge::generateForUser(
            $user,
            $request->ip(),
            $request->userAgent()
        );

        // Générer le QR code
        $validationUrl = $newBadge->getValidationUrl();

        $writer = new SvgWriter();
        $qrCode = new QrCode($validationUrl);
        $result = $writer->write($qrCode);
        $qrCodeSvg = $result->getString();

        return response()->json([
            'success' => true,
            'expires_at' => $newBadge->expires_at->timestamp,
            'qr_html' => $qrCodeSvg,
            'validation_url' => $validationUrl,
        ]);
    }

    /**
     * Valider un badge scanné (endpoint public)
     */
    public function validateBadge(Request $request, $token)
    {
        $badge = DigitalBadge::where('badge_token', $token)->first();

        if (!$badge) {
            return view('badge.validate', [
                'valid' => false,
                'message' => 'Badge introuvable ou invalide.',
            ]);
        }

        if (!$badge->isValid()) {
            $reason = $badge->isExpired() ? 'expiré' : 'révoqué';
            return view('badge.validate', [
                'valid' => false,
                'message' => "Badge $reason.",
                'badge' => $badge,
            ]);
        }

        // Marquer comme scanné
        $badge->markAsScanned();

        // Récupérer les données utilisateur
        $user = $badge->user;

        return view('badge.validate', [
            'valid' => true,
            'user' => $user,
            'badge' => $badge,
            'message' => 'Badge valide et vérifié.',
        ]);
    }

    /**
     * Révoquer le badge actif (optionnel)
     */
    public function revoke(Request $request)
    {
        $user = Auth::user();

        DigitalBadge::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return redirect()->route('dashboard')
            ->with('success', 'Badge révoqué avec succès.');
    }
}
