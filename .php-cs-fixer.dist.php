<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/config-stubs')
    ->exclude('vendor');

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true,
    ])
    ->setFinder($finder)
;
