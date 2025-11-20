<?php

namespace App\Mail;

use App\Models\DeveloperApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DeveloperApplication $application;
    public string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(DeveloperApplication $application, string $reason)
    {
        $this->application = $application;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application OAuth Rejetée - SAGAPASS',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.application-rejected',
            with: [
                'applicationName' => $this->application->name,
                'reason' => $this->reason,
                'dashboardUrl' => route('developers.dashboard'),
                'editUrl' => route('developers.applications.edit', $this->application),
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
