<?php namespace Gffuma\DataTable;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class DataTable implements Arrayable, Jsonable {

    /**
     * Laravel request
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Base query using by datatable
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * Columns mapping array
     *
     * @var array
     */
    protected $columns;

    /**
     * Array of callbacks using for custom filters
     *
     * @var array
     */
    protected $filters;

    /**
     * Each callback
     *
     * @var \Closure|null
     */
    protected $eachCallback;

    /**
     * Map callback (apply after eachCallback if provided)
     *
     * @var \Closure|null
     */
    protected $mapCallback;

    /**
     * Create a new DataTable instace
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @param  array  $filters
     * @param  \Closure|null  $eachCallback
     * @param  \Closure|null  $mapCallback
     * @return void
     */
    public function __construct(
        IlluminateRequest $request,
        QueryBuilder $query,
        array $columns,
        array $filters,
        Closure $eachCallback = null,
        Closure $mapCallback = null
    )
    {
        $this->request = $request;
        $this->query = $query;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->eachCallback = $eachCallback;
        $this->mapCallback = $mapCallback;
    }

    /**
     * Get DataTable data in JSON
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get DataTable data array
     *
     * @return array
     */
    public function toArray()
    {
        // Clone the query to mantain original object
        $query = clone $this->query;

        // Count total records
        $totalRecords = $query->count();

        // Adding filter where condition
        $this->where($query);

        // Count filtered records
        $filteredRecords = $query->count();

        // Adding limit and order
        $this->orderBy($query);
        $this->limit($query);

        // Get data rows
        $dataRows = $query->get();

        // Each Closure
        if ( ! is_null($this->eachCallback)) {
            array_map($this->eachCallback, $dataRows);
        }

        // Map Closure
        if ( ! is_null($this->mapCallback)) {
            $dataRows = array_map($this->mapCallback, $dataRows, array_keys($dataRows));
        }

        // Draw params
        $draw = $this->request->has('draw') ? intval($this->request->get('draw')) : 0;

        // jQuery DataTable formatted response
        return array(
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $dataRows
        );
    }

    /**
     * Get DataTable SQL (util for bebugging)
     *
     * @return string
     */
    public function toSql()
    {
        // Clone the query to mantain original object
        $query = clone $this->query;

        // Adding all condition
        $this->where($query);
        $this->orderBy($query);
        $this->limit($query);

        // Give the plain query
        return $query->toSql();
    }

    /**
     * Set SQL where condition on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function where(QueryBuilder $query)
    {
        $this->whereGenericSearch($query);
        $this->whereColumnSearch($query);
    }

    /**
     * Set SQL generic where search condition on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function whereGenericSearch(QueryBuilder $query)
    {
        if ($this->getSearch() != '' && $this->request->has('columns')) {

            $columns = $this->request->get('columns');
            $search = $this->getSearch();

            $query->where(function ($query) use ($search, $columns)
            {
                foreach ($columns as $column) {

                    $data = $column['data'];

                    if ($column['searchable'] === 'true') {
                        $this->genericSearchConditionAt($query, $data, $search);
                    }
                }
            });
        }
    }

    /**
     * Set SQL column where search condition on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function whereColumnSearch(QueryBuilder $query)
    {
        if ($this->request->has('columns')) {

            $columns = $this->request->get('columns');

            $query->where(function ($query) use ($columns)
            {
                foreach ($columns as $column) {

                    $value = $column['search']['value'];
                    $data = $column['data'];

                    if ($column['searchable'] === 'true' && ! empty($value)) {
                        $this->columnSearchConditionAt($query, $data, $value);
                    }
                }
            });
        }
    }

    /**
     * Set generic search conditon on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string $columnName
     * @param  string $search
     * @return void
     */
    protected function genericSearchConditionAt(QueryBuilder $query, $columnName, $search)
    {
        // Mapped column name
        $mappedColumnName = $this->columnName($columnName);

        if (isset($this->filters[$columnName]) && is_callable($this->filters[$columnName])) {

            // Callback filter and params
            $filter = $this->filters[$columnName];
            $params = array($query, $mappedColumnName, $search, true);

            // Call filter in OR block
            $query->orWhere(function ($query) use ($filter, $params)
            {
                call_user_func_array($filter, $params);
            });

        } else {
            // Use standar condition SQL LIKE
            $query->orWhere($mappedColumnName, "LIKE", "%{$search}%");
        }
    }

    /**
     * Set column search condtion on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string $columnName
     * @param  string $search
     * @return void
     *
     */
    protected function columnSearchConditionAt(QueryBuilder $query, $columnName, $search){

        // Mapped column name
        $mappedColumnName = $this->columnName($columnName);

        if (isset($this->filters[$columnName]) && is_callable($this->filters[$columnName])) {

            // Callback filter and params
            $filter = $this->filters[$columnName];
            $params = array($query, $mappedColumnName, $search, false);

            // Call filter in AND block
            $query->where(function ($query) use ($filter, $params)
            {
                call_user_func_array($filter, $params);
            });

        } else {
            // Use standar condition SQL LIKE
            $query->where($mappedColumnName, "LIKE", "%{$search}%");
        }
    }

    /**
     * Set SQL orderBy condition on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function orderBy(QueryBuilder $query)
    {
        if ($this->request->has('order') && $this->request->has('columns')) {

            $orders = $this->request->get('order');
            $columns = $this->request->get('columns');

            foreach ($orders as $order) {

                $columnIndex = $order['column'];
                $direction = $order['dir'];

                if (isset($columns[$columnIndex]) && $columns[$columnIndex]['orderable'] === 'true' ) {
                    $columnName = $columns[$columnIndex]['data'];
                    $mappedColumnName = $this->columnName($columnName);
                    $query->orderBy($mappedColumnName, $direction);
                }
            }
        }
    }

    /**
     * Set SQL limit condition on query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function limit(QueryBuilder $query)
    {
        if ($this->request->has('start')) {
            $query->skip($this->request->get('start'));
        }
        if ($this->request->has('length') && $this->request->get('length') != '-1') {
            $query->take($this->request->get('length'));
        }
    }

    /**
     * Get mapped column name
     *
     * @param  string  $columnName
     * @return string
     */
    protected function columnName($columnName)
    {
        if (isset($this->columns[$columnName])) {
            return $this->columns[$columnName];
        }

        return $columnName;
    }

    /**
     * Get generic search string
     *
     * @return string
     */
    protected function getSearch()
    {
        if ($this->request->has('search')) {
            $search = $this->request->get('search');
            return $search['value'];
        }

        // No search input
        return '';
    }

}
