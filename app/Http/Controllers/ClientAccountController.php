<?php

namespace App\Http\Controllers;

use App\Models\CustomRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientAccountController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isPartner()) {
            return redirect()->route('partner.dashboard');
        }

        abort_unless($user !== null && ($user->isClient() || $user->isAdmin()), 403);

        $orders = Order::query()
            ->with('items')
            ->where('user_id', $user->id)
            ->latest('placed_at')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'type_label' => $order->type->label(),
                'total' => $order->totalFormatted(),
                'placed_at' => $order->placed_at?->format('d/m/Y H:i'),
                'items_count' => $order->items->count(),
            ]);

        $customRequests = CustomRequest::query()
            ->with(['quotes' => fn ($query) => $query->latest()])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function (CustomRequest $request) {
                $latestQuote = $request->quotes->first();

                return [
                    'id' => $request->id,
                    'title' => $request->title,
                    'description' => $request->description,
                    'quantity' => $request->quantity,
                    'budget' => $request->budget_cents !== null
                        ? number_format($request->budget_cents / 100, 2, ',', ' ').' $'
                        : null,
                    'status' => $request->status->value,
                    'status_label' => $request->status->label(),
                    'created_at' => $request->created_at?->format('d/m/Y'),
                    'latest_quote' => $latestQuote
                        ? [
                            'amount' => $latestQuote->amountFormatted(),
                            'status' => $latestQuote->status->value,
                            'status_label' => $latestQuote->status->label(),
                        ]
                        : null,
                ];
            });

        return Inertia::render('Account/Index', [
            'client' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'orders' => $orders,
            'custom_requests' => $customRequests,
        ]);
    }
}
