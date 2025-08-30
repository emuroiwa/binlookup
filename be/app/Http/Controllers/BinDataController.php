<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BinDataResource;
use App\Services\Data\BinDataQueryService;
use App\Services\Data\BinDataExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;

class BinDataController extends Controller
{
    public function __construct(
        private readonly BinDataQueryService $queryService,
        private readonly BinDataExportService $exportService
    ) {}

    /**
     * Display a paginated, filterable listing of BIN data
     */
    public function index(Request $request): Response|AnonymousResourceCollection
    {
        $binData = $this->queryService->getFilteredAndPaginatedData($request);

        if ($request->wantsJson() || $request->is('api/*')) {
            return BinDataResource::collection($binData);
        }

        $filterOptions = $this->queryService->getFilterOptions();
        $stats = $this->queryService->getBasicStats();
        $filters = $request->only([
            'bin', 'bank', 'brand', 'type', 'country', 'date_from', 
            'date_to', 'sort', 'direction', 'per_page', 'search', 'import_id'
        ]);

        return Inertia::render('BinData/Index', [
            'binData' => BinDataResource::collection($binData),
            'filterOptions' => $filterOptions,
            'stats' => $stats,
            'filters' => $filters
        ]);
    }

    /**
     * Export filtered BIN data to Excel format
     */
    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportService->exportFiltered($request);
    }

    /**
     * Get available filter options for the frontend
     */
    public function filterOptions(): array
    {
        return $this->queryService->getFilterOptions();
    }
}
