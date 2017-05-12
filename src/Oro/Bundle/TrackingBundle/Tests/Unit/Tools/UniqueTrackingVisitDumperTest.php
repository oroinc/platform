<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;
use Oro\Bundle\TrackingBundle\Migration\FillUniqueTrackingVisitsQuery;
use Oro\Bundle\TrackingBundle\Tools\UniqueTrackingVisitDumper;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;

class UniqueTrackingVisitDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var FillUniqueTrackingVisitsQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fillQuery;

    /**
     * @var UniqueTrackingVisitDumper
     */
    private $dumper;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->fillQuery = $this->getMockBuilder(FillUniqueTrackingVisitsQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dumper = new UniqueTrackingVisitDumper($this->registry, $this->logger, $this->fillQuery);
    }

    public function testRefreshAggregatedDataException()
    {
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');

        /** @var Connection $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(UniqueTrackingVisit::class)
            ->willReturn($em);
        $this->registry->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->fillQuery->expects($this->once())
            ->method('setConnection')
            ->with($connection);

        $exception = new \Exception('Test');
        $this->fillQuery->expects($this->once())
            ->method('execute')
            ->with($this->logger)
            ->willThrowException($exception);
        $em->expects($this->once())
            ->method('rollback');
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Tracking visit aggregation failed: Test', ['exception' => $exception]);

        $this->assertFalse($this->dumper->refreshAggregatedData());
    }

    public function testRefreshAggregatedData()
    {
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');

        /** @var Connection $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(UniqueTrackingVisit::class)
            ->willReturn($em);
        $this->registry->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->fillQuery->expects($this->once())
            ->method('setConnection')
            ->with($connection);

        $this->fillQuery->expects($this->once())
            ->method('execute')
            ->with($this->logger);
        $em->expects($this->once())
            ->method('commit');
        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->dumper->refreshAggregatedData());
    }
}
