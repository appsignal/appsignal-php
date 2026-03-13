<?php

namespace AppSignal\Environments;

use AppSignal\Config;
use AppSignal\Patches\Symfony\HttpRoutePatch;
use AppSignal\Patches\Symfony\LoggerPatch;

class Symfony implements Environment
{
    use HasPatches;

    /** @var array<int, class-string|object> */
    protected array $patches = [
        HttpRoutePatch::class,
        //LoggerPatch::class,
    ];

    public function __construct(protected ?string $basePath = null) {}

    public function getConfig(): Config
    {
        return Config::tryFromFile($this->basePath . Config::CONFIG_PATH);
    }
}
