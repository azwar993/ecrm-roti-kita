<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleCoverPublishingTest extends TestCase
{
    public function test_valid_article_covers_are_published_to_public_storage(): void
    {
        Storage::fake('public');

        $exitCode = Artisan::call('articles:publish-covers');

        $this->assertSame(0, $exitCode);
        foreach (config('rotikita-article-covers') as $cover) {
            Storage::disk('public')->assertExists($cover['path']);
            $sourcePath = storage_path('app/imports/rotikita-article-covers/'.$cover['source']);
            $this->assertSame(hash_file('sha256', $sourcePath), hash('sha256', Storage::disk('public')->get($cover['path'])));
        }
    }

    public function test_existing_public_cover_files_are_not_overwritten(): void
    {
        Storage::fake('public');
        $firstCover = array_values(config('rotikita-article-covers'))[0];
        Storage::disk('public')->put($firstCover['path'], 'preserve existing asset');

        $exitCode = Artisan::call('articles:publish-covers');

        $this->assertSame(0, $exitCode);
        $this->assertSame('preserve existing asset', Storage::disk('public')->get($firstCover['path']));
    }
}
