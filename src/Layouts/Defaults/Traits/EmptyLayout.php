<?php

namespace Marshmallow\Nova\Flexible\Layouts\Defaults\Traits;

use Laravel\Nova\Fields\Heading;

trait EmptyLayout
{
    public function fields()
    {
        return [
            Heading::make('This layout has no settings'),
        ];
    }
}
