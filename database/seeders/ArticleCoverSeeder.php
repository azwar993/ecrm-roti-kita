<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ArticleCoverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publicDisk = Storage::disk('public');

        foreach (config('rotikita-article-covers', []) as $slug => $cover) {
            if (! $publicDisk->exists($cover['path'])) {
                continue;
            }

            $article = Article::query()->where('slug', $slug)->first();

            if ($article && blank($article->cover_path)) {
                $article->forceFill(['cover_path' => $cover['path']])->save();
            }
        }
    }
}
