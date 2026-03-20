<?php

namespace App;

class ErrorsController
{
    public function show(): void
    {
        $this->foo();
    }

    public function nested(): void
    {
        Bar::nestedBaz();
    }

    protected function foo(): void
    {
        Bar::baz();
    }
}
