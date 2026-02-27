<?php

namespace App\Compat\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CiDatabaseConnection
{
    public function table(string $table): CiQueryBuilder
    {
        return new CiQueryBuilder($table);
    }

    public function query(string $sql, array $bindings = []): CiResult
    {
        $isSelect = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $sql) === 1;

        if ($isSelect) {
            $rows = DB::select($sql, $bindings);
            $rows = array_map(static fn ($row) => (array) $row, $rows);
            return new CiResult($rows);
        }

        DB::statement($sql, $bindings);
        return new CiResult([]);
    }

    public function transStart(): void
    {
        DB::beginTransaction();
    }

    public function transComplete(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::commit();
        }
    }

    public function transStatus(): bool
    {
        return true;
    }

    public function transBegin(): void
    {
        DB::beginTransaction();
    }

    public function transCommit(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::commit();
        }
    }

    public function transRollback(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    public function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    public function fieldExists(string $field, string $table): bool
    {
        return Schema::hasColumn($table, $field);
    }

    public function getFieldNames(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    public function insertID(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }

    public function error(): array
    {
        return [];
    }
}
