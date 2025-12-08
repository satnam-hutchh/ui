<?php

namespace Hutchh\Ui\Console\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

trait RepositoryConsoleTrait
{

	/**
     * Build the repository replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildRepositoryModelReplacements(array $replace, ?string $name)
    {
        $repositoryClass    = $this->parseRepository($this->option('repo')??$name);
        $modelName          = $this->parseModel($this->option('model')??$name);
        
        if (! class_exists($repositoryClass) && confirm("A {$repositoryClass} repository does not exist. Do you want to generate it?", default: true)) {
            $this->call('hutchh:repo', array_filter([
                'name' => "{$repositoryClass}",
                '--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
            ]));
        }

        $reflector  = parent::getNamespace($repositoryClass);

        return array_merge($replace, [
            'DummyFullRepositoryClass'      => "{$reflector} as Repositories",
            '{{ namespacedRepository }}'    => "{$reflector} as Repositories",
            '{{namespacedRepository}}'      => "{$reflector} as Repositories",

            'DummyRepositoryClass'          => "Repositories\\".class_basename($repositoryClass),
            '{{ repository }}'              => "Repositories\\".class_basename($repositoryClass),
            '{{repository}}'                => "Repositories\\".class_basename($repositoryClass),

            'DummyRepositoryVariable'       => lcfirst(class_basename($repositoryClass)),
            '{{ repositoryVariable }}'      => lcfirst(class_basename($repositoryClass)),
            '{{repositoryVariable}}'        => lcfirst(class_basename($repositoryClass)),
        ]);
    }

	/**
     * Build the repository replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildRepositoryReplacements(array $replace, ?string $name)
    {
        $repositoryClass    = $this->parseRepository($this->option('repo')??$name);

        if (! class_exists($repositoryClass) && confirm("A {$repositoryClass} repository does not exist. Do you want to generate it?", default: true)) {
            $this->call('hutchh:repo', ['name' => $repositoryClass]);
        }

        $reflector  = parent::getNamespace($repositoryClass);

        return array_merge($replace, [
            'DummyFullRepositoryClass'      => "{$reflector} as Repositories",
            '{{ namespacedRepository }}'    => "{$reflector} as Repositories",
            '{{namespacedRepository}}'      => "{$reflector} as Repositories",

            'DummyRepositoryClass'          => "Repositories\\".class_basename($repositoryClass),
            '{{ repository }}'              => "Repositories\\".class_basename($repositoryClass),
            '{{repository}}'                => "Repositories\\".class_basename($repositoryClass),

            'DummyRepositoryVariable'       => lcfirst(class_basename($repositoryClass)),
            '{{ repositoryVariable }}'      => lcfirst(class_basename($repositoryClass)),
            '{{repositoryVariable}}'        => lcfirst(class_basename($repositoryClass)),
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
    protected function parseRepository($repository)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $repository)) {
            throw new InvalidArgumentException('Repository name contains invalid characters.');
        }

        return $this->qualifyRepository($repository);
    }

       /**
     * Qualify the given repository class base name.
     *
     * @param  string  $repository
     * @return string
     */
    protected function qualifyRepository(string $repository)
    {
        $repository = ltrim($repository, '\\/');

        $repository = str_replace('/', '\\', $repository);

        $rootNamespace = $this->rootNamespace();

        if (!Str::endsWith($repository, 'Repository')) {
            $repository   = $repository.'Repository';
        }

        if (Str::startsWith($repository, $rootNamespace)) {
            
            return $repository;
        }

        return is_dir(app_path('Repositories'))
            ? $rootNamespace.'Repositories\\'.$repository
            : $rootNamespace.$repository;
    }
}
