<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use App\Services\ApiService;
use App\Models\Order;

class SyncOrders extends Command
{
    protected $signature   = 'sync:orders';
    protected $description = 'Синхронизация заказов';

    public function handle()
    {
        $page     = 1;
        $lastPage = 1;
        $total    = 0;

        do {
            $response = Http::timeout(120)->get('http://109.73.206.144:6969/api/orders', [
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
            $items   = data_get($response, 'data', []);

            if (empty($items)) {
                $this->warn("Нет данных: " . json_encode($response));
                break;
            }

            Order::upsert(
                $items,
                ['g_number'],
                [
                    'date', 'last_change_date', 'supplier_article', 'tech_size',
                    'barcode', 'total_price', 'discount_percent', 'warehouse_name',
                    'oblast', 'income_id', 'odid', 'nm_id', 'subject',
                    'category', 'brand', 'is_cancel', 'cancel_dt',
                ]
            );
            $total += count($items);

            unset($response, $items, $httpResponse);
            gc_collect_cycles();


            $this->info("Страница {$page}/{$lastPage} — загружено {$total}");
            $page++;



        } while ($page <= $lastPage);


        $this->info("Готово! Всего: {$total} заказов");
    }
}
