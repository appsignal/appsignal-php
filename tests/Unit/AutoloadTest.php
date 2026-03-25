<?php

namespace Appsignal\Tests\Unit;

use Appsignal\Appsignal;
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
        Appsignal::setInstance(null);
    }

    #[Group('no-extension')]
    public function testDoesNotInitializeWithoutExtension(): void
    {
        $this->assertFalse(Appsignal::extensionIsLoaded());

        /** @var \Mockery\MockInterface&Appsignal $spy */
        $spy = Mockery::spy(Appsignal::class);
        Appsignal::setInstance($spy);

        $warning = $this->callAndCaptureWarnings(function () {
            require __DIR__ . '/../../_autoload.php';
        });

        $this->assertEquals('The "opentelemetry" extension must be loaded to use Appsignal', $warning);
        $spy->shouldNotHaveReceived('initialize');
    }

    public function testInitializesWithExtension(): void
    {
        $this->assertTrue(Appsignal::extensionIsLoaded());

        /** @var \Mockery\MockInterface&Appsignal $spy */
        $spy = Mockery::spy(Appsignal::class);
        Appsignal::setInstance($spy);

        require __DIR__ . '/../../_autoload.php';

        $spy->shouldHaveReceived('initialize')
            ->once(); // @phpstan-ignore method.notFound
    }
}
