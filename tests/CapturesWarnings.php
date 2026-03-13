<?php

namespace AppSignal\Tests;

use Closure;

trait CapturesWarnings
{
    /**
     * @param Closure(): void $closure
     */
    protected function callAndCaptureWarnings(Closure $closure): string
    {
        $warning = "";
        set_error_handler(function (int $errorNumber, string $errorString) use (&$warning) {
            $warning = $errorString;
            return true;
        }, E_USER_WARNING);

        try {
            $closure();
        } finally {
            restore_error_handler();
        }
        return $warning;
    }
}
