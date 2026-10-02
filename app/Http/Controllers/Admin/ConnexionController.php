<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConnexionController extends Controller
{
    private const TENTATIVES_MAX = 5;

    public function create(): Response
    {
        return Inertia::render('Admin/Connexion');
    }

    public function store(Request $request): RedirectResponse
    {
        $identifiants = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $cle = Str::transliterate(Str::lower($identifiants['email'])).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, self::TENTATIVES_MAX)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.',
            ]);
        }

        if (! Auth::attempt($identifiants, $request->boolean('remember'))) {
            RateLimiter::hit($cle, 60);

            // Message identique que l'e-mail existe ou non.
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }

        RateLimiter::clear($cle);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.statistiques'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalogue');
    }
}
