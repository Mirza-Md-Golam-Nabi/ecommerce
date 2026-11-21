<?php

namespace App\Support;

use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;

/**
 * Whether the database supports the ngram FULLTEXT parser used for product search.
 * Only real MySQL has it; MariaDB (reports driver `mysql` too) and SQLite fall back to LIKE.
 */
final class FullTextSearch
{
    public static function supportsNgram(): bool
    {
        $connection = DB::connection();

        return $connection instanceof MySqlConnection && ! $connection->isMaria();
    }
}
