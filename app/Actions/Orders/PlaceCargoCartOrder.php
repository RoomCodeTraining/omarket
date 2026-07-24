<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\CargoStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\CargoItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\User;
use App\Support\CargoCart;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PlaceCargoCartOrder
{
    /**
     * @param  array{
     *     guest_name: string,
     *     guest_email: string,
     *     notes?: string|null,
     *     create_account?: bool,
     *     password?: string|null
     * }  $data
     */
    public function handle(array $data, ?User $user = null): Order
    {
        $state = CargoCart::state();

        if ($state['cargo_id'] === null || $state['items'] === []) {
            throw ValidationException::withMessages([
                'cart' => 'Votre panier arrivage est vide.',
            ]);
        }

        return DB::transaction(function () use ($data, $user, $state) {
            $resolvedUser = $this->resolveUser($data, $user);
            $subtotal = 0;
            $lines = [];

            foreach ($state['items'] as $line) {
                /** @var CargoItem $item */
                $item = CargoItem::query()
                    ->with(['cargo', 'product'])
                    ->lockForUpdate()
                    ->findOrFail($line['cargo_item_id']);

                if ((int) $item->cargo_id !== (int) $state['cargo_id']) {
                    throw ValidationException::withMessages([
                        'cart' => 'Le panier mélange plusieurs cargos. Videz-le et recommencez.',
                    ]);
                }

                if (! in_array($item->cargo?->status, [CargoStatus::Open, CargoStatus::InTransit], true)) {
                    throw ValidationException::withMessages([
                        'cart' => "Le cargo {$item->cargo?->code} n’accepte plus de réservations.",
                    ]);
                }

                $quantity = (int) $line['quantity'];

                if ($quantity < 1 || $quantity > $item->quantityRemaining()) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuffisant pour « {$item->product?->name} » (reste : {$item->quantityRemaining()}).",
                    ]);
                }

                $lineTotal = $item->unit_price_cents * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'item' => $item,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $order = Order::query()->create([
                'user_id' => $resolvedUser?->id,
                'cargo_id' => $state['cargo_id'],
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'type' => OrderType::Cargo,
                'status' => OrderStatus::PendingPayment,
                'subtotal_cents' => $subtotal,
                'shipping_cents' => 0,
                'total_cents' => $subtotal,
                'currency' => SiteSettings::currencyCode(),
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                /** @var CargoItem $item */
                $item = $line['item'];
                $product = $item->product;

                $item->increment('quantity_reserved', $line['quantity']);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product?->name ?? 'Produit arrivage',
                    'product_unit' => $product?->unit ?? 'unité',
                    'unit_price_cents' => $item->unit_price_cents,
                    'quantity' => $line['quantity'],
                    'line_total_cents' => $line['line_total'],
                    'cargo_item_id' => $item->id,
                ]);

                Reservation::query()->create([
                    'cargo_item_id' => $item->id,
                    'order_id' => $order->id,
                    'user_id' => $resolvedUser?->id,
                    'guest_name' => $data['guest_name'],
                    'guest_email' => $data['guest_email'],
                    'quantity' => $line['quantity'],
                    'unit_price_cents' => $item->unit_price_cents,
                    'status' => ReservationStatus::Confirmed,
                    'reference' => 'RSV-'.strtoupper(Str::random(8)),
                ]);
            }

            CargoCart::clear();

            if ($resolvedUser !== null && Auth::id() !== $resolvedUser->id) {
                Auth::login($resolvedUser);
            }

            return $order->refresh()->load(['items', 'cargo']);
        });
    }

    /**
     * @param  array{guest_name: string, guest_email: string, create_account?: bool, password?: string|null}  $data
     */
    private function resolveUser(array $data, ?User $user): ?User
    {
        if ($user !== null) {
            return $user;
        }

        $requiresAccount = SiteSettings::checkoutRequiresAccount();
        $createAccount = (bool) ($data['create_account'] ?? false);

        if ($requiresAccount && ! $createAccount) {
            throw ValidationException::withMessages([
                'guest_email' => 'Un compte client est requis pour valider une commande.',
            ]);
        }

        if (! $createAccount) {
            return null;
        }

        $password = $data['password'] ?? null;

        if (! is_string($password) || strlen($password) < 8) {
            throw ValidationException::withMessages([
                'password' => 'Le mot de passe doit contenir au moins 8 caractères.',
            ]);
        }

        if (User::query()->where('email', $data['guest_email'])->exists()) {
            throw ValidationException::withMessages([
                'guest_email' => 'Un compte existe déjà avec cet e-mail. Connectez-vous.',
            ]);
        }

        return User::query()->create([
            'name' => $data['guest_name'],
            'email' => $data['guest_email'],
            'password' => Hash::make($password),
            'role' => UserRole::Client,
            'is_admin' => false,
            'can_publish' => false,
        ]);
    }
}
