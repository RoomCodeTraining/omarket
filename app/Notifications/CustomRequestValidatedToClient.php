<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CustomRequest;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomRequestValidatedToClient extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomRequest $customRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->customRequest;
        $appName = SiteSettings::storeName();
        $name = $notifiable->name ?? $request->guest_name ?? 'Client';

        return (new MailMessage)
            ->subject("Course validée — {$request->title}")
            ->greeting("Bonjour {$name},")
            ->line("Bonne nouvelle : votre course « {$request->title} » a été validée par l’équipe {$appName}.")
            ->line('Nous préparons un devis et organiserons l’achat / l’expédition.')
            ->line('Fournisseur : '.$request->supplierLabel())
            ->action('Voir mon compte', url('/compte'))
            ->line('Vous recevrez le devis par e-mail dès qu’il sera prêt.')
            ->salutation("L’équipe {$appName}");
    }
}
