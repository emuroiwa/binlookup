<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Exports\BinDataExport;
use App\Models\BinData;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BinDataExportService
{
    public function exportFiltered(Request $request): BinaryFileResponse
    {
        $query = $this->buildExportQuery($request);
        $filename = $this->generateFilename($request);

        return Excel::download(new BinDataExport($query), $filename);
    }

    private function buildExportQuery(Request $request)
    {
        $query = BinData::query()->with(['binLookup.binImport']);

        // Apply same filters as query service
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
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('bin_number', 'like', $search.'%')
                    ->orWhere('bank_name', 'like', '%'.$search.'%')
                    ->orWhere('card_brand', 'like', '%'.$search.'%')
                    ->orWhere('country_name', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    private function generateFilename(Request $request): string
    {
        $filename = 'bin-data-export-'.now()->format('Y-m-d-H-i-s');

        if ($request->filled('bin')) {
            $filename .= '-bin-'.$request->get('bin');
        }

        if ($request->filled('country')) {
            $filename .= '-'.strtolower(str_replace(' ', '-', $request->get('country')));
        }

        if ($request->filled('brand')) {
            $filename .= '-'.strtolower($request->get('brand'));
        }

        return $filename.'.xlsx';
    }
}