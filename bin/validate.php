#!/usr/bin/env php
<?php

[$projectRoot, $autoload] = [$argv[1], $argv[2]];

require $autoload;

$environment = match (true) {
    class_exists(\Illuminate\Foundation\Application::class) => new \Appsignal\Environments\Laravel($projectRoot),
    class_exists(\Symfony\Component\HttpKernel\Kernel::class) => new \Appsignal\Environments\Symfony($projectRoot),
    default => new \Appsignal\Environments\Vanilla($projectRoot),
};

$config = $environment->getConfig();

if (!$config->isValid()) {
    echo 'The AppSignal config is invalid. Missing: ' . implode(', ', $config->getMissingFields()) . PHP_EOL;
    exit(1);
}

echo 'The AppSignal config is valid.' . PHP_EOL;
