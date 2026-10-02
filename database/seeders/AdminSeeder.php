<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        ['name' => $name, 'email' => $email, 'password' => $password] = config('catalogue.admin');

        if (blank($email) || blank($password)) {
            $this->command?->warn('DEMO_ADMIN_EMAIL ou DEMO_ADMIN_PASSWORD absent : compte admin non créé.');

            return;
        }

        // est_admin n'est pas « fillable » : aucun formulaire ne doit pouvoir le modifier.
        User::unguarded(fn () => User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'email_verified_at' => now(), 'est_admin' => true],
        ));
    }
}
