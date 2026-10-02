<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

/**
 * Nettoie le HTML des documents avant affichage, avec l'extension DOM native
 * (sans dépendance) : seules les balises de mise en forme de la liste
 * blanche survivent, tous les attributs sont retirés sauf `scope` sur les
 * en-têtes de tableau. Les titres h2 reçoivent un identifiant pour le sommaire.
 *
 * Le contenu de démonstration est généré par les seeders, mais un futur
 * import ou éditeur ne doit jamais pouvoir injecter de script.
 */
final class ContenuHtml
{
    private const BALISES = [
        'p', 'h2', 'h3', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'strong', 'em', 'code', 'br', 'blockquote',
    ];

    /** Balises supprimées avec tout leur contenu. */
    private const BALISES_DANGEREUSES = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math'];

    /**
     * @return array{html: string, sommaire: list<array{id: string, titre: string}>}
     */
    public static function preparer(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        // Le préfixe XML force l'UTF-8 ; LIBXML_NONET interdit tout accès réseau.
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="racine">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        $racine = $dom->getElementById('racine');
        if (! $racine) {
            return ['html' => '', 'sommaire' => []];
        }

        self::nettoyer($racine);

        $sommaire = [];
        $identifiants = [];
        foreach ($racine->getElementsByTagName('h2') as $titre) {
            $id = Str::slug($titre->textContent) ?: 'section';
            $base = $id;
            for ($n = 2; isset($identifiants[$id]); $n++) {
                $id = "{$base}-{$n}";
            }
            $identifiants[$id] = true;
            $titre->setAttribute('id', $id);
            $sommaire[] = ['id' => $id, 'titre' => trim($titre->textContent)];
        }

        $sortie = '';
        foreach ($racine->childNodes as $enfant) {
            $sortie .= $dom->saveHTML($enfant);
        }

        return ['html' => $sortie, 'sommaire' => $sommaire];
    }

    private static function nettoyer(DOMNode $noeud): void
    {
        foreach (iterator_to_array($noeud->childNodes) as $enfant) {
            if ($enfant->nodeType === XML_COMMENT_NODE || $enfant->nodeType === XML_PI_NODE) {
                $noeud->removeChild($enfant);

                continue;
            }
            if (! $enfant instanceof DOMElement) {
                continue;
            }

            $balise = strtolower($enfant->tagName);
            if (in_array($balise, self::BALISES_DANGEREUSES, true)) {
                $noeud->removeChild($enfant);

                continue;
            }

            self::nettoyer($enfant);

            if (! in_array($balise, self::BALISES, true)) {
                // Balise inconnue : on garde son texte, pas l'élément.
                while ($enfant->firstChild) {
                    $noeud->insertBefore($enfant->firstChild, $enfant);
                }
                $noeud->removeChild($enfant);

                continue;
            }

            foreach (iterator_to_array($enfant->attributes) as $attribut) {
                $garder = $balise === 'th' && $attribut->name === 'scope' && in_array($attribut->value, ['row', 'col'], true);
                if (! $garder) {
                    $enfant->removeAttribute($attribut->name);
                }
            }
        }
    }
}
