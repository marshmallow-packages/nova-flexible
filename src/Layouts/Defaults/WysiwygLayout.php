<?php

namespace Marshmallow\Nova\Flexible\Layouts\Defaults;

use Laravel\Nova\Fields\Text;
use Marshmallow\Nova\Flexible\Layouts\Layout;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;
// use Laravel\Nova\Fields\Markdown;
// use Marshmallow\Pages\Flexible\Layouts\Layout;
// use Marshmallow\Pages\Flexible\Layouts\Traits\EmptyLayout;

class WysiwygLayout extends MarshmallowLayout
{
	/**
     * The layout's unique identifier
     *
     * @var string
     */
    protected $name = 'wysiwyg';

    /**
     * The displayed title
     *
     * @var string
     */
    protected $title = 'Title + Text';

    protected $description = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.';

    protected $image = 'https://marshmallow.test/cdn/flex/sections-content-sections.svg';

    protected $tags = ['Content', 'Marshmallow'];

    /**
     * Get the fields displayed by the layout.
     *
     * @return array
     */
    public function fields()
    {
        return [
        	Text::make('Title'),
            config('pages.wysiwyg')::make('Content'),
        ];
    }

    protected function getComponentClass ()
    {
        return \Marshmallow\Nova\Flexible\View\Components\Wysiwyg::class;
    }
}
