<?php

namespace Appsignal\Environments;

use Appsignal\Config;
use Appsignal\Patches\Symfony\HttpRoutePatch;
use Appsignal\Patches\Symfony\LoggerPatch;

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
