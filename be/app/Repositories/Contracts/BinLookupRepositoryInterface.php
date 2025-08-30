<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\BinImport;
use App\Models\BinLookup;
use Illuminate\Database\Eloquent\Collection;

interface BinLookupRepositoryInterface
{
    public function create(array $data): BinLookup;

    public function find(string $id): ?BinLookup;

    public function findOrFail(string $id): BinLookup;

    public function update(BinLookup $binLookup, array $data): bool;

    public function batchInsert(array $records): void;

    public function findByImportId(string $importId, int $chunkSize = 100): \Illuminate\Support\LazyCollection;

    public function getStatsByImport(BinImport $import): object;

    public function findFailedLookupsByImport(BinImport $import, int $limit = 5): Collection;

    public function paginateByImport(BinImport $import, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}