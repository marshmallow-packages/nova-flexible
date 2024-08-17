<?php

namespace Marshmallow\Nova\Flexible\Layouts\Defaults;

use Laravel\Nova\Fields\Text;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;

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

    protected $description = 'A What You See Is What You Get editor.';

    protected $image = 'https://marshmallow.dev/cdn/flex/layout/containers.png';

    protected $tags = ['Content', 'Marshmallow'];

    /**
     * Get the fields displayed by the layout.
     *
     * @return array<\Laravel\Nova\Fields\Field>
     */
    public function fields()
    {
        return [
            Text::make('Title'),
            config('pages.wysiwyg')::make('Content'),
        ];
    }

    protected function getComponentClass()
    {
        return \Marshmallow\Nova\Flexible\View\Components\Wysiwyg::class;
    }
}
