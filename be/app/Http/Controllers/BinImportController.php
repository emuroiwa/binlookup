<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Http\Resources\BinImportResource;
use App\Models\BinImport;
use App\Repositories\Contracts\BinImportRepositoryInterface;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use App\Services\Import\BinImportProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BinImportController extends Controller
{
    public function __construct(
        private readonly BinImportProcessingService $importProcessingService,
        private readonly BinImportRepositoryInterface $binImportRepository,
        private readonly BinLookupRepositoryInterface $binLookupRepository
    ) {}

    /**
     * Display a listing of bin imports with pagination and filtering
     */
    public function index(Request $request): Response|AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'search']);
        $perPage = (int) $request->get('per_page', 15);
        
        $imports = $this->binImportRepository->paginate($filters, $perPage);

        // Return Inertia response for web requests, JSON for API
        if ($request->wantsJson() || $request->is('api/*')) {
            return BinImportResource::collection($imports);
        }

        return Inertia::render('BinImports/Index', [
            'imports' => BinImportResource::collection($imports),
            'filters' => $filters + ['per_page' => $perPage]
        ]);
    }

    /**
     * Store a newly created bin import from CSV upload
     */
    public function store(ImportCsvRequest $request)
    {
        try {
            $import = $this->importProcessingService->processImport($request->file('file'));

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'CSV import started successfully',
                    'data' => new BinImportResource($import),
                ], 201);
            }

            return redirect()->route('bin-imports.index')
                ->with('success', 'CSV import started successfully. Processing ' . $import->total_bins . ' BIN numbers.');

        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Invalid CSV format',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('bin-imports.index')
                ->with('error', 'Invalid CSV format: ' . $e->getMessage());

        } catch (\Exception $e) {
            Log::error('BIN import failed', [
                'error' => $e->getMessage(),
                'file' => $request->file('file')?->getClientOriginalName(),
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Import failed',
                    'error' => 'An error occurred while processing the CSV file',
                ], 500);
            }

            return redirect()->route('bin-imports.index')
                ->with('error', 'Import failed. Please check your CSV file format.');
        }
    }

    /**
     * Display the specified bin import with detailed statistics
     */
    public function show(BinImport $binImport, Request $request)
    {
        // Get paginated lookup results
        $binLookups = $this->binLookupRepository->paginateByImport($binImport);

        if ($request->wantsJson() || $request->is('api/*')) {
            $stats = $this->binLookupRepository->getStatsByImport($binImport);
            $recentErrors = $this->binLookupRepository->findFailedLookupsByImport($binImport);

            $statistics = [
                'total_lookups' => $stats->total,
                'status_breakdown' => [
                    'completed' => $stats->completed,
                    'failed' => $stats->failed,
                    'pending' => $stats->total - $stats->completed - $stats->failed,
                ],
                'recent_errors' => $recentErrors,
            ];

            return response()->json([
                'data' => new BinImportResource($binImport),
                'statistics' => $statistics,
            ]);
        }

        return Inertia::render('BinImports/Show', [
            'binImport' => new BinImportResource($binImport),
            'binLookups' => $binLookups
        ]);
    }

    /**
     * Remove the specified bin import and all related records
     */
    public function destroy(BinImport $binImport, Request $request)
    {
        try {
            DB::transaction(function () use ($binImport) {
                // Delete related bin data and lookups (cascade will handle this)
                $this->binImportRepository->delete($binImport);
            });

            Log::info('BIN import deleted', [
                'import_id' => $binImport->id,
                'filename' => $binImport->filename,
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Import deleted successfully',
                ]);
            }

            return redirect()->route('bin-imports.index')
                ->with('success', 'Import deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete BIN import', [
                'import_id' => $binImport->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Failed to delete import',
                    'error' => 'An error occurred while deleting the import',
                ], 500);
            }

            return redirect()->route('bin-imports.index')
                ->with('error', 'Failed to delete import');
        }
    }
}
