<?php

namespace App\Controller;

use AppSignal\AppSignal;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController
{
    #[Route('/', methods: ['GET'])]
    public function index(): Response
    {
        return new Response();
    }

    #[Route('/', methods: ['POST'])]
    public function indexPost(): Response
    {
        return new Response();
    }

    #[Route('/instrument', methods: ['GET'])]
    public function instrument(): Response
    {
        AppSignal::instrument(
            'my-span',
            [
                'string-attribute' => 'abcdef',
                'int-attribute' => 1234,
                'bool-attribute' => true,
            ],
            function () {}
        );

        return new Response();
    }

    #[Route('/instrument-nested', methods: ['GET'])]
    public function instrumentNested(): Response
    {
        AppSignal::instrument('parent', ['msg' => 'from parent span'], function () {
            $span = AppSignal::instrument('child', ['msg' => 'from child span']);
            $span->end();
        });

        return new Response();
    }

    #[Route('/set-action', methods: ['GET'])]
    public function setAction(): Response
    {
        AppSignal::setAction('my action');

        return new Response();
    }

    #[Route('/custom-data', methods: ['GET'])]
    public function customData(): Response
    {
        AppSignal::addCustomData([
            'string-attribute' => 'abcdef',
            'int-attribute' => 1234,
            'bool-attribute' => true,
        ]);

        return new Response();
    }

    #[Route('/tags', methods: ['GET'])]
    public function tags(): Response
    {
        AppSignal::addTags([
            'string-tag' => 'some value',
            'integer-tag' => 1234,
            'bool-tag' => true,
        ]);

        return new Response();
    }

    #[Route('/log', methods: ['GET'])]
    public function log(LoggerInterface $logger): Response
    {
        $logger->info('My log');

        return new Response();
    }

    #[Route('/log-with-attributes', methods: ['GET'])]
    public function logWithAttributes(LoggerInterface $logger): Response
    {
        $logger->info('My log with attributes', ['foo' => 'bar']);

        return new Response();
    }

    #[Route('/set-gauge', methods: ['GET'])]
    public function setGauge(): Response
    {
        AppSignal::setGauge('my_gauge', 12);
        AppSignal::setGauge('my_gauge_with_attributes', 13, ["region" => "eu"]);

        return new Response();
    }

    #[Route('/add-distribution-values', methods: ['GET'])]
    public function addDistributionValues(): Response
    {
        AppSignal::addDistributionValue('memory_usage', 50);
        AppSignal::addDistributionValue('memory_usage', 70);

        AppSignal::addDistributionValue('with_attributes', 10, ["region" => "eu"]);
        AppSignal::addDistributionValue('with_attributes', 20, ["region" => "eu"]);
        AppSignal::addDistributionValue('with_attributes', 30, ["region" => "eu"]);

        return new Response();
    }

    #[Route('/counter', methods: ['GET'])]
    public function counter(): Response
    {
        AppSignal::incrementCounter("my_counter", 1);
        AppSignal::incrementCounter("my_counter", 3, ["region" => "eu"]);

        return new Response();
    }
}
