<?php namespace Gffuma\DataTable;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder implements Arrayable, Jsonable {

    /**
     * Laravel request
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * DataTable query
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * DataTable columns mapping
     *
     * @var array
     */
    protected $columns = array();

    /**
     * DataTable array of callbacks using for custom filters
     *
     * @var array
     */
    protected $filters = array();

    /**
     * DataTable each callback
     *
     * @var \Closure|null
     */
    protected $eachCallback;

    /**
     * DataTable map callback (apply after eachCallback if provided)
     *
     * @var \Closure|null
     */
    protected $mapCallback;

    /**
     * Create a new Builder instance
     *
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(IlluminateRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Get DataTable build data in JSON
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return $this->build()->toJson();
    }

    /**
     * Get DataTable build data array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->build()->toArray();
    }

    /**
     * Build a new DataTable instance
     *
     * @return \Gffuma\DataTable\DataTable
     */
    public function build()
    {
        return new DataTable(
            $this->request,
            $this->query,
            $this->columns,
            $this->filters,
            $this->eachCallback,
            $this->mapCallback
        );
    }

    /**
     * Set DataTable query
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Gffuma\DataTable\Builder
     */
    public function query(QueryBuilder $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Set DataTable columns
     *
     * @param  array  $query
     * @return \Gffuma\DataTable\Builder
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set DataTable filters
     *
     * @param  array  $filters
     * @return \Gffuma\DataTable\Builder
     */
    public function filters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Set DataTable filter on field
     *
     * @param  string  $filed
     * @param  \Closure $callback
     * @return \Gffuma\DataTable\Builder
     */
    public function filter($field, Closure $callback)
    {
        $this->filters[$field] = $callback;
        return $this;
    }

    /**
     * Set DataTable each callback
     *
     * @param  \Closure  $eachCallback
     * @return \Gffuma\DataTable\Builder
     */
    public function each(Closure $eachCallback)
    {
        $this->eachCallback = $eachCallback;
        return $this;
    }

    /**
     * Set DataTable map callback
     *
     * @param  \Closure  $mapCallback
     * @return \Gffuma\DataTable\Builder
     */
    public function map(Closure $mapCallback)
    {
        $this->mapCallback = $mapCallback;
        return $this;
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (starts_with($method, 'filter')) {

            // Get snake case name of "filerField" method part
            $field = snake_case(str_replace('filter','',$method));

            // Call filter method with field
            return call_user_func_array(array($this, 'filter'), array_merge(array($field), $parameters));
        }

        $className = get_class($this);
        throw new \BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }

}
