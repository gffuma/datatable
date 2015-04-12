<?php namespace Gffuma\DataTable;

use Illuminate\Support\ServiceProvider;

class DataTableServiceProvider extends ServiceProvider {

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('datatable', function()
        {
            return $this->app->make('Gffuma\DataTable\Factory');
        });
    }

}
