<?php

namespace Tests\Feature\Search;

use App\Support\MiniatureCategorie;
use Database\Seeders\ReferentielSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishThumbnailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_one_svg_per_category(): void
    {
        Storage::fake('s3');
        $this->seed(ReferentielSeeder::class);

        $this->artisan('app:thumbnails')->assertSuccessful();

        Storage::disk('s3')->assertExists(MiniatureCategorie::chemin('roue'));
        $this->assertCount(8, Storage::disk('s3')->allFiles('thumbnails/categories'));
        $this->assertStringStartsWith('<svg', Storage::disk('s3')->get(MiniatureCategorie::chemin('volant')));
    }
}
