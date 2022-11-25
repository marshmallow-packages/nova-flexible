<?php

namespace Marshmallow\Nova\Flexible\Layouts;

use Spatie\MediaLibrary\HasMedia;
use Marshmallow\Nova\Flexible\Layouts\Layout;
use Marshmallow\Nova\Flexible\Concerns\HasMediaLibrary;

class MarshmallowMediaLayout extends Layout implements HasMedia
{
    use HasMediaLibrary;
}
