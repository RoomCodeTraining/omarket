<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CustomRequest;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomRequestCargoAssignedToClient extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomRequest $customRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->customRequest->loadMissing('cargo');
        $cargo = $request->cargo;
        $appName = SiteSettings::storeName();
        $name = $notifiable->name ?? $request->guest_name ?? 'Client';

        $mail = (new MailMessage)
            ->subject("Votre course est sur le cargo {$cargo?->code} — {$request->title}")
            ->greeting("Bonjour {$name},")
            ->line("Votre course « {$request->title} » a été associée à un arrivage {$appName}.");

        if ($cargo !== null) {
            $mail->line("Cargo : {$cargo->code} — {$cargo->name}")
                ->line('Trajet : '.$cargo->routeLabel())
                ->line('Statut : '.$cargo->status->label())
                ->when(
                    $cargo->departure_at,
                    fn (MailMessage $message) => $message->line('Départ : '.$cargo->departure_at->format('d/m/Y')),
                )
                ->when(
                    $cargo->estimated_arrival_at,
                    fn (MailMessage $message) => $message->line(
                        'Arrivée estimée Canada : '.$cargo->estimated_arrival_at->format('d/m/Y'),
                    ),
                );
        }

        return $mail
            ->action('Suivre les arrivages', url('/arrivages'))
            ->line('Nous vous tiendrons informé de la suite du trajet.')
            ->salutation("L’équipe {$appName}");
    }
}
