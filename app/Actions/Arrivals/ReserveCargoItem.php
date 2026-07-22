<?php

namespace App\Actions\Arrivals;

use App\Enums\CargoStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ReservationStatus;
use App\Models\CargoItem;
use App\Models\Order;
use App\Models\OrderItem;
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
                ->with(['cargo', 'product'])
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

            $lineTotal = $item->unit_price_cents * $quantity;
            $product = $item->product;

            $order = Order::query()->create([
                'user_id' => $user?->id,
                'cargo_id' => $item->cargo_id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'type' => OrderType::Cargo,
                'status' => OrderStatus::PaymentConfirmed,
                'subtotal_cents' => $lineTotal,
                'shipping_cents' => 0,
                'total_cents' => $lineTotal,
                'currency' => 'CAD',
                'notes' => "Réservation arrivage {$item->cargo?->code}",
                'placed_at' => now(),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_name' => $product?->name ?? 'Produit arrivage',
                'product_unit' => $product?->unit ?? 'unité',
                'unit_price_cents' => $item->unit_price_cents,
                'quantity' => $quantity,
                'line_total_cents' => $lineTotal,
                'cargo_item_id' => $item->id,
            ]);

            $reservation = Reservation::query()->create([
                'cargo_item_id' => $item->id,
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'quantity' => $quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'status' => ReservationStatus::Confirmed,
                'reference' => 'RSV-'.strtoupper(Str::random(8)),
            ]);

            return $reservation;
        });
    }
}
