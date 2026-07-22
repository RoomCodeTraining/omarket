<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        $user = $request->user();
        $sessionEmail = $request->session()->get('order_access.'.$order->id);

        $allowed = ($user !== null && (int) $order->user_id === (int) $user->id)
            || (is_string($sessionEmail) && strcasecmp($sessionEmail, (string) $order->guest_email) === 0)
            || ($user?->isAdmin() ?? false);

        abort_unless($allowed, 403);

        $order->load('items');

        return Inertia::render('Orders/Show', [
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'type_label' => $order->type->label(),
                'customer_name' => $order->customerName(),
                'customer_email' => $order->customerEmail(),
                'shipping_address' => $order->shippingAddressFormatted(),
                'shipping_phone' => $order->shipping_phone,
                'subtotal' => $order->subtotalFormatted(),
                'total' => $order->totalFormatted(),
                'placed_at' => $order->placed_at?->format('d/m/Y H:i'),
                'notes' => $order->notes,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'unit' => $item->product_unit,
                    'quantity' => $item->quantity,
                    'unit_price' => number_format($item->unit_price_cents / 100, 2, ',', ' ').' $',
                    'line_total' => number_format($item->line_total_cents / 100, 2, ',', ' ').' $',
                ]),
            ],
        ]);
    }
}
