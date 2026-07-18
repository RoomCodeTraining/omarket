<?php

namespace App\Http\Controllers;

use App\Actions\Courses\SubmitCustomRequest;
use App\Http\Requests\Courses\StoreCustomRequestRequest;
use App\Models\CustomRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $examples = CustomRequest::query()
            ->with(['quotes'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (CustomRequest $request) => [
                'id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'quantity' => $request->quantity,
                'budget' => $request->budget_cents
                    ? number_format($request->budget_cents / 100, 2, ',', ' ').' $'
                    : null,
                'status' => $request->status->value,
                'status_label' => $request->status->label(),
                'quote' => $request->quotes->first()
                    ? [
                        'amount' => $request->quotes->first()->amountFormatted(),
                        'status_label' => $request->quotes->first()->status->label(),
                        'message' => $request->quotes->first()->message,
                    ]
                    : null,
            ]);

        return Inertia::render('Courses/Index', [
            'examples' => $examples,
        ]);
    }

    public function store(StoreCustomRequestRequest $request, SubmitCustomRequest $action): RedirectResponse
    {
        $customRequest = $action->handle($request->payload(), $request->user());

        return back()->with(
            'success',
            "Demande « {$customRequest->title} » envoyée. Nous revenons vers vous avec un devis.",
        );
    }
}
