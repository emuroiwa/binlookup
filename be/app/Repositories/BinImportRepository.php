<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BinImport;
use App\Repositories\Contracts\BinImportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BinImportRepository implements BinImportRepositoryInterface
{
    public function create(array $data): BinImport
    {
        return BinImport::create($data);
    }

    public function find(string $id): ?BinImport
    {
        return BinImport::find($id);
    }

    public function findOrFail(string $id): BinImport
    {
        return BinImport::findOrFail($id);
    }

    public function update(BinImport $binImport, array $data): bool
    {
        return $binImport->update($data);
    }

    public function delete(BinImport $binImport): bool
    {
        return $binImport->delete() ?? false;
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BinImport::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('filename', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getProgressStats(BinImport $binImport): array
    {
        $stats = $binImport->binLookups()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'completed' => $stats->completed ?? 0,
            'failed' => $stats->failed ?? 0,
        ];
    }
}