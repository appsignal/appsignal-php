# AppSignal for PHP

The AppSignal for PHP library.

![Tests](https://github.com/appsignal/appsignal-php/actions/workflows/ci.yml/badge.svg)

## Installation

Install the latest version of AppSignal with:

```bash
composer require appsignal/appsignal-php`
```

> [!IMPORTANT]
> This package depends on `opentelemetry` PHP extension. Make sure you have it installed.

## Installing `opentelemetry` PHP extension

First, make sure the build dependencies required to install the OpenTelemetry PHP extension are installed.

On Ubuntu/Debian:
```bash
sudo apt-get install gcc make autoconf
```
or macOS:
```bash
brew install gcc make autoconf
```

Then, install the OpenTelemetry PHP extension using PECL:
```bash
pecl install opentelemetry
```

Finally, enable the extension in `php.ini`
```ini
[opentelemetry]
extension=opentelemetry.so
```

For more ways to install `opentelemetry` extension (pie, pickle, Docker), see the [Installing the OpenTelemetry extension](https://docs.appsignal.com/php/installation.html#install-the-opentelemetry-php-extension) section in AppSignal Docs.

## Basic usage

> [!TIP]
> For Laravel application auto-instrumentation install `open-telemetry/opentelemetry-auto-laravel` package.
> For Symfony application auto-instrumentation install `open-telemetry/opentelemetry-auto-symfony` package.

```php
use Appsignal\Appsignal;

// add a custom instrumentation span to the current trace
Appsignal::instrument('some_event', fn() => sleep(1));

// add a custom instrumentation span to the current trace with data
Appsignal::instrument('some_event', ['region' => 'eu'], fn() => sleep(1));

// customize the name of the trace
Appsignal::setAction('my action'),

// add attributes to the current span
Appsignal::addAttributes([
    'string-attribute' => 'abcdef',
    'int-attribute' => 1234,
    'bool-attribute' => true,
]);

// add tags to the current span
Appsignal::addTags([
    'string-tag' => 'some value',
    'integer-tag' => 1234,
    'bool-tag' => true,
]);

// report a handled exception
Appsignal::setError($exception);

// add metrics
Appsignal::setGauge('my_gauge', 12);
Appsignal::setGauge('my_gauge_with_attributes', 13, ['region' => 'eu']);

Appsignal::addDistributionValue('memory_usage', 50);
Appsignal::addDistributionValue('memory_usage', 70);

Appsignal::addDistributionValue('with_attributes', 10, ['region' => 'eu']);
Appsignal::addDistributionValue('with_attributes', 20, ['region' => 'eu']);
Appsignal::addDistributionValue('with_attributes', 30, ['region' => 'eu']);

Appsignal::incrementCounter('my_counter', 1);
Appsignal::incrementCounter('my_counter', 3, ['region' => 'eu']);
```

## Development

### Installation

This package uses [Composer](https://getcomposer.org) as the dependency manager. Once you have Composer installed, install all dependencies:

```bash
composer install
```

If you'd rather not install PHP and Composer locally, you can run Composer commands in a Docker container using the `scripts/call_composer` command:

```bash
scripts/call_composer any_composer_command_or_script
```

### Testing

Run the following command in the root of this repository to run all [PHPUnit](https://phpunit.de/index.html) tests:

```bash
composer test

# or with Docker
scripts/test
```

or run a single test file:

```bash
composer test tests/Path/To/YourTest.php

# or with Docker
scripts/test tests/Path/To/YourTest.php
```

or a single test case:

```bash
composer test --filter testMethodName

# or with Docker
scripts/test --filter testMethodName
```

This package supports PHP versions `>= 8.4`. If you want to run tests against a specific version of PHP, pass `PHP_VERSION` environment variable to the `scripts/test` command:

```bash
PHP_VERSION=8.5 scripts/test
```

### Linting

You can run PHPStan anaylzer:

```bash
composer lint

# or with Docker
scripts/call_composer lint
```

or run PHP Coding Standards Fixer:

```bash
composer cs                    # check
composer cs:fix                # fix

# or with Docker
scripts/call_composer cs       # check
scripts/call_composer cs:fix   # fix
```

## Contributing

Thinking of contributing to this repo? Awesome! 🚀

Please follow our [Contributing guide][contributing-guide] in our documentation and follow our [Code of Conduct][coc].

Also, we would be very happy to send you Stroopwafels. Have look at everyone we have sent a package to so far on our [Stroopwafels page][waffles-page].

## Support

[Contact us][contact] and speak directly with the engineers working on AppSignal. They will help you get set up, tweak your code and make sure you get the most out of using AppSignal.

[appsignal]: https://www.appsignal.com/php
[appsignal-sign-up]: https://appsignal.com/users/sign_up
[contact]: mailto:support@appsignal.com
[coc]: https://docs.appsignal.com/appsignal/code-of-conduct.html
[waffles-page]: https://www.appsignal.com/waffles
[docs]: https://docs.appsignal.com/php
[contributing-guide]: http://docs.appsignal.com/appsignal/contributing.html
