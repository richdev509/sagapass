<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
        $this->user = $document->user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $documentTypeName = $this->document->document_type === 'cni'
            ? 'Carte Nationale d\'Identité'
            : 'Passeport';

        return $this->subject('✅ Document Vérifié - SAGAPASS')
                    ->markdown('emails.documents.approved')
                    ->with([
                        'userName' => $this->user->first_name . ' ' . $this->user->last_name,
                        'documentType' => $documentTypeName,
                        'documentNumber' => $this->document->document_number,
                        'verifiedAt' => $this->document->verified_at->format('d/m/Y à H:i'),
                        'dashboardUrl' => route('dashboard'),
                    ]);
    }
}
