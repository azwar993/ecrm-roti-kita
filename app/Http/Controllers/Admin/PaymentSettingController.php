<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function edit(): View
    {
        $settings = PaymentSetting::query()->whereKey(1)->first();
        $qrisImageAvailable = $settings?->qris_image && Storage::disk('public')->exists($settings->qris_image);
        $qrisImageUrl = $qrisImageAvailable ? Storage::disk('public')->url($settings->qris_image) : null;

        return view('admin.payment-settings', compact('settings', 'qrisImageAvailable', 'qrisImageUrl'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_qris' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'required_with:bank_account_name,bank_account_number', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'required_with:bank_name,bank_account_number', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'required_with:bank_name,bank_account_name', 'string', 'max:80', 'regex:/^[0-9 .-]+$/'],
        ]);
        unset($data['remove_qris']);
        $newImage = null;
        if ($request->hasFile('qris_image')) {
            $newImage = $request->file('qris_image')->store('payment-config', 'public');
            $data['qris_image'] = $newImage;
        } elseif ($request->boolean('remove_qris')) {
            $data['qris_image'] = null;
        } else {
            unset($data['qris_image']);
        }

        try {
            DB::transaction(function () use ($data): void {
                PaymentSetting::query()->updateOrCreate(['id' => 1], $data);
            }, 3);
        } catch (\Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $exception;
        }

        // Keep replaced images because historical payments store their original QRIS path.
        return back()->with('success', 'Konfigurasi pembayaran berhasil disimpan.');
    }
}
