<?php


/**
 * Collection of functions available to all layouts
 *
 * @category Layouts
 * @package  Marshmallow\Pages
 * @author   Stef van Esch <stef@marshmallow.dev>
 * @license  MIT Licence
 * @link     https://marshmallow.dev
 */

namespace Marshmallow\Nova\Flexible\Layouts;

use Livewire\Livewire;

if (config('flexible.has_media_library')) {
    $className = \Marshmallow\Nova\Flexible\Layouts\MarshmallowMediaLayout::class;
} else {
    $className = \Marshmallow\Nova\Flexible\Layouts\Layout::class;
}

class_alias($className, '\Marshmallow\Nova\Flexible\Layouts\MarshmallowDynamicLayout');

class MarshmallowLayout extends MarshmallowDynamicLayout
{
    /**
     * Render the view component for this layout.
     *
     * @return string HTML of this layout
     */
    public function render()
    {
        /**
         * If the database contains a layout json but the layout classes
         * have been removed because they where deprecated, we wil
         * return an empty string so no exception will be thrown.
         */
        if (!method_exists($this, 'getComponentClass')) {
            return '';
        }

        $component_class_name = $this->getComponentClass();
        $layout = (new $component_class_name($this));

        if (class_exists(\Livewire\Component::class)) {
            if ($layout instanceof \Livewire\Component) {
                $livewire_component = Livewire::mount($layout->getComponentName(), ['layout' => $this]);
                return is_string($livewire_component) ? $livewire_component : $livewire_component->html();
            }
        }

        return $layout->render();
    }

    /**
     * Get the public url of an image
     *
     * @param string $field Field from the current layout where an image is stored.
     *
     * @return string Public url of an image
     */
    public function getImage(string $field = 'image')
    {
        if (!$this->hasImage($field)) {
            return null;
        }

        if (config('app.asset_url')) {
            return asset($this->attributes[$field]);
        }

        return asset('storage/' . $this->attributes[$field]);
    }

    /**
     * [hasImage description]
     *
     * @param string $field [description]
     *
     * @return bool        [description]
     */
    public function hasImage(string $field = 'image')
    {
        return (array_key_exists($field, $this->attributes));
    }

    /**
     * [hasTag description]
     *
     * @param [type] $tags [description]
     *
     * @return bool [description]
     */
    public function hasTag($tags)
    {
        foreach ($tags as $tag) {
            if (in_array($tag, $this->tags)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the unique key of this Flex by $layout->key
     * so it can be used in blade files
     * @return string unique key for this flex
     */
    public function getKeyAttribute(): string
    {
        return $this->key;
    }

    /**
     * Get the target of a link by checking if the url
     * is local or not.
     * @param   string $attribute Field name in the Flex object
     * @return  string|null A blank target or null if its local
     */
    public function getLinkTarget(string $attribute): ?string
    {
        if (strpos($this->{$attribute}, 'http') === false) {
            return null;
        }

        if (substr($this->{$attribute}, 0, strlen(env('APP_URL'))) == env('APP_URL')) {
            return null;
        }

        return 'target="_blank"';
    }

    public function onLoad()
    {
        //
    }
}
