<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TrackingBundle\Entity\Repository\UniqueTrackingVisitRepository;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;
use Oro\Bundle\TrackingBundle\EventListener\TrackingVisitEntityListener;

class TrackingVisitEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var TrackingVisitEntityListener
     */
    private $listener;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->listener = new TrackingVisitEntityListener($this->configManager, $this->registry);
    }

    public function testPrePersistDisabledAggregation()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_tracking.precalculated_statistic_enabled')
            ->willReturn(false);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $entity = new TrackingVisit();
        $this->listener->prePersist($entity);
    }

    public function testPrePersist()
    {
        $entity = new TrackingVisit();

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['oro_tracking.precalculated_statistic_enabled'], ['oro_locale.timezone'])
            ->willReturnOnConsecutiveCalls(true, 'Europe/Kiev');

        $repository = $this->getMockBuilder(UniqueTrackingVisitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('logTrackingVisit')
            ->with($entity, $this->isInstanceOf(\DateTimeZone::class));

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(UniqueTrackingVisit::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(UniqueTrackingVisit::class)
            ->willReturn($em);

        $this->listener->prePersist($entity);
    }
}
