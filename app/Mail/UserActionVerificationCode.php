<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserActionVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $action,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Code de vérification - RH Manager',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.user-action-verification-code',
        );
    }
}
