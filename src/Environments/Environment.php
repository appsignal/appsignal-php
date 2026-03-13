<?php

namespace AppSignal\Environments;

use AppSignal\Config;

interface Environment
{
    public function applyPatches(): void;
    public function getConfig(): Config;
}
