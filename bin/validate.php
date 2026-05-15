#!/usr/bin/env php
<?php

[$projectRoot, $configPath, $autoload] = [$argv[1], $argv[2], $argv[3]];

require $autoload;

if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

$config = \Appsignal\Config::load($configPath);

if (!$config->isValid()) {
    echo 'Appsignal config is invalid. Missing: ' . implode(', ', $config->getMissingFields()) . PHP_EOL;
    exit(1);
}

echo 'Appsignal config is valid.' . PHP_EOL;
