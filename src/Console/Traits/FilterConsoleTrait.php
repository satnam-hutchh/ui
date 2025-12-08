<?php

namespace Hutchh\Ui\Console\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

trait FilterConsoleTrait
{

	/**
     * Build the filter replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildFilterReplacements(array $replace, ?string $name)
    {
        $filterClass = $this->parseFilter($this->option('filter')??$name);

        if (! class_exists($filterClass) && confirm("A {$filterClass} filter does not exist. Do you want to generate it?", default: true)) {
            $this->call('hutchh:filter', ['name' => $filterClass]);
        }

        $reflector  = parent::getNamespace($filterClass);

        return array_merge($replace, [
            'DummyFullFilterClass'      => "{$reflector} as Filters",
            '{{ namespacedFilter }}'    => "{$reflector} as Filters",
            '{{namespacedFilter}}'      => "{$reflector} as Filters",
            'DummyFilterClass'          => "Filters\\".class_basename($filterClass),
            '{{ filter }}'              => "Filters\\".class_basename($filterClass),
            '{{filter}}'                => "Filters\\".class_basename($filterClass),
            'DummyFilterVariable'       => lcfirst(class_basename($filterClass)),
            '{{ filterVariable }}'      => lcfirst(class_basename($filterClass)),
            '{{filterVariable}}'        => lcfirst(class_basename($filterClass)),
        ]);
    }

    /**
     * Get the fully-qualified filter class name.
     *
     * @param  string  $filter
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseFilter($filter)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $filter)) {
            throw new InvalidArgumentException('Filter name contains invalid characters.');
        }

        return $this->qualifyFilter($filter);
    }

       /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyFilter(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (!Str::endsWith($model, 'Filter')) {
            $model   = $model.'Filter';
        }

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Filters'))
            ? $rootNamespace.'Filters\\'.$model
            : $rootNamespace.$model;
    }
}
