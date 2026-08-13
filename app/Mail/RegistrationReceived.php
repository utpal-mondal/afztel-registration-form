<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New ' . $this->registration->company_type . ' registration: ' . $this->registration->company_name,
            replyTo: [$this->registration->email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registration-admin');
    }

    /**
     * Attach whatever documents the applicant uploaded.
     */
    public function attachments(): array
    {
        $files = array_filter([
            $this->registration->registration_doc_path,
            $this->registration->vat_certificate_path,
            $this->registration->signature_path,
        ]);

        return array_map(
            fn ($path) => Attachment::fromStorageDisk('local', $path),
            array_values($files)
        );
    }
}
