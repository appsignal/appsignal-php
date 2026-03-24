<?php

namespace App\Controller;

use App\Bar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    protected function foo(): void
    {
        Bar::baz();
    }
}
