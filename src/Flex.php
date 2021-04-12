<?php

namespace Marshmallow\Nova\Flexible;

use Exception;
use Illuminate\Support\Str;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;
use Marshmallow\Nova\Flexible\Layouts\Defaults\WysiwygLayout;

class Flex
{
    protected $default_layouts = [
        'wysiwyg' => WysiwygLayout::class,
    ];

    public function getLayouts()
    {
        if ($this->autoDiscoveryIsActive()) {
            return $this->autoDiscoverLayouts();
        }
        if (!empty(config('flexible.layouts'))) {
            $layouts_array = [];
            foreach (config('flexible.layouts') as $key => $layout) {
                if (is_callable($layout)) {
                    $callable_result = $layout();
                    if (!is_array($callable_result)) {
                        throw new Exception('Your layout callable should return an array');
                    }
                    $layouts_array = array_merge($layouts_array, $callable_result);
                } else {
                    $layouts_array[$key] = $layout;
                }
            }

            if ($this->loadDefaultLayouts()) {
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

    protected function autoDiscoverLayouts(): array
    {
        $layouts_folder = $this->getLayoutsFolder();
        $it = new RecursiveDirectoryIterator($layouts_folder);

        $layouts = [];
        foreach (new RecursiveIteratorIterator($it) as $file) {
            if ($file->getExtension() == 'php') {
                $layout = $this->resolveFilePathToClass($file);
                if ($layout->shouldNotBeAutoLoaded()) {
                    continue;
                }
                $layouts[$layout->name()] = get_class($layout);
            }
        }

        if ($this->loadDefaultLayouts()) {
            $layouts = array_merge(
                $layouts,
                $this->default_layouts
            );
        }

        return $layouts;
    }

    protected function resolveFilePathToClass($file): MarshmallowLayout
    {
        $local_namespace = (string) Str::of($file->getPathName())
            ->remove('.php')
            ->remove(app_path())
            ->prepend('\App')
            ->replace('/', '\\');

        return new $local_namespace;
    }

    protected function getLayoutsFolder(): string
    {
        return app_path('Flexible');
    }

    protected function autoDiscoveryIsActive(): bool
    {
        return (array_key_exists('auto-discovery', config('flexible')) && config('flexible.auto-discovery') === true);
    }

    protected function loadDefaultLayouts(): bool
    {
        return config('flexible.merge_layouts') === true;
    }
}
