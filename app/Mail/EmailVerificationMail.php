<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('✉️ Vérifiez votre adresse email - SAGAPASS')
                    ->markdown('emails.verify-email')
                    ->with([
                        'userName' => $this->user->first_name . ' ' . $this->user->last_name,
                        'verificationUrl' => $this->verificationUrl,
                        'expiresAt' => '60 minutes',
                    ]);
    }
}
