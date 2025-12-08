<?php

namespace Hutchh\Ui\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Illuminate\Support\Str;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'hutchh:type')]
class TypesMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hutchh:type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new type class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

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
            if (!str_ends_with($name, 'Model')) {
                $name = $name . 'Model';
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
        $stub = '/stubs/types/types.plain.stub';

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
        return $rootNamespace.'\Http\Models';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base filter import if we are already in the base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if (!str_ends_with($name, 'Model')) {
            $name = $name . 'Model';
        }

        $rootNamespace = $this->rootNamespace();
        $filterNamespace = $this->getNamespace($name);
        
        $replace = [];

        $baseControllerExists = file_exists($this->getPath("{$rootNamespace}Http\Models\abstractFilter"));

        if ($baseControllerExists) {
            $replace["use {$filterNamespace}\abstractFilter;\n"] = '';
        } else {
            $replace[' extends abstractFilter'] = '';
            $replace["use {$rootNamespace}Http\Models\abstractFilter;\n"] = '';
        }

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }


}
