<?php

namespace Marshmallow\Nova\Flexible;

use Error;
use Exception;
use TypeError;
use Illuminate\Support\Str;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\Cache;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;
use Marshmallow\Nova\Flexible\Layouts\Defaults\WysiwygLayout;
use Marshmallow\Nova\Flexible\Layouts\DependedLayout;

class Flex
{
    protected $loaded_layouts;

    protected $default_layouts = [
        'wysiwyg' => WysiwygLayout::class,
    ];

    protected function getDefaultLayouts(string $model = null)
    {
        $layout = $this->default_layouts;
        if ($model && class_exists($model)) {
            if (method_exists($model, 'activateDependendFlexible') && $model::activateDependendFlexible()) {
                $layout['depended-layout'] = DependedLayout::class;
            }
        }
        return $layout;
    }

    public function getLayouts(string $model = null)
    {
        if ($this->autoDiscoveryIsActive()) {
            return $this->autoDiscoverLayouts($model);
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
                    $this->getDefaultLayouts($model)
                );
            }

            return $layouts_array;
        }

        return $this->getDefaultLayouts($model);
    }

    public function render($page)
    {
        $html = '';
        foreach ($page->layout as $layout) {
            $html .= $layout->render();
        }

        return $html;
    }

    protected function autoDiscoverLayouts(string $model = null): array
    {
        if ($this->loaded_layouts) {
            return $this->loaded_layouts;
        }

        $layouts = [];
        $layouts_folder = $this->getLayoutsFolder();

        if (file_exists($layouts_folder)) {
            $it = new RecursiveDirectoryIterator($layouts_folder);

            foreach (new RecursiveIteratorIterator($it) as $file) {
                if ($file->getExtension() == 'php') {

                    try {
                        $layout = $this->resolveFilePathToClass($file);
                        if ($layout->shouldNotBeAutoLoaded()) {
                            continue;
                        }
                        $layouts[$layout->name()] = get_class($layout);
                    } catch (TypeError $e) {
                        /**
                         * This is not a flexible layout file. We will not
                         * load this so therefor we dont need to do anything
                         * in this catched exception.
                         */
                    } catch (Error $e) {
                        /**
                         * This is probably a trait file. We don't load these
                         * either.
                         */
                    }
                }
            }
        }


        if ($this->loadDefaultLayouts()) {
            $layouts = array_merge(
                $layouts,
                $this->getDefaultLayouts($model)
            );
        }

        $this->loaded_layouts = $layouts;

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

    public function flushLayoutsCache()
    {
        Cache::forget(static::getCacheKey());
    }

    public static function getCacheKey(): string
    {
        return "marshmallow.flexible-layouts-cache";
    }

    public function getLayoutsFromCache(string $model = null)
    {
        return Cache::rememberForever(static::getCacheKey(), function () use ($model) {
            return self::getLayouts($model);
        });
    }
}
