<?php

namespace App\Console\Commands;

use App\Services\ProductLaunchPreparationService;
use Illuminate\Console\Command;

class PrepareProductLaunch extends Command
{
    protected $signature = 'products:prepare-launch {--apply : Apply stock 30 and activate products that pass sales checks}';

    protected $description = 'Preview or prepare catalog products for a demo launch';

    public function handle(ProductLaunchPreparationService $preparation): int
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || filled(config('database.connections.sqlite.url'))) {
            $this->error('This command is restricted to SQLite in-memory. No changes were made.');

            return self::FAILURE;
        }

        $report = $this->option('apply') ? $preparation->apply() : $preparation->preview();
        $this->info('Produk ditemukan pada database saat ini: '.$report['total']);
        $this->info('Siap diaktifkan: '.count($report['ready']));
        $this->info('Tertahan: '.count($report['blocked']));
        $this->info('Stok yang akan disetel ke 30: '.count($report['stock_changes']));

        foreach ($report['stock_changes'] as $product) {
            $this->line('STOK '.$product->sku.' — '.$product->stock_qty.' → 30');
        }

        foreach ($report['ready'] as $product) {
            $this->line('SIAP '.$product->sku.' — '.$product->name.($product->is_active ? ' (sudah aktif)' : ' (akan aktif)'));
        }
        foreach ($report['blocked'] as $item) {
            $this->warn('TERTAHAN '.$item['product']->sku.' — '.implode(' ', $item['reasons']));
        }
        $this->comment($this->option('apply') ? 'Persiapan diterapkan pada SQLite.' : 'Dry-run; gunakan --apply untuk menerapkan hanya pada SQLite.');

        return self::SUCCESS;
    }
}
