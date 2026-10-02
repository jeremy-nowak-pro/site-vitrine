<?php

namespace Database\Seeders;

use Database\Seeders\Support\Referentiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferentielSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $horodatage = ['created_at' => $now, 'updated_at' => $now];

        DB::table('categories')->insert(array_map(
            fn (string $forme, array $categorie) => [
                'nom' => $categorie['nom'],
                'slug' => Str::slug($categorie['nom']),
                'forme' => $forme,
                ...$horodatage,
            ],
            array_keys(Referentiel::CATEGORIES),
            Referentiel::CATEGORIES,
        ));

        DB::table('echelles')->insert(array_map(
            fn (array $echelle) => [
                'libelle' => $echelle[0],
                // « 1/18 » devient « 1-18 » : Str::slug supprimerait la barre et donnerait « 118 ».
                'slug' => str_replace('/', '-', $echelle[0]),
                'rapport' => $echelle[1],
                ...$horodatage,
            ],
            Referentiel::ECHELLES,
        ));

        DB::table('fabricants')->insert(array_map(
            fn (array $fabricant) => [
                'nom' => $fabricant[0],
                'slug' => Str::slug($fabricant[0]),
                'pays' => $fabricant[2],
                ...$horodatage,
            ],
            Referentiel::FABRICANTS,
        ));

        DB::table('materiaux')->insert(array_map(
            fn (string $nom) => ['nom' => $nom, 'slug' => Str::slug($nom), ...$horodatage],
            Referentiel::MATERIAUX,
        ));

        DB::table('periodes')->insert(array_map(
            fn (array $periode) => [
                'libelle' => $periode[0],
                'slug' => Str::slug($periode[0]),
                'annee_debut' => $periode[1],
                'annee_fin' => $periode[2],
                ...$horodatage,
            ],
            Referentiel::PERIODES,
        ));

        DB::table('modeles')->insert(array_map(
            fn (array $modele) => ['nom' => $modele[0], 'slug' => Str::slug($modele[0]), ...$horodatage],
            Referentiel::MODELES,
        ));
    }
}
