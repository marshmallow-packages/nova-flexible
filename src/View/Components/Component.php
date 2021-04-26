<?php

namespace Marshmallow\Nova\Flexible\View\Components;

use Marshmallow\Nova\Flexible\Layouts\Layout;
use Illuminate\View\Component as IlluminateViewComponent;

class Component extends IlluminateViewComponent
{
    protected $layout;

    /**
     * Create a new component instance.
     *
     * @param Layout $layout
     */
    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return array
     */
    public function baseLayoutWith(): array
    {
        return array_merge([
            'layout' => $this->layout,
        ], $this->layout->getWith());
    }

    protected function getOrDefault(string $column, $default)
    {
        $attributes = $this->layout->getAttributes();
        if ($attributes && array_key_exists($column, $attributes)) {
            $content = $this->layout->getAttributes()[$column];
            if ($content) {
                return $content;
            }
        }

        return $default;
    }

    /**
     * This method will always be overruled by the component class
     * that extends this class.
     */
    public function render()
    {
        //
    }
}
