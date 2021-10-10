# Laravel Tasks

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anthonyconklin/laravel-tasks.svg?style=flat-square)](https://packagist.org/packages/anthonyconklin/laravel-tasks)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/anthonyconklin/laravel-tasks/run-tests?label=tests)](https://github.com/anthonyconklin/laravel-tasks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/anthonyconklin/laravel-tasks/Check%20&%20fix%20styling?label=code%20style)](https://github.com/anthonyconklin/laravel-tasks/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/anthonyconklin/laravel-tasks.svg?style=flat-square)](https://packagist.org/packages/anthonyconklin/laravel-tasks)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require anthonyconklin/laravel-tasks
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="AnthonyConklin\LaravelTasks\LaravelTasksServiceProvider" --tag="laravel-tasks-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
class AddNewUserTask extends LaravelTask {
  public function handle() {
    return User::create($this->all());
  }
}

AddNewUserTask::run(request());
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Anthony Conklin](https://github.com/AnthonyConklin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
