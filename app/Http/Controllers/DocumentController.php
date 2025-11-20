<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = Auth::guard('web')->user()->documents()
            ->latest()
            ->paginate(10);

        return view('documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::guard('web')->user();

        // Vérifier que l'email est vérifié
        if (!$user->email_verified_at) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez vérifier votre email avant de pouvoir soumettre un document. Veuillez consulter votre boîte mail.');
        }

        // Vérifier que le téléphone et la date de naissance sont renseignés
        if (!$user->phone || !$user->date_of_birth) {
            return redirect()->route('profile.edit')
                ->with('error', 'Vous devez compléter votre profil (téléphone et date de naissance) avant de pouvoir soumettre un document.');
        }

        // Récupérer les documents actifs de l'utilisateur
        $activeDocuments = $user->documents()
            ->whereIn('verification_status', ['pending', 'verified'])
            ->get();

        return view('documents.create', compact('activeDocuments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Vérifier que l'email est vérifié
        if (!$user->email_verified_at) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez vérifier votre email avant de pouvoir soumettre un document.');
        }

        // Vérifier que le téléphone et la date de naissance sont renseignés
        if (!$user->phone || !$user->date_of_birth) {
            return redirect()->route('profile.edit')
                ->with('error', 'Vous devez compléter votre profil (téléphone et date de naissance) avant de pouvoir soumettre un document.');
        }

        // Validation de base
        $request->validate([
            'document_type' => ['required', 'in:cni,passport'],
        ]);

        // Vérifier si l'utilisateur a déjà un document actif du même type
        $existingDocument = Auth::guard('web')->user()->documents()
            ->where('document_type', $request->document_type)
            ->whereIn('verification_status', ['pending', 'verified'])
            ->first();

        if ($existingDocument) {
            $documentTypeName = $request->document_type === 'cni' ? 'Carte Nationale d\'Identité (NIU)' : 'Passeport';
            $statusText = $existingDocument->verification_status === 'pending' ? 'en attente de vérification' : 'déjà vérifié';

            return redirect()->route('documents.create')
                ->withInput()
                ->with('error', "Vous avez déjà un document {$documentTypeName} {$statusText}. Vous ne pouvez pas soumettre un autre document du même type tant que celui-ci est actif.");
        }

        // Validation conditionnelle selon le type
        $rules = [
            'document_type' => ['required', 'in:cni,passport'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after:issue_date', 'after:today'],
            'front_photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ];

        // Pour NIU (carte nationale): exactement 10 chiffres + numéro de carte + verso obligatoire
        if ($request->document_type === 'cni') {
            $rules['card_number'] = ['required', 'string', 'regex:/^[A-Z0-9]{9}$/', 'unique:documents,card_number'];
            $rules['document_number'] = ['required', 'regex:/^\d{10}$/', 'unique:documents,document_number'];
            $rules['back_photo'] = ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120']; // Obligatoire pour CNI
        } else {
            // Pour passeport: format alphanumérique + verso optionnel + pas de card_number
            $rules['document_number'] = ['required', 'string', 'min:6', 'max:20', 'unique:documents,document_number'];
            $rules['back_photo'] = ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120']; // Optionnel pour passeport
        }

        $validated = $request->validate($rules, [
            'card_number.regex' => 'Le numéro de carte doit contenir exactement 9 caractères alphanumériques (lettres majuscules et chiffres).',
            'card_number.unique' => 'Ce numéro de carte existe déjà dans notre système.',
            'document_number.regex' => 'Le numéro NIU doit contenir exactement 10 chiffres.',
            'document_number.unique' => 'Ce numéro de document existe déjà dans notre système.',
            'expiry_date.after' => 'La date d\'expiration doit être postérieure à la date de délivrance et à aujourd\'hui.',
        ]);

        $user = Auth::guard('web')->user();

        // Créer le dossier utilisateur
        $userFolder = 'documents/' . $user->id;

        // Sauvegarder les photos
        $frontPath = $request->file('front_photo')->store($userFolder, 'private');
        $backPath = $request->hasFile('back_photo')
            ? $request->file('back_photo')->store($userFolder, 'private')
            : null;

        // Créer le document
        $document = $user->documents()->create([
            'document_type' => $validated['document_type'],
            'card_number' => $validated['card_number'] ?? null,
            'document_number' => $validated['document_number'],
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'front_photo_path' => $frontPath,
            'back_photo_path' => $backPath,
            'verification_status' => 'pending',
        ]);

        return redirect()->route('documents.index')
            ->with('success', 'Document ajouté avec succès ! Il sera vérifié prochainement.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $document = Auth::guard('web')->user()->documents()->findOrFail($id);

        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $document = Auth::guard('web')->user()->documents()->findOrFail($id);

        // Ne permettre la modification que pour les documents en attente
        if ($document->verification_status !== 'pending') {
            return redirect()->route('documents.index')
                ->with('error', 'Impossible de modifier un document déjà vérifié.');
        }

        return view('documents.edit', compact('document'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $document = Auth::guard('web')->user()->documents()->findOrFail($id);

        if ($document->verification_status !== 'pending') {
            return redirect()->route('documents.index')
                ->with('error', 'Impossible de modifier un document déjà vérifié.');
        }

        // Validation conditionnelle selon le type
        $rules = [
            'document_type' => ['required', 'in:cni,passport'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after:issue_date', 'after:today'],
            'front_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ];

        // Pour NIU (carte nationale): exactement 10 chiffres + numéro de carte + verso obligatoire si pas déjà présent
        if ($request->document_type === 'cni') {
            $rules['card_number'] = ['required', 'string', 'regex:/^[A-Z0-9]{9}$/', 'unique:documents,card_number,' . $document->id];
            $rules['document_number'] = ['required', 'regex:/^\d{10}$/', 'unique:documents,document_number,' . $document->id];
            // Verso obligatoire seulement si le document n'en a pas déjà un
            if (!$document->back_photo_path) {
                $rules['back_photo'] = ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'];
            } else {
                $rules['back_photo'] = ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'];
            }
        } else {
            // Pour passeport: format alphanumérique + verso optionnel + pas de card_number
            $rules['document_number'] = ['required', 'string', 'min:6', 'max:20', 'unique:documents,document_number,' . $document->id];
            $rules['back_photo'] = ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'];
        }

        $validated = $request->validate($rules, [
            'card_number.regex' => 'Le numéro de carte doit contenir exactement 9 caractères alphanumériques (lettres majuscules et chiffres).',
            'card_number.unique' => 'Ce numéro de carte existe déjà dans notre système.',
            'document_number.regex' => 'Le numéro NIU doit contenir exactement 10 chiffres.',
            'document_number.unique' => 'Ce numéro de document existe déjà dans notre système.',
            'expiry_date.after' => 'La date d\'expiration doit être postérieure à la date de délivrance et à aujourd\'hui.',
        ]);

        // Mettre à jour les photos si fournies
        if ($request->hasFile('front_photo')) {
            Storage::disk('private')->delete($document->front_photo_path);
            $validated['front_photo_path'] = $request->file('front_photo')
                ->store('documents/' . Auth::guard('web')->id(), 'private');
        }

        if ($request->hasFile('back_photo')) {
            if ($document->back_photo_path) {
                Storage::disk('private')->delete($document->back_photo_path);
            }
            $validated['back_photo_path'] = $request->file('back_photo')
                ->store('documents/' . Auth::guard('web')->id(), 'private');
        }

        $document->update($validated);

        return redirect()->route('documents.index')
            ->with('success', 'Document mis à jour avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $document = Auth::guard('web')->user()->documents()->findOrFail($id);

        // Vérifier le statut : seuls les documents rejected peuvent être supprimés
        if ($document->verification_status === 'verified') {
            return redirect()->route('documents.index')
                ->with('error', 'Impossible de supprimer un document vérifié. Les documents vérifiés sont conservés pour des raisons légales et de traçabilité.');
        }

        if ($document->verification_status === 'pending') {
            return redirect()->route('documents.index')
                ->with('error', 'Impossible de supprimer un document en attente de vérification. Veuillez attendre que l\'administrateur traite votre demande. Si vous souhaitez annuler, contactez notre support.');
        }

        // Supprimer les fichiers
        if ($document->front_photo_path) {
            Storage::disk('private')->delete($document->front_photo_path);
        }
        if ($document->back_photo_path) {
            Storage::disk('private')->delete($document->back_photo_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document supprimé avec succès.');
    }

    /**
     * Serve a private document image
     */
    public function serveImage(string $id, string $type)
    {
        $document = Auth::guard('web')->user()->documents()->findOrFail($id);

        // Vérifier le type (front ou back)
        if ($type === 'front' && $document->front_photo_path) {
            $path = $document->front_photo_path;
        } elseif ($type === 'back' && $document->back_photo_path) {
            $path = $document->back_photo_path;
        } else {
            abort(404);
        }

        // Vérifier que le fichier existe
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        // Retourner le fichier
        return Storage::disk('private')->response($path);
    }
}
