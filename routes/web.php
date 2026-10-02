<?php

use App\Http\Controllers\Admin\ConnexionController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\PieceController as AdminPieceController;
use App\Http\Controllers\Admin\StatistiquesController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\FavorisController;
use App\Http\Controllers\PieceController;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogueController::class)->name('catalogue');

Route::get('/pieces/{piece:reference}', [PieceController::class, 'show'])->name('pieces.show');
Route::get('/pieces/{piece:reference}/fichier.{format}', [PieceController::class, 'fichier'])
    ->whereIn('format', ['glb', 'stl'])
    ->name('pieces.fichier');

Route::get('/favoris', [FavorisController::class, 'index'])->name('favoris');
Route::get('/favoris/pieces', [FavorisController::class, 'pieces'])
    ->middleware('throttle:60,1')
    ->name('favoris.pieces');

Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation');
Route::get('/documentation/{document:slug}', [DocumentationController::class, 'show'])->name('documentation.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/connexion', [ConnexionController::class, 'create'])->name('connexion');
        Route::post('/connexion', [ConnexionController::class, 'store'])->name('connexion.store');
    });

    // Aucune route d'écriture : l'ajout de pièce et l'import CSV sont désactivés dans la démo.
    Route::middleware(['auth', 'can:acceder-admin'])->group(function () {
        Route::redirect('/', '/admin/statistiques')->name('accueil');
        Route::get('/statistiques', StatistiquesController::class)->name('statistiques');
        Route::get('/pieces/nouvelle', [AdminPieceController::class, 'create'])->name('pieces.create');
        Route::get('/import', ImportController::class)->name('import');
        Route::post('/deconnexion', [ConnexionController::class, 'destroy'])->name('deconnexion');
    });
});
