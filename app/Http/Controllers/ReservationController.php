<?php

namespace App\Http\Controllers;

use App\Actions\Arrivals\ReserveCargoItem;
use App\Http\Requests\Arrivals\StoreReservationRequest;
use App\Models\CargoItem;
use Illuminate\Http\RedirectResponse;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, ReserveCargoItem $action): RedirectResponse
    {
        $item = CargoItem::query()->findOrFail($request->integer('cargo_item_id'));

        $reservation = $action->handle(
            $item,
            $request->only(['guest_name', 'guest_email', 'quantity']),
            $request->user(),
        );

        return back()->with(
            'success',
            "Réservation confirmée ({$reservation->reference}). Un e-mail de suivi sera envoyé à {$reservation->guest_email}.",
        );
    }
}
