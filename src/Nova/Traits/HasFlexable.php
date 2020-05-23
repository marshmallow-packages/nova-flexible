<?php

namespace Marshmallow\Nova\Flexible\Nova\Traits;

use Marshmallow\Pages\Facades\Page;
use Marshmallow\Nova\Flexible\Flexible;
use Marshmallow\Nova\Flexible\Facades\Flex;

trait HasFlexable
{
    protected function getFlex()
    {
        $flex = Flexible::make('Layout');
        foreach (Flex::getLayouts() as $layout) {
            $flex->addLayout($layout)->collapsed();
        }
        return $flex;
    }
}
