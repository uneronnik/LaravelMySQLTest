<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAll extends Command
{
    protected $signature   = 'sync:all';
    protected $description = 'Синхронизация всех данных';

    public function handle()
    {
        $this->info('=== Начинаем синхронизацию ===');

        $this->call('sync:incomes');
        $this->call('sync:stocks');
        $this->call('sync:orders');
        $this->call('sync:sales');

        $this->info('=== Всё синхронизировано ===');
    }
}
