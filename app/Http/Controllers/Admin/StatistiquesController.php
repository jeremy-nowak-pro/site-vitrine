<?php

namespace App\Http\Controllers\Admin;

use App\Admin\StatistiquesFictives;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StatistiquesController extends Controller
{
    public function __invoke(StatistiquesFictives $statistiques): Response
    {
        return Inertia::render('Admin/Statistiques', $statistiques->generer(now()->startOfDay()));
    }
}
