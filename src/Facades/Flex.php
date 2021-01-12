<?php

namespace Marshmallow\Nova\Flexible\Facades;

use Illuminate\Support\Facades\Facade;

class Flex extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Marshmallow\Nova\Flexible\Flex::class;
    }
}
