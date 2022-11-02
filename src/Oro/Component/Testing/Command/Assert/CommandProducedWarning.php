<?php

namespace Oro\Component\Testing\Command\Assert;

use Oro\Component\Testing\Command\CommandOutputNormalizer;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Checks if the command produced a warning (output should contain '[WARNING]' indicator, optionally - a specific
 * warning message text).
 */
class CommandProducedWarning extends Constraint
{
    /** @var string|null */
    private $expectedWarningMessage;

    /** @var array */
    private $errors = [];

    public function __construct(?string $expectedWarningMessage)
    {
        $this->expectedWarningMessage = $expectedWarningMessage;
    }

    protected function matches($commandTester): bool
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        $output = CommandOutputNormalizer::toSingleLine($commandTester);
        if (!str_contains($output, '[WARNING]')) {
            $this->errors[] = 'The console command should display a warning message if there were any warnings.';
        }
        if (null !== $this->expectedWarningMessage && !str_contains($output, $this->expectedWarningMessage)) {
            $this->errors[] = \sprintf(
                'The console command should display the warning message "%s".',
                $this->expectedWarningMessage
            );
        }
        return 0 === count($this->errors);
    }

    protected function failureDescription($commandTester): string
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        return \sprintf(
            "command produced a warning:\n- %s\nCommand output:\n%s\n",
            \implode("\n- ", $this->errors),
            $commandTester->getDisplay()
        );
    }

    public function toString(): string
    {
        return 'command produced a warning';
    }
}
