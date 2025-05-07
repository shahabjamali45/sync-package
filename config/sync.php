<?php

return [
    'cloud_connection' => env('SYNC_CLOUD_DB_CONNECTION', 'mysql_cloud'),
    'chunk_size' => 500,
    'log_table' => 'sync_logs',
    'tables' => [/* tables to sync or leave empty for all */],
];
