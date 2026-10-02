<?php

namespace Database\Seeders\Support;

use Random\Randomizer;

/**
 * Rédige le contenu HTML des documents de démonstration à partir de gabarits.
 * Toutes les valeurs interpolées passent par e() ; le HTML produit reste donc
 * sûr même si le référentiel change.
 */
final class ContenuDocument
{
    /**
     * Procédures atelier : sujet, matériau concerné, étapes.
     */
    public const PROCEDURES = [
        ['Préparation des tirages résine', 'Résine polyuréthane', [
            'Retirer les évents de coulée à la scie fine, sans forcer sur la pièce.',
            'Poncer les plans de joint au grain 400 puis 800, sous un filet d’eau.',
            'Laver à l’eau tiède savonneuse pour éliminer l’agent de démoulage.',
            'Laisser sécher deux heures à température ambiante avant l’apprêt.',
        ]],
        ['Dégraissage et apprêt du zamak', 'Zamak', [
            'Dégraisser chaque pièce à l’alcool isopropylique avec un pinceau dur.',
            'Dépolir les surfaces visibles au grain 1000.',
            'Appliquer un primaire d’accrochage pour métaux non ferreux en voile fin.',
            'Laisser sécher douze heures avant la première couche de teinte.',
        ]],
        ['Pliage des photodécoupes en laiton', 'Laiton photodécoupé', [
            'Recuire la planche à la flamme si le métal résiste au pliage.',
            'Détacher la pièce au cutter, sur une plaque de verre.',
            'Plier dans une plieuse à mors fins en suivant les lignes gravées.',
            'Renforcer chaque pli par une goutte de cyanoacrylate fluide.',
        ]],
        ['Collage des pièces en métal blanc', 'Métal blanc', [
            'Ajuster les surfaces de contact à la lime douce.',
            'Mélanger une colle époxy 5 minutes en quantité juste suffisante.',
            'Maintenir l’assemblage avec des pinces crocodile pendant la prise.',
            'Retirer les bavures de colle au scalpel avant durcissement complet.',
        ]],
        ['Montage des pneus en caoutchouc', 'Caoutchouc', [
            'Tiédir le pneu dans de l’eau à 50 °C pour l’assouplir.',
            'Engager le premier talon sur la jante, puis faire rouler le second.',
            'Proscrire la cyanoacrylate, qui blanchit la gomme.',
            'Contrôler la concentricité en faisant tourner la roue sur son axe.',
        ]],
        ['Ébavurage des pièces en ABS injecté', 'ABS injecté', [
            'Couper au plus près du point d’injection avec une pince coupante plate.',
            'Reprendre le reliquat au scalpel, lame neuve.',
            'Poncer au grain 600 pour effacer la trace de coupe.',
            'Assembler à la colle plastique liquide appliquée par capillarité.',
        ]],
        ['Contrôle dimensionnel des pièces tournées', 'Aluminium tourné', [
            'Stabiliser les pièces et l’instrument à 20 °C pendant une heure.',
            'Mesurer diamètre et longueur au pied à coulisse numérique, trois relevés par cote.',
            'Comparer la moyenne à la cote nominale de la fiche technique.',
            'Isoler toute pièce hors tolérance et la consigner dans le registre qualité.',
        ]],
        ['Polissage des pièces en aluminium tourné', 'Aluminium tourné', [
            'Poncer à l’eau du grain 1500 au grain 3000 en croisant les passes.',
            'Lustrer à la pâte à polir sur feutre monté en minitour, vitesse lente.',
            'Dégraisser à l’alcool pour retirer les résidus de pâte.',
            'Protéger par un vernis brillant si la pièce sera manipulée.',
        ]],
        ['Stockage des pièces résine', 'Résine polyuréthane', [
            'Ranger les pièces à plat, à l’abri de la lumière directe.',
            'Maintenir le local entre 15 et 25 °C pour éviter les déformations.',
            'Séparer les pièces fines par du papier de soie.',
            'Redresser une pièce voilée à l’eau chaude, puis la fixer à plat pendant le refroidissement.',
        ]],
        ['Réparation d’une photodécoupe déformée', 'Laiton photodécoupé', [
            'Recuire localement la zone déformée.',
            'Redresser la pièce entre deux plaques de verre, par pression progressive.',
            'Reprendre les pliages un par un, dans l’ordre de la notice.',
            'Renforcer les plis fragilisés à l’étain basse température.',
        ]],
        ['Peinture à l’aérographe des pièces en zamak', 'Zamak', [
            'Régler l’aérographe entre 1,2 et 1,5 bar, buse de 0,3 mm.',
            'Diluer la teinte jusqu’à la consistance d’un lait entier.',
            'Appliquer trois voiles croisés à 15 cm, dix minutes d’intervalle.',
            'Étuver 30 minutes à 40 °C avant le vernis.',
        ]],
        ['Nettoyage des grappes ABS avant peinture', 'ABS injecté', [
            'Laver les grappes à l’eau tiède additionnée de liquide vaisselle.',
            'Brosser les détails gravés avec une brosse à dents souple.',
            'Rincer à l’eau déminéralisée pour éviter les traces de calcaire.',
            'Sécher à l’air libre, sans contact avec les doigts.',
        ]],
    ];

    private const TEINTES = [
        'Gris acier', 'Noir satiné', 'Aluminium brossé', 'Rouge course', 'Bleu nuit',
        'Bronze doré', 'Gomme anthracite', 'Chrome', 'Ivoire', 'Vert anglais', 'Titane', 'Jaune signal',
    ];

    private const FINITIONS = ['mat', 'satiné', 'brillant', 'métallisé'];

    private const DENSITES = [
        'Zamak' => 6.6,
        'Résine polyuréthane' => 1.15,
        'ABS injecté' => 1.05,
        'Laiton photodécoupé' => 8.5,
        'Aluminium tourné' => 2.7,
        'Caoutchouc' => 1.2,
        'Métal blanc' => 7.3,
    ];

    public function __construct(private readonly Randomizer $hasard) {}

    /**
     * @param  array{parties: list<string>, objet: string}  $categorie
     */
    public function notice(array $categorie, string $modele): string
    {
        $parties = $categorie['parties'];
        [$premiere, $deuxieme] = [$parties[0], $parties[1]];
        $derniere = $parties[count($parties) - 1];
        $colle = $this->choisir(['cyanoacrylate gel', 'époxy 5 minutes', 'colle plastique liquide']);

        $contenu = array_map(
            fn (string $partie) => '<li>'.e(ucfirst($partie)).' × '.$this->hasard->getInt(1, 4).'</li>',
            $parties,
        );

        $etapes = [
            'Détacher '.$this->article($premiere).' de la grappe au plus près du point d’injection, puis poncer le reliquat au grain 600.',
            'Présenter '.$this->article($deuxieme).' à blanc sur '.$this->article($premiere).', sans colle, pour vérifier l’alignement.',
            'Si l’ajustement force, reprendre le logement au foret de '.$this->decimal($this->hasard->getFloat(0.4, 1.2)).' mm monté sur porte-foret.',
            'Coller '.$this->article($deuxieme).' avec une pointe de '.$colle.'. Temps de prise : '.$this->hasard->getInt(2, 15).' minutes.',
            'Mettre en place '.$this->article($derniere).', puis laisser sécher '.$this->hasard->getInt(2, 12).' heures avant peinture.',
            'Contrôler l’alignement final sur un plan de verre avant le vernis.',
        ];

        return implode("\n", [
            '<p>Cette notice décrit le montage '.e($categorie['objet']).' de la '.e($modele).', toutes échelles confondues. Lire l’ensemble des étapes avant de détacher la première pièce.</p>',
            '<h2>Contenu du sachet</h2>',
            '<ul>'.implode('', $contenu).'</ul>',
            '<h2>Outillage</h2>',
            '<ul><li>Pince coupante plate</li><li>Scalpel et lames neuves</li><li>Papier abrasif grain 600</li><li>Brucelles</li><li>'.e(ucfirst($colle)).'</li></ul>',
            '<h2>Étapes</h2>',
            '<ol>'.implode('', array_map(fn (string $etape) => '<li>'.e($etape).'</li>', $etapes)).'</ol>',
            '<h2>Points de vigilance</h2>',
            '<p>Ne pas forcer '.e($this->article($premiere)).' au moment de l’assemblage : à cette échelle, les parois descendent sous 0,4 mm et cassent sans prévenir.</p>',
        ]);
    }

    /**
     * @param  array{parties: list<string>, objet: string}  $categorie
     */
    public function peinture(array $categorie, string $modele, string $materiau): string
    {
        $lignes = array_map(fn (string $partie) => sprintf(
            '<tr><td>%s</td><td>%s</td><td>TC-%03d</td><td>%s</td></tr>',
            e(ucfirst($partie)),
            e($this->choisir(self::TEINTES)),
            $this->hasard->getInt(1, 240),
            e($this->choisir(self::FINITIONS)),
        ), $categorie['parties']);

        return implode("\n", [
            '<p>Teintes et ordre d’application pour la mise en peinture '.e($categorie['objet']).' de la '.e($modele).'. Les références TC renvoient au nuancier interne.</p>',
            '<h2>Préparation</h2>',
            '<p>Support en '.e(mb_strtolower($materiau)).' : appliquer la procédure de préparation correspondante, puis un apprêt gris clair en deux voiles.</p>',
            '<h2>Teintes</h2>',
            '<table><thead><tr><th>Zone</th><th>Teinte</th><th>Référence</th><th>Finition</th></tr></thead><tbody>'.implode('', $lignes).'</tbody></table>',
            '<h2>Ordre d’application</h2>',
            '<ol><li>Teintes claires d’abord, au pinceau plat ou à l’aérographe.</li><li>Masquage des zones peintes au ruban de masquage fin.</li><li>Teintes foncées, puis détails au pinceau 3/0.</li><li>Retrait du masquage avant séchage complet.</li></ol>',
            '<h2>Vernis et patine</h2>',
            '<p>Vernis '.e($this->choisir(['satiné', 'mat', 'brillant'])).' en voile léger. Patine au lavis brun dilué dans les creux, essuyée au coton-tige après cinq minutes.</p>',
        ]);
    }

    /**
     * @param  list<string>  $etapes
     */
    public function procedure(string $sujet, string $materiau, array $etapes): string
    {
        $equipements = $materiau === 'Résine polyuréthane' || str_contains($sujet, 'Ponçage')
            ? ['Gants nitrile', 'Masque FFP2 pendant le ponçage', 'Lunettes de protection']
            : ['Gants nitrile', 'Lunettes de protection', 'Poste ventilé'];

        return implode("\n", [
            '<h2>Objet</h2>',
            '<p>'.e($sujet).'. Cette procédure s’applique à toutes les pièces en '.e(mb_strtolower($materiau)).' traitées en atelier.</p>',
            '<h2>Équipement de protection</h2>',
            '<ul>'.implode('', array_map(fn (string $epi) => '<li>'.e($epi).'</li>', $equipements)).'</ul>',
            '<h2>Mode opératoire</h2>',
            '<ol>'.implode('', array_map(fn (string $etape) => '<li>'.e($etape).'</li>', $etapes)).'</ol>',
            '<h2>Contrôle</h2>',
            '<p>Le responsable d’atelier vérifie un échantillon de '.$this->hasard->getInt(3, 10).' pièces par lot et consigne le résultat dans le registre qualité.</p>',
        ]);
    }

    /**
     * @param  array{reference: string, materiau: string, echelle: string, longueur: float, largeur: float, hauteur: float, fabricant: string}  $piece
     */
    public function fiche(array $piece, string $modele): string
    {
        $volumeCm3 = $piece['longueur'] * $piece['largeur'] * $piece['hauteur'] / 1000;
        // Les pièces sont creuses ou ajourées : on retient un taux de remplissage de 30 %.
        $masse = $volumeCm3 * 0.3 * self::DENSITES[$piece['materiau']];
        $tolerance = in_array($piece['materiau'], ['Aluminium tourné', 'Laiton photodécoupé'], true) ? '0,05' : '0,1';

        $lignes = [
            'Référence de base' => $piece['reference'],
            'Fabricant' => $piece['fabricant'],
            'Matériau' => $piece['materiau'],
            'Échelle' => $piece['echelle'],
            'Encombrement' => $this->decimal($piece['longueur']).' × '.$this->decimal($piece['largeur']).' × '.$this->decimal($piece['hauteur']).' mm',
            'Tolérance' => '± '.$tolerance.' mm',
            'Masse estimée' => $this->decimal(max(0.1, $masse)).' g',
            'Conditionnement' => $this->choisir(['Sachet zip', 'Boîte individuelle', 'Blister']),
        ];

        $tableau = implode('', array_map(
            fn (string $cle, string $valeur) => '<tr><th scope="row">'.e($cle).'</th><td>'.e($valeur).'</td></tr>',
            array_keys($lignes),
            $lignes,
        ));

        return implode("\n", [
            '<p>Caractéristiques de référence pour la '.e($modele).' au '.e($piece['echelle']).'. Les variantes du même lot partagent ces valeurs à la tolérance près.</p>',
            '<h2>Caractéristiques</h2>',
            '<table><tbody>'.$tableau.'</tbody></table>',
            '<h2>Remarques</h2>',
            '<p>La masse est calculée à partir de l’encombrement et de la densité du matériau. Elle sert à estimer les frais d’envoi et ne remplace pas une pesée.</p>',
        ]);
    }

    /**
     * @template T
     *
     * @param  list<T>  $valeurs
     * @return T
     */
    private function choisir(array $valeurs): mixed
    {
        return $valeurs[$this->hasard->getInt(0, count($valeurs) - 1)];
    }

    private function decimal(float $valeur): string
    {
        return number_format($valeur, 1, ',', ' ');
    }

    /**
     * Article défini adapté au nom de la sous-partie (« la jante », « l’assise », « les dérives »).
     */
    private function article(string $partie): string
    {
        if (str_ends_with($partie, 's')) {
            return "les {$partie}";
        }
        if (preg_match('/^[aeéèêiîoôuh]/u', $partie)) {
            return "l’{$partie}";
        }

        return in_array($partie, ['jante', 'culasse', 'assise', 'lame', 'couronne', 'lentille', 'coupelle', 'sortie'], true)
            ? "la {$partie}"
            : "le {$partie}";
    }
}
