<?php

namespace Database\Seeders;

use App\Models\Piece;
use Database\Seeders\Support\Referentiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Génère les pièces par insertions groupées, sans Eloquent ni Faker : à
 * 300 000 lignes, créer chaque modèle individuellement prendrait des dizaines
 * de minutes. Le tirage est déterministe pour obtenir le même catalogue à
 * chaque installation.
 *
 * Les compatibilités relient une pièce aux dernières pièces du même modèle de
 * voiture, à la même échelle et d'une autre catégorie, dans les deux sens.
 */
class PieceSeeder extends Seeder
{
    private const TAILLE_LOT = 1000;

    private const COMPATIBLES_MAX = 3;

    private const PHRASES_MATERIAU = [
        'Zamak' => 'Pièce moulée sous pression, à dégraisser avant la mise en peinture.',
        'Résine polyuréthane' => 'Tirage résine à ébavurer et laver avant montage.',
        'ABS injecté' => 'Pièce injectée sur grappe, assemblage à la colle plastique.',
        'Laiton photodécoupé' => 'Planche photodécoupée à plier en suivant la notice.',
        'Aluminium tourné' => 'Usinage de précision, finition brute prête à polir.',
        'Caoutchouc' => 'Gomme souple, se monte sans colle.',
        'Métal blanc' => 'Moulage en métal blanc, collage à l’époxy recommandé.',
    ];

    private Randomizer $hasard;

    /** @var list<array<string, mixed>> */
    private array $lotPieces = [];

    /** @var list<array{piece_id: int, compatible_id: int}> */
    private array $lotCompatibilites = [];

    public function run(): void
    {
        $total = (int) config('catalogue.seed.pieces');
        $this->hasard = new Randomizer(new Mt19937(20261001));

        $categories = DB::table('categories')->pluck('id', 'forme');
        $echelles = DB::table('echelles')->pluck('id', 'rapport');
        $fabricants = DB::table('fabricants')->pluck('id', 'nom');
        $materiaux = DB::table('materiaux')->pluck('id', 'nom');
        $periodes = DB::table('periodes')->pluck('id', 'libelle');
        $modeles = DB::table('modeles')->pluck('id', 'nom');

        $tirageEchelles = [];
        foreach (Referentiel::ECHELLES as [$libelle, $rapport, $poids]) {
            array_push($tirageEchelles, ...array_fill(0, $poids, [$libelle, $rapport]));
        }
        $formes = array_keys(Referentiel::CATEGORIES);

        /** @var array<string, list<array{0: int, 1: string}>> $groupes */
        $groupes = [];
        $premierId = (int) Piece::max('id') + 1;
        $maintenant = now()->getTimestamp();
        $deuxAns = 730 * 86400;

        $barre = $this->command?->getOutput()->createProgressBar($total);
        $barre?->start();

        for ($i = 0; $i < $total; $i++) {
            $id = $premierId + $i;
            [$modeleNom, $periodeIndex] = $this->choisir(Referentiel::MODELES);
            $periodeLibelle = Referentiel::PERIODES[$periodeIndex][0];
            $forme = $this->choisir($formes);
            $categorie = Referentiel::CATEGORIES[$forme];
            [$echelleLibelle, $rapport] = $this->choisir($tirageEchelles);
            [$fabricantNom, $fabricantCode] = $this->choisir(Referentiel::FABRICANTS);
            $materiauNom = Referentiel::MATERIAUX[$this->choisir($categorie['materiaux'])];
            $variante = $this->choisir($categorie['variantes']);

            [$longueur, $largeur, $hauteur] = array_map(
                fn (int $reel) => max(0.5, round($reel / $rapport * $this->hasard->getFloat(0.85, 1.15), 2)),
                $categorie['reel'],
            );

            $cree = date('Y-m-d H:i:s', $maintenant - $this->hasard->getInt(0, $deuxAns));

            $this->lotPieces[] = [
                'id' => $id,
                'reference' => sprintf('%s-%s%02d-%06d', $fabricantCode, $categorie['code'], $rapport, $id),
                'nom' => "{$variante} pour {$modeleNom}",
                'description' => sprintf(
                    '%s au %s en %s pour %s (%s). Fabrication %s, encombrement %s × %s × %s mm. %s',
                    $variante,
                    $echelleLibelle,
                    Referentiel::minuscule($materiauNom),
                    Referentiel::la($modeleNom),
                    mb_strtolower($periodeLibelle),
                    $fabricantNom,
                    $this->mm($longueur),
                    $this->mm($largeur),
                    $this->mm($hauteur),
                    self::PHRASES_MATERIAU[$materiauNom],
                ),
                'categorie_id' => $categories[$forme],
                'echelle_id' => $echelles[$rapport],
                'fabricant_id' => $fabricants[$fabricantNom],
                'materiau_id' => $materiaux[$materiauNom],
                'periode_id' => $periodes[$periodeLibelle],
                'modele_id' => $modeles[$modeleNom],
                'longueur_mm' => $longueur,
                'largeur_mm' => $largeur,
                'hauteur_mm' => $hauteur,
                'chemin_modele_3d' => null,
                'chemin_stl' => null,
                'chemin_miniature' => Referentiel::cheminMiniature($forme),
                'created_at' => $cree,
                'updated_at' => $cree,
            ];

            $cleGroupe = "{$modeleNom}|{$rapport}";
            $liees = 0;
            foreach (array_reverse($groupes[$cleGroupe] ?? []) as [$autreId, $autreForme]) {
                if ($autreForme === $forme || $liees === self::COMPATIBLES_MAX) {
                    continue;
                }
                $this->lotCompatibilites[] = ['piece_id' => $id, 'compatible_id' => $autreId];
                $this->lotCompatibilites[] = ['piece_id' => $autreId, 'compatible_id' => $id];
                $liees++;
            }
            $groupes[$cleGroupe][] = [$id, $forme];
            $groupes[$cleGroupe] = array_slice($groupes[$cleGroupe], -6);

            if (count($this->lotPieces) === self::TAILLE_LOT) {
                $this->enregistrerLot();
                $barre?->advance(self::TAILLE_LOT);
            }
        }

        $reste = count($this->lotPieces);
        $this->enregistrerLot();
        $barre?->advance($reste);
        $barre?->finish();
        $this->command?->newLine();

        // Les identifiants ont été fixés à l'insertion : la séquence doit reprendre après.
        DB::statement("select setval(pg_get_serial_sequence('pieces', 'id'), coalesce(max(id), 1)) from pieces");
    }

    private function enregistrerLot(): void
    {
        if ($this->lotPieces === []) {
            return;
        }

        DB::transaction(function () {
            DB::table('pieces')->insert($this->lotPieces);
            foreach (array_chunk($this->lotCompatibilites, 5000) as $compatibilites) {
                DB::table('piece_compatibilite')->insert($compatibilites);
            }
        });

        $this->lotPieces = [];
        $this->lotCompatibilites = [];
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $valeurs
     * @return T
     */
    private function choisir(array $valeurs): mixed
    {
        return $valeurs[$this->hasard->getInt(0, count($valeurs) - 1)];
    }

    private function mm(float $valeur): string
    {
        return number_format($valeur, 1, ',', ' ');
    }
}
