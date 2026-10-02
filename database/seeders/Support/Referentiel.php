<?php

namespace Database\Seeders\Support;

use App\Support\MiniatureCategorie;

/**
 * Données de référence du catalogue de démonstration. Tous les fabricants et
 * modèles de voitures sont fictifs.
 */
final class Referentiel
{
    /**
     * Matériaux, dans l'ordre d'insertion.
     */
    public const MATERIAUX = [
        'Zamak',
        'Résine polyuréthane',
        'ABS injecté',
        'Laiton photodécoupé',
        'Aluminium tourné',
        'Caoutchouc',
        'Métal blanc',
    ];

    /**
     * Libellé, rapport et poids de tirage (les échelles 1/18 et 1/43 dominent le marché).
     */
    public const ECHELLES = [
        ['1/8', 8, 2],
        ['1/12', 12, 4],
        ['1/18', 18, 10],
        ['1/24', 24, 7],
        ['1/32', 32, 3],
        ['1/43', 43, 9],
        ['1/64', 64, 4],
    ];

    /**
     * Libellé, année de début, année de fin.
     */
    public const PERIODES = [
        ['Avant 1950', null, 1949],
        ['1950-1969', 1950, 1969],
        ['1970-1989', 1970, 1989],
        ['1990-2009', 1990, 2009],
        ['Depuis 2010', 2010, null],
    ];

    /**
     * Nom, code de référence, pays.
     */
    public const FABRICANTS = [
        ['Atelier Lumière', 'ALU', 'France'],
        ['Mécaminia', 'MCM', 'France'],
        ['Rivarol Modèles', 'RIV', 'France'],
        ['Petits Bolides', 'PTB', 'Belgique'],
        ['Zinc & Vernis', 'ZEV', 'France'],
        ['Ateliers Corvel', 'COR', 'Suisse'],
        ['Moulages Bréhat', 'BRE', 'France'],
        ['Fonderie Vasseur', 'VAS', 'France'],
        ['Miniatures Delorme', 'DEL', 'Canada'],
        ['Résines du Nord', 'RDN', 'Belgique'],
        ['Officina Scala', 'OFS', 'Italie'],
        ['Kleinwerk', 'KLW', 'Allemagne'],
    ];

    /**
     * Nom du modèle de voiture et index de sa période dans PERIODES.
     */
    public const MODELES = [
        ['Aurore Type B', 0],
        ['Bellerive 22 CV', 0],
        ['Corvel Torpédo', 0],
        ['Montclar Roadster', 0],
        ['Aurèle 600', 1],
        ['Sirocco Coupé', 1],
        ['Vasseur Berlinette', 1],
        ['Estérel GT', 1],
        ['Delorme Spider', 1],
        ['Rivarol 1300', 1],
        ['Panthère Sport', 2],
        ['Mistral Turbo', 2],
        ['Tramontane GTI', 2],
        ['Bréhat Sprint', 2],
        ['Orion 2000', 2],
        ['Atlas Groupe B', 2],
        ['Vortex GT', 3],
        ['Zéphyr RS', 3],
        ['Kestrel Evo', 3],
        ['Nova Coupé', 3],
        ['Altaïr V12', 3],
        ['Sable Rallye', 3],
        ['Ionis E-GT', 4],
        ['Vortex Hybrid', 4],
        ['Strata LMP', 4],
        ['Helix R', 4],
        ['Lumen Electra', 4],
        ['Cobalt Track', 4],
    ];

    /**
     * Une catégorie par forme procédurale de la visionneuse.
     *
     * - reel : dimensions d'une pièce de voiture réelle en mm (longueur, largeur, hauteur),
     *   divisées ensuite par le rapport d'échelle ;
     * - materiaux : index dans MATERIAUX ;
     * - parties : sous-parties nommées, reprises par la visionneuse et les notices ;
     * - objet : complément utilisé dans les titres de documents.
     *
     * @var array<string, array{nom: string, code: string, reel: array{0: int, 1: int, 2: int}, materiaux: list<int>, variantes: list<string>, parties: list<string>, objet: string}>
     */
    public const CATEGORIES = [
        'roue' => [
            'nom' => 'Jantes et pneus',
            'code' => 'JA',
            'reel' => [660, 660, 230],
            'materiaux' => [0, 4, 1, 5],
            'variantes' => ['Jante 5 branches', 'Jante à rayons', 'Jante pleine', 'Jante 3 pièces', 'Roue complète slick', 'Roue complète rainurée', 'Jante étoile'],
            'parties' => ['jante', 'pneu', 'écrou central'],
            'objet' => 'des jantes et pneus',
        ],
        'moteur' => [
            'nom' => 'Moteurs',
            'code' => 'MO',
            'reel' => [700, 620, 640],
            'materiaux' => [1, 6, 2],
            'variantes' => ['Bloc moteur V8', 'Moteur 4 cylindres', 'Moteur 6 en ligne', 'Moteur flat-six', 'Moteur V12', 'Bloc turbo'],
            'parties' => ['bloc', 'culasse', 'carburateur', 'filtre à air'],
            'objet' => 'du moteur',
        ],
        'siege' => [
            'nom' => 'Sièges',
            'code' => 'SI',
            'reel' => [560, 520, 980],
            'materiaux' => [1, 2, 6],
            'variantes' => ['Siège baquet', 'Siège sport', 'Banquette avant', 'Siège course', 'Siège pilote'],
            'parties' => ['assise', 'dossier', 'harnais'],
            'objet' => 'des sièges',
        ],
        'echappement' => [
            'nom' => 'Échappements',
            'code' => 'EC',
            'reel' => [1900, 260, 180],
            'materiaux' => [4, 6, 1],
            'variantes' => ['Ligne d’échappement complète', 'Silencieux arrière', 'Collecteur 4 en 1', 'Double sortie', 'Sortie latérale'],
            'parties' => ['collecteur', 'silencieux', 'sortie'],
            'objet' => 'de l’échappement',
        ],
        'aileron' => [
            'nom' => 'Ailerons',
            'code' => 'AI',
            'reel' => [1500, 380, 320],
            'materiaux' => [3, 1, 2],
            'variantes' => ['Aileron arrière', 'Aileron réglable', 'Becquet de coffre', 'Aileron col de cygne', 'Lame avant'],
            'parties' => ['lame', 'dérives', 'supports'],
            'objet' => 'de l’aileron',
        ],
        'volant' => [
            'nom' => 'Volants',
            'code' => 'VO',
            'reel' => [380, 380, 90],
            'materiaux' => [3, 1, 6],
            'variantes' => ['Volant 3 branches', 'Volant bois', 'Volant course', 'Volant 4 branches', 'Volant à palettes'],
            'parties' => ['couronne', 'branches', 'moyeu'],
            'objet' => 'du volant',
        ],
        'optique' => [
            'nom' => 'Optiques',
            'code' => 'OP',
            'reel' => [260, 210, 170],
            'materiaux' => [2, 1, 3],
            'variantes' => ['Phare rond', 'Phare rectangulaire', 'Feu arrière', 'Antibrouillard', 'Optique double'],
            'parties' => ['boîtier', 'réflecteur', 'lentille'],
            'objet' => 'des optiques',
        ],
        'suspension' => [
            'nom' => 'Suspensions',
            'code' => 'SU',
            'reel' => [140, 140, 420],
            'materiaux' => [4, 6, 3],
            'variantes' => ['Combiné ressort-amortisseur', 'Amortisseur réglable', 'Ressort hélicoïdal', 'Coilover course', 'Amortisseur à bonbonne'],
            'parties' => ['ressort', 'amortisseur', 'coupelle'],
            'objet' => 'des suspensions',
        ],
    ];

    /**
     * Auteurs fictifs des documents internes.
     */
    public const AUTEURS = [
        'Claire Morvan',
        'Hugo Lenoir',
        'Inès Garnier',
        'Thomas Riva',
        'Bureau d’études',
        'Atelier peinture',
        'Service qualité',
    ];

    /**
     * Passe la première lettre en minuscule, sauf pour un sigle (« ABS injecté »).
     */
    public static function minuscule(string $texte): string
    {
        $deuxieme = mb_substr($texte, 1, 1);

        return $deuxieme !== '' && mb_strtoupper($deuxieme) === $deuxieme && mb_strtolower($deuxieme) !== $deuxieme
            ? $texte
            : mb_strtolower(mb_substr($texte, 0, 1)).mb_substr($texte, 1);
    }

    /**
     * « la Vortex GT », mais « l’Aurore Type B » devant une voyelle.
     */
    public static function la(string $modele): string
    {
        return preg_match('/^[aeiouyàâéèêëîïôöûü]/iu', $modele) ? "l’{$modele}" : "la {$modele}";
    }

    public static function cheminMiniature(string $forme): string
    {
        return MiniatureCategorie::chemin($forme);
    }
}
