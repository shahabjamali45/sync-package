<?php

namespace SyncPackage\Commands;

use Illuminate\Console\Command;
use SyncPackage\Services\SyncService;

class SyncToCloudCommand extends Command
{
    protected $signature = 'sync:to-cloud {--table=}';
    protected $description = 'Sync local database tables to cloud database';

    public function handle(SyncService $syncService)
    {
        $table = $this->option('table');
        $this->info('Starting sync...');

        $results = $syncService->sync($table);

        $this->info("Sync complete. Synced {$results['count']} records.");
    }
}
