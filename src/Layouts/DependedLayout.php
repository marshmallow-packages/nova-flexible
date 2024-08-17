<?php

namespace Marshmallow\Nova\Flexible\Layouts;

use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class DependedLayout extends MarshmallowLayout
{
    /**
     * The layout's unique identifier
     *
     * @var string
     */
    protected $name = 'depended-layout';

    /**
     * The displayed title
     *
     * @var string
     */
    protected $title = 'Mirror another layout';

    /**
     * Description as shown in the layout selector.
     *
     * @var string
     */
    protected $description = 'Copy any layout from another resource and mirror it here.';

    /**
     * Image use in the layout selector
     *
     * @var string
     */
    protected $image = 'https://marshmallow.dev/cdn/flex/layout/dividers.png';

    /**
     * Add this layout to these tags.
     *
     * @var array<string>
     */
    protected $tags = ["Specials"];

    /**
     * Get the fields displayed by the layout.
     *
     * @return array
     * * @return array<\Laravel\Nova\Fields\Field>
     */
    public function fields()
    {
        $resource_model = NovaRequest::createFrom(request())
            ->model();

        return [
            Select::make(__('Depended Layout'), 'layout')->options(
                $resource_model::getOptionsForDependedLayoutSelect($resource_model),
            )->displayUsingLabels()->searchable(),
        ];
    }
}
