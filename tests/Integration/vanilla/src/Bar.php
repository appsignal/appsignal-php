<?php

namespace App;

use Exception;
use Throwable;

class Bar
{
    public static function baz(): void
    {
        throw new Exception('Inner');
    }

    public static function nestedBaz(): void
    {
        try {
            static::baz();
        } catch (Throwable $e) {
            throw new Exception('Wrapper', 0, $e);
        }
    }
}
