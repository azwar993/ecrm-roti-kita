<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->where('is_active', true)->latest('id');
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $products = $query->paginate(12)->withQueryString();
        $storedCategories = Product::query()
            ->whereNotNull('category')->where('category', '<>', '')
            ->select('category')->distinct()->orderBy('category')->pluck('category');
        $categories = collect(Product::CATEGORIES)
            ->merge($storedCategories)
            ->unique()
            ->values();

        $now = now();
        $combos = Combo::query()->where('is_active', true)
            ->whereHas('items')
            ->whereDoesntHave('items.product', fn ($query) => $query->where('is_active', false))
            ->where(function ($builder) use ($now): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($builder) use ($now): void {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->with(['items.product'])->latest('id')->get();

        $cart = $request->session()->get('rotikita_cart', []);
        $cartCount = is_array($cart) ? array_sum(array_map('intval', $cart)) : 0;

        return view('customer.catalog', compact('products', 'categories', 'search', 'category', 'combos', 'cartCount'));
    }
}
