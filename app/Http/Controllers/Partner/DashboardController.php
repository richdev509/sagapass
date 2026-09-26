<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\PartnerAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tableau de bord entreprise partenaire (guard 'partner') — profil, statut
 * de la demande, et identifiants API une fois approuvée. Tout est scopé via
 * partnerAccount()->developerApplications() : jamais un ID arbitraire pris
 * dans l'URL, pour ne jamais exposer les données d'un autre partenaire.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $account = $this->account();
        $application = $this->application($account);

        return view('partner.dashboard', [
            'account' => $account,
            'application' => $application,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $account = $this->account();
        $application = $this->application($account);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['required', 'url', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $account->update([
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'] ?? null,
        ]);

        // Le nom/site/description affichés à l'admin (OAuthManagementController)
        // restent ceux de la DeveloperApplication — on les garde synchronisés
        // avec le profil plutôt que d'avoir deux sources qui divergent.
        $application?->update([
            'name' => $data['company_name'],
            'website' => $data['website'],
            'description' => $data['description'],
        ]);

        return back()->with('status', 'Profil mis à jour.');
    }

    public function regenerateClientSecret(): RedirectResponse
    {
        $application = $this->approvedApplicationOrFail();
        $plain = $application->regenerateSecret();

        return back()
            ->with('revealed_label', 'client_secret')
            ->with('revealed_value', $plain)
            ->with('status', 'Nouveau client_secret généré — copiez-le maintenant, il ne sera plus jamais affiché en clair.');
    }

    public function regenerateWebhookSecret(): RedirectResponse
    {
        $application = $this->approvedApplicationOrFail();
        $plain = $application->regenerateWebhookSecret();

        return back()
            ->with('revealed_label', 'webhook_secret')
            ->with('revealed_value', $plain)
            ->with('status', 'Nouveau webhook_secret généré — mettez à jour votre configuration de vérification de signature.');
    }

    private function account(): PartnerAccount
    {
        /** @var PartnerAccount $account */
        $account = Auth::guard('partner')->user();

        return $account;
    }

    private function application(PartnerAccount $account): ?DeveloperApplication
    {
        return $account->developerApplications()->latest()->first();
    }

    private function approvedApplicationOrFail(): DeveloperApplication
    {
        $application = $this->application($this->account());

        abort_if(! $application || ! $application->isApproved(), 403);

        return $application;
    }
}
