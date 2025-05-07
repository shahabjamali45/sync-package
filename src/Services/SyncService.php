<?php

namespace SyncPackage\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SyncService
{
    public function sync($table = null): array
    {
        $localConnection = DB::connection();
        $cloudConnection = DB::connection(config('sync.cloud_connection'));
        $chunkSize = config('sync.chunk_size');

        $tables = $table ? [$table] : $this->getTables($localConnection);

        $totalSynced = 0;

        foreach ($tables as $tbl) {
            $rows = $localConnection->table($tbl)->get();
            $totalSynced += $rows->count();

            foreach ($rows->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $row) {
                    $cloudConnection->table($tbl)->updateOrInsert(
                        ['id' => $row->id],
                        (array) $row
                    );
                }
            }

            DB::table(config('sync.log_table'))->insert([
                'table_name' => $tbl,
                'synced_at' => now(),
                'record_count' => $rows->count(),
            ]);
        }

        return ['count' => $totalSynced];
    }

    protected function getTables($connection)
    {
        return $connection->getDoctrineSchemaManager()->listTableNames();
    }
}
