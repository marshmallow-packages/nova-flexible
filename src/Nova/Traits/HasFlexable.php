<?php

namespace Marshmallow\Nova\Flexible\Nova\Traits;

use Marshmallow\Nova\Flexible\Flexible;
use Marshmallow\Nova\Flexible\Facades\Flex;

trait HasFlexable
{
    protected function getFlex($name = 'Layout', $tags = [])
    {
        $ignore = [];
        if ($this instanceof MarshmallowLayout) {
            $ignore[] = $this->name();
        }

        $flex = Flexible::make($name);
        foreach (Flex::getLayouts() as $layout_slug => $layout) {
            if (in_array($layout_slug, $ignore)) {
                continue;
            }

            $layout_instance = new $layout;
            if (! empty($tags) && ! $layout_instance->hasTag($tags)) {
                continue;
            }

            $flex->addLayout($layout)->collapsed();
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
