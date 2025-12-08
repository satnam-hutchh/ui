<?php

namespace Hutchh\Ui\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Illuminate\Support\Str;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

use Hutchh\Ui\Console\Traits;

#[AsCommand(name: 'hutchh:controller')]
class ControllerMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    use Traits\RepositoryConsoleTrait;
    use Traits\FilterConsoleTrait;
    use Traits\TypesConsoleTrait;
    use Traits\ModelConsoleTrait;
    use Traits\RequestConsoleTrait;
    use Traits\ResourceConsoleTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hutchh:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = null;

        if ($type = $this->option('type')) {
            $stub = "/stubs/controller.{$type}.stub";
        } elseif ($this->option('parent')) {
            $stub = $this->option('singleton')
                ? '/stubs/controller.nested.singleton.stub'
                : '/stubs/controller.nested.stub';
        } elseif ($this->option('model')) {
            $stub = '/stubs/controller.model.stub';
        } elseif ($this->option('invokable')) {            
            $stub = '/stubs/controller.invokable.stub';
        } elseif ($this->option('singleton')) {
            $stub = '/stubs/controller.singleton.stub';
        } elseif ($this->option('resource')) {
            $stub = '/stubs/controller.stub';
        }

        if ($this->option('api') && is_null($stub)) {
            $stub = '/stubs/controller.stub';
        } elseif ($this->option('api') && ! is_null($stub) && ! $this->option('invokable')) {
            $stub = str_replace('.stub', '.api.stub', $stub);
        }

        $stub ??= '/stubs/controller.plain.stub';

        return $this->resolveStubPath($stub);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->option('api')){
            return $rootNamespace.'\Http\Controllers\Api';
        }
        return $rootNamespace.'\Http\Controllers';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in the base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $requestName            = $this->getNameInput();
        $requestName            = Str::replaceEnd('Controller', '', $requestName);
        $rootNamespace          = $this->rootNamespace();
        $controllerNamespace    = $this->getNamespace($name);

        $replace = [];

        if ($this->option('parent')) {
            $replace = $this->buildParentReplacements();
        }

        if ($this->option('model') || $this->option('resource')) {
            $replace = $this->buildModelReplacements($replace, $requestName);
        }

        if ($this->option('filter') || $this->option('resource')) {
            $replace = $this->buildFilterReplacements($replace, $requestName);
        }

        if ($this->option('types') || $this->option('resource')) {
            $replace = $this->buildTypesReplacements($replace, $requestName);
        }

        if ($this->option('repo') || $this->option('resource')) {
            $replace = $this->buildRepositoryModelReplacements($replace, $requestName);
        }

        if ($this->option('requests') || $this->option('resource')) {
            $replace = $this->buildFormRequestReplacements($replace, $requestName);
        }

        if ($this->option('resource')) {
            $replace = $this->buildFormResourceReplacements($replace, $requestName);
        }

        if ($this->option('creatable')) {
            $replace['abort(404);'] = '//';
        }

        if (!str_ends_with($name, 'Controller')) {
            $name = $name . 'Controller';
        }

        $baseControllerExists = file_exists($this->getPath("{$rootNamespace}Http\Controllers\Controller"));

        if ($baseControllerExists) {
            $replace["use {$controllerNamespace}\Controller;\n"] = '';
        } else {
            $replace[' extends Controller'] = '';
            $replace["use {$rootNamespace}Http\Controllers\Controller;\n"] = '';
        }

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {            
            if (!Str::endsWith($name, 'Controller')) {
                $name   = $name.'Controller';
            }
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.$name
        );
    }

    /**
     * Build the replacements for a parent controller.
     *
     * @return array
     */
    protected function buildParentReplacements()
    {
        $parentModelClass = $this->parseModel($this->option('parent'));

        if (! class_exists($parentModelClass) &&
            confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:model', ['name' => $parentModelClass]);
        }

        return [
            'ParentDummyFullModelClass'     => $parentModelClass,
            '{{ namespacedParentModel }}'   => $parentModelClass,
            '{{namespacedParentModel}}'     => $parentModelClass,
            'ParentDummyModelClass'         => class_basename($parentModelClass),
            '{{ parentModel }}'             => class_basename($parentModelClass),
            '{{parentModel}}'               => class_basename($parentModelClass),
            'ParentDummyModelVariable'      => lcfirst(class_basename($parentModelClass)),
            '{{ parentModelVariable }}'     => lcfirst(class_basename($parentModelClass)),
            '{{parentModelVariable}}'       => lcfirst(class_basename($parentModelClass)),
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
            ['api', null, InputOption::VALUE_NONE, 'Exclude the create and edit methods from the controller'],
            ['type', null, InputOption::VALUE_REQUIRED, 'Manually specify the controller stub file to use'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the controller already exists'],
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate a single method, invokable controller class'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model'],
            ['filter', 'f', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given filter'],
            ['types', 't', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given types'],
            ['repo', 'rp', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given repository'],
            ['parent', 'p', InputOption::VALUE_OPTIONAL, 'Generate a nested resource controller class'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller class'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Generate FormRequest classes for store and update'],
            ['singleton', 's', InputOption::VALUE_NONE, 'Generate a singleton resource controller class'],
            ['creatable', null, InputOption::VALUE_NONE, 'Indicate that a singleton resource should be creatable'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $type = select('Which type of controller would you like?', [
            'empty'     => 'Empty',
            'resource'  => 'Resource',
            'singleton' => 'Singleton',
            'api'       => 'API',
            'invokable' => 'Invokable',
        ]);

        if ($type !== 'empty') {
            $input->setOption($type, true);
        }

        if (in_array($type, ['api', 'resource', 'singleton'])) {
            $model = suggest(
                "What model is this $type controller for? (Optional)",
                $this->findAvailableModels()
            );

            if ($model) {
                $input->setOption('model', $model);
            }
        }
    }
}
