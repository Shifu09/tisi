<?php

namespace App\Providers;

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
        // Livewire 4 default es layouts::app; el namespace "layouts" solo se registra si existe
        // resources/views/layouts. Este starter usa components/layouts/app.blade.php.
        config(['livewire.component_layout' => 'components.layouts.app']);
    }
}
