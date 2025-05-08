<?php

namespace Sam\SyncPackage\Commands;

use Illuminate\Console\Command;
use Sam\SyncPackage\Services\SyncService;

/**
 * Class SyncToCloudCommand
 *
 * Console command to sync local database tables to the cloud database.
 *
 * @package Sam\SyncPackage\Commands
 */
class SyncToCloudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:to-cloud {--table= : Optional table to sync. If not provided, all tables will be synced.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local database tables to the cloud database.';

    /**
     * Execute the console command.
     *
     * @param  SyncService  $syncService
     * @return void
     */
    public function handle(SyncService $syncService): void
    {
        $table = $this->option('table');

        $this->line('');
        $this->line('=========================================');
        $this->line('Developer: Shahab Alam');
        $this->line('Project   : Data Sync Service');
        $this->line('Updated   : ' . now()->format('Y-m-d H:i:s'));
        $this->line('Version   : 1.0.0');
        $this->line('=========================================');
        $this->line('');


        $this->info("Starting sync...");

        // Perform the sync operation
        $results = $syncService->sync($table);

        // Display per-table sync report
        $this->table(
            ['Table', 'Rows Synced', 'Table Created'],
            $results['report']
        );

        // Summary output
        $this->info("\u{2714} Total Tables Created: {$results['tables_created']}");
        $this->info("\u{2714} Total Rows Synced: {$results['total_rows_synced']}");
    }
}
