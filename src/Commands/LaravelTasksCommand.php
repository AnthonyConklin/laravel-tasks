<?php

namespace AnthonyConklin\LaravelTasks\Commands;

use Illuminate\Console\Command;

class LaravelTasksCommand extends Command
{
    public $signature = 'laravel-tasks';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
