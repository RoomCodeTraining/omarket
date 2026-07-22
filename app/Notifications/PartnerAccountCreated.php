<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PartnerAccountCreated extends Notification implements ShouldQueue
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
            ->subject("Bienvenue partenaire — {$appName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre compte partenaire {$appName} est créé. Voici la suite :")
            ->line('1. Connectez-vous à votre espace pour préparer vos fiches produits (brouillons).')
            ->line('2. Notre équipe valide votre compte avant toute publication ou vente.')
            ->line('3. Une fois autorisé, soumettez chaque produit pour revue — il sera mis en ligne après validation Ôhéfê.')
            ->action('Accéder à mon espace', $dashboardUrl)
            ->line('Vous recevrez un message lorsque votre compte sera autorisé à publier.')
            ->salutation("L’équipe {$appName}");
    }
}
