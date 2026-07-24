<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CustomRequest;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomRequestValidatedForTeam extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomRequest $customRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->customRequest->loadMissing('user');
        $appName = SiteSettings::storeName();
        $client = $request->guest_name ?: $request->user?->name ?: 'Client';
        $email = $request->guest_email ?: $request->user?->email ?: '—';

        return (new MailMessage)
            ->subject("Course validée — {$request->title}")
            ->greeting('Équipe '.$appName.',')
            ->line("La course « {$request->title} » est validée et prête pour devis / exécution.")
            ->line("Client : {$client} ({$email})")
            ->line("Quantité : {$request->quantity}")
            ->line('Fournisseur : '.$request->supplierLabel())
            ->line($request->description)
            ->action('Ouvrir dans l’admin', url('/admin/courses/'.$request->id))
            ->salutation("Notification {$appName}");
    }
}
