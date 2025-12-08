<?php

namespace Hutchh\Ui\Console\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

trait RequestConsoleTrait
{

	/**
     * Generate the form requests for the given model and classes.
     *
     * @param  string  $modelClass
     * @param  string  $storeRequestClass
     * @param  string  $updateRequestClass
     * @return array
     */
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        $storeRequestClass  = $modelClass.'/StoreRequest';
        $updateRequestClass = $modelClass.'/UpdateRequest';

        
        $storeRequestClass    = $this->parseRequest($storeRequestClass);
        if (! class_exists($storeRequestClass) && confirm("A {$storeRequestClass} request does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:request', [
                'name' => $storeRequestClass,
            ]);
        }

        $updateRequestClass    = $this->parseRequest($updateRequestClass);
        if (! class_exists($updateRequestClass) && confirm("A {$updateRequestClass} request does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:request', [
                'name' => $updateRequestClass,
            ]);
        }

        return [$storeRequestClass, $updateRequestClass];
    }

    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @param  string  $modelClass
     * @return array
     */
    protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        [$namespace, $storeRequestClass, $updateRequestClass] = [
            $modelClass, 'StoreRequest', 'updateRequest',
        ];

        $namespace = 'App\\Http\\Requests';

        $this->generateFormRequests(
            $modelClass, $storeRequestClass, $updateRequestClass
        );

        $modelClass = ltrim($modelClass, '\\/');

        $modelClass = str_replace('/', '\\', $modelClass);

        $namespacedRequests = $namespace.'\\'.$modelClass.'  as Requests';

        return array_merge($replace, [
            '{{ storeRequest }}'        => 'Requests\\'.$storeRequestClass,
            '{{storeRequest}}'          => 'Requests\\'.$storeRequestClass,
            '{{ updateRequest }}'       => 'Requests\\'.$updateRequestClass,
            '{{updateRequest}}'         => 'Requests\\'.$updateRequestClass,
            '{{ namespacedRequests }}'  => $namespacedRequests,
            '{{namespacedRequests}}'    => $namespacedRequests,
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
    protected function parseRequest($repository)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $repository)) {
            throw new InvalidArgumentException('Request name contains invalid characters.');
        }

        return $this->qualifyRequest($repository);
    }

       /**
     * Qualify the given repository class base name.
     *
     * @param  string  $repository
     * @return string
     */
    protected function qualifyRequest(string $repository)
    {
        $repository = ltrim($repository, '\\/');

        $repository = str_replace('/', '\\', $repository);

        $rootNamespace = $this->rootNamespace();

        if (!Str::endsWith($repository, 'Request')) {
            $repository   = $repository.'Request';
        }

        if (Str::startsWith($repository, $rootNamespace)) {
            return $repository;
        }

        return is_dir(app_path('Http/Requests'))
            ? $rootNamespace.'Http\\Requests\\'.$repository
            : $rootNamespace.$repository;
    }
}
