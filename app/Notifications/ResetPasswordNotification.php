<?php

namespace App\Notifications;

use App\Support\FarmSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** The password reset email, in the farm's name instead of Laravel's default wording. */
class ResetPasswordNotification extends ResetPassword
{
    public function __construct(string $token, private ?string $sentBy = null)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        $farm    = FarmSettings::text('farm_name');
        $minutes = config('auth.passwords.users.expire', 60);

        $mail = (new MailMessage)
            ->subject("Reset your {$farm} password")
            ->greeting('Hello ' . ($notifiable->first_name ?: '') . ',');

        $mail = $this->sentBy
            ? $mail->line("{$this->sentBy} asked for a password reset link for your {$farm} account.")
            : $mail->line("We received a request to reset the password for your {$farm} account.");

        return $mail
            ->action('Choose a new password', $this->resetUrl($notifiable))
            ->line("This link works for {$minutes} minutes and can be used once.")
            ->line("If you didn't ask for this, you can ignore this email; your password stays the same.")
            ->salutation("— {$farm}");
    }
}
