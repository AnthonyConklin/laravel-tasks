<?php

namespace AnthonyConklin\LaravelTasks;

use AnthonyConklin\LaravelTasks\Commands\LaravelTasksCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
