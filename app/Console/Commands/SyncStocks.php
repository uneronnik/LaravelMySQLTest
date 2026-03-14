<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Stock;

class SyncStocks extends Command
{
    protected $signature   = 'sync:stocks';
    protected $description = 'Синхронизация остатков';

    public function handle()
    {
        $page     = 1;
        $lastPage = 1;
        $total    = 0;

        do {
            $response = Http::timeout(120)->get('http://109.73.206.144:6969/api/stocks', [
                'key'      => 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie',
                'page'     => $page,
                'limit'    => 500,
                'dateFrom' => now()->format('Y-m-d'),
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

            Stock::upsert(
                $items,
                ['barcode', 'warehouse_name'],
                [
                    'date', 'last_change_date', 'supplier_article', 'tech_size',
                    'quantity', 'is_supply', 'is_realization', 'quantity_full',
                    'in_way_to_client', 'in_way_from_client', 'nm_id',
                    'subject', 'category', 'brand', 'sc_code', 'price', 'discount',
                ]
            );
            $total += count($items);

            unset($response, $items, $httpResponse);
            gc_collect_cycles();


            $this->info("Страница {$page}/{$lastPage} — загружено {$total}");
            $page++;


        } while ($page <= $lastPage);

        $this->info("Готово: {$total}");
    }
}
