<?php

namespace Marshmallow\Nova\Flexible\Commands;

use Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use function Laravel\Prompts\table;
use Symfony\Component\Finder\Finder;
use Marshmallow\Nova\Flexible\Facades\Flex;
use Illuminate\Database\Eloquent\SoftDeletes;
use Marshmallow\Nova\Flexible\Concerns\HasFlexible;

class ListLayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flexible:list-layouts
                            {--model=* : Class names of the models to be pruned}
                            {--except=* : Class names of the models to be excluded from pruning}
                            {--path=* : Absolute path(s) to directories where models are located}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all layouts from flexible';

    protected $models = [];

    protected array $layouts_index = [];

    protected array $default_table_keys = ['Name', 'Class', 'Key', 'Total'];
    protected array $table_keys = [];
    protected array $table_values = [];

    protected array $model_count = [];

    protected array $ignored_cast_types = [
        'bigint', 'int', 'date', 'datetime', 'timestamp', 'float', 'decimal', 'boolean',
    ];

    protected array $allowed_column_types = [
        'text', 'mediumtext', 'longtext', 'json', 'jsonb', 'array', 'blob', 'mediumblob', 'longblob'
    ];

    protected array $ignored_attributes = [
        'id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected string $layout_class = 'Marshmallow\Nova\Flexible\Layouts\MarshmallowLayout';
    protected string $layout_collection_class = 'Marshmallow\Nova\Flexible\Layouts\Collection';
    protected string $default_model_class = 'Illuminate\Database\Eloquent\Model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = $this->models();

        if ($models->isEmpty()) {
            $this->components->info('No models with flexible found.');
            return;
        }

        $models = $this->getModelCollection($models);

        $this->models = $models->mapWithKeys(function ($columns, $model) {
            $allowed_columns = $this->checkColumnTypes($model, $columns);
            return [$model => $allowed_columns];
        });

        $this->setTableKeys();

        $this->models->each(function ($model_columns, $model_class) {
            collect($model_columns)->each(function ($column, $id) use ($model_class) {
                $model = (new $model_class);
                $uses = class_uses_recursive($model);
                $softDeletes = collect($uses)->contains(SoftDeletes::class);

                if ($softDeletes) {
                    $models = $model->withTrashed()->get();
                } else {
                    $models = $model->all();
                }

                $models->each(function ($model) use ($column) {
                    $this->countLayoutByModel($model, $column);
                });
            });

            $this->table_keys = array_merge($this->table_keys, [$model_class]);
        })->toArray();

        $this->createTableValues();

        $this->table_keys = collect($this->table_keys)->map(function ($key) {
            return Str::of($key)->afterLast('\\')->snake()->replace('_', ' ')->title($key)->toString();
        })->toArray();

        return table(
            $this->table_keys,
            $this->table_values,
        );
    }

    protected function removeEmptyColumns()
    {
        foreach ($this->table_keys as $key) {
            if (in_array($key, $this->default_table_keys)) {
                continue;
            }

            $key_count = Arr::get($this->model_count, $key, 0);
            if ($key_count > 0) {
                continue;
            }

            $this->table_keys = collect($this->table_keys)->reject(function ($table_key) use ($key) {
                return $table_key == $key;
            })->toArray();

            collect($this->table_values)->each(function ($columns, $value_key) use ($key, $key_count) {
                if ($key_count > 0) {
                    return;
                }

                $columns = collect($columns)->reject(function ($value, $column) use ($key, $key_count) {
                    if ($column == $key && $key_count <= 0) {
                        return true;
                    }
                })->toArray();

                $this->table_values[$value_key] = $columns;
            });
        }
    }

    protected function checkColumnTypes(string $model, array $columns): array
    {
        $model_class = (new $model);
        $tableName = $model_class->getTable();
        return collect($columns)->filter(function ($column) use ($model, $tableName) {
            $type = Schema::getColumnType($tableName, $column);
            return in_array($type, $this->allowed_column_types);
        })->toArray();
    }

    protected function getModelCollection($models): Collection
    {
        return collect($models)->mapWithKeys(function ($model) {

            if (!array_key_exists($model, $this->models)) {
                $this->models[$model] = [];
            }

            $model_class = (new $model);
            $tableName = $model_class->getTable();

            $columns = Schema::getColumnListing($tableName);
            $casts = collect($model_class->getCasts());

            $ignored_casts = $casts->filter(function ($cast, $column) {
                return in_array($cast, $this->ignored_cast_types);
            })->keys()->toArray();

            $attributes = collect($columns)->reject(function ($attribute) use ($model) {
                return in_array($attribute, $this->models[$model]);
            })->reject(function ($attribute) {
                return in_array($attribute, $this->ignored_attributes);
            })->reject(function ($attribute) use ($ignored_casts) {
                return in_array($attribute, $ignored_casts);
            })->values()->toArray();

            return [$model => $attributes];
        });
    }

    protected function createTableValues(): void
    {
        $this->sortIndexByCount();

        collect($this->layouts_index)->each(function ($value, $key) {
            $this->table_values[$key] = [
                $value['name'],
                $value['class'],
                $value['key'],
                $value['count'],
            ];

            collect($value['models'])->each(function ($model_count, $model_class) use ($key) {
                $this->table_values[$key][$model_class] = $model_count;
                $original_count = $this->model_count[$model_class] ?? 0;
                $this->model_count[$model_class] = ($original_count + $model_count);
            });
        });

        $this->removeEmptyColumns();
    }

    protected function countLayoutByModel(object $model, string $layout_column = 'layout'): void
    {
        $model_class = (get_class($model));
        $model_array_key = 'models.' . $model_class;

        $layouts = $model->{$layout_column};

        if (!$layouts) {
            return;
        }

        if (is_string($layouts)) {
            $layouts = json_decode($layouts);
            if (!$layouts || is_int($layouts)) {
                return;
            }

            foreach ($layouts as $layout) {
                $key = $layout->layout;
                if ($key == 'depended-layout') {
                    return;
                }
                $content = Arr::get($this->layouts_index, $key);

                $model_count = Arr::get($content, $model_array_key);
                if (!$model_count) {
                    $model_count = 0;
                    $content['models'][$model_class] = $model_count;
                }

                $content['models'][$model_class] = ($model_count + 1);

                $count = Arr::get($content, 'count');
                $content = Arr::set($content, 'count', $count + 1);
                Arr::set($this->layouts_index, $key, $content);
            }
            return;
        }


        if (!$layouts instanceof $this->layout_collection_class) {
            return;
        }

        if (is_string($layouts)) {
            $layouts = collect($layouts);
        }

        $layouts->reject(function ($layout) {
            return collect(class_parents($layout))->contains($this->default_model_class);
        })->filter(function ($layout) {
            return collect(class_parents($layout))->contains($this->layout_class);
        })->each(function ($layout, $id) use ($model, $model_class, $model_array_key) {
            $layout_key = $layout->name();
            $content = Arr::get($this->layouts_index, $layout_key);

            if (!$content) {
                $content = $this->setLayoutsIndex(key: $layout_key, model: $model);
            }

            $model_count = Arr::get($content, $model_array_key);
            if (!$model_count) {
                $model_count = 0;
                $content['models'][$model_class] = $model_count;
            }

            $content['models'][$model_class] = ($model_count + 1);
            $count = Arr::get($content, 'count');

            $content = Arr::set($content, 'count', $count + 1);
            Arr::set($this->layouts_index, $layout_key, $content);
        });
    }


    protected function setLayoutsIndex(string $key, string $name = null, string $class = null, object $model = null): array
    {
        $content = Arr::get($this->layouts_index, $key);

        if (!$content) {
            $model_class = null;
            $model_count = 0;
            if ($model) {
                $model_class = (get_class($model));
                $model_count = 0;
            }
            $content = [
                "name" => $name ?? Str::title($key),
                "class" => $class ?? 'Unknown layout class',
                "key" => $key,
                "count" => 0,
                "models" => [],
            ];
            if ($model) {
                $content['models'] = [
                    $model_class => $model_count,
                ];
            }

            foreach ($this->models as $model_class => $model_columns) {
                $content['models'][$model_class] = $model_count;
            }
        }

        Arr::set($this->layouts_index, $key, $content);

        return $content;
    }

    protected function setTableKeys(): void
    {
        collect(Flex::getLayouts())->each(function ($layout_class, $key) {
            $layout_instance = new $layout_class;
            $this->setLayoutsIndex(key: $key, name: $layout_instance->title(), class: $layout_class);
        });

        $this->table_keys = $this->default_table_keys;
    }

    protected function sortIndexByCount(): array
    {
        $count = [];
        foreach ($this->layouts_index as $key => $row) {
            $count[$key] = $row['count'];
        }
        array_multisort($count, SORT_DESC, $this->layouts_index);

        return $this->layouts_index;
    }


    /**
     * Get the path where models are located.
     *
     * @return string[]|string
     */
    protected function getPath()
    {
        if (!empty($path = $this->option('path'))) {
            return collect($path)->map(function ($path) {
                return base_path($path);
            })->all();
        }

        return app_path('Models');
    }


    /**
     * Determine the models that should be pruned.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function models()
    {
        if (!empty($models = $this->option('model'))) {
            return collect($models)->filter(function ($model) {
                return class_exists($model);
            })->values();
        }

        $except = $this->option('except');

        if (!empty($models) && !empty($except)) {
            throw new InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        $all_models = collect(Finder::create()->in($this->getPath())->files()->name('*.php'))
            ->map(function ($model) {
                $namespace = $this->laravel->getNamespace();

                return $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
                );
            })->values()
            ->merge(collect($this->models)->keys()->toArray())
            ->merge(get_declared_classes())
            ->unique()
            ->filter(function ($class) {
                return collect(class_parents($class))->contains($this->default_model_class);
            })->when(!empty($except), function ($models) use ($except) {
                return $models->reject(function ($model) use ($except) {
                    return in_array($model, $except);
                });
            })->filter(function ($model) {
                return class_exists($model);
            })->filter(function ($model) {
                return $this->isFlexible($model);
                return true;
            })->values();

        $parents = $all_models->map(function ($model) {
            return collect(class_parents($model))
                ->reject(function ($parent) {
                    return $parent === $this->default_model_class;
                })->values();
        })->flatten()->filter()->values();

        return $all_models->reject(function ($model) use ($parents) {
            return $parents->contains($model);
        });
    }

    /**
     * Determine if the given model class is prunable.
     *
     * @param  string  $model
     * @return bool
     */
    protected function isFlexible($model)
    {
        $uses = class_uses_recursive($model);
        $has_flexible = collect($uses)->contains(HasFlexible::class);
        if ($has_flexible) {
            return true;
        }

        $model_class = (new $model);
        $casts = $model_class->getCasts();

        return collect($casts)->filter(function ($cast, $column) {
            return Str::contains($cast, 'FlexibleCast');
        })->count() > 0;
    }


    protected function getFlexibleCastColumns($model): array
    {
        $model_class = (new $model);
        $casts = $model_class->getCasts();

        return collect($casts)->filter(function ($cast, $column) use ($model) {
            return Str::contains($cast, 'FlexibleCast');
        })->keys()->toArray();
    }
}
