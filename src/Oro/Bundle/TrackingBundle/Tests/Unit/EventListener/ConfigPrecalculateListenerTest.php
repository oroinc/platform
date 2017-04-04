<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\TrackingBundle\EventListener\ConfigPrecalculateListener;

class ConfigPrecalculateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ConfigPrecalculateListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->listener = new ConfigPrecalculateListener($this->registry);
    }

    public function testOnUpdateAfterNothingChanged()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('isChanged')
            ->withConsecutive(['oro_tracking.precalculated_statistic_enabled'], ['oro_locale.timezone'])
            ->willReturn(false);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterRecalculationDisabled()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('isChanged')
            ->withConsecutive(['oro_tracking.precalculated_statistic_enabled'], ['oro_locale.timezone'])
            ->willReturnOnConsecutiveCalls(true, false);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with('oro_tracking.precalculated_statistic_enabled')
            ->willReturn(false);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterTimezoneChanged()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('isChanged')
            ->withConsecutive(['oro_tracking.precalculated_statistic_enabled'], ['oro_locale.timezone'])
            ->willReturnOnConsecutiveCalls(false, true);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class));
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterCalculationEnabled()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_tracking.precalculated_statistic_enabled')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with('oro_tracking.precalculated_statistic_enabled')
            ->willReturn(true);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Job::class));
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->listener->onUpdateAfter($event);
    }
}
