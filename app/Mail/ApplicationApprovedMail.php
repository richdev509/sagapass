<?php

namespace App\Mail;

use App\Models\DeveloperApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DeveloperApplication $application;

    /**
     * Create a new message instance.
     */
    public function __construct(DeveloperApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application OAuth Approuvée - SAGAPASS',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.application-approved',
            with: [
                'applicationName' => $this->application->name,
                'clientId' => $this->application->client_id,
                'approvedAt' => $this->application->approved_at,
                'dashboardUrl' => route('developers.dashboard'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
