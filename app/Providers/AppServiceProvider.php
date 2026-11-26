<?php

namespace App\Providers;

use App\Support\Money;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Собственный шаблон постраничной навигации (без CSS-фреймворков).
        Paginator::defaultView('pagination.custom');
        Paginator::defaultSimpleView('pagination.custom');

        // Суммы в рублях: @money($value)
        Blade::directive('money', fn (string $expression) => "<?php echo e(\\App\\Support\\Money::format({$expression})); ?>");
    }
}
