<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Promotion::query()->withCount('orders')->latest('id');
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        if ($search !== '') {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }
        if ($status === 'active') $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);
        if ($status === 'expired') $query->where('ends_at', '<', now());

        return view('admin.promotions', [
            'promotions' => $query->paginate(12)->withQueryString(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.promotion-form', ['promotion' => new Promotion()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        Promotion::create($data);

        return redirect()->route('admin.promotions')->with('success', 'Promosi berhasil dibuat.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotion-form', compact('promotion'));
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validatedData($request, $promotion));

        return redirect()->route('admin.promotions')->with('success', 'Perubahan promosi berhasil disimpan.');
    }

    public function toggleActive(Promotion $promotion): RedirectResponse
    {
        $promotion->is_active = ! $promotion->is_active;
        $promotion->save();

        return back()->with('success', $promotion->is_active ? 'Promosi diaktifkan.' : 'Promosi dinonaktifkan.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->orders()->exists()) {
            $promotion->update(['is_active' => false]);

            return back()->with('success', 'Promosi sudah dipakai pesanan; promosi dinonaktifkan agar riwayat tetap utuh.');
        }

        $promotion->delete();

        return redirect()->route('admin.promotions')->with('success', 'Promosi yang belum digunakan berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Promotion $promotion = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotion?->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'discount_type' => ['required', Rule::in([Promotion::TYPE_PERCENTAGE, Promotion::TYPE_FIXED])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'min_order_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'max_discount_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'ends_at.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'discount_value.gt' => 'Nilai diskon harus lebih besar dari nol.',
        ]);

        if ($data['discount_type'] === Promotion::TYPE_PERCENTAGE && (float) $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount_value' => 'Diskon persentase tidak boleh lebih dari 100%.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
