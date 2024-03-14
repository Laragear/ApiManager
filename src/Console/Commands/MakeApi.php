<?php

namespace Laragear\ApiManager\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function class_basename;
use function file_exists;
use function str_replace;
use function trim;

/**
 * @internal
 */
#[AsCommand('make:api', 'Creates a new API class to simplify server-side requests')]
class MakeApi extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new API class to simplify server-side requests';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'API Server';

    /**
     * Build the class with the given name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        return str_replace('dummy-class', Str::snake(class_basename($name), '-'), parent::buildClass($name));
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace().'Http\Apis\\';
    }

    /**
     * Get the destination class path.
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/api.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../../../'.$stub;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => [
                'What should the '.strtolower($this->type).' be named?', 'E.g. Chirp',
            ],
        ];
    }
}
