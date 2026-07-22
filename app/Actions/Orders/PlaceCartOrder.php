<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class PlaceCartOrder
{
    /**
     * @param  array{
     *     guest_name: string,
     *     guest_email: string,
     *     shipping_phone?: string|null,
     *     shipping_line1: string,
     *     shipping_line2?: string|null,
     *     shipping_city: string,
     *     shipping_province: string,
     *     shipping_postal_code: string,
     *     shipping_country?: string,
     *     notes?: string|null,
     *     create_account?: bool,
     *     password?: string|null
     * }  $data
     */
    public function handle(array $data, ?User $user = null): Order
    {
        $raw = Cart::raw();

        if ($raw === []) {
            throw ValidationException::withMessages([
                'cart' => 'Votre panier est vide.',
            ]);
        }

        return DB::transaction(function () use ($data, $user, $raw) {
            $resolvedUser = $this->resolveUser($data, $user);

            $lines = [];
            $subtotal = 0;

            foreach ($raw as $item) {
                /** @var Product $product */
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($item['product_id']);

                if ($product === null || ! $product->isAvailableLocally()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Un article du panier n’est plus disponible. Mettez à jour votre panier.',
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($quantity < 1 || $quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuffisant pour « {$product->name} » (disponible : {$product->stock_quantity}).",
                    ]);
                }

                $lineTotal = $product->price_cents * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $shipping = 0;

            $order = Order::query()->create([
                'user_id' => $resolvedUser?->id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_line1' => $data['shipping_line1'],
                'shipping_line2' => $data['shipping_line2'] ?? null,
                'shipping_city' => $data['shipping_city'],
                'shipping_province' => $data['shipping_province'],
                'shipping_postal_code' => $data['shipping_postal_code'],
                'shipping_country' => $data['shipping_country'] ?? 'CA',
                'type' => OrderType::Stock,
                'status' => OrderStatus::PendingPayment,
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shipping,
                'total_cents' => $subtotal + $shipping,
                'currency' => SiteSettings::currencyCode(),
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_unit' => $product->unit,
                    'unit_price_cents' => $product->price_cents,
                    'quantity' => $line['quantity'],
                    'line_total_cents' => $line['line_total'],
                ]);

                $product->decrement('stock_quantity', $line['quantity']);
            }

            Cart::clear();

            if ($resolvedUser !== null && Auth::id() !== $resolvedUser->id) {
                Auth::login($resolvedUser);
            }

            return $order->refresh()->load('items');
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

        if (! ($data['create_account'] ?? false)) {
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
                'guest_email' => 'Un compte existe déjà avec cet e-mail. Connectez-vous ou validez en invité.',
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
