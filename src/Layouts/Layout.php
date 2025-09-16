<?php

namespace Marshmallow\Nova\Flexible\Layouts;

use Closure;
use Exception;
use ArrayAccess;
use JsonSerializable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use Marshmallow\Nova\Flexible\Flexible;
use Laravel\Nova\Fields\FieldCollection;
use Illuminate\Contracts\Support\Arrayable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\HelperFunctions\Facades\URL;
use Marshmallow\Nova\Flexible\Http\ScopedRequest;
use Marshmallow\Nova\Flexible\Concerns\HasFlexible;
use Marshmallow\Nova\Flexible\Http\FlexibleAttribute;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;

class Layout implements LayoutInterface, JsonSerializable, ArrayAccess, Arrayable
{
    use HasAttributes;
    use HidesAttributes;
    use HasFlexible;

    /**
     * The layout's name
     *
     * @var string
     */
    protected $name;

    /**
     * This is the field that is used to display the
     * title in a group.
     * @var string
     */
    protected $titleFromContent = 'title';

    /**
     * The layout's unique identifier
     *
     * @var string
     */
    protected $key;

    /**
     * The layout's temporary identifier
     *
     * @var string
     */
    protected $_key;

    /**
     * The layout's title
     *
     * @var string
     */
    protected $title;

    /**
     * Description as shown in the layout selector.
     *
     * @var string
     */
    protected $description = 'No description available';

    /**
     * Image use in the layout selector
     *
     * @var string
     */
    protected $image = 'https://marshmallow.dev/cdn/flex/layout/containers.png';

    /**
     * Add this layout to these tags.
     *
     * @var array<string>
     */
    protected $tags = ['Custom'];

    /**
     * The layout's registered fields
     *
     * @var \Laravel\Nova\Fields\FieldCollection
     */
    protected $fields;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The callback to be called when this layout is removed
     */
    protected $removeCallbackMethod;

    /**
     * The callback to generate the title of the flex items
     *
     * @var \Closure
     */
    protected $resolveTitleCallback;

    protected $resolvedTitle;

    /**
     * The maximum amount of this layout type that can be added
     * Can be set in custom layouts
     */
    protected $limit;

    /**
     * The parent model instance
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * @var array data that will be loaded into the view
     */
    protected $with = [];

    /**
     * Define that Layout is a model, when in fact it is not.
     *
     * @var bool
     */
    protected $exists = false;

    protected $autoLoad = true;

    /**
     * Define that Layout is a model, when in fact it is not.
     *
     * @var bool
     */
    protected $wasRecentlyCreated = false;

    /**
     * The relation resolver callbacks.
     *
     * @var array
     */
    protected  $relationResolvers = [];

    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Create a new base Layout instance
     *
     * @param string $title
     * @param string $name
     * @param array $fields
     * @param string $key
     * @param array $attributes
     * @param callable|null $removeCallbackMethod
     * @param int|null $limit
     * @return void
     */
    public function __construct($title = null, $name = null, $fields = null, $key = null, $attributes = [], ?callable $removeCallbackMethod = null, ?callable $resolveTitleUsing = null)
    {
        $this->title = $title ?? $this->title();
        $this->name = $name ?? $this->name();
        $this->fields = new FieldCollection($this->getFieldsArray($fields));
        $this->key = is_null($key) ? null : $this->getProcessedKey($key);
        $this->removeCallbackMethod = $removeCallbackMethod;
        $this->resolveTitleCallback = $resolveTitleUsing;

        $this->setRawAttributes($this->cleanAttributes($attributes));
    }

    public function translatableColumns(): ?array
    {
        return null;
    }

    protected function getFieldsArray($fields = null)
    {
        if ($fields) {
            return $fields;
        }

        /**
         * Only load the fields if we are using Nova.
         * There is no need to do this when we are rendering
         * fields in the front-end.
         */
        if (URL::isNova(request())) {
            $fields = $this->fields();

            if ($this->weAreTranslating()) {
                $columns = $this->translatableColumns();
                if (is_array($columns)) {
                    $fields = collect($fields)
                        ->filter(function ($field) use ($columns) {
                            return in_array($field->attribute, $columns);
                        })
                        ->values()
                        ->toArray();
                }
            }
        }

        if (!empty($fields)) {
            return $fields;
        }

        return [];
    }

    /**
     * Get the parent model instance
     *
     * @return Model $model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the parent model instance
     *
     * @param Model $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    public function setWith(array $with = [])
    {
        $this->with = $with;
    }

    public function getWith($key = null)
    {
        if ($key) {
            if (array_key_exists($key, $this->with)) {
                return $this->with[$key];
            }
            return null;
        }
        return $this->with;
    }

    public function setTitleFromContent($titleFromContent)
    {
        $this->titleFromContent = $titleFromContent;

        return $this;
    }

    /**
     * Retrieve the layout's name (identifier)
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Retrieve the layout's title
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * Retrieve the layout's fields
     *
     * @return array
     */
    public function fields()
    {
        return $this->fields ? $this->fields->all() : [];
    }

    /**
     * Retrieve the layout's unique key
     *
     * @return string
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Retrieve the key currently in use in the views
     *
     * @return string
     */
    public function inUseKey()
    {
        return $this->_key ?: $this->key();
    }

    /**
     * Check if this group matches the given key
     *
     * @param string $key
     * @return bool
     */
    public function matches($key)
    {
        return ($this->key === $key || $this->_key === $key);
    }

    public function shouldNotBeAutoLoaded()
    {
        return ($this->autoLoad === false);
    }

    /**
     * Resolve and return the result
     *
     * @return array
     */
    public function getResolved()
    {
        $this->resolve();

        return $this->getResolvedValue();
    }

    /**
     * Resolve the field for display and return the result.
     *
     * @return array
     */
    public function getResolvedForDisplay()
    {
        return $this->resolveForDisplay($this->getAttributes());
    }

    /**
     * Get an empty cloned instance
     *
     * @param  string  $key
     * @return Layout
     */
    public function duplicate($key)
    {
        return $this->duplicateAndHydrate($key);
    }

    /**
     * Get a cloned instance with set values
     *
     * @param  string  $key
     * @param  array  $attributes
     * @return Layout
     */
    public function duplicateAndHydrate($key, array $attributes = [])
    {
        $fields = $this->fields->map(function ($field) {
            return $this->cloneField($field);
        });

        return new static(
            $this->title,
            $this->name,
            $fields,
            $key,
            $attributes,
            $this->removeCallbackMethod,
            $this->limit
        );
        if (!is_null($this->model)) {
            $clone->setModel($this->model);
        }
        return $clone;
    }

    /**
     * Create a working field clone instance
     *
     * @param  \Laravel\Nova\Fields\Field $original
     * @return \Laravel\Nova\Fields\Field
     */
    protected function cloneField(Field $original)
    {
        $field = clone $original;

        $callables = ['displayCallback', 'resolveCallback', 'fillCallback', 'requiredCallback'];

        foreach ($callables as $callable) {
            if (!is_a($field->$callable ?? null, Closure::class)) {
                continue;
            }
            $field->$callable = $field->$callable->bindTo($field);
        }

        return $field;
    }

    /**
     * Resolve fields using given attributes.
     *
     * @param  bool $empty
     * @return void
     */
    public function resolve($empty = false)
    {
        $this->fields->each(function ($field) use ($empty) {
            $field->resolve($empty ? $this->duplicate($this->inUseKey()) : $this);
        });
    }

    /**
     * Resolve fields for display using given attributes.
     *
     * @param array $attributes
     * @return array
     */
    public function resolveForDisplay(array $attributes = [])
    {
        $this->fields->each(function ($field) use ($attributes) {
            $field->resolveForDisplay($attributes);
        });

        return $this->getResolvedValue();
    }

    /**
     * Filter the layout's fields for detail view
     *
     * @param NovaRequest $request
     * @param $resource
     */
    public function filterForDetail(NovaRequest $request, $resource)
    {
        if (method_exists($this->fields, 'filterForDetail')) {
            $this->fields = $this->fields->filterForDetail($request, $resource);
        }
    }

    /**
     * Get the layout's resolved representation. Best used
     * after a resolve() call
     *
     * @return array
     */
    public function getResolvedValue()
    {
        return [
            'layout' => $this->name,

            // The (old) temporary key is preferred to the new one during
            // field resolving because we need to keep track of the current
            // attributes during the next fill request that will override
            // the key with a new, stronger & definitive one.
            'key' => $this->inUseKey(),

            // The layout's fields now temporarily contain the resolved
            // values from the current group's attributes. If multiple
            // groups use the same layout, the current values will be lost
            // since each group uses the same fields by reference. That's
            // why we need to serialize the field's current state.
            'attributes' => $this->fields->jsonSerialize(),

            'title_data' => [
                'resolved' => $this->getResolvedTitle($this->fields->jsonSerialize()),
                'field' => $this->titleFromContent,
            ],
        ];
    }

    public function getResolvedTitle($fields)
    {
        return $this->resolveTitle(
            collect($fields)->pluck('value')->toArray()
        );
    }

    /**
     * Fill attributes using underlying fields and incoming request
     *
     * @param ScopedRequest $request
     * @return array
     */
    public function fill(ScopedRequest $request)
    {
        return  $this->fields->map(function ($field) use ($request) {
            return $field->fill($request, $this);
        })
            ->filter(function ($callback) {
                return is_callable($callback);
            })
            ->values()
            ->all();
    }

    /**
     * Get validation rules for fields concerned by given request
     *
     * @param ScopedRequest $request
     * @param string $specificity
     * @param string $key
     * @return array
     */
    public function generateRules(ScopedRequest $request, string $specificity, $key)
    {
        return  $this->fields->map(function ($field) use ($request, $specificity, $key) {
            return $this->getScopedFieldRules($field, $request, $specificity, $key);
        })
            ->collapse()
            ->all();
    }

    /**
     * Get validation rules for fields concerned by given request
     *
     * @param  \Laravel\Nova\Fields\Field $field
     * @param  ScopedRequest $request
     * @param  null|string $specificity
     * @param  string $key
     * @return array
     */
    protected function getScopedFieldRules($field, ScopedRequest $request, $specificity, $key)
    {
        $method = 'get' . ucfirst($specificity) . 'Rules';

        $rules = call_user_func([$field, $method], $request);

        return  collect($rules)->mapWithKeys(function ($validatorRules, $attribute) use ($key, $field) {
            $key = $key . '.attributes.' . $attribute;

            return [$key => $this->wrapScopedFieldRules($field, $validatorRules)];
        })
            ->filter()
            ->all();
    }

    /**
     * The method to call when this layout is removed
     *
     * @param Flexible $flexible
     * @return mixed
     */
    public function fireRemoveCallback(Flexible $flexible)
    {
        if (is_callable($this->removeCallbackMethod)) {
            return $this->removeCallbackMethod($flexible, $this);
        }

        return $this->removeCallback($flexible, $this);
    }

    /**
     * The default behaviour when removed
     *
     * @param  Flexible $flexible
     * @param  Layout $layout
     *
     * @return mixed
     */
    protected function removeCallback(Flexible $flexible, Layout $layout)
    {
        return;
    }

    /**
     * Wrap the rules in an array containing field information for later use
     *
     * @param  \Laravel\Nova\Fields\Field $field
     * @param  array $rules
     * @return null|array
     */
    protected function wrapScopedFieldRules($field, array $rules)
    {
        if (!$rules) {
            return null;
        }

        if (is_a($rules['attribute'] ?? null, FlexibleAttribute::class)) {
            return $rules;
        }

        return [
            'attribute' => FlexibleAttribute::make($field->attribute, $this->inUseKey()),
            'rules' => $rules,
        ];
    }

    /**
     * Dynamically retrieve attributes on the layout.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the layout.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return !is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Transform empty attribute values to null
     *
     * @param  array $attributes
     * @return array
     */
    protected function cleanAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (!is_string($value) || strlen($value)) {
                continue;
            }
            $attributes[$key] = null;
        }

        return $attributes;
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    protected function getDates()
    {
        return $this->dates ?: [];
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s';
    }

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        return $this->casts ?: [];
    }

    /**
     * Check if relation exists. Layouts do not have relations.
     *
     * @return bool
     */
    protected function relationLoaded()
    {
        return false;
    }

    /**
     * Get the dynamic relation resolver if defined or inherited, or return null.
     *
     * @param  string  $class
     * @param  string  $key
     * @return mixed
     */
    public function relationResolver($class, $key)
    {
        return null;
    }


    /**
     * Transform layout for serialization
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        // Calling an empty "resolve" first in order to empty all fields
        $this->resolve(true);

        return [
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,
            'image' => $this->image,
            'title_resolved' => ($this->hasResolverForTitleAttribute()) ? 1 : 0,
            'title_from_content' => $this->resolvedTitle,
            'fields' => $this->fields->jsonSerialize(),
            'limit' => $this->limit,
        ];
    }

    public function resolveTitleUsing(Closure $callable): self
    {
        $this->resolveTitleCallback = $callable;
        return $this;
    }

    public function resolveTitle($values)
    {
        if (!$this->hasResolverForTitleAttribute()) {
            $this->resolvedTitle = $this->titleFromContent;
        } else {
            if ($this->resolveTitleCallback) {
                $callback = $this->resolveTitleCallback;
                $this->resolvedTitle = $callback(...$values);
            } else {
                $this->resolvedTitle = $this->resolveTitleAttribute(...$values);
            }
            return $this->resolvedTitle;
        }
    }

    protected function hasResolverForTitleAttribute()
    {
        return (bool)($this->resolveTitleCallback || method_exists($this, 'resolveTitleAttribute'));
    }

    /**
     * Returns an unique key for this group if it's not already the case
     *
     * @param string $key
     * @return string
     * @throws Exception
     */
    protected function getProcessedKey($key)
    {
        if (strpos($key, '-') === false && strlen($key) === 16) {
            return $key;
        }

        // The key is either generated by Javascript or not strong enough.
        // Before assigning a new valid key we'll keep track of this one
        // in order to keep it usable in a Flexible::findGroup($key) search.
        $this->_key = $key;

        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil(16 / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil(16 / 2));
        } else {
            throw new Exception("No cryptographically secure random function available");
        }

        return substr(bin2hex($bytes), 0, 16);
    }

    public function weAreTranslating()
    {
        if (Request::hasMacro('getTranslatableLocale')) {
            $default_locale = config('app.default_locale') ?? config('app.locale');
            return Request::getTranslatableLocale() !== $default_locale;
        }

        return false;
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray();
    }


    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function forceFill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $attribute = Str::replace('->', '.', $key);

            Arr::set($this->attributes, $attribute, $value);
        }

        return $this;
    }

    /**
     * Determine if accessing missing attributes is disabled.
     *
     * @return bool
     */
    public static function preventsAccessingMissingAttributes()
    {
        return false;
    }

    /**
     * Prepare the object for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $layoutVars = array_keys(get_object_vars($this));
        return $layoutVars;
    }
}
