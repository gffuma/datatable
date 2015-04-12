<?php namespace Gffuma\DataTable;

use Illuminate\Support\Facades\Facade;

class DataTableFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'datatable';
    }

}
