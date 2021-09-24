<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\InstallerBundle\Command\InstallCommand;
use Oro\Bundle\InstallerBundle\Persister\YamlPersister;
use Oro\Bundle\InstallerBundle\ScriptManager;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group regression
 */
class InstallCommandTest extends TestCase
{
    use CommandTestingTrait;
    use TempDirExtension;

    /** @var InstallCommand */
    private $command;

    public function testDisplaysErrorAndTerminatesIfAlreadyInstalled()
    {
        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertProducedError($commandTester, 'application is already installed');
        $this->assertProducedWarning($commandTester, 'All data will be lost');
    }

    protected function setUp(): void
    {
        /** @noinspection PhpParamsInspection */
        $this->command = new InstallCommand(
            $this->createMock(YamlPersister::class),
            $this->createMock(ScriptManager::class),
            $this->createMock(Registry::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        $questionHelper = $this->createMock(QuestionHelper::class);
        /** @var HelperSet|MockObject $helperSet */
        $helperSet = $this->createMock(HelperSet::class);
        $helperSet->method('get')
            ->willReturn($questionHelper);
        $this->command->setHelperSet($helperSet);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('hasParameter')
            ->with('installed')
            ->willReturn(true);
        $container->method('getParameter')
            ->withConsecutive(['kernel.environment'], ['installed'])
            ->willReturnOnConsecutiveCalls(['dev'], ['2020-01-01T01:01:01-08:00']);
        $this->command->setContainer($container);
    }
}
