<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronDefinitionsLoadCommand;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\CronCommandStub;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\CronCommandWithArgumentsStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Tester\CommandTester;

class CronDefinitionsLoadCommandTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private CronDefinitionsLoadCommand $command;
    private Application $application;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Schedule::class)
            ->willReturn($this->entityManager);

        $this->mockRemoveAllSchedulesQuery();

        $this->command = new CronDefinitionsLoadCommand($this->doctrine);

        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->application->add($this->command);
    }

    private function mockRemoveAllSchedulesQuery(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('execute');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('from')
            ->with(Schedule::class, 'd')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('delete')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
    }

    /**
     * @return Schedule[]
     */
    private function executeAndGetPersistedSchedules(): array
    {
        $persisted = [];
        $this->entityManager->expects(self::any())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->entityManager->expects(self::once())
            ->method('flush');

        $commandTester = new CommandTester($this->command);
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        return $persisted;
    }

    private function findScheduleByCommand(array $schedules, string $commandName): Schedule
    {
        foreach ($schedules as $schedule) {
            if ($schedule->getCommand() === $commandName) {
                return $schedule;
            }
        }

        self::fail(sprintf('No schedule was persisted for command "%s".', $commandName));
    }

    public function testCommandWithArgumentsInterfaceGetsArgumentsFromGetDefaultArguments(): void
    {
        $command = new CronCommandWithArgumentsStub('oro:cron:test:with_arguments');
        $command->setDefaultArguments(['--process-timeout=1200']);
        $this->application->add($command);

        $schedules = $this->executeAndGetPersistedSchedules();
        $schedule = $this->findScheduleByCommand($schedules, 'oro:cron:test:with_arguments');

        self::assertSame(['--process-timeout=1200'], $schedule->getArguments());
        self::assertSame('*/1 * * * *', $schedule->getDefinition());
    }

    public function testCommandWithoutArgumentsInterfaceGetsEmptyArguments(): void
    {
        $command = new CronCommandStub('oro:cron:test:without_arguments');
        $this->application->add($command);

        $schedules = $this->executeAndGetPersistedSchedules();
        $schedule = $this->findScheduleByCommand($schedules, 'oro:cron:test:without_arguments');

        self::assertSame([], $schedule->getArguments());
    }

    public function testCommandWithArgumentsInterfaceReturningEmptyArrayGetsEmptyArguments(): void
    {
        $command = new CronCommandWithArgumentsStub('oro:cron:test:empty_arguments');
        $this->application->add($command);

        $schedules = $this->executeAndGetPersistedSchedules();
        $schedule = $this->findScheduleByCommand($schedules, 'oro:cron:test:empty_arguments');

        self::assertSame([], $schedule->getArguments());
    }

    public function testLazyCommandWithArgumentsInterfaceGetsArguments(): void
    {
        $command = new CronCommandWithArgumentsStub('oro:cron:test:lazy_with_arguments');
        $command->setDefaultArguments(['--process-timeout=1200']);

        $lazyCommand = new LazyCommand(
            'oro:cron:test:lazy_with_arguments',
            [],
            '',
            false,
            static fn () => $command
        );
        $this->application->add($lazyCommand);

        $schedules = $this->executeAndGetPersistedSchedules();
        $schedule = $this->findScheduleByCommand($schedules, 'oro:cron:test:lazy_with_arguments');

        self::assertSame(['--process-timeout=1200'], $schedule->getArguments());
    }
}
