<?php namespace Mylife\Service\Models;

use Mylife\Service\Provider\Mongodb\Model as Eloquent;

class DemoMongo extends Eloquent
{
    protected $collection = 'demo';
    
    protected $fillable = array('id', 'first_name', 'last_name', 'email');

}