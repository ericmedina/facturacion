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
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->publishes([
            __DIR__.'/config/facturacion.php'   =>  config_path('facturacion.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/database/migrations'      =>  database_path('migrations')
        ], 'migrations');
        
        $this->publishes([
            __DIR__.'/facturacion'              =>  public_path('facturacion'),
            __DIR__.'/css'                      =>  public_path('css')
        ],'assets');
        
        $this->publishes([
            __DIR__.'/pdf'                      =>  base_path('resources/views/pdf')
        ], 'pdf');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->make('Pampadev\Facturacion\Facturacion');
    }
}
