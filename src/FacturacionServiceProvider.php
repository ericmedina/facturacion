<?php

namespace Pampadev\Facturacion;

use Illuminate\Support\ServiceProvider;

class FacturacionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        include __DIR__.'/routes.php';
        $this->publishes([
            __DIR__.'/config/facturacion.php'   => config_path('facturacion.php'),
            __DIR__.'/facturacion/certs'        => storage_path('facturacion/certs'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Pampadev\Facturacion\Controllers\FacturacionController');
        $this->app->make('Pampadev\Facturacion\Facturacion');
    }
}
