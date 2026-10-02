<?php

namespace App\Support;

/**
 * Miniatures de démonstration : un pictogramme SVG au trait par catégorie,
 * servi depuis le stockage S3 avec un cache d'un an. Le suffixe de version
 * du nom de fichier change si le dessin change, ce qui invalide le cache.
 */
final class MiniatureCategorie
{
    private const VERSION = 'v1';

    private const FOND = '#f6f7f9';

    private const TRAIT = '#59616b';

    private const ACCENT = '#3b5b7e';

    public static function chemin(string $forme): string
    {
        return 'thumbnails/categories/'.$forme.'-'.self::VERSION.'.svg';
    }

    public static function svg(string $forme): string
    {
        $dessin = match ($forme) {
            'roue' => self::roue(),
            'moteur' => self::moteur(),
            'siege' => self::siege(),
            'echappement' => self::echappement(),
            'aileron' => self::aileron(),
            'volant' => self::volant(),
            'optique' => self::optique(),
            'suspension' => self::suspension(),
            default => '',
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 360" width="480" height="360">'
            .'<rect width="480" height="360" fill="'.self::FOND.'"/>'
            .'<g fill="none" stroke="'.self::TRAIT.'" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">'
            .$dessin
            .'</g></svg>';
    }

    private static function roue(): string
    {
        $branches = '';
        for ($i = 0; $i < 5; $i++) {
            $angle = deg2rad(-90 + $i * 72);
            $branches .= self::ligne(240 + 20 * cos($angle), 180 + 20 * sin($angle), 240 + 72 * cos($angle), 180 + 72 * sin($angle));
        }

        return '<circle cx="240" cy="180" r="118" stroke-width="22"/>'
            .'<circle cx="240" cy="180" r="80"/>'
            .$branches
            .'<circle cx="240" cy="180" r="16" stroke="'.self::ACCENT.'"/>';
    }

    private static function moteur(): string
    {
        return '<rect x="150" y="150" width="180" height="120" rx="6"/>'
            .'<rect x="140" y="118" width="200" height="32" rx="4"/>'
            .'<rect x="205" y="88" width="70" height="30" rx="4" stroke="'.self::ACCENT.'"/>'
            .'<ellipse cx="240" cy="74" rx="62" ry="14"/>'
            .self::ligne(170, 190, 310, 190).self::ligne(170, 220, 310, 220)
            .'<circle cx="122" cy="230" r="22"/>'.self::ligne(144, 230, 150, 230);
    }

    private static function siege(): string
    {
        return '<path d="M168 60 Q150 60 152 82 L172 250 Q174 268 194 268 L222 268"/>'
            .'<path d="M168 60 Q186 60 190 82 L206 230"/>'
            .'<path d="M182 268 L330 268 Q348 268 346 250 L340 232 Q336 222 322 222 L206 230"/>'
            .self::ligne(182, 110, 300, 236, self::ACCENT).self::ligne(190, 150, 268, 234, self::ACCENT)
            .self::ligne(214, 268, 214, 300).self::ligne(318, 268, 318, 300);
    }

    private static function echappement(): string
    {
        return '<path d="M60 110 Q110 110 130 160 M60 140 Q100 140 130 170 M60 170 Q95 170 130 180 M60 200 Q100 200 130 190"/>'
            .'<path d="M130 160 L130 194 L200 186 L200 168 Z"/>'
            .self::ligne(200, 177, 280, 177)
            .'<rect x="280" y="140" width="110" height="74" rx="36"/>'
            .'<path d="M390 168 L430 162 L430 192 L390 186" stroke="'.self::ACCENT.'"/>';
    }

    private static function aileron(): string
    {
        return '<path d="M70 120 Q240 92 410 120 L410 146 Q240 124 70 146 Z"/>'
            .'<rect x="58" y="96" width="16" height="84" rx="3"/>'
            .'<rect x="406" y="96" width="16" height="84" rx="3"/>'
            .self::ligne(170, 138, 190, 270, self::ACCENT).self::ligne(310, 138, 290, 270, self::ACCENT)
            .self::ligne(150, 270, 330, 270);
    }

    private static function volant(): string
    {
        return '<circle cx="240" cy="180" r="112" stroke-width="18"/>'
            .self::ligne(240, 180, 134, 186).self::ligne(240, 180, 346, 186).self::ligne(240, 180, 240, 290)
            .'<circle cx="240" cy="180" r="34" stroke="'.self::ACCENT.'"/>'
            .'<circle cx="240" cy="180" r="10"/>';
    }

    private static function optique(): string
    {
        return '<circle cx="240" cy="180" r="118"/>'
            .'<circle cx="240" cy="180" r="96" stroke="'.self::ACCENT.'"/>'
            .'<circle cx="240" cy="180" r="56"/>'
            .'<circle cx="240" cy="180" r="18"/>'
            .self::ligne(176, 118, 304, 118).self::ligne(156, 150, 324, 150).self::ligne(150, 210, 330, 210).self::ligne(176, 242, 304, 242);
    }

    private static function suspension(): string
    {
        $ressort = 'M196 92';
        for ($i = 0; $i < 7; $i++) {
            $y = 92 + $i * 26;
            $ressort .= ' L284 '.($y + 13).' L196 '.($y + 26);
        }

        return '<rect x="226" y="40" width="28" height="270" rx="6"/>'
            .'<ellipse cx="240" cy="80" rx="64" ry="12"/>'
            .'<ellipse cx="240" cy="286" rx="64" ry="12"/>'
            .'<path d="'.$ressort.'" stroke="'.self::ACCENT.'"/>'
            .'<circle cx="240" cy="30" r="12"/><circle cx="240" cy="324" r="12"/>';
    }

    private static function ligne(float $x1, float $y1, float $x2, float $y2, ?string $couleur = null): string
    {
        $stroke = $couleur ? ' stroke="'.$couleur.'"' : '';

        return sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f"%s/>', $x1, $y1, $x2, $y2, $stroke);
    }
}
