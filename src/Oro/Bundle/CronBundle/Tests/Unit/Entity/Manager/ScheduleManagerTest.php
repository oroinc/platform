<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Filter\SchedulesByArgumentsFilterInterface;

class ScheduleManagerTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'Oro\Bundle\CronBundle\Entity\Schedule';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SchedulesByArgumentsFilterInterface */
    private $schedulesByArgumentsFilter;

    /** @var ScheduleManager */
    private $manager;

    protected function setUp()
    {
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->schedulesByArgumentsFilter = $this->createMock(SchedulesByArgumentsFilterInterface::class);

        $this->manager = new ScheduleManager(
            $this->registry,
            $this->schedulesByArgumentsFilter,
            self::CLASS_NAME
        );
    }

    protected function tearDown()
    {
        unset($this->manager, $this->registry);
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

        $this->schedulesByArgumentsFilter->expects(static::once())
            ->method('filter')
            ->with($repositorySchedules, $arguments)
            ->willReturn([$repositorySchedules[1]]);

        static::assertTrue($this->manager->hasSchedule($command, $arguments, $definition));
    }

    public function testCreateSchedule()
    {
        $command = 'oro:test';
        $arguments = ['arg1', 'arg2'];
        $definition = '* * * * *';

        $this->schedulesByArgumentsFilter->expects(static::once())
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
     *
     * @param string $command
     * @param string $definition
     * @param array $schedules
     * @param string $exception
     * @param string $message
     */
    public function testCreateScheduleException($command, $definition, array $schedules, $exception, $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        if ($schedules) {
            $this->assertRepositoryCalled($command, $definition, $schedules);
            $this->schedulesByArgumentsFilter->expects(static::once())
                ->method('filter')
                ->willReturn([$schedules[0]]);
        }

        $this->manager->createSchedule($command, [], $definition);
    }

    /**
     * @return array
     */
    public function createScheduleExceptionProvider()
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

        $this->schedulesByArgumentsFilter->expects(static::once())
            ->method('filter')
            ->with($repositorySchedules, $arguments)
            ->willReturn($repositorySchedules[1]);

        static::assertSame(
            $repositorySchedules[1],
            $this->manager->getSchedulesByCommandAndArguments($command, $arguments)
        );
    }

    /**
     * @param $command
     * @param $definition
     * @param array|Schedule[] $schedules
     */
    protected function assertRepositoryCalled($command, $definition = '', array $schedules = [])
    {
        $findBy = ['command' => $command];
        if ($definition !== '') {
            $findBy['definition'] = $definition;
        }

        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository $repository */
        $repository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findBy')
            ->with($findBy)
            ->willReturn($schedules);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $em */
        $em = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())
            ->method('getRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::CLASS_NAME)
            ->willReturn($em);
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $definition
     * @return Schedule
     */
    private function createSchedule($command, array $arguments, $definition)
    {
        $schedule = new Schedule();
        $schedule
            ->setCommand($command)
            ->setArguments($arguments)
            ->setDefinition($definition);

        return $schedule;
    }
}
