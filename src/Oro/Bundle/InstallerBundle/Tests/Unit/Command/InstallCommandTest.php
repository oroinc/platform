<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\Command\InstallCommand;
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

    private InstallCommand $command;

    public function testDisplaysErrorAndTerminatesIfAlreadyInstalled(): void
    {
        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertProducedError($commandTester, 'application is already installed');
        $this->assertProducedWarning($commandTester, 'All data will be lost');
    }

    protected function setUp(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $applicationState = $this->createMock(ApplicationState::class);
        $applicationState->method('isInstalled')->willReturn(true);

        $container->set('oro_distribution.handler.application_status', $applicationState);

        /** @noinspection PhpParamsInspection */
        $this->command = new InstallCommand(
            $container,
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EventDispatcherInterface::class),
            $applicationState,
            $this->createMock(ScriptManager::class)
        );

        $questionHelper = $this->createMock(QuestionHelper::class);
        /** @var HelperSet|MockObject $helperSet */
        $helperSet = $this->createMock(HelperSet::class);
        $helperSet->method('get')
            ->willReturn($questionHelper);
        $this->command->setHelperSet($helperSet);
    }
}
