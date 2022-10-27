<?php

namespace Oro\Component\PhpUtils\Tests\Unit\CommandExecutor;

use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class CommandExecutorTest extends \PHPUnit\Framework\TestCase
{
    private const ENV = 'dev';
    private const CONSOLE_CMD_PATH = '-r';
    private const NON_DEFAULT_OPTION_NAME = 'non_default_option_name';
    private const DEFAULT_OPTION_NAME = 'default_option_name';

    /** @var CommandExecutor */
    private $commandExecutor;

    protected function setUp(): void
    {
        $this->commandExecutor = new CommandExecutor(self::CONSOLE_CMD_PATH, self::ENV);
    }

    public function testDefaultOption(): void
    {
        $this->assertEquals(
            CommandExecutor::DEFAULT_TIMEOUT,
            $this->commandExecutor->getDefaultOption('process-timeout')
        );

        $this->assertNull($this->commandExecutor->getDefaultOption(self::NON_DEFAULT_OPTION_NAME));

        $this->assertEquals(
            $this->commandExecutor,
            $this->commandExecutor->setDefaultOption(self::DEFAULT_OPTION_NAME, true)
        );

        $this->assertTrue($this->commandExecutor->getDefaultOption(self::DEFAULT_OPTION_NAME));
    }

    public function testRunCommand(): void
    {
        $logger = new TestLogger();
        $this->assertEquals(0, $this->commandExecutor->runCommand('echo "acme";', [], $logger));
        $this->assertTrue($logger->hasInfo('acme'));
    }

    public function testErrorCommand(): void
    {
        $logger = new TestLogger();
        $this->assertGreaterThanOrEqual(
            254,
            $this->commandExecutor->runCommand('error command', ['--ignore-errors' => true], $logger)
        );
    }

    public function testTimedOutCommand(): void
    {
        $logger = new TestLogger();
        $this->expectException(ProcessTimedOutException::class);
        $this->assertEquals(
            254,
            $this->commandExecutor->runCommand('echo "acme";sleep(2);', ['--process-timeout' => 1], $logger)
        );
        $this->assertFalse($logger->hasInfoRecords());
        $this->assertFalse($logger->hasWarningRecords());
    }
}
