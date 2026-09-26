<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\PartnerAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Demande de partenariat entreprise — page publique, aucune authentification
 * requise pour la soumettre. Crée un PartnerAccount (guard 'partner',
 * distinct des comptes citoyens SagaPass) et une DeveloperApplication en
 * attente ('pending'). Les identifiants API restent inutilisables tant
 * qu'un admin n'a pas approuvé (voir Admin\OAuthManagementController::approve())
 * — jamais auto-approuvé, ces identifiants donnant accès à de la
 * vérification d'identité sur des données réelles.
 */
class PartnerApplicationController extends Controller
{
    public function create(): View
    {
        return view('public.partner-apply');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:partner_accounts,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['required', 'url', 'max:255'],
            'redirect_uri' => ['required', 'url', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data) {
            $account = PartnerAccount::query()->create([
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
            ]);

            DeveloperApplication::query()->create([
                'partner_account_id' => $account->id,
                'name' => $data['company_name'],
                'description' => $data['description'],
                'website' => $data['website'],
                'redirect_uris' => [$data['redirect_uri']],
                'status' => 'pending',
            ]);

            Auth::guard('partner')->login($account);
        });

        return redirect()->route('partner.dashboard')->with(
            'status',
            'Votre demande a été soumise. Un administrateur doit l\'approuver avant que vos identifiants API ne soient actifs.'
        );
    }
}
