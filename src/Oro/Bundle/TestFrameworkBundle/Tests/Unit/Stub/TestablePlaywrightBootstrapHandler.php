<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub;

use Composer\IO\IOInterface;
use Oro\Bundle\TestFrameworkBundle\Composer\PlaywrightBootstrapHandler;

/**
 * Captures the command instead of running it.
 */
class TestablePlaywrightBootstrapHandler extends PlaywrightBootstrapHandler
{
    /** @var array<int, array{cmd: array, timeout: int, cwd: string}> */
    public static array $invocations = [];

    public static int $exitCode = 0;

    public static function reset(): void
    {
        self::$invocations = [];
        self::$exitCode = 0;
    }

    #[\Override]
    protected static function runProcess(IOInterface $inputOutput, array $cmd, int $timeout, string $cwd): int
    {
        self::$invocations[] = ['cmd' => $cmd, 'timeout' => $timeout, 'cwd' => $cwd];

        return self::$exitCode;
    }
}
