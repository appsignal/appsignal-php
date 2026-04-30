<?php

namespace Appsignal\Tests\Unit;

use Appsignal\Appsignal;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    use CapturesWarnings;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        $_ENV['_APPSIGNAL_TEST'] = true;
    }

    protected function tearDown(): void
    {
        unset($_ENV['_APPSIGNAL_TEST']);
        Appsignal::setInstance(null);
    }

    #[RunInSeparateProcess]
    public function testAutoloadForLaravelArtisan(): void
    {
        unset($_ENV['_APPSIGNAL_TEST']);
        $_SERVER['SCRIPT_NAME'] = 'artisan';

        /** @var \Mockery\MockInterface&Appsignal $spy */
        $spy = Mockery::spy(Appsignal::class);
        Appsignal::setInstance($spy);

        require __DIR__ . '/../../_autoload.php';

        $spy->shouldHaveReceived('initialize')
            ->once(); // @phpstan-ignore method.notFound
    }

    #[RunInSeparateProcess]
    public function testAutoloadForSymfonyConsole(): void
    {
        unset($_ENV['_APPSIGNAL_TEST']);
        $_SERVER['SCRIPT_NAME'] = 'bin/console';

        /** @var \Mockery\MockInterface&Appsignal $spy */
        $spy = Mockery::spy(Appsignal::class);
        Appsignal::setInstance($spy);

        require __DIR__ . '/../../_autoload.php';

        $spy->shouldHaveReceived('initialize')
            ->once(); // @phpstan-ignore method.notFound
    }

    #[RunInSeparateProcess]
    public function testDoesNotAutoloadForUnknownCliScript(): void
    {
        unset($_ENV['_APPSIGNAL_TEST']);
        $_SERVER['SCRIPT_NAME'] = 'some-other-script';

        /** @var \Mockery\MockInterface&Appsignal $spy */
        $spy = Mockery::spy(Appsignal::class);
        Appsignal::setInstance($spy);

        require __DIR__ . '/../../_autoload.php';

        $spy->shouldNotHaveReceived('initialize');
    }
}
