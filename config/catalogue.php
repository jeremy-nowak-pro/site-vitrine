<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Volumétrie des seeders
    |--------------------------------------------------------------------------
    |
    | 2 000 pièces par défaut. `sail artisan app:install --pieces=300000`
    | ou SEED_PIECES=300000 génère le jeu de test de charge.
    |
    */

    'seed' => [
        'pieces' => (int) env('SEED_PIECES', 2000),
        'documents' => (int) env('SEED_DOCUMENTS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compte administrateur de démonstration
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('DEMO_ADMIN_NAME', 'Administrateur démo'),
        'email' => env('DEMO_ADMIN_EMAIL'),
        'password' => env('DEMO_ADMIN_PASSWORD'),
    ],

];
