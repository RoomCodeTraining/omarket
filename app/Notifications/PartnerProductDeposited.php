<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PartnerProductDeposited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->product->loadMissing('cargo');
        $appName = SiteSettings::storeName();
        $qty = $product->warehouse_deposited_quantity;
        $when = $product->warehouse_deposited_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject("Dépôt enregistré — {$product->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L’équipe {$appName} a enregistré le dépôt de votre produit « {$product->name} » à l’entrepôt.")
            ->line('Quantité déposée : '.($qty ?? '—'))
            ->line("Date : {$when}")
            ->when(
                $product->cargo !== null,
                fn (MailMessage $mail) => $mail->line("Cargo : {$product->cargo->code} — {$product->cargo->name}"),
            )
            ->action('Voir mon espace', route('partner.dashboard'))
            ->salutation("L’équipe {$appName}");
    }
}
