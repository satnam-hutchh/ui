<?php

namespace Hutchh\Ui\Console\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

trait TypesConsoleTrait
{

	/**
     * Build the types replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildTypesReplacements(array $replace, ?string $name)
    {
        $typesClass = $this->parseTypes($this->option('types')??$name);

        if (! class_exists($typesClass) && confirm("A {$typesClass} types does not exist. Do you want to generate it?", default: true)) {
            $this->call('hutchh:type', ['name' => $typesClass]);
        }

        $reflector  = parent::getNamespace($typesClass);

        return array_merge($replace, [
            'DummyFullTypesClass'   => "{$reflector} as Models",
            '{{ namespacedTypes }}' => "{$reflector} as Models",
            '{{namespacedTypes}}'   => "{$reflector} as Models",

            'DummyTypesClass'       => "Models\\".class_basename($typesClass),
            '{{ types }}'           => "Models\\".class_basename($typesClass),
            '{{types}}'             => "Models\\".class_basename($typesClass),

            'DummyTypesVariable'    => lcfirst(class_basename($typesClass)),
            '{{ typesVariable }}'   => lcfirst(class_basename($typesClass)),
            '{{typesVariable}}'     => lcfirst(class_basename($typesClass)),
        ]);
    }

    /**
     * Get the fully-qualified types class name.
     *
     * @param  string  $types
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseTypes($types)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $types)) {
            throw new InvalidArgumentException('Types name contains invalid characters.');
        }

        return $this->qualifyTypes($types);
    }

       /**
     * Qualify the given types class base name.
     *
     * @param  string  $types
     * @return string
     */
    protected function qualifyTypes(string $types)
    {
        $types = ltrim($types, '\\/');

        $types = str_replace('/', '\\', $types);

        $rootNamespace = $this->rootNamespace();

        if (!Str::endsWith($types, 'Model')) {
            $types   = $types.'Model';
        }

        if (Str::startsWith($types, $rootNamespace)) {
            return $types;
        }

        return is_dir(app_path('Http/Models'))
            ? $rootNamespace.'Http\\Models\\'.$types
            : $rootNamespace.$types;
    }
}
