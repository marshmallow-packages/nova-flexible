<?php

namespace Marshmallow\Nova\Flexible\Casts;

use Marshmallow\Nova\Flexible\Facades\Flex;
use Marshmallow\Nova\Flexible\Value\FlexibleCast as BaseFlexibleCast;

class FlexibleCast extends BaseFlexibleCast
{
    protected $layouts = [];

    public function __construct()
    {
        $this->layouts = Flex::getLayouts();
    }
}
