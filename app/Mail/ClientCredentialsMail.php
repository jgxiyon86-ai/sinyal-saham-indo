<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $client,
        public readonly string $plainPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun Klient Sinyal Saham Indo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-credentials',
        );
    }
}

