<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Schema::hasTable()/hasColumn() issue real introspection queries against the
 * database. Both are called from code that runs on every request (AppServiceProvider
 * boot + SyncStoreCurrency middleware), which otherwise means two extra DB round
 * trips per request forever, even though the answer only ever changes once
 * (when a migration runs). Cached for an hour so a still-missing table keeps
 * checking until migrations catch up, instead of being cached wrong forever.
 */
class SchemaCache
{
    /** @var array<string, bool> */
    private static array $tables = [];

    /** @var array<string, bool> */
    private static array $columns = [];

    /**
     * Both AppServiceProvider::boot() and SyncStoreCurrency middleware check the
     * same table/column on every request; the static arrays keep the second
     * check a plain array lookup instead of a second Redis round trip.
     */
    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= Cache::remember("schema:table:{$table}", 3600, fn () => Schema::hasTable($table));
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}:{$column}";

        return self::$columns[$key] ??= Cache::remember("schema:column:{$key}", 3600, fn () => Schema::hasColumn($table, $column));
    }
}
