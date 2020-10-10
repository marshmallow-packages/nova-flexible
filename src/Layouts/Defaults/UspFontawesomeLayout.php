<?php

namespace Marshmallow\Nova\Flexible\Layouts\Defaults;

use Laravel\Nova\Fields\Text;
use Marshmallow\Nova\Flexible\Flexible;
use Marshmallow\Nova\Flexible\Layouts\Defaults\Traits\HasItems;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;
use Mdixon18\Fontawesome\Fontawesome;

class UspFontawesomeLayout extends MarshmallowLayout
{
    use HasItems;

    protected $items_attribute = 'usps';

    /**
     * The layout's unique identifier
     *
     * @var string
     */
    protected $name = 'uspfontawesome';

    /**
     * The displayed title
     *
     * @var string
     */
    protected $title = 'USP (Fontawesome)';

    protected $description = 'Create USPs with a Fontawesome icon.';

    protected $image = 'https://marshmallow.dev/cdn/flex/font-awesome.jpg';

    protected $tags = ['USP', 'Marshmallow'];

    /**
     * Get the fields displayed by the layout.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Flexible::make('USPS')
                ->addLayout(
                    'USP',
                    'uspfontawesome',
                    [
                        Text::make('Title'),
                        Text::make('Title2'),
                        Fontawesome::make('Icon'),
                        config('pages.wysiwyg')::make('Content'),
                    ],
                    function ($created_layout) {
                        return $created_layout->setTitleFromContent('title2');
                    }
                )
                ->button('Add USP')
                ->fullWidth()
                ->collapsed(),
        ];
    }

    protected function getComponentClass()
    {
        return \Marshmallow\Nova\Flexible\View\Components\UspFontawesome::class;
    }
}
