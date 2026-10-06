<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Cron\CronExpression;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Command\CronCommand;
use Oro\Bundle\CronBundle\Command\CronCommandFeatureCheckerInterface;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Tools\CronHelper;
use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceModeState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CronCommandTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private MaintenanceModeState&MockObject $maintenanceMode;
    private CronHelper&MockObject $cronHelper;
    private CommandRunnerInterface&MockObject $commandRunner;
    private CronCommandFeatureCheckerInterface&MockObject $commandFeatureChecker;
    private LoggerInterface&MockObject $logger;
    private CacheItemPoolInterface&MockObject $cache;
    private CronCommand $command;
    private Application $application;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->maintenanceMode = $this->createMock(MaintenanceModeState::class);
        $this->cronHelper = $this->createMock(CronHelper::class);
        $this->commandRunner = $this->createMock(CommandRunnerInterface::class);
        $this->commandFeatureChecker = $this->createMock(CronCommandFeatureCheckerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);

        $this->maintenanceMode->expects(self::any())
            ->method('isOn')
            ->willReturn(false);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::any())
            ->method('set')
            ->willReturnSelf();
        $this->cache->expects(self::any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $this->cache->expects(self::any())
            ->method('save')
            ->willReturn(true);

        $this->command = new CronCommand(
            $this->doctrine,
            $this->maintenanceMode,
            $this->cronHelper,
            $this->commandRunner,
            $this->commandFeatureChecker,
            $this->logger,
            $this->cache,
            'test'
        );

        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->application->add($this->command);
    }

    private function mockDueSchedule(string $commandName, array $arguments): Schedule
    {
        $schedule = new Schedule();
        $schedule->setCommand($commandName)
            ->setDefinition('* * * * *')
            ->setArguments($arguments);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findAll')
            ->willReturn([$schedule]);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Schedule::class)
            ->willReturn($repository);

        $this->commandFeatureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with($commandName)
            ->willReturn(true);

        $cronExpression = $this->createMock(CronExpression::class);
        $cronExpression->expects(self::any())
            ->method('isDue')
            ->willReturn(true);
        $this->cronHelper->expects(self::any())
            ->method('createCron')
            ->with('* * * * *')
            ->willReturn($cronExpression);

        return $schedule;
    }

    public function testExecuteResolvesArgumentsToKeyedOptionsForAsyncCommand(): void
    {
        $commandName = 'oro:test:command';
        $this->mockDueSchedule($commandName, ['--process-timeout=1200']);

        $this->application->add(new Command($commandName));

        $this->commandRunner->expects(self::once())
            ->method('run')
            ->with($commandName, ['--process-timeout' => '1200']);

        $commandTester = new CommandTester($this->command);
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }
}
