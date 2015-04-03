<?php namespace Mylife\Service\Models;

use Mylife\Service\Provider\Mongodb\Model as Eloquent;

class ContentMongo extends Eloquent
{
    protected $collection = 'playlist_content';

}