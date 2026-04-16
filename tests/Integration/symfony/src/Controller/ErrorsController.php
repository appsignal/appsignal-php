<?php

namespace App\Controller;

use App\Bar;
use Appsignal\Appsignal;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class ErrorsController
{
    #[Route('/error', methods: ['GET'])]
    public function show(): Response
    {
        $this->foo();
    }

    #[Route('/error-nested', methods: ['GET'])]
    public function nested(): Response
    {
        Bar::nestedBaz();
    }

    #[Route('/error-handled', methods: ['GET'])]
    public function handled(): Response
    {
        Appsignal::instrument('handled_error', closure: function () {
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
