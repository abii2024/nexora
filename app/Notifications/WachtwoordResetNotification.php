<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * US-15: Nederlandse variant van de standaard ResetPassword-notificatie.
 *
 *  - Token-lifetime: 60 minuten (Laravel's default via config/auth.php).
 *  - Mail-kanaal — in development logt `MAIL_MAILER=log` naar storage/logs/laravel.log.
 *  - Mail-template verwijst naar `resources/views/emails/wachtwoord-reset.blade.php`.
 */
class WachtwoordResetNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Wachtwoord herstellen — Nexora')
            ->view('emails.wachtwoord-reset', [
                'url' => $url,
                'minuten' => (int) (config('auth.passwords.'.config('auth.defaults.passwords').'.expire') ?? 60),
                'naam' => $notifiable->name ?? '',
            ]);
    }
}
