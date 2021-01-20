<?php

namespace Marshmallow\Nova\Flexible\Nova\Traits;

use Exception;
use Marshmallow\Nova\Flexible\Flexible;
use Marshmallow\Nova\Flexible\Facades\Flex;

trait HasFlexable
{
    protected function getFlex(...$arguments)
    {
        $count = count($arguments);
        if (!$count) {
            throw new Exception("Please provide a name");
        }

        if ($count == 2) {
            $name = $arguments[0];
            /**
             * If the second argument is not an array, it is the
             * column name. If it is an array, it is tags.
             */
            $column = (!is_array($arguments[1])) ? $arguments[1] : $arguments[0];
            $tags = (is_array($arguments[1])) ? $arguments[1] : [];
        }

        if ($count == 3) {
            $name = $arguments[0];
            $column = $arguments[1];
            $tags = $arguments[2];
        }

        $ignore = [];
        if ($this instanceof MarshmallowLayout) {
            $ignore[] = $this->name();
        }

        $flex = Flexible::make($name, $column);
        foreach (Flex::getLayouts() as $layout_slug => $layout) {
            if (in_array($layout_slug, $ignore)) {
                continue;
            }

            $layout_instance = new $layout;
            if (! empty($tags) && ! $layout_instance->hasTag($tags)) {
                continue;
            }

            $flex->addLayout($layout)
                 ->collapsed();
        }

        return $flex;
    }

    protected function getFlexByTag($tag, $name = 'Layout')
    {
        return $this->getFlex($name, [$tag]);
    }

    protected function getFlexByTags($tags, $name = 'Layout')
    {
        return $this->getFlex($name, $tags);
    }
}
