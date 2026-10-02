<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->redirectGuestsTo('/admin/connexion');
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Hors développement : page d'erreur Inertia ou JSON générique, jamais de trace.
        // Les erreurs de validation (422) et les redirections gardent leur comportement.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $statut = $response->getStatusCode();
            if (app()->hasDebugModeEnabled() || ! in_array($statut, [403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Une erreur est survenue'], $statut);
            }

            if ($statut === 419) {
                return back()->with('message', 'La page a expiré, veuillez réessayer.');
            }

            return Inertia::render('Erreur', ['statut' => $statut])
                ->toResponse($request)
                ->setStatusCode($statut);
        });
    })->create();
