<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $user;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Document $document, string $reason)
    {
        $this->document = $document;
        $this->user = $document->user;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $documentTypeName = $this->document->document_type === 'cni'
            ? 'Carte Nationale d\'Identité'
            : 'Passeport';

        return $this->subject('❌ Document Rejeté - SAGAPASS')
                    ->markdown('emails.documents.rejected')
                    ->with([
                        'userName' => $this->user->first_name . ' ' . $this->user->last_name,
                        'documentType' => $documentTypeName,
                        'documentNumber' => $this->document->document_number,
                        'rejectionReason' => $this->reason,
                        'rejectedAt' => now()->format('d/m/Y à H:i'),
                        'resubmitUrl' => route('documents.create'),
                        'dashboardUrl' => route('dashboard'),
                    ]);
    }
}
