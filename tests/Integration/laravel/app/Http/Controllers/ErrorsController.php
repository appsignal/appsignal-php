<?php

namespace App\Http\Controllers;

use App\Bar;
use Appsignal\Appsignal;
use Throwable;

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

    public function handled(): void
    {
        Appsignal::instrument('handled_error', function () {
            try {
                Bar::nestedBaz();
            } catch (Throwable $e) {
                Appsignal::setError($e);
            }
        });
    }

    protected function foo(): void
    {
        Bar::baz();
    }
}
