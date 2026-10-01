<?php

use App\Http\Controllers\ServiceStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', ServiceStatusController::class)->name('home');
