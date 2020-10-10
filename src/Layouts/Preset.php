<?php

namespace Marshmallow\Nova\Flexible\Layouts;

use Marshmallow\Nova\Flexible\Flexible;

abstract class Preset
{
    /**
     * Execute the preset configuration
     *
     * @return void
     */
    abstract public function handle(Flexible $field);
}
