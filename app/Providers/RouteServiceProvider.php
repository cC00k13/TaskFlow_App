<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{

/**
     * La ruta a la que redirigir después del login
     * @var string
     */
    public const HOME = '/dashboard'; // 
    
    // Definir constantes adicionales
    public const DASH = '/dashboard';

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
