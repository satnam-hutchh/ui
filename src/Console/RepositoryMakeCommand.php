<?php

namespace Hutchh\Ui\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Illuminate\Support\Str;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Hutchh\Ui\Console\Traits;

#[AsCommand(name: 'hutchh:repo')]
class RepositoryMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;
    use Traits\ModelConsoleTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hutchh:repo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

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
            if (!str_ends_with($name, 'Repository')) {
                $name = $name . 'Repository';
            }
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.$name
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = null;

        if ($this->option('model')) {
            $stub = '/stubs/repositories/repository.model.stub';
        }

        $stub ??= '/stubs/repositories/repository.plain.stub';

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
        return $rootNamespace.'\Repositories';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base repository import if we are already in the base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {

        $replace                = [];
        $requestName            = $this->getNameInput();
        $requestName            = Str::replaceEnd('Repository', '', $requestName);
        $rootNamespace          = $this->rootNamespace();
        $repositoryNamespace    = $this->getNamespace($name);

        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace, $requestName);
        }

        if (!str_ends_with($name, 'Repository')) {
            $name = $name . 'Repository';
        }

        
        

        $baseControllerExists = file_exists($this->getPath("{$rootNamespace}Repositories\abstractRepository"));

        if ($baseControllerExists) {
            $replace["use {$repositoryNamespace}\abstractRepository;\n"] = '';
        } else {
            $replace[' extends abstractRepository'] = '';
            $replace["use {$rootNamespace}Repositories\abstractRepository;\n"] = '';
        }

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model'],
        ];
    }


}
