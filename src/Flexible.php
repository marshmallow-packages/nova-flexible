<?php

namespace Marshmallow\Nova\Flexible;

use Exception;
use Laravel\Nova\Resource;
use Laravel\Nova\Fields\Field;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\Template\Template;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Nova\Flexible\Layouts\Preset;
use Marshmallow\Nova\Flexible\Value\Resolver;
use Laravel\Nova\Fields\SupportsDependentFields;
use Marshmallow\Nova\Flexible\Http\ScopedRequest;
use Marshmallow\Nova\Flexible\Layouts\LayoutInterface;
use Marshmallow\Nova\Flexible\Value\ResolverInterface;
use Marshmallow\Nova\Flexible\Layouts\Collection as LayoutsCollection;

class Flexible extends Field
{
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-flexible-content';

    /**
     * The available layouts collection
     *
     * @var \Marshmallow\Nova\Flexible\Layouts\Collection
     */
    protected $layouts;

    /**
     * The currently defined layout groups
     *
     * @var \Illuminate\Support\Collection
     */
    protected $groups;

    /**
     * The field's value setter & getter
     *
     * @var \Marshmallow\Nova\Flexible\Value\ResolverInterface
     */
    protected $resolver;

    /**
     * All the validated attributes
     *
     * @var array
     */
    protected static $validatedKeys = [];

    /**
     * All the validated attributes
     *
     * @var Model
     */
    public static $model;

    /**
     * This is an array of tags of all the flexible items you
     * wish to INCLUDE in your flexible template selector.
     */
    protected array $includeTags = [];

    /**
     * This is an array of tags of all the flexible items you
     * wish to EXCLUDE in your flexible template selector.
     */
    protected array $excludeTags = [];

    /**
     * Create a fresh flexible field instance
     *
     * @param  string  $name
     * @param  string|null  $attribute
     * @param  mixed|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->button(__('Add layout'));
        $this->allowedToCreate(true);
        $this->allowedToDelete(true);
        $this->allowedToChangeOrder(true);
        $this->fullWidth(true);
        $this->includeTags([]);
        $this->excludeTags([]);

        $this->menu('flexible-component-selector');

        $this->hideFromIndex();
    }

    public function loadConfig(array $config = [])
    {
        $flex = $this;
        if (!empty($config)) {
            foreach ($config as $method => $arguments) {
                if ($arguments) {
                    $flex = $flex->{$method}(...$arguments);
                } else {
                    $flex = $flex->{$method}();
                }
            }
        }

        return $flex;
    }

    public function simpleView()
    {
        $this->collapsed(false);
        $this->fullWidth(true);
        $this->withMeta(['simpleLayout' => true]);
        $this->allowedToCreate(false);
        $this->allowedToDelete(false);
        $this->allowedToChangeOrder(false);
        $this->includeTags([]);
        $this->excludeTags([]);
        return $this;
    }

    public function allowedToCreate($allowed)
    {
        return $this->withMeta(['allowedToCreate' => $allowed]);
    }

    public function allowedToDelete($allowed)
    {
        return $this->withMeta(['allowedToDelete' => $allowed]);
    }

    public function allowedToChangeOrder($allowed)
    {
        return $this->withMeta(['allowedToChangeOrder' => $allowed]);
    }

    /**
     * @param string $component The name of the component to use for the menu
     *
     * @param array  $data
     *
     * @return $this
     */
    public function menu($component, $data = [])
    {
        return $this->withMeta(['menu' => compact('component', 'data')]);
    }

    public function simpleMenu()
    {
        return $this->menu('flexible-drop-menu');
    }

    /**
     * Set the button's label
     *
     * @param string $label
     * @return $this
     */
    public function button($label)
    {
        return $this->withMeta(['button' => $label]);
    }

    /**
     * Make the flexible content take up the full width
     * of the form. Labels will sit above
     *
     * @return mixed
     */
    public function fullWidth($fullWidth = true)
    {
        return $this->withMeta(['fullWidth' => $fullWidth]);
    }

    /**
     * Set the tags you wish to include to
     * the flexible layout selector.
     *
     * @return self
     */
    public function includeTags(array $tags): self
    {
        $this->includeTags = $tags;
        return $this->withMeta(['includeTags' => $tags]);
    }

    /**
     * Set the tags you wish to exclude from
     * to the flexible layout selector.
     *
     * @return self
     */
    public function excludeTags(array $tags): self
    {
        $this->excludeTags = $tags;
        return $this->withMeta(['excludeTags' => $tags]);
    }

    /**
     * Make the flexible content take up the full width
     * of the form. Labels will sit above
     *
     * @return mixed
     */
    public function stacked()
    {
        return $this->fullWidth();
    }

    /**
     *  Prevent the 'Add Layout' button from appearing more than once
     *
     * @return $this
     */
    public function limit($limit = 1)
    {
        return $this->withMeta(['limit' => $limit]);
    }

    /**
     * Confirm remove
     *
     * @return $this
     */
    public function confirmRemove($label = '', $yes = 'Delete', $no = 'Cancel')
    {
        return $this->withMeta([
            'confirmRemove' => true,
            'confirmRemoveMessage' => $label,
            'confirmRemoveYes' => $yes,
            'confirmRemoveNo' => $no,
        ]);
    }

    /**
     * Set the field's resolver
     *
     * @param mixed $resolver
     * @return $this
     */
    public function resolver($resolver)
    {
        if (is_string($resolver) && is_a($resolver, ResolverInterface::class, true)) {
            $resolver = new $resolver();
        }

        if (!($resolver instanceof ResolverInterface)) {
            throw new \Exception('Resolver Class "' . get_class($resolver) . '" does not implement ResolverInterface.');
        }

        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Register a new layout
     *
     * @param array $arguments
     * @return $this
     */
    public function addLayout(...$arguments)
    {
        $count = count($arguments);

        $layoutClass = config('flexible.default_layout_class');

        if ($count === 3) {
            $this->registerLayout(new $layoutClass($arguments[0], $arguments[1], $arguments[2]));

            return $this;
        }

        if ($count === 4) {
            $callback = $arguments[3];
            $layout = $callback(new $layoutClass($arguments[0], $arguments[1], $arguments[2]));

            $this->registerLayout($layout);

            return $this;
        }

        if ($count === 6) {
            $this->registerLayout(new $layoutClass($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]));

            return $this;
        }

        if ($count !== 1) {
            throw new Exception('Invalid "addLayout" method call. Expected 1 or 3 arguments, ' . $count . ' given.');
        }

        $layout = $arguments[0];

        if (!($layout instanceof LayoutInterface)) {
            $layout = new $layout();
        }

        if (!($layout instanceof LayoutInterface)) {
            throw new \Exception('Layout Class "' . get_class($layout) . '" does not implement LayoutInterface.');
        }

        $this->registerLayout($layout);

        return $this;
    }

    /**
     * Apply a field configuration preset
     *
     * @param string|Preset $class
     * @param array $params
     * @return $this
     */
    public function preset($class, $params = [])
    {
        if (is_string($class)) {
            $preset = resolve($class, $params);
        } else if ($class instanceof Preset) {
            $preset = $class;
        }

        $preset->handle($this);

        return $this;
    }

    public function deletionNotAllowed(bool $value = true)
    {
        $this->withMeta(['deletion_not_allowed' => $value]);

        return $this;
    }

    public function collapsed(bool $value = true)
    {
        $this->withMeta(['collapsed' => $value]);

        return $this;
    }

    /**
     * Push a layout instance into the layouts collection
     *
     * @param \Marshmallow\Nova\Flexible\Layouts\LayoutInterface $layout
     * @return void
     */
    protected function registerLayout(LayoutInterface $layout)
    {
        if (!$this->layouts) {
            $this->layouts = new LayoutsCollection();
            $this->withMeta(['layouts' => $this->layouts]);
        }

        $this->addToLayouts($layout);
    }

    /**
     * Resolve the field's value.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        $this->registerOriginModel($resource);
        $this->handleDefaultPreset();
        $this->buildGroups($resource, $attribute);

        $this->value = $this->resolveGroups($this->groups);
    }

    public function addToLayouts($layout)
    {
        $included_tags = $this->includeTags;
        $excluded_tags = $this->excludeTags;

        if (!empty($included_tags) && !$layout->hasTag($included_tags)) {
            return false;
        }
        if (!empty($excluded_tags) && $layout->hasTag($excluded_tags)) {
            return false;
        }

        $this->layouts->push($layout);
    }
    /**
     * Resolve the field's value for display on index and detail views.
     *
     * @param mixed $resource
     * @param string|null $attribute
     * @return void
     */
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        $this->registerOriginModel($resource);

        $this->buildGroups($resource, $attribute);

        $this->value = $this->resolveGroupsForDisplay($this->groups);
    }

    /**
     * Check showing on detail.
     *
     * @param NovaRequest $request
     * @param $resource
     * @return bool
     */
    public function isShownOnDetail(NovaRequest $request, $resource): bool
    {
        $this->handleDefaultPreset();
        $this->layouts = $this->layouts->each(function ($layout) use ($request, $resource) {
            $layout->filterForDetail($request, $resource);
        });

        return parent::isShownOnDetail($request, $resource);
    }

    protected function handleDefaultPreset()
    {
        if (!$this->layouts && config('flexible.fill_empty_field')) {
            $classname = config('flexible.default_preset');
            $preset = resolve($classname, []);
            $preset->handle($this);
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return null|\Closure
     */
    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if (!$request->exists($requestAttribute)) {
            return null;
        }

        $attribute = $attribute ?? $this->attribute;

        $this->registerOriginModel($model);

        $this->buildGroups($model, $attribute);

        $callbacks = collect($this->syncAndFillGroups($request, $requestAttribute));

        $this->value = $this->resolver->set($model, $attribute, $this->groups);

        if ($callbacks->isEmpty()) {
            return null;
        }

        return function () use ($callbacks) {
            $callbacks->each->__invoke();
        };
    }

    /**
     * Process an incoming POST Request
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @return array
     */
    protected function syncAndFillGroups(NovaRequest $request, $requestAttribute)
    {
        if (!($raw = $this->extractValue($request, $requestAttribute))) {
            $this->fireRemoveCallbacks(collect());
            $this->groups = collect();

            return [];
        }

        $callbacks = [];

        $new_groups = collect($raw)->map(function ($item, $key) use ($request, &$callbacks) {
            $layout = $item['layout'];
            $key = $item['key'];
            $attributes = $item['attributes'];

            $group = $this->findGroup($key) ?? $this->newGroup($layout, $key);

            if (!$group) {
                return null;
            }

            $scope = ScopedRequest::scopeFrom($request, $attributes, $key);
            $callbacks = array_merge($callbacks, $group->fill($scope));

            return $group;
        })->filter();

        $this->fireRemoveCallbacks($new_groups);

        $this->groups = $new_groups;

        return $callbacks;
    }

    /**
     * Fire's the remove callbacks on the layouts
     *
     * @param $new_groups Collection This should be (all) the new groups to bne compared against to find the removed groups
     */
    protected function fireRemoveCallbacks($new_groups)
    {
        $new_group_keys = $new_groups->map(function ($item) {
            return $item->inUseKey();
        });

        $this->groups->filter(function ($item) use ($new_group_keys) {
            return !$new_group_keys->contains($item->inUseKey());
        })->each(function ($group) {
            if (method_exists($group, 'fireRemoveCallback')) {
                $group->fireRemoveCallback($this);
            }
        });
    }

    /**
     * Find the flexible value in given request
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $attribute
     * @return null|array
     */
    protected function extractValue(NovaRequest $request, $attribute)
    {
        $value = $request[$attribute];

        if (!$value) {
            return null;
        }

        if (!is_array($value)) {
            throw new Exception("Unable to parse incoming Flexible content, data should be an array.");
        }

        return $value;
    }

    /**
     * Resolve all contained groups and their fields
     *
     * @param  \Illuminate\Support\Collection  $groups
     * @return \Illuminate\Support\Collection
     */
    protected function resolveGroups($groups)
    {
        return $groups->map(function ($group) {
            return $group->getResolved();
        });
    }

    /**
     * Resolve all contained groups and their fields for display on index and
     * detail views.
     *
     * @param \Illuminate\Support\Collection $groups
     * @return \Illuminate\Support\Collection
     */
    protected function resolveGroupsForDisplay($groups)
    {
        return $groups->map(function ($group) {
            return $group->getResolvedForDisplay();
        });
    }

    /**
     * Define the field's actual layout groups (as "base models") based
     * on the field's current model & attribute
     *
     * @param  mixed  $resource
     * @param  string $attribute
     * @return \Illuminate\Support\Collection
     */
    protected function buildGroups($resource, $attribute)
    {
        if (!$this->resolver) {
            $this->resolver(Resolver::class);
        }

        return $this->groups = $this->resolver->get($resource, $attribute, $this->layouts);
    }

    /**
     * Find an existing group based on its key
     *
     * @param  string $key
     * @return \Marshmallow\Nova\Flexible\Layouts\Layout
     */
    protected function findGroup($key)
    {
        return $this->groups->first(function ($group) use ($key) {
            return $group->matches($key);
        });
    }

    /**
     * Create a new group based on its key and layout
     *
     * @param  string $layout
     * @param  string $key
     * @return \Marshmallow\Nova\Flexible\Layouts\Layout
     */
    protected function newGroup($layout, $key)
    {
        $layout = $this->layouts->find($layout);

        if (!$layout) {
            return null;
        }

        return $layout->duplicate($key);
    }

    /**
     * Get the validation rules for this field & its contained fields.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function getRules(NovaRequest $request): array
    {
        return parent::getRules($request);
    }

    /**
     * Get the creation rules for this field & its contained fields.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array|string
     */
    public function getCreationRules(NovaRequest $request): array
    {
        return array_merge_recursive(
            parent::getCreationRules($request),
            $this->getFlexibleRules($request, 'creation')
        );
    }

    /**
     * Get the update rules for this field & its contained fields.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function getUpdateRules(NovaRequest $request): array
    {
        return array_merge_recursive(
            parent::getUpdateRules($request),
            $this->getFlexibleRules($request, 'update')
        );
    }

    /**
     * Retrieve contained fields rules and assign them to nested array attributes
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @param string $specificity
     * @return array
     */
    protected function getFlexibleRules(NovaRequest $request, string $specificity)
    {
        if (!($value = $this->extractValue($request, $this->attribute))) {
            return [];
        }

        $rules = $this->generateRules($request, $value, $specificity);

        if (!is_a($request, ScopedRequest::class)) {
            // We're not in a nested flexible, meaning we're
            // assuming the field is located at the root of
            // the model's attributes. Therefore, we should now
            // register all the collected validation rules for later
            // reference (see Http\TransformsFlexibleErrors).
            static::registerValidationKeys($rules);

            // Then, transform the rules into an array that's actually
            // usable by Laravel's Validator.
            $rules = $this->getCleanedRules($rules);
        }

        return $rules;
    }

    /**
     * Format all contained fields rules and return them.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @param array $value
     * @param string $specificity
     * @return array
     */
    protected function generateRules(NovaRequest $request, $value, string $specificity)
    {
        return collect($value)->map(function ($item, $key) use ($request, $specificity) {
            $group = $this->newGroup($item['layout'], $item['key']);

            if (!$group) {
                return [];
            }

            $scope = ScopedRequest::scopeFrom($request, $item['attributes'], $item['key']);

            return $group->generateRules($scope, $specificity, $this->attribute . '.' . $key);
        })
            ->collapse()
            ->all();
    }

    /**
     * Transform Flexible rules array into an actual validator rules array
     *
     * @param  array $rules
     * @return array
     */
    protected function getCleanedRules(array $rules)
    {
        return array_map(function ($field) {
            return $field['rules'];
        }, $rules);
    }

    /**
     * Add validation keys to the validated Keys register, which will be
     * used for transforming validation errors later in the request cycle.
     *
     * @param  array $rules
     * @return void
     */
    protected static function registerValidationKeys(array $rules)
    {
        $validatedKeys = array_map(function ($field) {
            return $field['attribute'];
        }, $rules);

        static::$validatedKeys = array_merge(
            static::$validatedKeys,
            $validatedKeys
        );
    }

    /**
     * Return a previously registered validation key
     *
     * @param  string $key
     * @return null|\Marshmallow\Nova\Flexible\Http\FlexibleAttribute
     */
    public static function getValidationKey($key)
    {
        return static::$validatedKeys[$key] ?? null;
    }

    /**
     * Registers a reference to the origin model for nested & contained fields
     *
     * @param  mixed $model
     * @return void
     */
    protected function registerOriginModel($model)
    {
        if (is_a($model, Resource::class)) {
            $model = $model->model();
        } elseif (is_a($model, Template::class)) {
            $model = $model->getOriginal();
        }

        if (!is_a($model, Model::class)) {
            return;
        }

        static::$model = $model;
    }

    /**
     * Return the previously registered origin model
     *
     * @return null|Model
     */
    public static function getOriginModel()
    {
        return static::$model;
    }
}
