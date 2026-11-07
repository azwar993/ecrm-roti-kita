<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Schema::hasTable('articles')
            ? Article::query()->published()->latest('published_at')->latest('id')->paginate(9)
            : new LengthAwarePaginator([], 0, 9, 1, ['path' => request()->url()]);

        return view('storefront.articles', compact('articles'));
    }

    public function show(string $slug): View
    {
        abort_unless(Schema::hasTable('articles'), 404);
        $article = Article::query()->where('slug', $slug)->firstOrFail();
        abort_unless($article->status === Article::STATUS_PUBLISHED
            && $article->published_at !== null
            && $article->published_at->lessThanOrEqualTo(now()), 404);
        $relatedArticles = Article::query()->published()
            ->where('id', '<>', $article->id)
            ->where('category', $article->category)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('storefront.article-show', compact('article', 'relatedArticles'));
    }
}
