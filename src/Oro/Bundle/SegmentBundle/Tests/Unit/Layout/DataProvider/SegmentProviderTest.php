<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Layout\DataProvider\SegmentProvider;

class SegmentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SegmentProvider */
    private $provider;

    /** @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(SegmentManager::class);
        $this->provider = new SegmentProvider($this->manager);
    }

    public function testGetCollection()
    {
        $segment = new Segment();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn(['result']);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->manager->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->manager->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn($qb);

        $this->assertEquals(['result'], $this->provider->getCollection(1));
    }

    public function testGetCollectionWithoutSegment()
    {
        $this->manager->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->manager->expects($this->never())
            ->method('getEntityQueryBuilder');

        $this->assertEquals([], $this->provider->getCollection(1));
    }

    public function testGetCollectionWithoutQueryBuilder()
    {
        $segment = new Segment();

        $this->manager->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->manager->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn(null);

        $this->assertEquals([], $this->provider->getCollection(1));
    }
}
