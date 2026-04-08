<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\Command\InstallCommand;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\InstallerBundle\ScriptManager;
use Oro\Component\Testing\Command\CommandTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @group regression
 */
class InstallCommandTest extends TestCase
{
    use CommandTestingTrait;

    private Application $application;
    private InstallCommand $command;
    private array $executedCommands = [];
    private ManagerRegistry&MockObject $doctrine;
    private ApplicationState&MockObject $applicationState;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private KernelInterface&MockObject $kernel;
    private OroDataCacheManager&MockObject $dataCacheManager;
    private ContainerInterface&MockObject $container;

    public function testDisplaysErrorAndTerminatesIfAlreadyInstalled(): void
    {
        $this->applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturn(true);

        $this->eventDispatcher->expects(self::any())
            ->method('dispatch')
            ->willReturnArgument(0);

        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertProducedError($commandTester, 'application is already installed');
        $this->assertProducedWarning($commandTester, 'All data will be lost');
    }

    /**
     * @dataProvider prepareStepProvider
     */
    public function testPrepareStepWithDropDatabaseOption(bool $dropDatabase): void
    {
        $this->applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturn(false);

        $this->configureStopAfterPrepareStep();

        if ($dropDatabase) {
            $schemaMethod = method_exists(AbstractSchemaManager::class, 'introspectSchema')
                ? 'introspectSchema'
                : 'createSchema';

            $platform = $this->createMock(AbstractPlatform::class);

            $schema = $this->createMock(Schema::class);
            $schema->expects(self::once())
                ->method('toDropSql')
                ->with($platform)
                ->willReturn(['DROP TABLE a', 'DROP TABLE b']);

            $schemaManager = $this->createMock(AbstractSchemaManager::class);
            $schemaManager->expects(self::once())
                ->method($schemaMethod)
                ->willReturn($schema);

            $writableConnection = $this->createMock(Connection::class);
            $writableConnection->expects(self::once())
                ->method('getSchemaManager')
                ->willReturn($schemaManager);
            $writableConnection->expects(self::once())
                ->method('getDatabasePlatform')
                ->willReturn($platform);
            $writableConnection->expects(self::exactly(2))
                ->method('executeStatement')
                ->withConsecutive(['DROP TABLE a'], ['DROP TABLE b']);

            $readonlyConnection = $this->createMock(Connection::class);
            $readonlyConnection->expects(self::never())
                ->method('executeStatement');

            $this->doctrine->expects(self::once())
                ->method('getConnections')
                ->willReturn([
                    'default' => $writableConnection,
                    'readonly' => $readonlyConnection,
                ]);
        } else {
            $this->doctrine->expects(self::never())
                ->method('getConnections');
        }

        $input = $dropDatabase ? ['--drop-database' => true] : [];
        $this->doExecuteCommand($this->command, $input);

        self::assertArrayHasKey(
            'oro:check-requirements',
            $this->executedCommands,
            'oro:check-requirements command was not executed.'
        );
    }

    public static function prepareStepProvider(): array
    {
        return [
            'with drop-database option' => [true],
            'without drop-database option' => [false],
        ];
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('isDebug')->willReturn(false);

        $this->dataCacheManager = $this->createMock(OroDataCacheManager::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects(self::any())
            ->method('get')
            ->willReturnCallback(function (string $id): object {
                return match ($id) {
                    'oro_cache.oro_data_cache_manager' => $this->dataCacheManager,
                    default => $this->kernel,
                };
            });

        $this->command = new InstallCommand(
            $this->container,
            $this->doctrine,
            $this->eventDispatcher,
            $this->applicationState,
            $this->createMock(ScriptManager::class),
            $this->createMock(ValidatorInterface::class)
        );
        $this->command->setReadOnlyConnections(['readonly']);

        $this->setUpConsoleApplication();
        $this->registerCommandStubs();
    }

    /**
     * Creates a real Application so getCommandExecutor() receives a non-null Application instance.
     * --no-debug is added because it is normally a Symfony Framework global option that
     * getCommandExecutor() reads via $input->getOption('no-debug').
     */
    private function setUpConsoleApplication(): void
    {
        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->application->getDefinition()->addOption(
            new InputOption('no-debug', null, InputOption::VALUE_NONE, 'Disable debug mode')
        );
        $this->application->add($this->command);
    }

    /**
     * Registers command stubs for all commands the installer invokes
     */
    private function registerCommandStubs(): void
    {
        $commandNames = [
            'oro:check-requirements',
        ];
        foreach ($commandNames as $commandName) {
            $this->application->add(
                new class ($commandName, $this->executedCommands) extends Command {
                    private array $executedCommands;

                    public function __construct(string $commandName, array &$executedCommands)
                    {
                        $this->executedCommands = &$executedCommands;
                        parent::__construct($commandName);
                    }

                    #[\Override]
                    protected function configure(): void
                    {
                    }

                    #[\Override]
                    protected function execute(InputInterface $input, OutputInterface $output): int
                    {
                        $this->executedCommands[$this->getName()] = true;

                        return self::SUCCESS;
                    }
                }
            );
        }
    }

    /**
     * Configures the shared eventDispatcher to throw immediately after prepareStep completes,
     * stopping execute() before loadDataStep spawns any --process-isolation subprocesses.
     */
    private function configureStopAfterPrepareStep(): void
    {
        /** @var HelperSet|MockObject $helperSet */
        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(static function (object $event, ?string $eventName): object {
                if ($eventName === InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION) {
                    throw new \RuntimeException('stop after prepareStep');
                }

                return $event;
            });
    }
}
