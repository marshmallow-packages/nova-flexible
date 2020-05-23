<?php

namespace Marshmallow\Nova\Flexible;

use Illuminate\Support\Facades\Route;
use Marshmallow\MultiLanguage\Models\Language;
use Marshmallow\Nova\Flexible\Layouts\Defaults\WysiwygLayout;
use Marshmallow\Nova\Flexible\Layouts\Defaults\UspFontawesomeLayout;

class Flex
{
    protected $default_layouts = [
        'wysiwyg' => WysiwygLayout::class,
        'uspfontawesome' => UspFontawesomeLayout::class,
        'uspfontawesome2' => UspFontawesomeLayout::class,
        'uspfontawesome3' => UspFontawesomeLayout::class,
        'uspfontawesome4' => UspFontawesomeLayout::class,
    ];

    public function getLayouts()
    {
        if (!empty(config('flexible.layouts'))) {
            if (config('flexible.merge_layouts') === true) {
                return array_merge(
                    config('flexible.layouts'),
                    $this->default_layouts
                );
            }
            return config('flexible.layouts');
        }
        return $this->default_layouts;
    }

    public function render($page)
    {
        $html = '';
        foreach ($page->layout as $layout) {
            $html .= $layout->render();
        }
        return $html;
    }
}
