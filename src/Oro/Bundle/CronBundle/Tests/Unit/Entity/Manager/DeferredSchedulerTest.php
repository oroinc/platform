<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Component\Testing\Unit\EntityTrait;

class DeferredSchedulerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ScheduleManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $scheduleManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var string */
    protected $scheduleClass;

    /** @var DeferredScheduler */
    protected $deferredScheduler;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    protected function setUp()
    {
        $this->scheduleManager = $this->getMockBuilder(ScheduleManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->scheduleClass = Schedule::class;
        $this->objectManager = $this->getMock(ObjectManager::class);

        $this->deferredScheduler = new DeferredScheduler(
            $this->scheduleManager,
            $this->registry,
            $this->scheduleClass
        );
    }

    public function testAddAndFlush()
    {
        $cronExpression = '* * * * *';

        $arguments = ['--arg1=string', '--arg2=100500'];

        //hasSchedule
        $this->scheduleManager->expects($this->once())
            ->method('hasSchedule')
            ->with('oro:test:command', $arguments, $cronExpression)
            ->willReturn(false);

        //create schedule
        $scheduleEntity = new Schedule();
        $this->scheduleManager->expects($this->once())
            ->method('createSchedule')
            ->with('oro:test:command', $arguments, $cronExpression)
            ->willReturn($scheduleEntity);

        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($this->objectManager);
        $this->objectManager->expects($this->once())->method('persist')->with($scheduleEntity);

        $this->deferredScheduler->addSchedule('oro:test:command', $arguments, $cronExpression);

        $this->objectManager->expects($this->once())->method('flush');
        $this->deferredScheduler->flush();
        // second flush should be empty
        $this->deferredScheduler->flush();
    }

    public function testRemoveScheduleAndFlush()
    {
        $foundMatchedSchedule = (new Schedule())->setArguments(['--arg1=string', '--arg2=42']);
        $foundNonMatchedSchedule = (new Schedule())->setArguments(['--arg2=string', '--arg2=41']);

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['command' => 'oro:test:command', 'definition' => '* * * * *'])
            ->willReturn([$foundMatchedSchedule, $foundNonMatchedSchedule]);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with($this->scheduleClass)
            ->willReturn($repository);
        $this->objectManager->expects($this->once())
            ->method('contains')
            ->with($foundMatchedSchedule)
            ->willReturn(true);

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->objectManager);
        $this->objectManager->expects($this->once())->method('remove')->with($foundMatchedSchedule);

        $this->deferredScheduler->removeSchedule('oro:test:command', ['--arg1=string', '--arg2=42'], '* * * * *');

        $this->deferredScheduler->flush();
        // second flush should be empty
        $this->deferredScheduler->flush();
    }

    public function testUnmanageableEntityException()
    {
        $this->setValue($this->deferredScheduler, 'dirty', true);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->scheduleClass)
            ->willReturn(null);

        $this->setExpectedException('InvalidArgumentException', 'Please provide manageable schedule entity class');

        $this->deferredScheduler->flush();
    }
}
