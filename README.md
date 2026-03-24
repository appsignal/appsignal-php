# AppSignal for PHP

The AppSignal for PHP library.

- [AppSignal.com website][appsignal]
- [Documentation][docs]
- [Support][contact]

[![run-tests](https://img.shields.io/github/actions/workflow/status/appsignal/appsignal-php/ci.yml?label=tests&style=flat-square)](https://github.com/appsignal/appsignal-php/actions)

## Installation

Please follow the [installation guide](https://docs.appsignal.com/php/installation) in our documentation. We try to automatically instrument as many packages as possible, but may not always be able to. Make to sure follow any [instructions to add manual instrumentation](https://docs.appsignal.com/php/integrations).

## Development

### Installation

This package uses [Composer](https://getcomposer.org) as the dependency manager. First, make sure you have Composer installed. Then install the dependencies and prepare the project for development:

```bash
composer install
```

If you'd rather install PHP and Composer using Docker, there's no need to run a separate `composer install` command. You can run any Composer command in a Docker container using the `scripts/call_composer` command:

```bash
scripts/call_composer any_composer_command_or_script
```

### Testing

The tests for this library use [PHPUnit](https://phpunit.de/index.html) as the test runner and framework. Once you've installed the dependencies, run the following command in the root of this repository to run all tests:

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
scripts/call_composer cs:fix   # check
```

## Contributing

Thinking of contributing to this repo? Awesome! 🚀

Please follow our [Contributing guide][contributing-guide] in our documentation and follow our [Code of Conduct][coc].

Also, we would be very happy to send you Stroopwafels. Have look at everyone we have sent a package to so far on our [Stroopwafels page][waffles-page].

## Support

[Contact us][contact] and speak directly with the engineers working on AppSignal. They will help you get set up, tweak your code and make sure you get the most out of using AppSignal.

[appsignal]: https://www.appsignal.com/nodejs
[appsignal-sign-up]: https://appsignal.com/users/sign_up
[contact]: mailto:support@appsignal.com
[coc]: https://docs.appsignal.com/appsignal/code-of-conduct.html
[waffles-page]: https://www.appsignal.com/waffles
[docs]: https://docs.appsignal.com/php
[contributing-guide]: http://docs.appsignal.com/appsignal/contributing.html
