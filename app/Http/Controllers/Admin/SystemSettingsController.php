<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemSettingsController extends Controller
{
    /**
     * Afficher le formulaire des paramètres système
     */
    public function index()
    {
        $settings = [
            'maintenance_mode' => SystemSetting::isMaintenanceMode(),
            'beta_mode' => SystemSetting::isBetaMode(),
            'whatsapp_support_link' => SystemSetting::getWhatsAppLink(),
            'maintenance_message' => SystemSetting::get('maintenance_message', 'Le système est actuellement en maintenance. Nous serons de retour bientôt.'),
            'force_2fa_for_admins' => SystemSetting::get('force_2fa_for_admins', false),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Mettre à jour les paramètres système
     */
    public function update(Request $request)
    {
        // Convertir les checkboxes en boolean
        $request->merge([
            'maintenance_mode' => filter_var($request->input('maintenance_mode'), FILTER_VALIDATE_BOOLEAN),
            'beta_mode' => filter_var($request->input('beta_mode'), FILTER_VALIDATE_BOOLEAN),
            'force_2fa_for_admins' => filter_var($request->input('force_2fa_for_admins'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'maintenance_mode' => 'required|boolean',
            'beta_mode' => 'required|boolean',
            'force_2fa_for_admins' => 'required|boolean',
            'whatsapp_support_link' => 'required|url',
            'maintenance_message' => 'required|string|max:500',
        ], [
            'maintenance_mode.required' => 'Le mode maintenance est requis',
            'maintenance_mode.boolean' => 'Le mode maintenance doit être vrai ou faux',
            'beta_mode.required' => 'Le mode beta est requis',
            'beta_mode.boolean' => 'Le mode beta doit être vrai ou faux',
            'force_2fa_for_admins.required' => 'Le paramètre 2FA est requis',
            'force_2fa_for_admins.boolean' => 'Le paramètre 2FA doit être vrai ou faux',
            'whatsapp_support_link.required' => 'Le lien WhatsApp est requis',
            'whatsapp_support_link.url' => 'Le lien WhatsApp doit être une URL valide',
            'maintenance_message.required' => 'Le message de maintenance est requis',
            'maintenance_message.max' => 'Le message ne doit pas dépasser 500 caractères',
        ]);

        try {
            // Mettre à jour chaque paramètre
            SystemSetting::set('maintenance_mode', $validated['maintenance_mode'], 'boolean');
            SystemSetting::set('beta_mode', $validated['beta_mode'], 'boolean');
            SystemSetting::set('force_2fa_for_admins', $validated['force_2fa_for_admins'], 'boolean');
            SystemSetting::set('whatsapp_support_link', $validated['whatsapp_support_link'], 'string');
            SystemSetting::set('maintenance_message', $validated['maintenance_message'], 'string');

            // Invalider tout le cache des paramètres
            Cache::flush();

            return redirect()
                ->route('admin.settings.index')
                ->with('success', 'Les paramètres ont été mis à jour avec succès');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage())
                ->withInput();
        }
    }
}
