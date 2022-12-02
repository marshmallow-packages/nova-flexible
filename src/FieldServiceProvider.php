<?php

namespace Marshmallow\Nova\Flexible;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Marshmallow\Nova\Flexible\Commands\LayoutCommand;
use Marshmallow\Nova\Flexible\Commands\MakeLayoutCommand;
use Marshmallow\Nova\Flexible\Http\Middleware\InterceptFlexibleAttributes;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout;
use Marshmallow\Nova\Flexible\Layouts\MarshmallowMediaLayout;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addMiddleware();

        Nova::serving(function (ServingNova $event) {
            Nova::script('nova-flexible-content', __DIR__ . '/../dist/js/field.js');
            Nova::style('nova-flexible-content', __DIR__ . '/../dist/css/field.css');
        });

        $this->publishes([
            __DIR__ . '/../config/flexible.php' => config_path('flexible.php'),
        ]);

        Cache::forget("marshmallow.flexible-layouts-cache");
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/flexible.php',
            'flexible'
        );

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            LayoutCommand::class,
            MakeLayoutCommand::class,
        ]);
    }

    /**
     * Adds required middleware for Nova requests.
     *
     * @return void
     */
    public function addMiddleware()
    {
        $router = $this->app['router'];

        if ($router->hasMiddlewareGroup('nova')) {
            $router->pushMiddlewareToGroup('nova', InterceptFlexibleAttributes::class);

            return;
        }

        if (!$this->app->configurationIsCached()) {
            config()->set('nova.middleware', array_merge(
                config('nova.middleware', []),
                [InterceptFlexibleAttributes::class]
            ));
        }
    }
}
