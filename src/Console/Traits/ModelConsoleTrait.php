<?php

namespace Hutchh\Ui\Console\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

trait ModelConsoleTrait
{

	/**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace, ?string $name)
    {
        $modelClass = $this->parseModel($this->option('model')??$name);

        if (! class_exists($modelClass) && confirm("A {$modelClass} model does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:model', ['name' => $modelClass]);
        }
        
        $reflector  = parent::getNamespace($modelClass);

        return array_merge($replace, [
            'DummyFullModelClass'   => "{$reflector} as Eloquents",
            '{{ namespacedModel }}' => "{$reflector} as Eloquents",
            '{{namespacedModel}}'   => "{$reflector} as Eloquents",
            'DummyModelClass'       => "Eloquents\\".class_basename($modelClass),
            '{{ model }}'           => "Eloquents\\".class_basename($modelClass),
            '{{model}}'             => "Eloquents\\".class_basename($modelClass),
            'DummyModelVariable'    => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}'   => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}'     => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

       /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyModel(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }
}
