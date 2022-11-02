<?php

namespace Oro\Component\Testing\Command\Assert;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Checks if the command return code is 0 (integer zero).
 */
class CommandSuccessReturnCode extends Constraint
{
    protected function matches($commandTester): bool
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        return 0 === $commandTester->getStatusCode();
    }

    protected function failureDescription($other): string
    {
        return 'Command returned success return code';
    }

    public function toString(): string
    {
        return 'command returned success return code';
    }
}
