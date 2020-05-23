<?php

/**
 * Layout
 *
 * PHP version 7.4
 *
 * @category Layouts
 * @package  Marshmallow\Pages
 * @author   Stef van Esch <stef@marshmallow.dev>
 * @license  MIT Licence
 * @link     https://marshmallow.dev
 */
namespace Marshmallow\Nova\Flexible\Layouts;

use Marshmallow\Nova\Flexible\Layouts\Layout;


/**
 * Collection of functions available to all layouts
 *
 * @category Layouts
 * @package  Marshmallow\Pages
 * @author   Stef van Esch <stef@marshmallow.dev>
 * @license  MIT Licence
 * @link     https://marshmallow.dev
 */
class MarshmallowLayout extends Layout
{
    /**
     * Render the view component for this layout.
     *
     * @return string HTML of this layout
     */
    public function render()
    {
        $component_class_name = $this->getComponentClass();
        return (new $component_class_name($this))->render();
    }


    /**
     * Get the public url of an image
     *
     * @param string $field Field from the current layout where an image is stored.
     *
     * @return string Public url of an image
     */
    public function getImage(string $field)
    {
        return asset('storage/' . $this->{$field});
    }
}
