<?php namespace Gffuma\DataTable;

use Illuminate\Http\Request as IlluminateRequest;

class Factory {

	/**
	 * Laravel request
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Make a new Factory instace
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	public function __construct(IlluminateRequest $request)
	{
		$this->request = $request;
	}

	/**
	 * Factory a new DataTable builder instance
	 *
	 * @return \Gffuma\DataTable\Builder
	 */
	public function builder()
	{
		$builder = new Builder($this->request);
		return $builder;
	}
}
