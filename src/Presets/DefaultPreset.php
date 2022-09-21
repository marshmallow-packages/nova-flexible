<?php

namespace Marshmallow\Nova\Flexible\Presets;

use Marshmallow\Nova\Flexible\Flexible;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Nova\Flexible\Facades\Flex;
use Marshmallow\Nova\Flexible\Layouts\Preset;

class DefaultPreset extends Preset
{
    /**
     * The available blocks
     *
     * @var Illuminate\Support\Collection
     */
    protected $layouts;

    protected $isIndexRequest;
    /**
     * Create a new preset instance
     *
     * @return void
     */
    public function __construct(NovaRequest $request)
    {
        $this->request = $request;
        $this->isIndexRequest = $request->isResourceIndexRequest();

        if ($this->isIndexRequest) return;

        $layouts = Flex::getLayoutsFromCache();
        $this->layouts = collect($layouts);
    }

    /**
     * Execute the preset configuration
     *
     * @return void
     */
    public function handle(Flexible $field)
    {
        if ($this->isIndexRequest) return;

        $field->collapsed();

        $this->layouts->each(function ($layout) use ($field) {
            $field->addLayout($layout);
        });

        return $field;
    }
}