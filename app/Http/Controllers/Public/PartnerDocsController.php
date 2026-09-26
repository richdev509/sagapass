<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documentation partenaire - rend les fichiers Markdown de docs/partner-api/
 * en HTML (league/commonmark, déjà une dépendance du projet). Accessible aux
 * partenaires connectés (lien depuis le tableau de bord) ; pas de contenu
 * sensible dans ces fichiers, donc pas besoin de restreindre par statut
 * d'approbation.
 */
class PartnerDocsController extends Controller
{
    private const DOCS_PATH = 'docs/partner-api';

    /** Ordre d'affichage voulu - le guide du flux actuel en premier. */
    private const ORDER = [
        'VERIFICATION_SESSIONS_GUIDE',
        'PARTNER_WEBHOOK_GUIDE',
        'PARTNER_API_INTEGRATION',
        'PARTNER_API_RESPONSES',
        'PARTNER_VERIFY_UNIQUENESS',
        'PARTNER_VERIFICATION_CHALLENGE_GUIDE',
        'PARTNER_EMAIL_GUIDE',
        'PARTNER_API_BREAKING_CHANGES',
        'PARTNER_LARAVEL_MIGRATION_GUIDE',
    ];

    public function index(): View
    {
        $slugs = collect(File::files(base_path(self::DOCS_PATH)))
            ->filter(fn ($file) => $file->getExtension() === 'md')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->sortBy(fn ($slug) => array_search($slug, self::ORDER) !== false ? array_search($slug, self::ORDER) : 999)
            ->values();

        return view('public.partner-docs-index', ['slugs' => $slugs]);
    }

    public function show(string $slug): View|Response
    {
        // basename() neutralise toute tentative de traversée de répertoire
        // avant de vérifier l'existence du fichier.
        $path = base_path(self::DOCS_PATH . '/' . basename($slug) . '.md');

        if (! File::exists($path)) {
            abort(404);
        }

        $converter = new CommonMarkConverter();
        $html = $converter->convert(File::get($path))->getContent();

        return view('public.partner-docs-show', ['slug' => basename($slug), 'html' => $html]);
    }
}
