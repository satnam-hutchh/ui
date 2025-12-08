<?php

namespace Hutchh\Ui\Console\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

trait ResourceConsoleTrait
{

	/**
     * Generate the form requests for the given model and classes.
     *
     * @param  string  $modelClass
     * @param  string  $listResourceClass
     * @param  string  $detailResourceClass
     * @return array
     */
    protected function generateFormResources($modelClass, $listResourceClass, $detailResourceClass)
    {
        $listResourceClass  = $modelClass.'/ListResource';
        $detailResourceClass = $modelClass.'/DetailResource';

        
        $listResourceClass    = $this->parseResource($listResourceClass);
        if (! class_exists($listResourceClass) && confirm("A {$listResourceClass} resource does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:resource', [
                'name' => $listResourceClass,
            ]);
        }

        $detailResourceClass    = $this->parseResource($detailResourceClass);
        if (! class_exists($detailResourceClass) && confirm("A {$detailResourceClass} resource does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:resource', [
                'name' => $detailResourceClass,
            ]);
        }

        return [$listResourceClass, $detailResourceClass];
    }

    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @param  string  $modelClass
     * @return array
     */
    protected function buildFormResourceReplacements(array $replace, $modelClass)
    {
        [$namespace, $listResourceClass, $detailResourceClass] = [
            $modelClass, 'ListResource', 'DetailResource',
        ];

        if ($this->option('resource')) {
            $namespace = 'App\\Http\\Resources';

            $this->generateFormResources(
                $modelClass, $listResourceClass, $detailResourceClass
            );
        }

        $modelClass = ltrim($modelClass, '\\/');

        $modelClass = str_replace('/', '\\', $modelClass);

        $namespacedResources = $namespace.'\\'.$modelClass.'  as Resources';

        return array_merge($replace, [
            '{{ storeRequest }}'        => 'Resources\\'.$listResourceClass,
            '{{storeRequest}}'          => 'Resources\\'.$listResourceClass,
            '{{ updateRequest }}'       => 'Resources\\'.$detailResourceClass,
            '{{updateRequest}}'         => 'Resources\\'.$detailResourceClass,
            '{{ namespacedResources }}'  => $namespacedResources,
            '{{namespacedResources}}'    => $namespacedResources,
        ]);
    }

    /**
     * Get the fully-qualified repository class name.
     *
     * @param  string  $repository
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseResource($repository)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $repository)) {
            throw new InvalidArgumentException('Request name contains invalid characters.');
        }

        return $this->qualifyResource($repository);
    }

       /**
     * Qualify the given repository class base name.
     *
     * @param  string  $repository
     * @return string
     */
    protected function qualifyResource(string $repository)
    {
        $repository = ltrim($repository, '\\/');

        $repository = str_replace('/', '\\', $repository);

        $rootNamespace = $this->rootNamespace();

        if (!Str::endsWith($repository, 'Resource')) {
            $repository   = $repository.'Resource';
        }

        if (Str::startsWith($repository, $rootNamespace)) {
            return $repository;
        }

        return is_dir(app_path('Http/Resources'))
            ? $rootNamespace.'Http\\Resources\\'.$repository
            : $rootNamespace.$repository;
    }
}
