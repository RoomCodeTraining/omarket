<?php

namespace App\Actions\Arrivals;

use App\Enums\CargoStatus;
use App\Enums\ReservationStatus;
use App\Models\CargoItem;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ReserveCargoItem
{
    /**
     * @param  array{guest_name: string, guest_email: string, quantity: int}  $data
     */
    public function handle(CargoItem $cargoItem, array $data, ?User $user = null): Reservation
    {
        return DB::transaction(function () use ($cargoItem, $data, $user) {
            /** @var CargoItem $item */
            $item = CargoItem::query()
                ->with('cargo')
                ->lockForUpdate()
                ->findOrFail($cargoItem->id);

            if ($item->cargo?->status !== CargoStatus::Open) {
                throw ValidationException::withMessages([
                    'cargo_item_id' => 'Ce cargo n’accepte plus de réservations.',
                ]);
            }

            $quantity = (int) $data['quantity'];

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'La quantité doit être au moins 1.',
                ]);
            }

            if ($item->quantityRemaining() < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Quantité insuffisante (reste : {$item->quantityRemaining()}).",
                ]);
            }

            $item->increment('quantity_reserved', $quantity);

            return Reservation::query()->create([
                'cargo_item_id' => $item->id,
                'user_id' => $user?->id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'quantity' => $quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'status' => ReservationStatus::Confirmed,
                'reference' => 'RSV-'.strtoupper(Str::random(8)),
            ]);
        });
    }
}
