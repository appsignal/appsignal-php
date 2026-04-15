<?php

namespace Appsignal\Tests\stubs;

use Exception;

class BadClass
{
    public static function throw(): void
    {
        throw new RootCauseException('Root cause!');
    }

    public static function throwNested(): void
    {
        throw new OuterWrapperException(
            'OuterWrapper',
            0,
            new MiddleWrapperException(
                'MiddleWrapper',
                0,
                new InnerWrapperException('InnerWrapper')
            )
        );
    }

    public static function throwInClosure(): void
    {
        $callable = function () {
            static::throw();
        };

        $callable();
    }

    public static function throwWithSecrets(string $secret): void
    {
        static::throw();
    }

    public static function throwWithSecretsInClosure(string $secret): void
    {
        $callable = function (string $s) {
            static::throw();
        };

        $callable($secret);
    }
}

class RootCauseException extends Exception {};
class InnerWrapperException extends Exception {};
class MiddleWrapperException extends Exception {};
class OuterWrapperException extends Exception {};
