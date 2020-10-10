<?php

namespace Marshmallow\Nova\Flexible\View\Components;

use Illuminate\View\Component;
use Marshmallow\Nova\Flexible\Layouts\Layout;

class UspFontawesome extends Component
{
    protected $layout;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('marshmallow::components.usp-fontawesome')->with([
            'layout' => $this->layout,
        ]);
    }
}
