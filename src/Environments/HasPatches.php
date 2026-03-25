<?php

namespace Appsignal\Environments;

trait HasPatches
{
    public function applyPatches(): void
    {
        foreach ($this->patches as $patch) {
            $instance = is_object($patch) ? $patch : new $patch();
            $instance();
        }
    }
}
