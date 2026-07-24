<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CustomRequest;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomRequestSubmittedForTeam extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomRequest $customRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->customRequest->loadMissing(['user', 'items']);
        $appName = SiteSettings::storeName();
        $client = $request->guest_name ?: $request->user?->name ?: 'Client';
        $email = $request->guest_email ?: $request->user?->email ?: '—';
        $productsCount = $request->items->count();

        $mail = (new MailMessage)
            ->subject("Nouvelle course — {$request->title}")
            ->greeting('Équipe '.$appName.',')
            ->line("Une nouvelle demande de course a été créée sur {$appName}.")
            ->line("Client : {$client} ({$email})")
            ->line("Produits : {$productsCount} · Quantité totale : {$request->quantity}")
            ->line('Fournisseur : '.$request->supplierLabel());

        foreach ($request->items as $item) {
            $budget = $item->budgetFormatted();
            $mail->line($budget !== null
                ? "• {$item->label} × {$item->quantity} (budget {$budget})"
                : "• {$item->label} × {$item->quantity}");
        }

        if (filled($request->description) && $request->items->isNotEmpty()) {
            $autoDescription = CustomRequest::aggregatesFromItems($request->items, null)['description'];
            if (trim($request->description) !== trim($autoDescription)) {
                $mail->line('Notes : '.$request->description);
            }
        } elseif (filled($request->description) && $request->items->isEmpty()) {
            $mail->line($request->description);
        }

        return $mail
            ->action('Ouvrir dans l’admin', url('/admin/courses/'.$request->id.'/edit'))
            ->salutation("Notification {$appName}");
    }
}
