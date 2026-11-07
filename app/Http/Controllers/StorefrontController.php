<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Combo;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        // The landing page only shows real products stored in the database.
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->where('stock_qty', '>', 0)
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $storedCategories = Product::query()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $featuredCategories = collect(Product::CATEGORIES)
            ->merge($storedCategories)
            ->unique()
            ->values();

        $featuredCombos = $this->activeCombos()
            ->whereHas('items')
            ->with(['items.product'])
            ->latest('id')
            ->limit(3)
            ->get();

        $articles = Schema::hasTable('articles')
            ? Article::query()->published()->latest('published_at')->latest('id')->limit(3)->get()
            : collect();
        $promotions = $this->activePromotions()->limit(3)->get();

        return view('storefront.home', compact('featuredProducts', 'featuredCategories', 'featuredCombos', 'articles', 'promotions'));
    }

    public function about(): View
    {
        return view('storefront.about');
    }

    public function promotions(): View
    {
        return view('storefront.promotions', ['promotions' => $this->activePromotions()->paginate(9)]);
    }

    public function menu(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $sort = (string) $request->query('sort', 'newest');

        $productQuery = Product::query()->where('is_active', true);

        if ($search !== '') {
            $productQuery->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $productQuery->where('category', $category);
        }

        $products = match ($sort) {
            'price_asc' => $productQuery->orderBy('price')->orderByDesc('id'),
            'price_desc' => $productQuery->orderByDesc('price')->orderByDesc('id'),
            default => $productQuery->orderByRaw('CASE WHEN stock_qty > 0 THEN 0 ELSE 1 END')->orderByDesc('id'),
        };
        $products = $products->paginate(12)->withQueryString();

        $storedCategories = Product::query()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $categories = collect(Product::CATEGORIES)
            ->merge($storedCategories)
            ->unique()
            ->values();

        $combos = $this->activeCombos()
            ->whereHas('items')
            ->with(['items.product'])
            ->latest('id')
            ->get();

        return view('storefront.menu', compact('products', 'categories', 'search', 'category', 'combos'));
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('storefront.product-show', compact('product'));
    }

    private function activeCombos()
    {
        $now = now();

        return Combo::query()
            ->where('is_active', true)
            ->whereDoesntHave('items.product', fn ($query) => $query->where('is_active', false))
            ->where(function ($builder) use ($now): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($builder) use ($now): void {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    private function activePromotions()
    {
        $now = now();

        return Promotion::query()->where('is_active', true)
            ->whereNotNull('starts_at')->where('starts_at', '<=', $now)
            ->whereNotNull('ends_at')->where('ends_at', '>=', $now)
            ->where(function ($query): void {
                $query->whereNull('usage_limit')->orWhereRaw(
                    '(select count(*) from orders where orders.promotion_id = promotions.id) < promotions.usage_limit'
                );
            })
            ->latest('ends_at')->latest('id');
    }
}
