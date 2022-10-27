<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Filter\SchedulesByArgumentsFilterInterface;

class ScheduleManagerTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = Schedule::class;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SchedulesByArgumentsFilterInterface */
    private $schedulesByArgumentsFilter;

    /** @var ScheduleManager */
    private $manager;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->schedulesByArgumentsFilter = $this->createMock(SchedulesByArgumentsFilterInterface::class);

        $this->manager = new ScheduleManager(
            $this->registry,
            $this->schedulesByArgumentsFilter,
            self::CLASS_NAME
        );
    }

    public function testHasSchedule()
    {
        $command = 'oro:test';
        $definition = '* * * * *';
        $arguments = ['arg1', 'arg2'];
        $repositorySchedules = [
            $this->createSchedule('oro:test', [], '* * * * *'),
            $this->createSchedule('oro:test', ['arg1', 'arg2'], '* * * * *'),
        ];

        $this->assertRepositoryCalled($command, $definition, $repositorySchedules);

        $this->schedulesByArgumentsFilter->expects(self::once())
            ->method('filter')
            ->with($repositorySchedules, $arguments)
            ->willReturn([$repositorySchedules[1]]);

        self::assertTrue($this->manager->hasSchedule($command, $arguments, $definition));
    }

    public function testCreateSchedule()
    {
        $command = 'oro:test';
        $arguments = ['arg1', 'arg2'];
        $definition = '* * * * *';

        $this->schedulesByArgumentsFilter->expects(self::once())
            ->method('filter')
            ->willReturn([]);

        $this->assertRepositoryCalled($command, $definition);
        $this->assertEquals(
            $this->createSchedule($command, $arguments, $definition),
            $this->manager->createSchedule($command, $arguments, $definition)
        );
    }

    /**
     * @dataProvider createScheduleExceptionProvider
     */
    public function testCreateScheduleException(
        ?string $command,
        ?string $definition,
        array $schedules,
        string $exception,
        string $message
    ) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        if ($schedules) {
            $this->assertRepositoryCalled($command, $definition, $schedules);
            $this->schedulesByArgumentsFilter->expects(self::once())
                ->method('filter')
                ->willReturn([$schedules[0]]);
        }

        $this->manager->createSchedule($command, [], $definition);
    }

    public function createScheduleExceptionProvider(): array
    {
        $schedule = $this->createSchedule('oro:test', [], '* * * * *');

        return [
            'empty command' => [
                'command' => null,
                'definition' => '* * * * *',
                'schedules' => [],
                'exception' => 'InvalidArgumentException',
                'message' => 'Parameters "command" and "definition" must be specified.'
            ],
            'empty definition' => [
                'command' => 'oro:test',
                'definition' => null,
                'schedules' => [],
                'exception' => 'InvalidArgumentException',
                'message' => 'Parameters "command" and "definition" must be specified.'
            ],
            'exists schedule' => [
                'command' => 'oro:test',
                'definition' => '* * * * *',
                'schedules' => [$schedule],
                'exception' => 'LogicException',
                'message' => 'Schedule with same parameters already exists.'
            ],
        ];
    }

    public function testGetSchedulesByCommandAndArguments()
    {
        $command = 'oro:test';
        $arguments = ['arg1', 'arg2'];
        $repositorySchedules = [
            $this->createSchedule('oro:test', [], '* * * * *'),
            $this->createSchedule('oro:test', ['arg1', 'arg2'], '* * * * *'),
        ];

        $this->assertRepositoryCalled($command, '', $repositorySchedules);

        $this->schedulesByArgumentsFilter->expects(self::once())
            ->method('filter')
            ->with($repositorySchedules, $arguments)
            ->willReturn($repositorySchedules[1]);

        self::assertSame(
            $repositorySchedules[1],
            $this->manager->getSchedulesByCommandAndArguments($command, $arguments)
        );
    }

    private function assertRepositoryCalled(string $command, string $definition = '', array $schedules = [])
    {
        $findBy = ['command' => $command];
        if ($definition !== '') {
            $findBy['definition'] = $definition;
        }

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with($findBy)
            ->willReturn($schedules);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::CLASS_NAME)
            ->willReturn($em);
    }

    private function createSchedule(string $command, array $arguments, string $definition): Schedule
    {
        $schedule = new Schedule();
        $schedule
            ->setCommand($command)
            ->setArguments($arguments)
            ->setDefinition($definition);

        return $schedule;
    }
}
