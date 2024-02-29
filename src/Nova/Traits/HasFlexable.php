<?php

namespace Marshmallow\Nova\Flexible\Nova\Traits;

use Exception;
use Marshmallow\Nova\Flexible\Flexible;
use Marshmallow\Nova\Flexible\Facades\Flex;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;

trait HasFlexable
{
    /**
     * This is an array of tags of all the flexible items you
     * wish to INCLUDE in your flexible template selector.
     */
    protected array $includeFlexibleTags = [];

    /**
     * This is an array of tags of all the flexible items you
     * wish to EXCLUDE in your flexible template selector.
     */
    protected array $excludeFlexibleTags = [];


    /**
     * Set the tags you wish to include to
     * the flexible layout selector.
     *
     * @return self
     */
    protected function includeFlexibleTags(array $includeFlexibleTags): self
    {
        $this->includeFlexibleTags = $includeFlexibleTags;
        return $this;
    }

    /**
     * Set the tags you wish to exclude from
     * to the flexible layout selector.
     *
     * @return self
     */
    protected function excludeFlexibleTags(array $excludeFlexibleTags): self
    {
        $this->excludeFlexibleTags = $excludeFlexibleTags;
        return $this;
    }

    protected function getFlex(...$arguments)
    {

        $count = count($arguments);
        $tags = [];

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

        /**
         * Merge the tags that are provided with this method call
         * with the tags that have been provided via the
         * `includeFlexibleTags` method.
         */
        $tags = array_merge($this->includeFlexibleTags, $tags);

        $ignore = [];
        if ($this instanceof MarshmallowLayout) {
            $ignore[] = $this->name();
        }

        $flex = Flexible::make($name, $column)->hideFromIndex();
        foreach (Flex::getLayouts() as $layout_slug => $layout) {
            if (in_array($layout_slug, $ignore)) {
                continue;
            }

            $layout_instance = new $layout;
            if (!empty($tags) && !$layout_instance->hasTag($tags)) {
                continue;
            }

            if ($layout_instance->hasTag($this->excludeFlexibleTags)) {
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
