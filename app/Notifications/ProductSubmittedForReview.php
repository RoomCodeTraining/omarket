<?php

namespace App\Notifications;

use App\Filament\Resources\PartnerProducts\PartnerProductResource;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProductSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->product->loadMissing('owner');
        $appName = config('app.name', 'Ôhéfê Market');
        $partnerName = $product->owner?->name ?? 'Partenaire';
        $reviewUrl = PartnerProductResource::getUrl('edit', ['record' => $product]);

        return (new MailMessage)
            ->subject("Produit partenaire à valider — {$product->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$partnerName} a soumis un produit partenaire pour validation sur {$appName}.")
            ->line("Produit : {$product->name}")
            ->line('Statut : en attente de revue (espace Partenaires)')
            ->action('Examiner dans l’admin', $reviewUrl)
            ->line('Ouvrez la section Partenaires → Produits partenaires pour publier ou corriger.')
            ->salutation("L’équipe {$appName}");
    }
}
