<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Sale;

class SyncSales extends Command
{
    protected $signature   = 'sync:sales';
    protected $description = 'Синхронизация продаж';

    public function handle()
    {
        $page     = 1;
        $lastPage = 1;
        $total    = 0;

        do {
            $response = Http::timeout(120)->get('http://109.73.206.144:6969/api/sales', [
                'key'      => 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie',
                'page'     => $page,
                'limit'    => 500,
                'dateFrom' => '2000-01-01',
                'dateTo'   => now()->format('Y-m-d'),
            ]);

            if ($response->status() === 429) {
                $this->warn("429 — ждём 10 секунд...");
                sleep(10);
                continue;
            }

            $response = $response->json();

            $lastPage = data_get($response, 'meta.last_page', 1);
            $items    = data_get($response, 'data', []);

            if (empty($items)) {
                $this->warn("Нет данных: " . json_encode($response));
                break;
            }

            Sale::upsert(
                $items,
                ['sale_id'],
                [
                    'g_number', 'date', 'last_change_date', 'supplier_article',
                    'tech_size', 'barcode', 'total_price', 'discount_percent',
                    'is_supply', 'is_realization', 'promo_code_discount',
                    'warehouse_name', 'country_name', 'oblast_okrug_name',
                    'region_name', 'income_id', 'odid', 'spp', 'for_pay',
                    'finished_price', 'price_with_disc', 'nm_id',
                    'subject', 'category', 'brand', 'is_storno',
                ]
            );
            $total += count($items);

            unset($response, $items, $httpResponse);
            gc_collect_cycles();


            $this->info("Страница {$page}/{$lastPage} — загружено {$total}");
            $page++;



        } while ($page <= $lastPage);

        $this->info("Готово: {$total} продаж");
    }
}
