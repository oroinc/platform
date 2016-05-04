<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;

class ScheduleManagerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Oro\Bundle\CronBundle\Entity\Schedule';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var ScheduleManager */
    protected $manager;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->manager = new ScheduleManager($this->registry, self::CLASS_NAME);
    }

    protected function tearDown()
    {
        unset($this->manager, $this->registry);
    }

    /**
     * @dataProvider hasScheduleExceptionProvider
     *
     * @param string $command
     * @param array $arguments
     * @param string $definition
     * @param bool $expected
     */
    public function testHasSchedule($command, array $arguments, $definition, $expected)
    {
        $this->assertRepositoryCalled(
            $command,
            $definition,
            [
                $this->createSchedule('oro:test', [], '* * * * *'),
                $this->createSchedule('oro:test', ['arg1'], '* * * * *'),
                $this->createSchedule('oro:test', ['arg1', 'arg2'], '* * * * *')
            ]
        );

        $this->assertEquals($expected, $this->manager->hasSchedule($command, $arguments, $definition));
    }

    /**
     * @return array
     */
    public function hasScheduleExceptionProvider()
    {
        return [
            'exists schedule' => [
                'command' => 'oro:test',
                'arguments' => ['arg2', 'arg1'],
                'definition' => '* * * * *',
                'expected' => true
            ],
            'not exists schedule' => [
                'command' => 'oro:test',
                'arguments' => ['arg3'],
                'definition' => '* * * * *',
                'expected' => false
            ],
        ];
    }

    public function testCreateSchedule()
    {
        $command = 'oro:test';
        $arguments = ['arg1', 'arg2'];
        $definition = '* * * * *';

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
        $this->setExpectedException($exception, $message);

        if ($schedules) {
            $this->assertRepositoryCalled($command, $definition, $schedules);
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

    /**
     * @param $command
     * @param $definition
     * @param array|Schedule[] $schedules
     */
    protected function assertRepositoryCalled($command, $definition, array $schedules = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $repository */
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['command' => $command, 'definition' => $definition])
            ->willReturn($schedules);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
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
    protected function createSchedule($command, array $arguments, $definition)
    {
        $schedule = new Schedule();
        $schedule
            ->setCommand($command)
            ->setArguments($arguments)
            ->setDefinition($definition);

        return $schedule;
    }
}
