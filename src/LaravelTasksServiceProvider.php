<?php

namespace AnthonyConklin\LaravelTasks;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AnthonyConklin\LaravelTasks\Commands\LaravelTasksCommand;

class LaravelTasksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-tasks')
            ->hasConfigFile()
            ->hasCommand(LaravelTasksCommand::class);
    }
}
