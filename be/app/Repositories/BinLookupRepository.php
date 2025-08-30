<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BinImport;
use App\Models\BinLookup;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BinLookupRepository implements BinLookupRepositoryInterface
{
    public function create(array $data): BinLookup
    {
        return BinLookup::create($data);
    }

    public function find(string $id): ?BinLookup
    {
        return BinLookup::find($id);
    }

    public function findOrFail(string $id): BinLookup
    {
        return BinLookup::findOrFail($id);
    }

    public function update(BinLookup $binLookup, array $data): bool
    {
        return $binLookup->update($data);
    }

    public function batchInsert(array $records): void
    {
        foreach (array_chunk($records, 1000) as $chunk) {
            // Generate UUIDs for each record since raw insert doesn't trigger UUID generation
            $chunkWithUuids = array_map(function ($record) {
                $record['id'] = \Illuminate\Support\Str::uuid()->toString();
                return $record;
            }, $chunk);
            
            BinLookup::insert($chunkWithUuids);
        }
    }

    public function findByImportId(string $importId, int $chunkSize = 100): \Illuminate\Support\LazyCollection
    {
        return BinLookup::where('bin_import_id', $importId)
            ->lazy($chunkSize);
    }

    public function getStatsByImport(BinImport $import): object
    {
        return $import->binLookups()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->first();
    }

    public function findFailedLookupsByImport(BinImport $import, int $limit = 5): Collection
    {
        return $import->binLookups()
            ->where('status', 'failed')
            ->whereNotNull('error_message')
            ->latest('updated_at')
            ->limit($limit)
            ->get(['bin_number', 'error_message', 'attempts', 'updated_at']);
    }

    public function paginateByImport(BinImport $import, int $perPage = 20): LengthAwarePaginator
    {
        return $import->binLookups()
            ->with('binData')
            ->latest()
            ->paginate($perPage);
    }
}