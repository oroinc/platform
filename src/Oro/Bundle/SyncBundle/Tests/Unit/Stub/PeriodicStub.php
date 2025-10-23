<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\Stub;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use React\EventLoop\TimerInterface;

class PeriodicStub implements PeriodicInterface, TimerInterface
{
    public function tick(): void
    {
    }

    public function getTimeout(): int
    {
        return 30;
    }

    public function getInterval(): int
    {
        return 30;
    }

    public function getCallback(): \Closure
    {
        return static fn () => null;
    }

    public function isPeriodic(): bool
    {
        return true;
    }
}
