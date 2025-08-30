<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\BinDataRepository;
use App\Repositories\BinImportRepository;
use App\Repositories\BinLookupRepository;
use App\Repositories\Contracts\BinDataRepositoryInterface;
use App\Repositories\Contracts\BinImportRepositoryInterface;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BinImportRepositoryInterface::class, BinImportRepository::class);
        $this->app->bind(BinLookupRepositoryInterface::class, BinLookupRepository::class);
        $this->app->bind(BinDataRepositoryInterface::class, BinDataRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
