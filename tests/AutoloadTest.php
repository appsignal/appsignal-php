<?php

namespace AppSignal\Tests;

use AppSignal\AppSignal;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    use CapturesWarnings;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        $_ENV['LARAVEL_ARTISAN'] = true;
    }

    protected function tearDown(): void
    {
        unset($_ENV['LARAVEL_ARTISAN']);
        AppSignal::setInstance(null);
    }

    #[Group('no-extension')]
    public function testDoesNotInitializeWithoutExtension(): void
    {
        $this->assertFalse(AppSignal::extensionIsLoaded());

        /** @var \Mockery\MockInterface&AppSignal $spy */
        $spy = Mockery::spy(AppSignal::class);
        AppSignal::setInstance($spy);

        $warning = $this->callAndCaptureWarnings(function () {
            require __DIR__ . '/../_autoload.php';
        });

        $this->assertEquals('The "opentelemetry" extension must be loaded to use AppSignal', $warning);
        $spy->shouldNotHaveReceived('initialize');
    }

    public function testInitializesWithExtension(): void
    {
        $this->assertTrue(AppSignal::extensionIsLoaded());

        /** @var \Mockery\MockInterface&AppSignal $spy */
        $spy = Mockery::spy(AppSignal::class);
        AppSignal::setInstance($spy);

        require __DIR__ . '/../_autoload.php';

        $spy->shouldHaveReceived('initialize')
            ->once(); // @phpstan-ignore method.notFound
    }
}
