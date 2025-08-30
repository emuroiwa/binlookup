<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Models\BinData;
use App\Http\Resources\BinDataResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BinDataQueryService
{
    public function getFilteredAndPaginatedData(Request $request): LengthAwarePaginator
    {
        $query = BinData::query()->with(['binLookup.binImport']);

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        return $query->paginate($request->get('per_page', 15));
    }

    public function getFilterOptions(): array
    {
        return [
            'brands' => BinData::distinct('card_brand')
                ->whereNotNull('card_brand')
                ->orderBy('card_brand')
                ->pluck('card_brand'),
            'types' => BinData::distinct('card_type')
                ->whereNotNull('card_type')
                ->orderBy('card_type')
                ->pluck('card_type'),
            'countries' => BinData::distinct('country_name')
                ->whereNotNull('country_name')
                ->orderBy('country_name')
                ->pluck('country_name'),
        ];
    }

    public function getBasicStats(): array
    {
        return [
            'unique_banks' => BinData::distinct('bank_name')->whereNotNull('bank_name')->count(),
            'unique_countries' => BinData::distinct('country_name')->whereNotNull('country_name')->count(),
            'total_bins' => BinData::count(),
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('bin')) {
            $query->where('bin_number', 'like', $request->get('bin').'%');
        }

        if ($request->filled('bank')) {
            $query->where('bank_name', 'like', '%'.$request->get('bank').'%');
        }

        if ($request->filled('brand')) {
            $query->where('card_brand', $request->get('brand'));
        }

        if ($request->filled('type')) {
            $query->where('card_type', $request->get('type'));
        }

        if ($request->filled('country')) {
            $query->where(function ($q) use ($request) {
                $q->where('country_code', $request->get('country'))
                    ->orWhere('country_name', 'like', '%'.$request->get('country').'%');
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to').' 23:59:59');
        }

        if ($request->filled('search')) {
            $this->applyGlobalSearch($query, $request->get('search'));
        }

        if ($request->filled('import_id')) {
            $query->whereHas('binLookup', function ($q) use ($request) {
                $q->where('bin_import_id', $request->get('import_id'));
            });
        }
    }

    private function applyGlobalSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('bin_number', 'like', $search.'%')
                ->orWhere('bank_name', 'like', '%'.$search.'%')
                ->orWhere('card_brand', 'like', '%'.$search.'%')
                ->orWhere('country_name', 'like', '%'.$search.'%');
        });
    }

    private function applySorting($query, Request $request): void
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortFields = [
            'bin_number', 
            'bank_name', 
            'card_type', 
            'card_brand', 
            'country_name', 
            'created_at'
        ];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }
    }
}