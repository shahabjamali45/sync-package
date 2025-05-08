<?php

namespace Sam\SyncPackage\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncService
{
    /**
     * Syncs tables from the local database to the cloud database.
     *
     * @param string|null $table Optional single table to sync.
     * @return array Summary of sync results.
     */
    public function sync($table = null): array
    {
        $sourceConnection = DB::connection(); // Local DB
        $targetConnection = DB::connection(config('sync.cloud_connection')); // Cloud DB
        $chunkSize = config('sync.chunk_size', 100);

        $tables = $table ? [$table] : $this->getTables($sourceConnection);
        $tablesCreated = 0;
        $totalSynced = 0;
        $syncReport = [];

        foreach ($tables as $tbl) {
            $tableCreated = false;

            // Create the table in target if it does not exist
            if (!Schema::connection($targetConnection->getName())->hasTable($tbl)) {
                try {
                    $this->createTableFromSource($sourceConnection, $targetConnection, $tbl);
                    $tablesCreated++;
                    $tableCreated = true;
                } catch (\Exception $e) {
                    logger()->error("Failed to create table `$tbl` in target DB: " . $e->getMessage());
                    continue;
                }
            }

            $rows = collect($sourceConnection->table($tbl)->get());
            if ($rows->isEmpty()) {
                continue;
            }

            $hasId = Schema::connection($sourceConnection->getName())->hasColumn($tbl, 'id');

            try {
                if ($hasId) {
                    // Update or insert based on 'id'
                    $rows->chunk($chunkSize)->each(function ($chunk) use ($targetConnection, $tbl) {
                        foreach ($chunk as $row) {
                            $targetConnection->table($tbl)->updateOrInsert(
                                ['id' => $row->id],
                                (array) $row
                            );
                        }
                    });
                } else {
                    // Truncate and insert if no 'id' column
                    $targetConnection->table($tbl)->truncate();

                    $rows->chunk($chunkSize)->each(function ($chunk) use ($targetConnection, $tbl) {
                        $targetConnection->table($tbl)->insert(
                            $chunk->map(fn($row) => (array) $row)->toArray()
                        );
                    });
                }
            } catch (\Exception $e) {
                logger()->error("Failed to sync table `$tbl`: " . $e->getMessage());
                continue;
            }

            $count = $rows->count();
            $totalSynced += $count;

            // Build sync report
            $syncReport[] = [
                'table'         => $tbl,
                'rows_synced'   => $count,
                'table_created' => $tableCreated ? 'Yes' : 'No',
            ];
        }

        return [
            'total_rows_synced' => $totalSynced,
            'tables_created'    => $tablesCreated,
            'report'            => $syncReport,
        ];
    }

    /**
     * Get a list of table names from the given DB connection.
     *
     * @param \Illuminate\Database\Connection $connection
     * @return array
     */
    protected function getTables($connection): array
    {
        return $connection->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * Create a table in the target database using the structure from the source.
     *
     * @param \Illuminate\Database\Connection $sourceConnection
     * @param \Illuminate\Database\Connection $targetConnection
     * @param string $tableName
     * @throws \Exception
     */
    protected function createTableFromSource($sourceConnection, $targetConnection, string $tableName): void
    {
        $sqlResult = $sourceConnection->select("SHOW CREATE TABLE `$tableName`");

        if (empty($sqlResult)) {
            throw new \Exception("Cannot fetch CREATE TABLE statement for `$tableName`");
        }

        $createSQL = $sqlResult[0]->{'Create Table'};

        // Make CREATE TABLE safe
        $createSQL = preg_replace('/^CREATE TABLE /i', 'CREATE TABLE IF NOT EXISTS ', $createSQL);

        // Execute on target DB
        $targetConnection->unprepared($createSQL);
    }
}
