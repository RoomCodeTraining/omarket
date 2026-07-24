<?php

declare(strict_types=1);

namespace App\Actions\Partners;

use App\Enums\CargoStatus;
use App\Models\Cargo;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PartnerDepositReminder;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;

final class NotifyPartnersOfUpcomingCargoDeposit
{
    /**
     * @return int Number of partners notified
     */
    public function handle(?int $daysBefore = null): int
    {
        $days = $daysBefore ?? SiteSettings::partnerDepositReminderDays();
        $targetDate = now()->startOfDay()->addDays($days);

        $cargos = Cargo::query()
            ->whereDate('estimated_arrival_at', $targetDate->toDateString())
            ->whereIn('status', [
                CargoStatus::Open,
                CargoStatus::Closed,
                CargoStatus::InTransit,
            ])
            ->get();

        $notified = 0;

        foreach ($cargos as $cargo) {
            $products = Product::query()
                ->with('owner')
                ->where('cargo_id', $cargo->id)
                ->whereNotNull('user_id')
                ->whereNull('warehouse_deposited_at')
                ->whereNull('deposit_reminder_sent_at')
                ->where(function ($query): void {
                    $query->whereHas('orderItems')
                        ->orWhereHas('cargoItems.orderItems')
                        ->orWhereHas('cargoItems', fn ($itemQuery) => $itemQuery->where('quantity_reserved', '>', 0));
                })
                ->get();

            foreach ($products->groupBy('user_id') as $partnerProducts) {
                /** @var User|null $partner */
                $partner = $partnerProducts->first()?->owner;

                if ($partner === null || ! $partner->isPartner()) {
                    continue;
                }

                Notification::send(
                    $partner,
                    new PartnerDepositReminder(
                        $cargo,
                        $partnerProducts->pluck('name')->all(),
                    ),
                );

                Product::query()
                    ->whereIn('id', $partnerProducts->pluck('id'))
                    ->update(['deposit_reminder_sent_at' => now()]);

                $notified++;
            }
        }

        return $notified;
    }
}
