<?php

namespace Marshmallow\Nova\Flexible\Value;

use Marshmallow\Nova\Flexible\Concerns\HasFlexible;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class FlexibleCast implements CastsAttributes
{
    use HasFlexible;

    /**
     * @var array
     */
    protected $layouts = [
        'wysiwyg' => \Marshmallow\Nova\Flexible\Layouts\Defaults\WysiwygLayout::class,
    ];

    /**
     * @var Illuminate\Database\Eloquent\Model
     */
    protected $model;

    public function get($model, string $key, $value, array $attributes)
    {
        $this->model = $model;

        return $this->cast($value, $this->getLayoutMapping());
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    protected function getLayoutMapping()
    {
        return $this->layouts;
    }
}
