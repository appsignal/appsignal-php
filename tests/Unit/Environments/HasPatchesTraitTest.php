<?php

namespace AppSignal\Tests\Unit\Environments;

use AppSignal\Config;
use AppSignal\Environments\Environment;
use AppSignal\Environments\HasPatches;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HasPatchesTraitTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRegisterHooksCallsInvokeOnEachHook(): void
    {
        /** @var \Mockery\MockInterface&FakeHook $hookA */
        $hookA = Mockery::spy(FakeHook::class);
        /** @var \Mockery\MockInterface&FakeHook $hookB */
        $hookB = Mockery::spy(FakeHook::class);

        $env = new FakeEnvironment([$hookA, $hookB]);
        $env->applyPatches();

        $hookA->shouldHaveReceived('__invoke')->once(); // @phpstan-ignore method.notFound
        $hookB->shouldHaveReceived('__invoke')->once(); // @phpstan-ignore method.notFound
    }
}

class FakeHook
{
    public function __invoke(): void {}
}

class FakeEnvironment implements Environment
{
    use HasPatches;
    /**
     * @param array<int,mixed> $patches
     */
    public function __construct(protected array $patches = []) {}

    public function getConfig(): Config
    {
        return new Config();
    }
}
