<?php

namespace AnthonyConklin\LaravelTasks;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AnthonyConklin\LaravelTasks\LaravelTask
 */
class LaravelTasksFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-tasks';
    }
}
