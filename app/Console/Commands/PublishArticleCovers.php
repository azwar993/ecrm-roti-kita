<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PublishArticleCovers extends Command
{
    protected $signature = 'articles:publish-covers';

    protected $description = 'Copy validated article cover JPEGs into public storage without replacing existing files';

    public function handle(): int
    {
        $covers = config('rotikita-article-covers', []);
        $sourceDirectory = storage_path('app/imports/rotikita-article-covers');
        $validatedSources = [];

        foreach ($covers as $cover) {
            $sourcePath = $sourceDirectory.DIRECTORY_SEPARATOR.$cover['source'];

            if (! is_file($sourcePath)) {
                $this->error('File sumber tidak ditemukan: '.$cover['source']);

                return self::FAILURE;
            }

            $image = getimagesize($sourcePath);
            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($sourcePath);

            if ($image === false || $image[2] !== IMAGETYPE_JPEG || $mimeType !== 'image/jpeg') {
                $this->error('File bukan JPEG valid: '.$cover['source']);

                return self::FAILURE;
            }

            $validatedSources[] = [$sourcePath, $cover];
        }

        $publicDisk = Storage::disk('public');

        foreach ($validatedSources as [$sourcePath, $cover]) {
            if ($publicDisk->exists($cover['path'])) {
                $this->line('Dipertahankan (sudah ada): '.$cover['path']);

                continue;
            }

            $contents = file_get_contents($sourcePath);

            if ($contents === false || ! $publicDisk->put($cover['path'], $contents)) {
                $this->error('Gagal menyimpan cover: '.$cover['path']);

                return self::FAILURE;
            }

            $this->info('Disalin: '.$cover['source'].' → '.$cover['path']);
        }

        $this->comment('Aset dipublikasikan. Jalankan ArticleSeeder pada lingkungan yang disetujui untuk mengisi cover_path kosong.');

        return self::SUCCESS;
    }
}
