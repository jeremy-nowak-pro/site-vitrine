<?php

namespace Database\Seeders;

use App\Enums\TypeDocument;
use App\Models\Piece;
use Database\Seeders\Support\ContenuDocument;
use Database\Seeders\Support\Referentiel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Génère les documents internes et les rattache aux pièces concernées.
 *
 * Chaque document part d'une pièce tirée au hasard, ce qui garantit au moins
 * une pièce liée quel que soit le volume du catalogue :
 * - notice et guide de peinture : même modèle de voiture et même catégorie ;
 * - fiche technique : même modèle, même catégorie et même échelle ;
 * - procédure atelier : même matériau.
 */
class DocumentSeeder extends Seeder
{
    private const PIECES_PAR_DOCUMENT = 40;

    private Randomizer $hasard;

    private ContenuDocument $redaction;

    /** @var array<string, true> */
    private array $slugs = [];

    public function run(): void
    {
        $this->hasard = new Randomizer(new Mt19937(19500101));
        $this->redaction = new ContenuDocument($this->hasard);

        $total = (int) config('catalogue.seed.documents');
        $nbProcedures = min(count(ContenuDocument::PROCEDURES), intdiv($total, 5));
        $reste = $total - $nbProcedures;
        $nbNotices = (int) ceil($reste * 0.375);
        $nbPeintures = intdiv($reste - $nbNotices, 2);
        $nbFiches = $reste - $nbNotices - $nbPeintures;

        $bornes = Piece::query()->selectRaw('min(id) as min, max(id) as max')->first();
        if ($bornes?->max === null) {
            $this->command?->warn('Aucune pièce en base : documents non générés.');

            return;
        }

        for ($i = 0; $i < $nbNotices; $i++) {
            $piece = $this->pieceAuHasard($bornes->min, $bornes->max);
            $categorie = Referentiel::CATEGORIES[$piece->categorie->forme];
            $this->creer(
                TypeDocument::NoticeMontage,
                'Montage '.$categorie['objet'].' de '.Referentiel::la($piece->modele->nom),
                $this->redaction->notice($categorie, $piece->modele->nom),
                fn (Builder $pieces) => $pieces->where('modele_id', $piece->modele_id)->where('categorie_id', $piece->categorie_id),
            );
        }

        for ($i = 0; $i < $nbPeintures; $i++) {
            $piece = $this->pieceAuHasard($bornes->min, $bornes->max);
            $categorie = Referentiel::CATEGORIES[$piece->categorie->forme];
            $this->creer(
                TypeDocument::GuidePeinture,
                'Mise en peinture '.$categorie['objet'].' de '.Referentiel::la($piece->modele->nom),
                $this->redaction->peinture($categorie, $piece->modele->nom, $piece->materiau->nom),
                fn (Builder $pieces) => $pieces->where('modele_id', $piece->modele_id)->where('categorie_id', $piece->categorie_id),
            );
        }

        for ($i = 0; $i < $nbFiches; $i++) {
            $piece = $this->pieceAuHasard($bornes->min, $bornes->max);
            $categorie = Referentiel::CATEGORIES[$piece->categorie->forme];
            $this->creer(
                TypeDocument::FicheTechnique,
                'Fiche technique '.$categorie['objet'].' de '.Referentiel::la($piece->modele->nom).' au '.$piece->echelle->libelle,
                $this->redaction->fiche([
                    'reference' => $piece->reference,
                    'fabricant' => $piece->fabricant->nom,
                    'materiau' => $piece->materiau->nom,
                    'echelle' => $piece->echelle->libelle,
                    'longueur' => $piece->longueur_mm,
                    'largeur' => $piece->largeur_mm,
                    'hauteur' => $piece->hauteur_mm,
                ], $piece->modele->nom),
                fn (Builder $pieces) => $pieces
                    ->where('modele_id', $piece->modele_id)
                    ->where('categorie_id', $piece->categorie_id)
                    ->where('echelle_id', $piece->echelle_id),
            );
        }

        $materiaux = DB::table('materiaux')->pluck('id', 'nom');
        foreach (array_slice(ContenuDocument::PROCEDURES, 0, $nbProcedures) as [$sujet, $materiau, $etapes]) {
            $this->creer(
                TypeDocument::ProcedureAtelier,
                $sujet,
                $this->redaction->procedure($sujet, $materiau, $etapes),
                fn (Builder $pieces) => $pieces->where('materiau_id', $materiaux[$materiau]),
            );
        }
    }

    private function pieceAuHasard(int $min, int $max): Piece
    {
        return Piece::query()
            ->with(['categorie:id,forme', 'modele:id,nom', 'echelle:id,libelle', 'materiau:id,nom', 'fabricant:id,nom'])
            ->findOrFail($this->hasard->getInt($min, $max));
    }

    /**
     * @param  callable(Builder<Piece>): Builder<Piece>  $piecesConcernees
     */
    private function creer(TypeDocument $type, string $titre, string $contenu, callable $piecesConcernees): void
    {
        // « au 1/12 » devient « au-1-12 » plutôt que « au-112 ».
        $base = Str::slug(str_replace('/', ' ', $titre));
        $slug = $base;
        for ($suffixe = 2; isset($this->slugs[$slug]); $suffixe++) {
            $slug = $base.'-'.$suffixe;
        }
        $this->slugs[$slug] = true;

        $maintenant = now();
        $documentId = DB::table('documents')->insertGetId([
            'titre' => $titre,
            'slug' => $slug,
            'type' => $type->value,
            'contenu' => $contenu,
            'version' => $this->hasard->getInt(1, 3).'.'.$this->hasard->getInt(0, 9),
            'auteur' => Referentiel::AUTEURS[$this->hasard->getInt(0, count(Referentiel::AUTEURS) - 1)],
            'date_mise_a_jour' => $maintenant->copy()->subDays($this->hasard->getInt(0, 540))->toDateString(),
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);

        DB::table('document_piece')->insertUsing(
            ['document_id', 'piece_id'],
            $piecesConcernees(Piece::query()->selectRaw('?::bigint, id', [$documentId]))
                ->orderBy('id')
                ->limit(self::PIECES_PAR_DOCUMENT)
                ->toBase(),
        );
    }
}
