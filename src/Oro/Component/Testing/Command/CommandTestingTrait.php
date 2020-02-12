<?php

namespace Oro\Component\Testing\Command;

use Oro\Component\Testing\Command\Assert\CommandOutputContains;
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
     * @param array $params
     * @return CommandTester
     */
    private function doExecuteCommand($command, array $params = []): CommandTester
    {
        if (\is_string($command)) {
            if (!$this instanceof KernelTestCase) {
                throw new Exception(\sprintf(
                    '%s accepts command name (string) as the first parameter' .
                    ' only in functional tests (or other tests based on KernelTestCase)',
                    __METHOD__
                ));
            }
            $app = new Application(static::$kernel);
            $command = $app->find($command);
        }
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        return $commandTester;
    }

    private function assertOutputContains(CommandTester $commandTester, string $expectedText)
    {
        self::assertThat($commandTester, new CommandOutputContains($expectedText));
    }

    private function assertProducedWarning(CommandTester $commandTester, string $expectedWarningMessage = null)
    {
        self::assertThat($commandTester, new CommandProducedWarning($expectedWarningMessage));
    }

    private function assertSuccessReturnCode(CommandTester $commandTester)
    {
        self::assertThat($commandTester, new CommandSuccessReturnCode());
    }

    private function assertProducedError(CommandTester $commandTester, string $expectedErrorMessage = null)
    {
        self::assertThat($commandTester, new CommandProducedError($expectedErrorMessage));
    }
}
