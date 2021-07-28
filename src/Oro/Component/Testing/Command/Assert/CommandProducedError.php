<?php

namespace Oro\Component\Testing\Command\Assert;

use Oro\Component\Testing\Command\CommandOutputNormalizer;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Checks if the command produced an error properly (non-zero return code and '[ERROR]' indicator in the output,
 * and optionally - a specific error message text).
 */
class CommandProducedError extends Constraint
{
    /** @var string|null */
    private $expectedErrorMessage;

    /** @var array */
    private $errors = [];

    public function __construct(?string $expectedErrorMessage)
    {
        $this->expectedErrorMessage = $expectedErrorMessage;
    }

    protected function matches($commandTester): bool
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        if (0 === $commandTester->getStatusCode()) {
            $this->errors[] = 'The console command should return a non-zero return value if there were any errors.';
        }
        $output = CommandOutputNormalizer::toSingleLine($commandTester);
        if (!str_contains($output, '[ERROR]')) {
            $this->errors[] = 'The console command should display an error message if there were any errors.';
        }
        if (null !== $this->expectedErrorMessage && !str_contains($output, $this->expectedErrorMessage)) {
            $this->errors[] = \sprintf(
                'The console command should display the error message "%s".',
                $this->expectedErrorMessage
            );
        }
        return 0 === count($this->errors);
    }

    protected function failureDescription($commandTester): string
    {
        /** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
        return \sprintf(
            "command produced an error:\n- %s\nReturn code: %s\nCommand output:\n%s\n",
            \implode("\n- ", $this->errors),
            $commandTester->getStatusCode(),
            $commandTester->getDisplay()
        );
    }

    public function toString(): string
    {
        return 'command produced an error';
    }
}
