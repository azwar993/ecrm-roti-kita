<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('articles'), 503, 'Fitur artikel menunggu migration database.');

        $query = Article::query()->latest('id');
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        if (in_array($status, [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED], true)) {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%'));
        }

        return view('admin.articles', [
            'articles' => $query->paginate(12)->withQueryString(),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        abort_unless(Schema::hasTable('articles'), 503, 'Fitur artikel menunggu migration database.');

        return view('admin.article-form', ['article' => new Article]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['author_id'] = $request->user()->id;
        $coverPath = $request->file('cover')?->store('articles', 'public');
        $data['cover_path'] = $coverPath;
        try {
            $article = Article::create($data);
        } catch (\Throwable $exception) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }
            throw $exception;
        }

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Artikel berhasil disimpan.');
    }

    public function edit(Article $article): View
    {
        return view('admin.article-form', compact('article'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validatedData($request, $article);
        $oldCover = $article->cover_path;
        $newCover = null;
        if ($request->hasFile('cover')) {
            $newCover = $request->file('cover')->store('articles', 'public');
            $data['cover_path'] = $newCover;
        }
        try {
            $article->update($data);
        } catch (\Throwable $exception) {
            if ($newCover) {
                Storage::disk('public')->delete($newCover);
            }
            throw $exception;
        }
        if ($request->hasFile('cover') && $oldCover && $oldCover !== $article->cover_path) {
            Storage::disk('public')->delete($oldCover);
        }

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $coverPath = $article->cover_path;
        $article->delete();
        if ($coverPath) {
            Storage::disk('public')->delete($coverPath);
        }

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Article $article = null): array
    {
        $slug = Str::slug((string) ($request->input('slug') ?: $request->input('title')));
        if ($slug === '') {
            $slug = Str::lower(Str::random(12));
        }
        $request->merge(['slug' => $slug]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'category' => [$article ? 'sometimes' : 'required', Rule::in(['Tips Bakery', 'Edukasi Bakery', 'Inspirasi', 'Tips Roti', 'Tips Dapur'])],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:180'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:320'],
            'body' => ['required', 'string', 'max:50000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'status' => ['required', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['published_at'] = $data['status'] === Article::STATUS_PUBLISHED
            ? ($data['published_at'] ?? $article?->published_at ?? now())
            : null;
        foreach (['category', 'seo_title', 'seo_description'] as $metadataField) {
            if ($article && ! array_key_exists($metadataField, $data)) {
                $data[$metadataField] = $article->{$metadataField};
            }
        }
        unset($data['cover']);

        return $data;
    }
}
