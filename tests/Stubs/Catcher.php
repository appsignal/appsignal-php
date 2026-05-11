<?php

namespace Appsignal\Tests\Stubs;

use Throwable;

class Catcher
{
    /**
     * @param callable(): mixed $call
     */
    public static function callAndCapture(callable $call): Throwable
    {
        try {
            $call();
        } catch (Throwable $e) {
            return $e;
        }

        throw new \RuntimeException("Expected callable to throw");
    }
}
