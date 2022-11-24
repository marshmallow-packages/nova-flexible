<?php

namespace Marshmallow\Nova\Flexible\Layouts;

use Spatie\MediaLibrary\HasMedia;
use Marshmallow\Nova\Flexible\Concerns\HasMediaLibrary;

/**
 * Collection of functions available to all layouts
 *
 * @category Layouts
 * @package  Marshmallow\Pages
 * @author   Stef van Esch <stef@marshmallow.dev>
 * @license  MIT Licence
 * @link     https://marshmallow.dev
 */
class MarshmallowMediaLayout extends MarshmallowLayout implements HasMedia
{
    use HasMediaLibrary;
}
