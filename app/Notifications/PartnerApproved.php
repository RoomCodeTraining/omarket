<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PartnerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = route('partner.dashboard');
        $appName = config('app.name', 'Ôhéfê Market');

        return (new MailMessage)
            ->subject("Compte partenaire autorisé — {$appName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle : votre compte partenaire {$appName} est autorisé à publier.")
            ->line('Vous pouvez maintenant :')
            ->line('1. Créer ou finaliser vos fiches produits en brouillon.')
            ->line('2. Soumettre chaque produit pour revue Ôhéfê.')
            ->line('3. Une fois validé, le produit sera visible avec les produits du cargo (arrivages), pas en boutique.')
            ->action('Ouvrir mon espace partenaire', $dashboardUrl)
            ->line('Merci de faire partie du réseau Ôhéfê Market.')
            ->salutation("L’équipe {$appName}");
    }
}
