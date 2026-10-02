<?php

/**
 * Routeur du serveur PHP intégré utilisé par Sail en local.
 *
 * `artisan serve` sert les fichiers de public/ sans en-tête de cache. Ce
 * routeur ajoute un cache d'un an aux fichiers de /build/assets, dont le nom
 * contient une empreinte (app-BtruES75.js) : un nouveau build change le nom,
 * donc l'URL. Le reste suit le comportement de `artisan serve`. En production,
 * la même règle se règle dans nginx (voir README).
 */
$chemin = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$public = __DIR__.'/../public';
$fichier = realpath($public.$chemin);

if ($fichier !== false && str_starts_with($fichier, realpath($public).'/build/assets/') && is_file($fichier)) {
    $types = [
        'js' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'svg' => 'image/svg+xml',
        'woff2' => 'font/woff2',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    header('Content-Type: '.($types[pathinfo($fichier, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: '.filesize($fichier));
    readfile($fichier);

    return true;
}

if ($chemin !== '/' && $fichier !== false && str_starts_with($fichier, realpath($public)) && is_file($fichier)) {
    return false;
}

require $public.'/index.php';
