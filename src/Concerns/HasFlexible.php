<?php

namespace Marshmallow\Nova\Flexible\Concerns;

use Laravel\Nova\NovaServiceProvider;
use Marshmallow\Nova\Flexible\Facades\Flex;
use Marshmallow\Nova\Flexible\Layouts\Layout;
use Marshmallow\Nova\Flexible\Layouts\Collection;
use Marshmallow\Nova\Flexible\Value\FlexibleCast;
use Illuminate\Support\Collection as BaseCollection;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;

trait HasFlexible
{
    /**
     * Parse a Flexible Content attribute
     *
     * @param string $attribute
     * @param array  $layoutMapping
     * @return \Marshmallow\Nova\Flexible\Layouts\Collection
     */
    public function flexible($attribute, $layoutMapping = [])
    {
        $flexible = data_get($this->attributes, $attribute);

        return $this->cast($flexible, $layoutMapping);
    }

    /**
     * Cast a Flexible Content value
     *
     * @param array $value
     * @param array $layoutMapping
     * @return \Marshmallow\Nova\Flexible\Layouts\Collection
     */
    public function cast($value, $layoutMapping = [])
    {
        if (app()->getProvider(NovaServiceProvider::class)) {
            return $value;
        }

        return $this->toFlexible($value ?: null, $layoutMapping);
    }

    public function flex($column, $with = [])
    {
        return $this->toFlexible(
            $this->{$column},
            Flex::getLayouts(),
            $with
        );
    }

    /**
     * Parse a Flexible Content from value
     *
     * @param mixed $value
     * @param array $layoutMapping
     * @return \Marshmallow\Nova\Flexible\Layouts\Collection
     */
    public function toFlexible($value, $layoutMapping = [], $with = [])
    {
        $flexible = $this->getFlexibleArrayFromValue($value);

        if (is_null($flexible)) {
            return new Collection();
        }

        if (! is_array($flexible)) {
            return new Collection();
        }

        return new Collection(
            array_filter($this->getMappedFlexibleLayouts($flexible, $layoutMapping, $with))
        );
    }

    /**
     * Transform incoming value into an array of usable layouts
     *
     * @param mixed $value
     * @return array|null
     */
    protected function getFlexibleArrayFromValue($value)
    {
        if (is_string($value)) {
            $value = json_decode($value);

            return is_array($value) ? $value : null;
        }

        if (is_a($value, BaseCollection::class)) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Map array with Flexible Content Layouts
     *
     * @param array $flexible
     * @param array $layoutMapping
     * @return array
     */
    protected function getMappedFlexibleLayouts(array $flexible, array $layoutMapping, array $with = [])
    {
        return array_map(function ($item) use ($layoutMapping, $with) {
            return $this->getMappedLayout($item, $layoutMapping, $with);
        }, $flexible);
    }

    /**
     * Transform given layout value into a usable Layout instance
     *
     * @param mixed $item
     * @param array $layoutMapping
     * @return null|\Marshmallow\Nova\Flexible\Layouts\LayoutInterface
     */
    protected function getMappedLayout($item, array $layoutMapping, array $with = [])
    {
        $name = null;
        $key = null;
        $attributes = [];

        if (is_string($item)) {
            $item = json_decode($item);
        }

        if (is_array($item)) {
            $name = $item['layout'] ?? null;
            $key = $item['key'] ?? null;
            $attributes = (array) $item['attributes'] ?? [];
        } elseif (is_a($item, \stdClass::class)) {
            $name = $item->layout ?? null;
            $key = $item->key ?? null;
            $attributes = (array) $item->attributes ?? [];
        } elseif (is_a($item, Layout::class)) {
            $name = $item->name();
            $key = $item->key();
            $attributes = $item->getAttributes();
        }

        return $this->createMappedLayout($name, $key, $attributes, $layoutMapping, $with);
    }

    /**
     * Transform given layout value into a usable Layout instance
     *
     * @param string $name
     * @param string $key
     * @param array  $attributes
     * @param array  $layoutMapping
     * @return \Marshmallow\Nova\Flexible\Layouts\LayoutInterface
     */
    protected function createMappedLayout($name, $key, $attributes, array $layoutMapping, array $with = [])
    {
        $classname = array_key_exists($name, $layoutMapping)
            ? $layoutMapping[$name]
            : MarshmallowLayout::class;

        $layout = new $classname($name, $name, [], $key, $attributes);

        $model = is_a($this, FlexibleCast::class)
            ? $this->model
            : $this;

        $layout->onLoad();
        $layout->setModel($model);
        $layout->setWith($with);

        return $layout;
    }
}
