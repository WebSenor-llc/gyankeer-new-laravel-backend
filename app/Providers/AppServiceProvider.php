<?php

namespace App\Providers;

use App\Services\PayrollEngine;
use App\Services\PFCalculator;
use App\Services\ESICalculator;
use App\Services\PTCalculator;
use App\Services\LWFCalculator;
use App\Services\TaxCalculator;
use App\Services\GratuityCalculator;
use App\Services\BonusCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Statutory calculators are stateless singletons
        $this->app->singleton(PFCalculator::class);
        $this->app->singleton(ESICalculator::class);
        $this->app->singleton(PTCalculator::class);
        $this->app->singleton(LWFCalculator::class);
        $this->app->singleton(TaxCalculator::class);
        $this->app->singleton(GratuityCalculator::class);
        $this->app->singleton(BonusCalculator::class);

        // Engine wires them all together
        $this->app->singleton(PayrollEngine::class, function($app) {
            return new PayrollEngine(
                $app->make(PFCalculator::class),
                $app->make(ESICalculator::class),
                $app->make(PTCalculator::class),
                $app->make(LWFCalculator::class),
                $app->make(TaxCalculator::class),
                $app->make(GratuityCalculator::class),
                $app->make(BonusCalculator::class),
            );
        });
    }

    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useTailwind();
        date_default_timezone_set(config('app.timezone', 'Asia/Kolkata'));
    }
}
