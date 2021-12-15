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
    protected $name = 'make:flex';

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
    protected $lines;

    protected $component_class_name = '';
    protected $title = '';
    protected $layout_class = '';
    protected $component_class_path = '';
    protected $subdirectory;
    protected $fields = '';
    protected $use = '';
    protected $tags = '"Custom"';

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
            $this->subdirectory = '\\' . Str::beforeLast($name_path, '/');
            $this->subdirectory =  Str::replace('/', '\\', $this->subdirectory);
        }

        $this->component_class_name = $name_path;
        $this->component_class_path = str_replace('/', '\\', '\App\View\Components\\' . $name_path . 'Component::class');


        $tags = explode('\\', $this->subdirectory);
        foreach ($tags as $tag) {
            if ($tag == "") {
                continue;
            }

            $this->tags .= '"' . $tag . '"';
        }

        $this->tags = Str::replace('""', '", "', $this->tags);

        $this->layout_class = $this->title . 'Layout';

        $this->writeFile('View', $name_path);
        $this->writeFile('Component', $name_path);
        $this->writeFile('Layout', $name_path);

        if (empty($this->lines)) {
            $this->line("<options=bold,reverse;fg=red>FLEXIBLE COMPONENT NOT CREATED </> \n");
            return 0;
        }

        $this->line("<options=bold,reverse;fg=green>FLEXIBLE COMPONENT CREATED </> 🤙\n");
        foreach ($this->lines as $line) {
            $this->line($line);
        }

        //Remove if Autodiscovery Enabled
        $slug_name      = str_replace('.', '-', $this->getView());
        $layout_name    = str_replace('/', '\\', $this->layout_class);
        $layout_path    = '\App\Flexible\Layouts' . $this->subdirectory . '\\' . $layout_name . '::class';

        $text = PHP_EOL . 'Dont forget to add: ' . PHP_EOL;
        $text .= "'{$slug_name}' => {$layout_path}" . PHP_EOL;
        $text .= 'to config/flexible.php';

        $this->line($text);

        return 0;
    }


    protected function writeFile($type, $name_path)
    {
        switch ($type) {
            case "Layout":
                $relative_path = 'Flexible/Layouts/' . $name_path  . 'Layout.php';
                $path = app_path($relative_path);
                break;
            case "Component":
                $relative_path = 'View/Components/' . $name_path  . 'Component.php';
                $path = app_path($relative_path);
                break;
            case "View":
                $relative_path = 'views/' . str_replace('.', '/', 'components.' . $this->getView())  . '.blade.php';
                $path = resource_path($relative_path);
                break;
        }

        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error($type . ' already exists!');
            return false;
        }

        file_put_contents(
            $path,
            $this->parseStubContent($type)
        );

        $relative_path = ($type == 'View' ? 'resources/' : 'app/') . $relative_path;
        $type_name = Str::upper($type);
        $line = "<options=bold;fg=green>{$type_name}:</> {$relative_path}";

        $this->lines[] = $line;
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
            '{{subdirectory}}' =>  $this->subdirectory ?? null,
            '{{component_class}}' => $this->title . 'Component',
            '{{name}}' => str_replace('.', '-', $this->getView()),
            '{{component_name}}' => $this->getView(),
            '{{title}}' => $this->title,
            '{{class}}' => $this->layout_class,
            '{{component_class_path}}' => $this->component_class_path,
            '{{fields}}' => $this->fields,
            '{{use}}' => $this->use,
            '{{tags}}' => $this->tags,
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
