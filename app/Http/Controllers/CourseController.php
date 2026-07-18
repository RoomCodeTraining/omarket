<?php

namespace App\Http\Controllers;

use App\Actions\Courses\SubmitCustomRequest;
use App\Http\Requests\Courses\StoreCustomRequestRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Courses/Index');
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
