<?php

namespace EggDigital\Service\Facades;

use Illuminate\Support\Facades\Facade;

class PcsClient extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'service\pcsclient'; }

}