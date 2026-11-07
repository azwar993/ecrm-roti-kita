<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $query = Combo::query()->with('items.product')->withCount('orderItems')->latest('id');
        if ($search !== '') {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($status === 'active') $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        return view('admin.combos', ['combos' => $query->paginate(12)->withQueryString(), 'search' => $search, 'status' => $status]);
    }

    public function create(): View
    {
        return view('admin.combo-form', ['combo' => new Combo(), 'products' => Product::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $items] = $this->validatedData($request);
        $imagePath = $request->file('image')?->store('combos', 'public');
        $data['image_path'] = $imagePath;
        unset($data['image']);

        try {
            DB::transaction(function () use ($data, $items): void {
                $combo = Combo::create($data);
                $combo->products()->sync($items);
            }, 3);
        } catch (\Throwable $exception) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            throw $exception;
        }

        return redirect()->route('admin.combos.index')->with('success', 'Combo berhasil dibuat.');
    }

    public function edit(Combo $combo): View
    {
        $combo->load('items');
        return view('admin.combo-form', ['combo' => $combo, 'products' => Product::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, Combo $combo): RedirectResponse
    {
        [$data, $items] = $this->validatedData($request, $combo);
        unset($data['image']);
        $oldImage = $combo->image_path;
        $imagePath = $request->file('image')?->store('combos', 'public');
        if ($imagePath) $data['image_path'] = $imagePath;

        try {
            DB::transaction(function () use ($combo, $data, $items): void {
                $lockedCombo = Combo::query()->whereKey($combo->id)->lockForUpdate()->firstOrFail();
                $lockedCombo->update($data);
                $lockedCombo->products()->sync($items);
            }, 3);
        } catch (\Throwable $exception) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            throw $exception;
        }
        if ($imagePath && $oldImage) Storage::disk('public')->delete($oldImage);

        return redirect()->route('admin.combos.index')->with('success', 'Perubahan combo berhasil disimpan.');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        if ($combo->orderItems()->exists()) {
            $combo->update(['is_active' => false]);
            return back()->with('success', 'Combo sudah tercatat pada pesanan; combo dinonaktifkan agar riwayat tetap utuh.');
        }

        $imagePath = $combo->image_path;
        $combo->delete();
        if ($imagePath) Storage::disk('public')->delete($imagePath);

        return redirect()->route('admin.combos.index')->with('success', 'Combo yang belum dipesan berhasil dihapus.');
    }

    public function toggleActive(Combo $combo): RedirectResponse
    {
        $combo->is_active = ! $combo->is_active;
        $combo->save();
        return back()->with('success', $combo->is_active ? 'Combo diaktifkan.' : 'Combo dinonaktifkan.');
    }

    private function validatedData(Request $request, ?Combo $combo = null): array
    {
        $request->merge(['sku' => strtoupper(trim((string) $request->input('sku')))]);
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('combos', 'sku')->ignore($combo?->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $activeIds = Product::query()->whereIn('id', $productIds)->where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id);
        if ($activeIds->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => 'Semua produk komponen harus aktif.']);
        }

        $items = collect($data['items'])->mapWithKeys(fn ($item) => [(int) $item['product_id'] => ['quantity' => (int) $item['quantity']]])->all();
        unset($data['items']);
        $data['is_active'] = $request->boolean('is_active');

        return [$data, $items];
    }
}
