<?php
declare(strict_types=1);

namespace Oro\Component\Testing\Command;

use Oro\Component\Testing\Command\Assert\CommandOutputIsEmpty;
use Oro\Component\Testing\Command\Assert\CommandProducedError;
use Oro\Component\Testing\Command\Assert\CommandProducedWarning;
use Oro\Component\Testing\Command\Assert\CommandSuccessReturnCode;
use PHPUnit\Framework\Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Collection of miscellaneous methods to test console commands in unit and functional tests.
 */
trait CommandTestingTrait
{
    /**
     * @param Command|string $command Command instance or command name (execute by name is supported only in functional
     *                                  tests or other tests based on KernelTestCase.
     * @param array $input
     * @param array $options Additional options that will be used when instantiating the command (e.g. 'verbosity').
     * @return CommandTester
     */
    private function doExecuteCommand($command, array $input = [], array $options = []): CommandTester
    {
        if (\is_string($command)) {
            if (!$this instanceof KernelTestCase) {
                throw new Exception(\sprintf(
                    '%s accepts command name (string) as the first parameter' .
                    ' only in functional tests (or other tests based on KernelTestCase)',
                    __METHOD__
                ));
            }
            $app = new Application(static::$kernel ?? static::bootKernel());
            $command = $app->find($command);
        }
        $commandTester = new CommandTester($command);
        $commandTester->execute($input, $options);

        return $commandTester;
    }

    private function assertOutputContains(CommandTester $commandTester, string $expectedText): void
    {
        self::assertStringContainsString($expectedText, CommandOutputNormalizer::toSingleLine($commandTester));
    }

    private function assertOutputNotContains(CommandTester $commandTester, string $expectedText): void
    {
        self::assertStringNotContainsString($expectedText, CommandOutputNormalizer::toSingleLine($commandTester));
    }

    private function assertOutputIsEmpty(CommandTester $commandTester): void
    {
        self::assertThat($commandTester, new CommandOutputIsEmpty());
    }

    private function assertProducedWarning(CommandTester $commandTester, string $expectedWarningMessage = null): void
    {
        self::assertThat($commandTester, new CommandProducedWarning($expectedWarningMessage));
    }

    private function assertSuccessReturnCode(CommandTester $commandTester): void
    {
        self::assertThat($commandTester, new CommandSuccessReturnCode());
    }

    private function assertProducedError(CommandTester $commandTester, string $expectedErrorMessage = null): void
    {
        self::assertThat($commandTester, new CommandProducedError($expectedErrorMessage));
    }
}
