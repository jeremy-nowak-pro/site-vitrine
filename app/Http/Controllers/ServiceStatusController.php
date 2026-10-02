<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Page temporaire de l'étape 1 : vérifie que chaque service Sail répond.
 * Remplacée par le catalogue à l'étape 4.
 */
class ServiceStatusController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Status', [
            'services' => [
                $this->check('PostgreSQL', fn () => DB::scalar('select version()')),
                $this->check('Redis', function () {
                    Cache::store('redis')->put('status-check', 'ok', 10);

                    return Cache::store('redis')->get('status-check') === 'ok' ? 'lecture / écriture OK' : null;
                }),
                $this->check('Meilisearch', fn () => 'v'.Http::timeout(3)
                    ->get(config('scout.meilisearch.host').'/version')
                    ->throw()
                    ->json('pkgVersion')),
                $this->check('Stockage S3 (RustFS)', function () {
                    $disk = Storage::disk('s3');
                    $disk->put('status-check.txt', 'ok');

                    return $disk->get('status-check.txt') === 'ok' ? 'bucket « '.config('filesystems.disks.s3.bucket').' » accessible' : null;
                }),
            ],
        ]);
    }

    /**
     * @param  callable(): ?string  $probe
     * @return array{name: string, ok: bool, detail: string}
     */
    private function check(string $name, callable $probe): array
    {
        try {
            $detail = $probe();

            return ['name' => $name, 'ok' => $detail !== null, 'detail' => $detail ?? 'réponse inattendue'];
        } catch (Throwable $e) {
            Log::warning('Service indisponible', ['service' => $name, 'exception' => $e->getMessage()]);

            return ['name' => $name, 'ok' => false, 'detail' => 'indisponible'];
        }
    }
}
