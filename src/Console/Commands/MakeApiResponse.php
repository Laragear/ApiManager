<?php

namespace Laragear\ApiManager\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

use function file_exists;

/**
 * @internal
 */
#[AsCommand('make:api-response', 'Creates a custom Response for a given API class.')]
class MakeApiResponse extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api-response';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a custom Response for a given API class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'API Response';

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return bool|null
     */
    public function handle()
    {
        // If the API Test Api doesn't exist, ask to create it.
        if ($this->apiDoesntExists() && $this->wantsToCreateApi()) {
            $this->createApi();
        }

        return parent::handle();
    }

    /**
     * Check if the API Server file does not exist.
     */
    protected function apiDoesntExists(): bool
    {
        $api = Str::ucfirst($this->argument('api'));

        return !file_exists($this->getPath($this->laravel->getNamespace()."Http\Apis\\$api"));
    }

    /**
     * Ask the user if the missing API Server file should be created.
     */
    protected function wantsToCreateApi(): bool
    {
        $api = Str::ucfirst($this->argument('api'));

        return $this->confirm("The API Server [$api] doesn't exists. Do you want to create it?", true);
    }

    /**
     * Create an API Server by calling another command.
     */
    protected function createApi(): void
    {
        $this->call('make:api', ['name' => Str::studly($this->argument('api'))]);
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        $api = Str::studly($this->argument('api'));

        return $this->laravel->getNamespace()."Http\Apis\\$api\\Responses\\";
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
        return $this->resolveStubPath('/stubs/api-response.stub');
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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['api', InputArgument::REQUIRED, 'The name of the API for the namespace of the custom response'],
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type)],
        ];
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'api' => [
                'What should the api server be named?', 'E.g. Chirper',
            ],
            'name' => [
                'What should the api response be named?', 'E.g. ViewResponse',
            ],
        ];
    }
}
