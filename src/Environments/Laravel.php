<?php

namespace Appsignal\Environments;

use Appsignal\Config;

class Laravel implements Environment
{
    use HasPatches;

    /** @var array<int, class-string|object> */
    protected array $patches = [];

    public function __construct(protected ?string $basePath = null) {}

    public function getConfig(): Config
    {
        return Config::tryFromFile($this->basePath . Config::CONFIG_PATH);
    }
}
