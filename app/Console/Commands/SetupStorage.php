<?php

namespace App\Console\Commands;

use Aws\S3\Exception\S3Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

/**
 * Prépare le bucket S3 local : création si absent et lecture publique limitée
 * au préfixe des miniatures. Les GLB, STL et documents restent privés et
 * passent par des URL signées.
 */
#[Signature('app:storage-setup')]
#[Description('Crée le bucket S3 et applique la politique de lecture publique des miniatures')]
class SetupStorage extends Command
{
    public const PUBLIC_PREFIX = 'thumbnails/';

    public function handle(): int
    {
        /** @var AwsS3V3Adapter $disk */
        $disk = Storage::disk('s3');
        $client = $disk->getClient();
        $bucket = config('filesystems.disks.s3.bucket');

        if (! $client->doesBucketExistV2($bucket)) {
            $client->createBucket(['Bucket' => $bucket]);
            $this->components->info("Bucket « {$bucket} » créé.");
        }

        try {
            $client->putBucketPolicy([
                'Bucket' => $bucket,
                'Policy' => json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [[
                        'Effect' => 'Allow',
                        'Principal' => ['AWS' => ['*']],
                        'Action' => ['s3:GetObject'],
                        'Resource' => ["arn:aws:s3:::{$bucket}/".self::PUBLIC_PREFIX.'*'],
                    ]],
                ], JSON_THROW_ON_ERROR),
            ]);
        } catch (S3Exception $e) {
            $this->components->error('Politique du bucket refusée : '.$e->getAwsErrorMessage());

            return self::FAILURE;
        }

        $this->components->info('Stockage prêt, lecture publique sur '.self::PUBLIC_PREFIX);

        return self::SUCCESS;
    }
}
