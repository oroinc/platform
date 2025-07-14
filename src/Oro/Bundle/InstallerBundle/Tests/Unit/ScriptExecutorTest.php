<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\ScriptExecutor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScriptExecutorTest extends TestCase
{
    public function testRunScript(): void
    {
        $testScriptFile = realpath(__DIR__ . '/Fixture/src/TestPackage/install.php');

        $output = $this->createMock(StreamOutput::class);
        $output->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains(sprintf('Launching "Test Package Installer" (%s) script', $testScriptFile))],
                ['Test Package Installer data']
            );

        $container = $this->createMock(ContainerInterface::class);
        $commandExecutor = $this->createMock(CommandExecutor::class);

        $scriptExecutor = new ScriptExecutor($output, $container, $commandExecutor);
        $scriptExecutor->runScript($testScriptFile);
    }
}
