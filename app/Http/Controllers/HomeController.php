<?php

namespace App\Http\Controllers;

use App\Enums\CargoStatus;
use App\Models\Cargo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $nextCargo = Cargo::query()
            ->whereIn('status', [CargoStatus::Open, CargoStatus::Draft])
            ->orderBy('estimated_arrival_at')
            ->first();

        return Inertia::render('Home', [
            'nextArrival' => $nextCargo ? [
                'label' => $nextCargo->routeLabel(),
                'eta' => $nextCargo->estimated_arrival_at?->format('d/m/Y') ?? 'Annonce à venir',
            ] : [
                'label' => 'Abidjan → Montréal',
                'eta' => 'Annonce à venir',
            ],
        ]);
    }
}
