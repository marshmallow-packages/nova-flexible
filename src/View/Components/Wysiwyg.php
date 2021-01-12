<?php

namespace Marshmallow\Nova\Flexible\View\Components;

class Wysiwyg extends Component
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('marshmallow::components.wysiwyg')->with(array_merge(
            $this->baseLayoutWith(),
            [
                /**
                 * Custom with data can be added here
                 */
            ]
        ));
    }
}
