<?php

namespace Marshmallow\Nova\Flexible;

use Exception;
use Illuminate\Support\Facades\Route;
use Marshmallow\MultiLanguage\Models\Language;
use Marshmallow\Nova\Flexible\Layouts\Defaults\WysiwygLayout;
use Marshmallow\Nova\Flexible\Layouts\Defaults\UspFontawesomeLayout;

class Flex
{
    protected $default_layouts = [
        'wysiwyg' => WysiwygLayout::class,
        'uspfontawesome' => UspFontawesomeLayout::class,
    ];

    public function getLayouts()
    {
        if (!empty(config('flexible.layouts'))) {
            $layouts_array = [];
            foreach (config('flexible.layouts') as $layout) {
                if (is_callable($layout)) {
                    $callable_result = $layout();
                    if (!is_array($callable_result)) {
                        throw new Exception('Your layout callable should return an array');
                    }
                    $layouts_array = array_merge($layouts_array, $callable_result);
                } else {
                    $layouts_array[] = $layout;
                }
            }

            if (config('flexible.merge_layouts') === true) {
                return array_merge(
                    $layouts_array,
                    $this->default_layouts
                );
            }
            return $layouts_array;
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
