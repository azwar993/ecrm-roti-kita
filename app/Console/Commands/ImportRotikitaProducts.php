<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

#[Signature('rotikita:import-products {--source= : Import package directory} {--expected-count=40 : Required number of manifest rows}')]
#[Description('Import draft RotiKita products without replacing existing SKU records.')]
class ImportRotikitaProducts extends Command
{
    public function handle(): int
    {
        $sourceDirectory = (string) ($this->option('source') ?: base_path('storage/app/imports/rotikita-40'));
        $expectedCount = (int) $this->option('expected-count');

        try {
            $rows = $this->readManifest($sourceDirectory);
            $this->validateManifest($rows, $sourceDirectory, $expectedCount);
        } catch (Throwable $exception) {
            $this->error('Import dibatalkan sebelum perubahan: '.$exception->getMessage());

            return self::FAILURE;
        }

        $newlyStoredFiles = [];

        try {
            $result = DB::transaction(function () use ($rows, &$newlyStoredFiles): array {
                $skus = array_column($rows, 'sku');
                $existingSkus = Product::query()
                    ->whereIn('sku', $skus)
                    ->lockForUpdate()
                    ->pluck('sku')
                    ->all();
                $existingSkuLookup = array_fill_keys($existingSkus, true);
                $summary = ['imported' => 0, 'skipped' => 0, 'conflicts' => 0, 'photos' => 0, 'failed' => 0];
                $details = [];

                foreach ($rows as $row) {
                    if (isset($existingSkuLookup[$row['sku']])) {
                        $summary['skipped']++;
                        $details[] = [$row['sku'], 'dilewati', 'SKU sudah ada; record lama tidak diubah'];

                        continue;
                    }

                    $targetPath = 'products/'.$row['photo_name'];
                    $sourceHash = hash_file('sha256', $row['source_path']);
                    $targetExists = Storage::disk('public')->exists($targetPath);

                    if ($targetExists) {
                        $targetHash = hash('sha256', Storage::disk('public')->get($targetPath));
                        if (! hash_equals($sourceHash, $targetHash)) {
                            $summary['conflicts']++;
                            $details[] = [$row['sku'], 'konflik file', $targetPath.' sudah ada dengan isi berbeda'];

                            continue;
                        }
                    } else {
                        $contents = file_get_contents($row['source_path']);
                        if ($contents === false || ! Storage::disk('public')->put($targetPath, $contents)) {
                            throw new RuntimeException('Foto gagal disimpan untuk '.$row['sku'].'.');
                        }

                        $newlyStoredFiles[] = $targetPath;
                        $summary['photos']++;
                    }

                    $priceIsProposed = $row['price_requires_approval'];
                    Product::query()->create([
                        'sku' => $row['sku'],
                        'name' => $row['name'],
                        'category' => $row['category'],
                        'description' => $row['description'],
                        'price' => $row['price'],
                        'stock_qty' => 0,
                        'image_path' => $targetPath,
                        'is_active' => false,
                        'unit' => $row['unit'],
                        'price_status' => $priceIsProposed ? Product::PRICE_STATUS_PROPOSED : Product::PRICE_STATUS_FINAL,
                        'admin_note' => $row['admin_note'],
                    ]);

                    $existingSkuLookup[$row['sku']] = true;
                    $summary['imported']++;
                    $details[] = [$row['sku'], $priceIsProposed ? 'diimpor; harga usulan' : 'diimpor', $targetPath];
                }

                return ['summary' => $summary, 'details' => $details];
            }, 3);
        } catch (Throwable $exception) {
            foreach ($newlyStoredFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            $this->error('Transaksi import dibatalkan; file baru dibersihkan. '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['SKU', 'Hasil', 'Catatan'], $result['details']);
        $this->newLine();
        $this->info('Berhasil: '.$result['summary']['imported']);
        $this->line('Dilewati (SKU sudah ada): '.$result['summary']['skipped']);
        $this->line('Konflik file: '.$result['summary']['conflicts']);
        $this->line('Foto baru disalin: '.$result['summary']['photos']);
        $this->line('Gagal: '.$result['summary']['failed']);

        return self::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function readManifest(string $sourceDirectory): array
    {
        $manifestPath = $sourceDirectory.DIRECTORY_SEPARATOR.'produk_import_40.csv';
        if (! is_file($manifestPath)) {
            throw new RuntimeException('Manifest CSV tidak ditemukan: '.$manifestPath);
        }

        $handle = fopen($manifestPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Manifest CSV tidak dapat dibaca.');
        }

        try {
            $headers = fgetcsv($handle);
            if (! is_array($headers) || $headers === []) {
                throw new RuntimeException('Header manifest CSV kosong.');
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    throw new RuntimeException('Jumlah kolom manifest tidak konsisten pada baris '.(count($rows) + 2).'.');
                }

                $rows[] = array_combine($headers, $values);
            }
        } finally {
            fclose($handle);
        }

        $required = ['sku', 'nama_produk', 'kategori', 'harga_draft_rp', 'satuan', 'deskripsi_draft', 'nama_file_foto', 'stok_awal', 'status_awal', 'perlu_persetujuan_harga', 'catatan'];
        foreach ($required as $column) {
            if (! in_array($column, $headers, true)) {
                throw new RuntimeException('Kolom wajib tidak ada: '.$column);
            }
        }

        return $rows;
    }

    /** @param list<array<string, mixed>> $rows */
    private function validateManifest(array &$rows, string $sourceDirectory, int $expectedCount): void
    {
        if ($expectedCount > 0 && count($rows) !== $expectedCount) {
            throw new RuntimeException('Manifest berisi '.count($rows).' baris; diharapkan '.$expectedCount.'.');
        }
        if ($rows === []) {
            throw new RuntimeException('Manifest tidak memuat produk.');
        }

        $seenSkus = [];
        $photoDirectory = $sourceDirectory.DIRECTORY_SEPARATOR.'photos';

        foreach ($rows as $index => $row) {
            $sku = strtoupper(trim((string) $row['sku']));
            $photoName = (string) $row['nama_file_foto'];
            $expectedPhotoPattern = '/^'.preg_quote($sku, '/').'_[a-z0-9-]+\.jpg$/';

            if (! preg_match('/^ROT-\d{3}$/', $sku) || isset($seenSkus[$sku])) {
                throw new RuntimeException('SKU tidak valid atau duplikat di manifest pada baris '.($index + 2).'.');
            }
            $seenSkus[$sku] = true;

            $category = trim((string) $row['kategori']);
            if (! in_array($category, Product::CATEGORIES, true)) {
                throw new RuntimeException('Kategori tidak diizinkan untuk '.$sku.': '.$category);
            }
            if (! is_numeric($row['harga_draft_rp']) || (float) $row['harga_draft_rp'] <= 0) {
                throw new RuntimeException('Harga tidak valid untuk '.$sku.'.');
            }
            if (trim((string) $row['satuan']) === '' || mb_strlen((string) $row['satuan']) > 30) {
                throw new RuntimeException('Satuan kosong atau terlalu panjang untuk '.$sku.'.');
            }
            if ((int) $row['stok_awal'] !== 0 || ! str_contains(mb_strtolower((string) $row['status_awal']), 'draft')) {
                throw new RuntimeException('Produk '.$sku.' wajib berstok 0 dan berstatus draft.');
            }
            if (! in_array(strtoupper((string) $row['perlu_persetujuan_harga']), ['YA', 'TIDAK'], true)) {
                throw new RuntimeException('Flag persetujuan harga tidak valid untuk '.$sku.'.');
            }
            if (basename($photoName) !== $photoName || ! preg_match($expectedPhotoPattern, $photoName)) {
                throw new RuntimeException('Nama foto tidak sesuai SKU untuk '.$sku.'.');
            }

            $sourcePath = $photoDirectory.DIRECTORY_SEPARATOR.$photoName;
            if (! is_file($sourcePath)) {
                throw new RuntimeException('Foto sumber tidak ditemukan untuk '.$sku.': '.$sourcePath);
            }
            $imageInfo = @getimagesize($sourcePath);
            if (! is_array($imageInfo) || ($imageInfo['mime'] ?? null) !== 'image/jpeg') {
                throw new RuntimeException('Foto bukan JPEG valid untuk '.$sku.'.');
            }

            $priceIsProposed = strtoupper((string) $row['perlu_persetujuan_harga']) === 'YA';
            $adminNote = trim((string) $row['catatan']);
            if ($priceIsProposed && ! str_contains(mb_strtolower($adminNote), 'harga')) {
                $adminNote = 'Harga usulan—perlu konfirmasi pemilik. '.$adminNote;
            }

            $rows[$index] = [
                'sku' => $sku,
                'name' => trim((string) $row['nama_produk']),
                'category' => $category,
                'price' => number_format((float) $row['harga_draft_rp'], 2, '.', ''),
                'unit' => trim((string) $row['satuan']),
                'description' => trim((string) $row['deskripsi_draft']),
                'photo_name' => $photoName,
                'source_path' => $sourcePath,
                'price_requires_approval' => $priceIsProposed,
                'admin_note' => $adminNote,
            ];
        }
    }
}
