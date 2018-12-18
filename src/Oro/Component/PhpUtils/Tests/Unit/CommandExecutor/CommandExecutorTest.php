<?php

namespace Oro\Component\PhpUtils\Tests\Unit\CommandExecutor;

use Monolog\Logger;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class CommandExecutorTest extends \PHPUnit\Framework\TestCase
{
    private const ENV = 'dev';
    private const CONSOLE_CMD_PATH = '-r';
    private const NON_DEFAULT_OPTION_NAME = 'non_default_option_name';
    private const DEFAULT_OPTION_NAME = 'default_option_name';

    /** @var CommandExecutor */
    private $commandExecutor;

    protected function setUp()
    {
        $this->commandExecutor = new CommandExecutor(self::CONSOLE_CMD_PATH, self::ENV);
    }

    public function testDefaultOption(): void
    {
        self::assertEquals(
            CommandExecutor::DEFAULT_TIMEOUT,
            $this->commandExecutor->getDefaultOption('process-timeout')
        );

        self::assertNull($this->commandExecutor->getDefaultOption(self::NON_DEFAULT_OPTION_NAME));

        self::assertEquals(
            $this->commandExecutor,
            $this->commandExecutor->setDefaultOption(self::DEFAULT_OPTION_NAME, true)
        );

        self::assertTrue($this->commandExecutor->getDefaultOption(self::DEFAULT_OPTION_NAME));
    }

    public function testRunCommand(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Logger $logger */
        $logger = self::createMock(Logger::class);
        $logger->expects(self::once())->method('warning');
        $logger->expects(self::once())->method('error');
        $logger->expects(self::once())->method('info');

        self::assertEquals(0, $this->commandExecutor->runCommand('echo "acme";', []));
        $this->commandExecutor->runCommand('error command', ['--ignore-errors' => true], $logger);

        self::expectException(ProcessTimedOutException::class);
        $this->commandExecutor->runCommand('sleep(2);', ['--process-timeout' => 1]);
    }
}
