<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\BinImport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BinImportRepositoryInterface
{
    public function create(array $data): BinImport;

    public function find(string $id): ?BinImport;

    public function findOrFail(string $id): BinImport;

    public function update(BinImport $binImport, array $data): bool;

    public function delete(BinImport $binImport): bool;

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getProgressStats(BinImport $binImport): array;
}