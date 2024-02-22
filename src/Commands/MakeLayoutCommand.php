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
    protected $lines = [];

    protected $component_class_name = '';
    protected $title = '';
    protected $layout_class = '';
    protected $component_class_path = '';
    protected $subdirectory;
    protected $fields = '';
    protected $use = '';
    protected $tags = '"Custom"';
    protected $template_files = [];

    protected $livewire_component = true;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name_path = Str::ucfirst(str_replace('.', '/', $this->getView('studly')));
        $this->livewire_component = $this->option('livewire') || $this->option('template');

        $this->title = Str::afterLast($name_path, '/');
        if (Str::contains($name_path, '/')) {
            $this->subdirectory = '\\' . Str::beforeLast($name_path, '/');
            $this->subdirectory =  Str::replace('/', '\\', $this->subdirectory);
        }

        $this->component_class_name = $name_path;

        $this->component_class_path = $this->livewire_component
            ? str_replace('/', '\\', '\App\Http\Livewire\\' . $name_path . '::class')
            : str_replace('/', '\\', '\App\View\Components\\' . $name_path . 'Component::class');

        $tags = explode('\\', $this->subdirectory);
        $tags = array_filter($tags);

        if ($this->livewire_component) {
            $tags[] = 'Livewire';
        }

        foreach ($tags as $tag) {
            $this->tags .= '"' . $tag . '"';
        }

        $this->tags = Str::replace('""', '", "', $this->tags);

        $this->layout_class = $this->title . 'Layout';

        if ($template = $this->option('template')) {
            $created = $this->{$template}();
            if ($created) {
                $this->line("<options=bold,reverse;fg=green>FLEXIBLE COMPONENT CREATED </> 🤙\n");
                foreach ($this->lines as $line) {
                    $this->line($line);
                }
            }
            return;
        }

        $this->writeFile('View', $name_path);

        if ($this->livewire_component) {
            $this->writeFile('LivewireComponent', $name_path);
        } else {
            $this->writeFile('Component', $name_path);
        }
        $this->writeFile('Layout', $name_path);

        if (empty($this->lines)) {
            $this->line("<options=bold,reverse;fg=red>FLEXIBLE COMPONENT NOT CREATED </> \n");
            return 0;
        }

        $this->line("<options=bold,reverse;fg=green>FLEXIBLE COMPONENT CREATED </> 🤙\n");
        foreach ($this->lines as $line) {
            $this->line($line);
        }

        if (!config('flexible.auto-discovery') || true) {
            $slug_name      = str_replace('.', '-', $this->getView());
            $layout_name    = str_replace('/', '\\', $this->layout_class);
            $layout_path    = '\App\Flexible\Layouts' . $this->subdirectory . '\\' . $layout_name . '::class';

            $text = PHP_EOL . 'Dont forget to add: ' . PHP_EOL;
            $text .= "'{$slug_name}' => {$layout_path}" . PHP_EOL;
            $text .= 'to config/flexible.php';
            $this->line($text);
        }

        if ($this->livewire_component) {
            $this->newLine();
            $this->line("<options=bold,reverse;fg=magenta>Please note: Flexible Livewire Components are still in beta. Please add any issue to the package github page. 🤙 </>");
        }

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
            case "LivewireComponent":
                $relative_path = 'Http/Livewire/' . $name_path  . '.php';
                $path = app_path($relative_path);
                break;
            case "View":
                $relative_path = $this->livewire_component
                    ? 'views/' . str_replace('.', '/', 'livewire.' . $this->getView())  . '.blade.php'
                    : 'views/' . str_replace('.', '/', 'components.' . $this->getView())  . '.blade.php';

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

    protected function parseTemplateStubContent($stub_file)
    {
        $stub_content = file_get_contents($stub_file);
        // dd($this->getParams());
        return strtr($stub_content, $this->getParams());
    }

    protected function getParams()
    {
        $component_class = $this->livewire_component
            ? $this->title
            : $this->title . 'Component';

        return [
            '{{subdirectory}}' =>  $this->subdirectory ?? null,
            '{{component_class}}' => $component_class,
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

    protected function addTemplateFile(string $target, string $stub)
    {
        $this->template_files[$target] = __dir__ . "/../Stubs/Templates/{$stub}";
        return $this;
    }

    protected function newsletter(): bool
    {
        $component_class_name = $this->component_class_name;
        $view_path = Str::of($this->getView())->replace('.', '/')->__toString();

        $this->addTemplateFile(
            target: "/app/Flexible/Layouts/{$component_class_name}Layout.php",
            stub: 'Newsletter/NewsletterLayout.stub'
        )->addTemplateFile(
            target: "/app/Http/Livewire/{$component_class_name}.php",
            stub: 'Newsletter/Newsletter.stub'
        )->addTemplateFile(
            target: "/resources/views/livewire/{$view_path}.blade.php",
            stub: 'Newsletter/newsletter.blade.stub'
        );

        return $this->generateTemplate();
    }

    protected function form(): bool
    {
        $component_class_name = $this->component_class_name;
        $view_path = Str::of($this->getView())->replace('.', '/')->__toString();
        $blade_file = class_exists('Marshmallow\Components\ComponentsServiceProvider')
            ? 'form-mm-components'
            : 'form';

        $this->addTemplateFile(
            target: "/app/Flexible/Layouts/{$component_class_name}Layout.php",
            stub: 'Form/FormLayout.stub'
        )->addTemplateFile(
            target: "/app/Http/Livewire/{$component_class_name}.php",
            stub: 'Form/Form.stub'
        )->addTemplateFile(
            target: "/resources/views/livewire/{$view_path}.blade.php",
            stub: "Form/{$blade_file}.blade.stub"
        )->addTemplateFile(
            target: "/resources/views/emails/{$view_path}/admin.blade.php",
            stub: 'Form/mail-to-admin.blade.stub'
        )->addTemplateFile(
            target: "/resources/views/emails/{$view_path}/customer.blade.php",
            stub: 'Form/mail-to-customer.blade.stub'
        )->addTemplateFile(
            target: "/app/Mail/{$component_class_name}/MailToAdmin.php",
            stub: 'Form/MailToAdmin.stub'
        )->addTemplateFile(
            target: "/app/Mail/{$component_class_name}/MailToCustomer.php",
            stub: 'Form/MailToCustomer.stub'
        )->addTemplateFile(
            target: "/app/Jobs/{$component_class_name}/SendMailToAdmin.php",
            stub: 'Form/SendFormMailToAdmin.stub'
        )->addTemplateFile(
            target: "/app/Jobs/{$component_class_name}/SendMailToCustomer.php",
            stub: 'Form/SendFormMailToCustomer.stub'
        );


        // [] form.blade.stub
        // [] Form.stub
        // [] FormLayout.stub
        // [] mail-to-admin.blade.stub
        // [] mail-to-customer.blade.stub
        // [] MailToAdmin.stub
        // [] MailToCustomer.stub
        // [] SendFormMailToAdmin.stub
        // [] SendFormMailToCustomer.stub

        return $this->generateTemplate();
    }

    protected function generateTemplate(): bool
    {
        $exists_errors = '';
        $files = collect($this->template_files)->each(function ($stub, $target) use (&$exists_errors) {
            $path = base_path($target);
            if ($this->files->exists($path) && !$this->option('force')) {
                $exists_errors .= "{$target} already exists" . PHP_EOL;
            }

            if (!$this->files->isDirectory(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0777, true, true);
            }
        });

        if ($exists_errors) {
            $this->error($exists_errors);
            return false;
        }

        $files->each(function ($stub, $target) {
            if (Str::startsWith($target, '/')) {
                $target = Str::substr($target, 1);
            }
            $this->lines[] = "Created {$target}";
            file_put_contents(
                base_path($target),
                $this->parseTemplateStubContent($stub)
            );
        });

        $line = "<options=bold;fg=green>Newsletter templates is created!</>";

        $this->lines[] = $line;

        return true;
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
            ['livewire', null, InputOption::VALUE_NONE, 'Handle this as a livewire component'],
            ['template', null, InputOption::VALUE_REQUIRED, 'Create component from a template'],
        ];
    }
}
