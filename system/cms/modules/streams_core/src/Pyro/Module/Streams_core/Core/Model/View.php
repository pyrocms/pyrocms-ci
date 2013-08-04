<?php namespace Pyro\Module\Streams_core\Model;

use Pyro\Model\Eloquent;
use Pyro\Module\Streams_core\Core\Collection\ViewCollection;

class Field extends Eloquent
{
    /**
     * Define the table name
     *
     * @var string
     */
	protected $table = 'data_views';

    /**
     * The attributes that aren't mass assignable
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Disable updated_at and created_at on table
     *
     * @var boolean
     */
    public $timestamps = false;

    public function newCollection(array $models = array())
    {
        return new ViewCollection($models);
    }
}