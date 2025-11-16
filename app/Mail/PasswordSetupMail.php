<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;
    public $token;
    public $isForgotPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $resetUrl, string $token, bool $isForgotPassword = false)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->token = $token;
        $this->isForgotPassword = $isForgotPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Load company relationship if not already loaded
        if (!$this->user->relationLoaded('company')) {
            $this->user->load('company');
        }
        
        $companyName = $this->user->company->name ?? 'Our Platform';
        
        $subject = $this->isForgotPassword 
            ? 'Reset Your Password - ' . $companyName
            : 'Set Your Password - Welcome to ' . $companyName;
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-setup',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
