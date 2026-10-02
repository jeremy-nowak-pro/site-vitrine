<?php

namespace App\Admin;

use App\Models\Document;
use App\Models\Piece;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Statistiques de démonstration. Les chiffres sont inventés mais stables sur
 * une journée (le tirage dépend de la date) et plausibles : creux le
 * week-end, légère tendance haussière, documents ~10 fois moins consultés.
 * Les « plus consultés » pointent vers de vraies pièces et de vrais documents
 * pour que les liens fonctionnent.
 *
 * En production, ces valeurs viendront de la table consultations (index
 * type + consulte_le + consultable_id).
 */
class StatistiquesFictives
{
    private const JOURS = 30;

    /**
     * @return array<string, mixed>
     */
    public function generer(CarbonInterface $aujourdhui): array
    {
        $hasard = new Randomizer(new Mt19937(crc32($aujourdhui->toDateString())));
        $jour = CarbonImmutable::instance($aujourdhui);

        $serie = [];
        for ($i = self::JOURS - 1; $i >= 0; $i--) {
            $date = $jour->subDays($i);
            $weekEnd = $date->isWeekend() ? 0.68 : 1.0;
            $tendance = 1 + (self::JOURS - $i) * 0.006;
            $serie[] = [
                'date' => $date->toDateString(),
                'pieces' => (int) round(840 * $weekEnd * $tendance * $hasard->getFloat(0.88, 1.12)),
                'documents' => (int) round(92 * $weekEnd * $tendance * $hasard->getFloat(0.82, 1.18)),
            ];
        }

        $consultationsPieces = array_sum(array_column($serie, 'pieces'));
        $consultationsDocuments = array_sum(array_column($serie, 'documents'));

        return [
            'indicateurs' => [
                ['libelle' => 'Fiches pièces consultées', 'valeur' => $consultationsPieces, 'evolution' => $hasard->getInt(4, 14)],
                ['libelle' => 'Documents consultés', 'valeur' => $consultationsDocuments, 'evolution' => $hasard->getInt(-6, 9)],
                ['libelle' => 'STL téléchargés', 'valeur' => (int) round($consultationsPieces * $hasard->getFloat(0.05, 0.08)), 'evolution' => $hasard->getInt(2, 18)],
                ['libelle' => 'Pièces ajoutées aux favoris', 'valeur' => (int) round($consultationsPieces * $hasard->getFloat(0.03, 0.05)), 'evolution' => $hasard->getInt(-3, 11)],
            ],
            'serie' => $serie,
            'piecesPopulaires' => $this->piecesPopulaires($hasard),
            'documentsPopulaires' => $this->documentsPopulaires($hasard),
        ];
    }

    /**
     * @return list<array{reference: string, nom: string, echelle: string, consultations: int}>
     */
    private function piecesPopulaires(Randomizer $hasard): array
    {
        $bornes = Piece::query()->toBase()->selectRaw('min(id) as min, max(id) as max')->first();
        if ($bornes?->max === null) {
            return [];
        }

        $ids = [];
        while (count($ids) < min(10, $bornes->max - $bornes->min + 1)) {
            $ids[$hasard->getInt($bornes->min, $bornes->max)] = true;
        }
        $pieces = Piece::query()
            ->select(['id', 'reference', 'nom', 'echelle_id'])
            ->with('echelle:id,libelle')
            ->whereIn('id', array_keys($ids))
            ->get();

        return $this->classer($pieces->map(fn (Piece $piece) => [
            'reference' => $piece->reference,
            'nom' => $piece->nom,
            'echelle' => $piece->echelle->libelle,
        ])->all(), $hasard, 420);
    }

    /**
     * @return list<array{slug: string, titre: string, type: string, consultations: int}>
     */
    private function documentsPopulaires(Randomizer $hasard): array
    {
        $documents = Document::query()->orderBy('id')->get(['id', 'slug', 'titre', 'type'])->all();
        $documents = array_slice($hasard->shuffleArray($documents), 0, 8);

        return $this->classer(array_map(fn (Document $document) => [
            'slug' => $document->slug,
            'titre' => $document->titre,
            'type' => $document->type->libelle(),
        ], $documents), $hasard, 160);
    }

    /**
     * Attribue des consultations décroissantes (distribution à longue traîne).
     *
     * @template T of array<string, string>
     *
     * @param  list<T>  $lignes
     * @return list<T&array{consultations: int}>
     */
    private function classer(array $lignes, Randomizer $hasard, int $maximum): array
    {
        $classees = [];
        foreach (array_values($lignes) as $rang => $ligne) {
            $classees[] = [...$ligne, 'consultations' => (int) round($maximum / (1 + $rang * 0.35) * $hasard->getFloat(0.92, 1.0))];
        }

        return $classees;
    }
}
