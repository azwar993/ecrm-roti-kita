<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->latest('id');
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $category = trim((string) $request->query('category', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'low') {
            $query->where('is_active', true)->whereBetween('stock_qty', [1, 5]);
        } elseif ($status === 'out') {
            $query->where('is_active', true)->where('stock_qty', 0);
        }

        $products = $query->paginate(10)->withQueryString();
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::where('is_active', true)
            ->whereBetween('stock_qty', [1, 5])->count();
        $outOfStockProducts = Product::where('is_active', true)
            ->where('stock_qty', 0)->count();

        return view('admin.products', compact(
            'products', 'search', 'status', 'totalProducts', 'activeProducts',
            'lowStockProducts', 'outOfStockProducts', 'category'
        ) + ['categories' => $this->categoryOptions()]);
    }

    public function create(): View
    {
        return view('admin.product-form', [
            'product' => new Product,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['sku'] = strtoupper(trim($data['sku']));
        $data['is_active'] = $request->boolean('is_active');
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            if (! is_string($imagePath) || $imagePath === '') {
                throw ValidationException::withMessages(['image' => 'Foto gagal disimpan. Periksa kapasitas storage lalu coba kembali.']);
            }
        }
        $data['image_path'] = $imagePath;
        unset($data['image'], $data['remove_image']);

        try {
            DB::transaction(fn () => Product::create($data), 3);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()->route('admin.products')->with('success', 'Produk berhasil ditambahkan ke database.');
    }

    public function show(Product $product): View
    {
        $product->loadCount(['stockMovements', 'comboItems']);

        return view('admin.product-show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('admin.product-form', [
            'product' => $product,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $data['sku'] = strtoupper(trim($data['sku']));
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        $removeImage = $request->boolean('remove_image');
        $oldImage = $product->image_path;
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            if (! is_string($imagePath) || $imagePath === '') {
                throw ValidationException::withMessages(['image' => 'Foto gagal disimpan. Foto lama tetap digunakan. Periksa storage lalu coba kembali.']);
            }
            $data['image_path'] = $imagePath;
        } elseif ($removeImage) {
            $data['image_path'] = null;
        }

        try {
            $oldImage = DB::transaction(function () use ($product, $data): ?string {
                $lockedProduct = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
                $previousImage = $lockedProduct->image_path;
                $lockedProduct->update($data);

                return $previousImage;
            }, 3);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        if (($imagePath || $removeImage) && $oldImage && $oldImage !== $imagePath) {
            $this->deleteImageIfUnused($oldImage);
        }

        return redirect()->route('admin.products')->with('success', 'Perubahan produk berhasil disimpan.');
    }

    /** Deactivate instead of deleting, so transaction history can keep the product reference. */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products')->with('success', 'Produk dinonaktifkan. Data dan foto tetap disimpan.');
    }

    public function toggleActive(Product $product): RedirectResponse
    {
        if (! $product->is_active && $product->price_status === Product::PRICE_STATUS_PROPOSED) {
            throw ValidationException::withMessages(['price' => 'Konfirmasi harga usulan sebelum mengaktifkan produk.']);
        }

        if (! $product->is_active && (! $product->image_path || ! Storage::disk('public')->exists($product->image_path))) {
            throw ValidationException::withMessages(['image' => 'Tambahkan foto produk yang valid sebelum mengaktifkan produk.']);
        }

        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Produk diaktifkan dan tampil di katalog.' : 'Produk dinonaktifkan.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $request->merge(['sku' => strtoupper(trim((string) $request->input('sku')))]);
        $request->merge(['price_status' => $request->input('price_status', $product?->price_status ?? Product::PRICE_STATUS_FINAL)]);
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:80', Rule::in($this->categoryOptions()->all())],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'unit' => ['nullable', 'string', 'max:30'],
            'price_status' => ['required', Rule::in([Product::PRICE_STATUS_FINAL, Product::PRICE_STATUS_PROPOSED])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'stock_qty' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'sku.unique' => 'SKU sudah digunakan produk lain.',
            'price.gt' => 'Harga harus lebih besar dari nol.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran foto maksimal 4 MB.',
        ]);

        $skuExists = Product::query()
            ->whereRaw('UPPER(sku) = ?', [$data['sku']])
            ->when($product, fn ($query) => $query->whereKeyNot($product->getKey()))
            ->exists();
        if ($skuExists) {
            throw ValidationException::withMessages(['sku' => 'SKU sudah digunakan produk lain.']);
        }

        if ($request->boolean('is_active') && $data['price_status'] === Product::PRICE_STATUS_PROPOSED) {
            throw ValidationException::withMessages(['price_status' => 'Konfirmasi harga usulan sebelum mengaktifkan produk.']);
        }

        unset($data['remove_image']);

        if ($request->boolean('is_active')) {
            $hasUploadedImage = $request->hasFile('image');
            $keepsExistingImage = $product?->image_path
                && ! $request->boolean('remove_image')
                && Storage::disk('public')->exists($product->image_path);

            if (! $hasUploadedImage && ! $keepsExistingImage) {
                throw ValidationException::withMessages(['image' => 'Unggah foto produk sebelum mengaktifkannya. Produk tanpa foto dapat disimpan sebagai nonaktif.']);
            }
        }

        return $data;
    }

    /** @return Collection<int, string> */
    private function categoryOptions(): Collection
    {
        $storedCategories = Product::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return collect(Product::CATEGORIES)
            ->merge(Product::LEGACY_CATEGORIES)
            ->merge($storedCategories)
            ->unique()
            ->values();
    }

    private function deleteImageIfUnused(string $path): void
    {
        if (! Product::query()->where('image_path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
