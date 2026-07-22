<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Partners\RegisterPartner;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Partners\RegisterPartnerRequest;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PartnerAuthController extends Controller
{
    public function showRegister(): Response
    {
        abort_unless(SiteSettings::partnerRegistrationEnabled(), 404);

        return Inertia::render('Auth/PartnerRegister');
    }

    public function register(RegisterPartnerRequest $request, RegisterPartner $action): RedirectResponse
    {
        abort_unless(SiteSettings::partnerRegistrationEnabled(), 404);

        $partner = $action->handle($request->payload());

        Auth::login($partner);
        $request->session()->regenerate();

        return redirect()
            ->route('partner.dashboard')
            ->with('success', 'Compte partenaire créé. Vous pourrez publier après validation par Ôhéfê.');
    }

    public function showLogin(): Response
    {
        return Inertia::render('Auth/PartnerLogin');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->onlyInput('email');
        }

        $user = $request->user();

        if ($user?->role !== UserRole::Partner) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Cet espace est réservé aux partenaires.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('partner.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
