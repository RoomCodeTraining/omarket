<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Cargo;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PartnerDepositReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $productNames
     */
    public function __construct(
        public Cargo $cargo,
        public array $productNames,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Ôhéfê Market');
        $eta = $this->cargo->estimated_arrival_at?->format('d/m/Y') ?? 'bientôt';
        $warehouse = SiteSettings::warehouseAddressFormatted();
        $products = collect($this->productNames)
            ->filter()
            ->map(fn (string $name) => '• '.$name)
            ->implode("\n");

        $mail = (new MailMessage)
            ->subject("Dépôt entrepôt requis — cargo {$this->cargo->code}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le cargo {$this->cargo->code} ({$this->cargo->name}) arrive estimé le {$eta}.")
            ->line('Des commandes portent sur vos produits associés à cet arrivage. Merci de déposer le stock à l’entrepôt Ôhéfê avant l’arrivée :')
            ->line($products !== '' ? $products : '• Vos produits liés à ce cargo');

        if ($warehouse !== '') {
            $mail->line('Adresse de dépôt :')->line($warehouse);
        } else {
            $mail->line('Les coordonnées de l’entrepôt seront précisées par l’équipe Ôhéfê.');
        }

        return $mail
            ->action('Ouvrir mon espace partenaire', route('partner.dashboard'))
            ->line('Une fois le dépôt effectué, marquez-le comme déposé depuis votre tableau de bord.')
            ->salutation("L’équipe {$appName}");
    }
}
