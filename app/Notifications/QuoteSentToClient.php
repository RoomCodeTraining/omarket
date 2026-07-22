<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuoteSentToClient extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quote $quote) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quote = $this->quote->loadMissing('customRequest');
        $request = $quote->customRequest;
        $appName = SiteSettings::storeName();
        $name = $notifiable->name ?? $request?->guest_name ?? 'Client';

        return (new MailMessage)
            ->subject("Votre devis course — {$request?->title}")
            ->greeting("Bonjour {$name},")
            ->line("Nous avons préparé un devis pour votre course « {$request?->title} » sur {$appName}.")
            ->line('Montant : '.$quote->amountFormatted())
            ->when(
                filled($quote->message),
                fn (MailMessage $mail) => $mail->line($quote->message),
            )
            ->when(
                $quote->valid_until,
                fn (MailMessage $mail) => $mail->line('Valable jusqu’au '.$quote->valid_until->format('d/m/Y').'.'),
            )
            ->action('Voir les courses', url('/courses'))
            ->line('Répondez à cet e-mail ou contactez-nous pour accepter ou poser une question.')
            ->salutation("L’équipe {$appName}");
    }
}
