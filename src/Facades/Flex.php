<?php

namespace Marshmallow\Nova\Flexible\Facades;

class Flex extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return \Marshmallow\Nova\Flexible\Flex::class;
    }
}
