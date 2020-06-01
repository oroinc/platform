<?php

namespace Oro\Component\Testing\Command\Assert;

use Oro\Component\Testing\Command\CommandOutputNormalizer;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Checks if the (normalized) command output contains a certain text
 */
class CommandOutputContains extends Constraint
{
    /** @var string */
    private $expectedText;

    public function __construct(string $expectedText)
    {
        $this->expectedText = $expectedText;
    }

    protected function matches($commandTester): bool
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        $output = CommandOutputNormalizer::toSingleLine($commandTester);

        return false !== \strpos($output, $this->expectedText);
    }

    public function toString(): string
    {
        return 'command output contains';
    }

    protected function failureDescription($commandTester): string
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        $output = CommandOutputNormalizer::toSingleLine($commandTester);

        return \sprintf('%s contains %s', $this->exporter()->export($output), $this->expectedText);
    }
}
