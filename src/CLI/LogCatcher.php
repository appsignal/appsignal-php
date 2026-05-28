<?php

namespace Appsignal\CLI;

use Psr\Log\AbstractLogger;

class LogCatcher extends AbstractLogger
{
    /** @var string[] */
    public array $errors = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($level === 'error') {
            $this->errors[] = (string) $message;
        }
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
