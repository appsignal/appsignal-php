<?php

namespace App\Http\Controllers;

use App\Bar;

class ErrorsController extends Controller
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
