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

        /**
         * Frontend-only rupee formatter — rounds to whole rupees with Indian
         * thousands grouping (₹1,44,105). Database values keep full decimals;
         * only the displayed string is rounded.
         *
         * Usage in Blade:
         *   @rupees($payslip->net_pay)            → ₹1,44,105
         *   @rupees($payslip->net_pay, false)     → 1,44,105   (no symbol)
         */
        \Illuminate\Support\Facades\Blade::directive('rupees', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatRupees({$expression}); ?>";
        });
    }

    /**
     * Format a number as Indian-grouped whole-rupee string.
     *   12345.67 → '₹12,346'
     *   144105   → '₹1,44,105'
     */
    public static function formatRupees($value, bool $withSymbol = true): string
    {
        $rounded = (int) round((float) $value);
        // Indian grouping: last 3 digits, then groups of 2
        $sign = $rounded < 0 ? '-' : '';
        $abs  = abs($rounded);
        if ($abs < 1000) {
            $formatted = (string) $abs;
        } else {
            $last3 = substr((string) $abs, -3);
            $rest  = substr((string) $abs, 0, -3);
            // Group remaining digits in 2s from the right
            $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formatted = $rest . ',' . $last3;
        }
        return ($withSymbol ? '₹' : '') . $sign . $formatted;
    }
}
