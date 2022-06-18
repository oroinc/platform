<?php

namespace Oro\Bundle\InstallerBundle\Tests\Functional\Command;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\TestFrameworkBundle\Command\TestVerbosityCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandExecutorTest extends WebTestCase
{
    private function getCommandExecutor(OutputInterface $output): CommandExecutor
    {
        $this->initClient();

        $application = new Application();
        $application->setCommandLoader(new FactoryCommandLoader([
            'oro:test:verbosity' => function () {
                return new TestVerbosityCommand();
            }
        ]));

        return new CommandExecutor(
            null,
            $output,
            $application,
            $this->createMock(OroDataCacheManager::class)
        );
    }

    public function testRestoreVerbosityLevelAfterCommandExecutionWhenCommandExecutedWithoutVerbosity(): void
    {
        $output = new BufferedOutput();
        $commandExecutor = $this->getCommandExecutor($output);

        $commandExecutor->runCommand('oro:test:verbosity');
        self::assertStringContainsString('Verbosity: Normal', $output->fetch());

        $commandExecutor->runCommand('oro:test:verbosity');
        self::assertStringContainsString('Verbosity: Normal', $output->fetch());
    }

    public function testRestoreVerbosityLevelAfterCommandExecutionWhenCommandExecutedWithVerbosity(): void
    {
        $output = new BufferedOutput();
        $commandExecutor = $this->getCommandExecutor($output);

        $commandExecutor->runCommand('oro:test:verbosity', ['--verbose' => 2]);
        self::assertStringContainsString('Verbosity: VeryVerbose', $output->fetch());

        $commandExecutor->runCommand('oro:test:verbosity');
        self::assertStringContainsString('Verbosity: Normal', $output->fetch());
    }

    public function testRestoreVerbosityLevelAfterCommandExecutionWhenCommandExecutedInQuietMode(): void
    {
        $output = new BufferedOutput();
        $commandExecutor = $this->getCommandExecutor($output);

        $commandExecutor->runCommand('oro:test:verbosity', ['--quiet' => true]);
        self::assertStringNotContainsString('Verbosity:', $output->fetch());

        $commandExecutor->runCommand('oro:test:verbosity');
        self::assertStringContainsString('Verbosity: Normal', $output->fetch());
    }

    public function testUseDefaultVerbosityLevel(): void
    {
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $commandExecutor = $this->getCommandExecutor($output);

        $commandExecutor->runCommand('oro:test:verbosity');
        self::assertStringContainsString('Verbosity: Verbose', $output->fetch());

        $commandExecutor->runCommand('oro:test:verbosity', ['--verbose' => 2]);
        self::assertStringContainsString('Verbosity: VeryVerbose', $output->fetch());
    }

    public function testUseDefaultVerbosityLevelWhenItIsQuietMode(): void
    {
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        $commandExecutor = $this->getCommandExecutor($output);

        $commandExecutor->runCommand('oro:test:verbosity');
        self::assertStringNotContainsString('Verbosity:', $output->fetch());

        $commandExecutor->runCommand('oro:test:verbosity', ['--verbose' => 2]);
        self::assertStringNotContainsString('Verbosity:', $output->fetch());
    }
}
