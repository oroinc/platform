<?php

declare(strict_types=1);

namespace Oro\Component\Testing\Command\Assert;

use Oro\Component\Testing\Command\CommandOutputNormalizer;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Checks if the (normalized) command output contains no text at all.
 */
class CommandOutputIsEmpty extends Constraint
{
    #[\Override]
    protected function matches($commandTester): bool
    {
        /** @var CommandTester $commandTester */
        $output = CommandOutputNormalizer::toSingleLine($commandTester);

        return '' === $output;
    }

    #[\Override]
    public function toString(): string
    {
        return 'command output is empty';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function failureDescription($commandTester): string
    {
        /** @var CommandTester $commandTester */
        $output = CommandOutputNormalizer::toSingleLine($commandTester);

        return \sprintf('%s is empty', $this->exporter()->export($output));
    }
}
