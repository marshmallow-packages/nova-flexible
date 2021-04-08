<?php

namespace Marshmallow\Nova\Flexible\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeLayoutCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:flex-component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Flexible layout, view component and connecting resource';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Flexible Layout';

    protected $component_class_name = '';
    protected $title = '';
    protected $layout_class = '';
    protected $component_class_path = '';
    protected $subdirectory;
    protected $fields = '';
    protected $use = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name_path = Str::ucfirst(str_replace('.', '/', $this->getView('studly')));

        $this->title = Str::afterLast($name_path, '/');
        if (Str::contains($name_path, '/')) {
            $this->subdirectory = Str::before($name_path, '/');
        }
        $this->component_class_name = $name_path;
        $this->component_class_path = str_replace('/', '\\', '\App\View\Components\\' . $name_path . '::class');

        $this->layout_class = $this->title;

        $this->writeFile('View', $name_path);
        $this->writeFile('Component', $name_path);
        $this->writeFile('Layout', $name_path);

        return 0;
    }


    protected function writeFile($type, $name_path)
    {
        switch ($type) {
            case "Layout":
                $path = app_path('Flexible/Layouts/' . $name_path  . '.php');
                break;
            case "Component":
                $path = app_path('View/Components/' . $name_path  . '.php');
                break;
            case "View":
                $path = resource_path('views/' . str_replace('.', '/', 'components.' . $this->getView())  . '.blade.php');
                break;
        }

        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error($type . ' already exists!');
            return;
        }

        file_put_contents(
            $path,
            $this->parseStubContent($type)
        );
    }

    /**
     * Get the view name relative to the components directory.
     *
     * @return string view
     */
    protected function getView($type = 'kebab')
    {
        $this->name = str_replace('\\', '/', $this->argument('name'));

        return collect(explode('/', $this->name))
            ->map(function ($part) use ($type) {
                return Str::$type($part);
            })
            ->implode('.');
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub($stub_name = 'LayoutFields')
    {
        return (__DIR__ . '/../Stubs/' . $stub_name . '.stub');
    }

    protected function getStubContent($stub_name)
    {
        return file_get_contents(__dir__ . '/../Stubs/' . $stub_name . '.stub');
    }

    protected function parseStubContent($stub_name)
    {
        $content = $this->getStubContent($stub_name);
        return strtr($content, $this->getParams());
    }

    protected function getParams()
    {
        return [
            '{{subdirectory}}' => '\\' . $this->subdirectory ?? null,
            '{{component_class}}' => $this->title,
            '{{name}}' => str_replace('.', '-', $this->getView('slug')),
            '{{component_name}}' => $this->getView('slug'),
            '{{title}}' => $this->title,
            '{{class}}' => $this->layout_class,
            '{{component_class_path}}' => $this->component_class_path,
            '{{fields}}' => $this->fields,
            '{{use}}' => $this->use,
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
        ];
    }
}
