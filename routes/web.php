<?php

use App\Http\Controllers\CatalogueController;
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
