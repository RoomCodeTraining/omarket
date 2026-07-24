<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Clients\RegisterClient;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Clients\RegisterClientRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientAuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/ClientLogin');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/ClientRegister');
    }

    public function register(RegisterClientRequest $request, RegisterClient $action): RedirectResponse
    {
        $client = $action->handle($request->payload());

        Auth::login($client);
        $request->session()->regenerate();

        return redirect()
            ->route('account.index')
            ->with('success', 'Compte créé. Bienvenue sur Ôhéfê Market.');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->onlyInput('email');
        }

        $user = $request->user();

        if ($user?->role === UserRole::Partner) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Utilisez l’espace partenaire pour vous connecter.',
            ])->onlyInput('email');
        }

        if ($user?->role !== UserRole::Client && ! ($user?->isAdmin() ?? false)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
