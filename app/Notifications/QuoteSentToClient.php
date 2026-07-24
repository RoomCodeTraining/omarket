<?php

declare(strict_types=1);

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
        $quote = $this->quote->loadMissing(['customRequest.items', 'customRequest.user']);
        $request = $quote->customRequest;
        $appName = SiteSettings::storeName();
        $name = $notifiable->name
            ?? $request?->guest_name
            ?? $request?->user?->name
            ?? 'Client';

        $mail = (new MailMessage)
            ->subject("Votre devis course — {$request?->title}")
            ->greeting("Bonjour {$name},")
            ->line("Nous avons préparé un devis pour votre course « {$request?->title} » sur {$appName}.")
            ->line('Montant du devis : '.$quote->amountFormatted());

        if ($request !== null) {
            foreach ($request->items as $item) {
                $budget = $item->budgetFormatted();
                $mail->line($budget !== null
                    ? "• {$item->label} × {$item->quantity} (budget indiqué {$budget})"
                    : "• {$item->label} × {$item->quantity}");
            }
        }

        if (filled($quote->message)) {
            $mail->line($quote->message);
        }

        if ($quote->valid_until) {
            $mail->line('Valable jusqu’au '.$quote->valid_until->format('d/m/Y').'.');
        }

        return $mail
            ->action('Voir mon compte', url('/compte'))
            ->line('Répondez à cet e-mail ou contactez-nous pour accepter le devis ou poser une question.')
            ->salutation("L’équipe {$appName}");
    }
}
