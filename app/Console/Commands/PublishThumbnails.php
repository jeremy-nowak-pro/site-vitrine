<?php

namespace App\Console\Commands;

use App\Models\Categorie;
use App\Support\MiniatureCategorie;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:thumbnails')]
#[Description('Dépose les miniatures de démonstration des catégories sur le stockage S3')]
class PublishThumbnails extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('s3');

        foreach (Categorie::query()->pluck('forme') as $forme) {
            $disk->put(MiniatureCategorie::chemin($forme), MiniatureCategorie::svg($forme), [
                'ContentType' => 'image/svg+xml',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
        }

        $this->components->info('Miniatures publiées.');

        return self::SUCCESS;
    }
}
