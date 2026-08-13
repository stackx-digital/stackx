<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Passwordless sign-in link. In local dev MAIL_MAILER=log writes the link to
 * storage/logs/laravel.log so the team can sign in without an SMTP setup.
 */
class MagicLoginLink extends Notification
{
    use Queueable;

    public function __construct(public string $url) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your STACKx sign-in link')
            ->greeting('STACKx Ad Intelligence')
            ->line('Click below to sign in. This link expires shortly and can be used once.')
            ->action('Sign in', $this->url)
            ->line('If you didn’t request this, you can ignore this email.');
    }
}
