<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Income;

class SyncIncomes extends Command
{
    protected $signature   = 'sync:incomes';
    protected $description = 'Синхронизация поставок';

    public function handle()
    {
        $page     = 1;
        $lastPage = 1;
        $total    = 0;

        do {
            $response = Http::timeout(120)->get('http://109.73.206.144:6969/api/incomes', [
                'key' => 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie',
                'page' => $page,
                'limit' => 500,
                'dateFrom' => '2000-01-01',
                'dateTo' => now()->format('Y-m-d'),
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

            Income::upsert(
                $items,
                ['income_id'],
                [
                    'number', 'date', 'last_change_date', 'supplier_article',
                    'tech_size', 'barcode', 'quantity', 'total_price',
                    'date_close', 'warehouse_name', 'nm_id',
                ]
            );
            $total += count($items);

            unset($response, $items, $httpResponse);
            gc_collect_cycles();


            $this->info("Страница {$page}/{$lastPage} — загружено {$total}");
            $page++;



        } while ($page <= $lastPage);

        $this->info("Готово: {$total} поставок");
    }
}
