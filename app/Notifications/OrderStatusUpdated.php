<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $appName = SiteSettings::storeName();
        $name = $notifiable->name ?? $order->customerName();

        return (new MailMessage)
            ->subject("Commande {$order->reference} — {$order->status->label()}")
            ->greeting("Bonjour {$name},")
            ->line("Le statut de votre commande {$order->reference} a été mis à jour.")
            ->line('Nouveau statut : '.$order->status->label())
            ->line('Montant : '.$order->totalFormatted())
            ->action('Voir la boutique', url('/'))
            ->line('Merci de votre confiance.')
            ->salutation("L’équipe {$appName}");
    }
}
