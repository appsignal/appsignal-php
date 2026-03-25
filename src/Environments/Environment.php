<?php

namespace Appsignal\Environments;

use Appsignal\Config;

interface Environment
{
    public function applyPatches(): void;
    public function getConfig(): Config;
}
